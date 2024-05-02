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
use App\Services\S3Upload;

use function PHPUnit\Framework\throwException;

class RecipeController extends Controller
{
    /**
     * home表示
     */
    public function home()
    {
        $recipeModel = new Recipe();
        // レシピを取得
        $recipes = $recipeModel->getRecipe();

        // レシピを取得
        $popular_recipes = $recipeModel->getPopularRecipes();

        return view('home', compact('recipes', 'popular_recipes'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $recipeModel = new Recipe();

        $recipes = $recipeModel->indexRecipes($request);

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
        $s3Upload = new S3Upload();
        $path = $s3Upload->upload($request);
        // s3のURL取得
        $url = $s3Upload->getUrl($path);

        $insert_params = $this->setStoreRecipeParams($posts, $url, $uuid);

        // DBにs3のURLを保存
        try {
            DB::beginTransaction();
            $s3Upload->storeS3(
                $insert_params["recipe"],
                $insert_params["ingredient"],
                $insert_params["steps"]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::debug(print_r($e->getMessage(), true));
            throw $e;
        }

        flash()->success('レシピ投稿したぞ');
        return redirect()->route('recipe.show',['id' => $uuid]);
    }

    private function setStoreRecipeParams($posts, $url, $uuid)
    {
        $recipe = [
            'id' => $uuid,
            'title' => $posts['title'],
            'description' => $posts['description'],
            'category_id' => $posts['category'],
            'image' => $url,
            'user_id' => Auth::id()
        ];

        $ingredients = [];
        foreach($posts['ingredients'] as $key => $ingredient) {
            $ingredients[$key] = [
                'recipe_id' => $uuid,
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity']
            ];
        }
        // stepを作成
        $steps = [];
        foreach ($posts['steps'] as $key => $step) {
            $steps[$key] = [
                'recipe_id' => $uuid,
                'step_number' => $key + 1,
                'description' => $step
            ];
        }
        return [
            "recipe" => $recipe,
            "ingredients" => $ingredients,
            "steps" => $steps
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipeModel = new Recipe();

        $recipe = $recipeModel->getShowRecipe($id);

        // viewを１増やす
        $recipe_record = Recipe::find($id);
        $recipe_record->increment('views');

        // レシピの投稿者とログインユーザーが一致しているか
        $is_my_recipe = false;
        if (Auth::check() && (Auth::id() === $recipe['user_id'])) {
            $is_my_recipe = true;
        }

        $is_reviewed = false;
        if(Auth::check()) {
            $is_reviewed = $recipe->reviews->contains('user_id', Auth::id());
        }

        return view('recipes.show', compact('recipe', 'is_my_recipe', 'is_reviewed'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $recipeModel = new Recipe();

        $recipe = $recipeModel->getEditRecipe($id);

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
        Recipe::where('id', $id)->delete();
        flash()->warning('レシピを削除しました');

        return redirect()->route('home');
    }
}
