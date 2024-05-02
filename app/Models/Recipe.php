<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Ingredient;

class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'id' => 'string'
    ];

    public function category()
    {
        return $this->hasOne(Category::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function steps()
    {
        return $this->hasMany(Step::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // ホームページ用のレシピ取得
    public function getRecipe()
    {
        return Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit(3)
            ->get();
    }

    // 人気のレシピ取得
    public function getPopularRecipe()
    {
        return Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'recipes.views', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit(2)
            ->get();
    }

    // 
    public function indexRecipes($request)
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
        return $query->paginate(5);
    }

    public function getShowRecipe($id)
    {
        return Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first();
    }

    public function getEditRecipe($id)
    {
        return Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first()
            ->toArray();
    }


}
