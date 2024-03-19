<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RecipeCreateRequest;
use App\Http\Requests\RecipeUpdateRequest;
use App\Models\Step;

use function PHPUnit\Framework\throwException;

class RecipeController extends Controller
{
    /**
     * home表示
     */
    public function home()
    {
        // レシピを取得
        $recipes = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit(3)
            ->get();

        // ブラウザにデータを表示できる
        // dd($recipes);

        // レシピを取得
        $popular_recipes = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'recipes.views', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit(2)
            ->get();

        return view('home', compact('recipes', 'popular_recipes'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        // レシピを取得
        $query = Recipe::query()->select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name',
            \DB::raw('AVG(reviews.rating) as rating'))
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->leftJoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id')
            ->orderBy('recipes.created_at', 'desc');

        if( ! empty($filters)) {
            // カテゴリー
            if( ! empty($filters['categories'])) {
                $query->whereIn('recipes.category_id', $filters['categories']);
            }

            // タイトル（like検索）
            if( ! empty($filters['title'])) {
                $query->where('recipes.title', 'like', '%'.$filters['title'].'%');
            }

            // 評価
            if( ! empty($filters['rating'])) {
                $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']]);
            }

        }
        $recipes = $query->paginate(5);

        $categories = Category::all();

        return view('recipes.index', compact('recipes', 'categories', 'filters'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();

        return view('recipes.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RecipeCreateRequest $request)
    {
        $posts = $request->all();
        $uuid = Str::uuid()->toString();

        // s3に画像をアップロード
        $image = $request->file('image');
        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        // s3のURL取得
        $url = Storage::disk('s3')->url($path);

        // DBにs3のURLを保存
        try {
            DB::beginTransaction();
            Recipe::insert([
                'id' => $uuid,
                'title' => $posts['title'],
                'description' => $posts['description'],
                'category_id' => $posts['category'],
                'image' => $url,
                'user_id' => Auth::id()
            ]);

            $ingredients = [];
            foreach($posts['ingredients'] as $key => $ingredient) {
                $ingredients[$key] = [
                    'recipe_id' => $uuid,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity']
                ];
            }
            Ingredient::insert($ingredients);

            // stepを作成
            $steps = [];
            foreach ($posts['steps'] as $key => $step) {
                $steps[$key] = [
                    'recipe_id' => $uuid,
                    'step_number' => $key + 1,
                    'description' => $step
                ];
            }
            STEP::insert($steps);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::debug(print_r($e->getMessage(), true));
            throw $e;
        }

        flash()->success('レシピ投稿したぞ');
        return redirect()->route('recipe.show',['id' => $uuid]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipe = Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first();

        // レシピの投稿者とログインユーザーが一致しているか
        $is_my_recipe = false;
        if (Auth::check() && (Auth::id() === $recipe['user_id'])) {
            $is_my_recipe = true;
        }

        // viewを１増やす
        $recipe_record = Recipe::find($id);
        $recipe_record->increment('views');
        return view('recipes.show', compact('recipe', 'is_my_recipe'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $recipe = Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first()
            ->toArray();

        if( ! Auth::check() && (Auth::id() === $recipe['user_id'])) abort(403);

        $categories = Category::all();

        return view('recipes.edit', compact('recipe', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RecipeUpdateRequest $request, string $id)
    {
        $posts = $request->all();
        $update_array = [
            'title' => $posts['title'],
            'description' => $posts['description'],
            'category_id' => $posts['category_id']
        ];

        // 画像の更新があるかどうかでurlを判定
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
            // s3のURL取得
            $url = Storage::disk('s3')->url($path);
            $update_array['image'] = $url;
        }

        try {
            DB::beginTransaction();
            Recipe::where('id', $id)->update($update_array);

            // 更新前のものを削除
            Ingredient::where('recipe_id', $id)->delete();
            Step::where('recipe_id', $id)->delete();

            // 更新用のパラメータでinsert
            $ingredients = [];
            foreach($posts['ingredients'] as $key => $ingredient) {
                $ingredients[$key] = [
                    'recipe_id' => $id,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity']
                ];
            }
            Ingredient::insert($ingredients);

            // stepを作成
            $steps = [];
            foreach ($posts['steps'] as $key => $step) {
                $steps[$key] = [
                    'recipe_id' => $id,
                    'step_number' => $key + 1,
                    'description' => $step
                ];
            }
            STEP::insert($steps);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::debug(print_r($e->getMessage()));
        }

        flash()->success('レシピを更新しました！');
        return redirect()->route('recipe.show', ['id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
