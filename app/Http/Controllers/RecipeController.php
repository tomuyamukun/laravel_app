<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
    public function store(Request $request)
    {
        $post = $request->all();

        // s3に画像をアップロード
        $image = $request->file('image');
        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        // s3のURL取得
        $url = Storage::disk('s3')->url($path);

        // DBにs3のURLを保存
        Recipe::insert([
            'id' => Str::uuid(),
            'title' => $post['title'],
            'description' => $post['description'],
            'category_id' => $post['category'],
            'image' => $url,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipe = Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first();

        // viewを１増やす
        $recipe_record = Recipe::find($id);
        $recipe_record->increment('views');
        return view('recipes.show', compact('recipe'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
