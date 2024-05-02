<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Step;



class S3Upload
{
    /**
     * s3に画像をアップロード
     */
    public function upload($request)
    {
        $image = $request->file('image');
        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        return $path;
    }

    /**
     * s3のurlを取得
     */
    public function getUrl($path)
    {
        return Storage::disk('s3')->url($path);
    }

    /**
     * DBにS3のURLを保存
     */
    public function storeS3($recipe, $ingredient, $steps)
    {
        Recipe::insert($recipe);
        Ingredient::insert($ingredient);
        STEP::insert($steps);
    }

}