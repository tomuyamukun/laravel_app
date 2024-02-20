# 開発環境の構築

## 実行したコマンド

- docker run -it -v $(pwd):/opt -w /opt laravelsail/php81-composer:latest /bin/bash
- composer create-project 'laravel/laravel:10.*' sail-example
- cd sail-example
- php artisan sail:install
- exit
- ./vendor/bin/sail up -d
- sudo chown -R ユーザー名:ユーザー名 .

■ docker desktop for Windowsでpermittion denidedエラーの出る方
- sudo addgroup --system docker
- sudo adduser $USER docker
- newgrp docker
- sudo chown root:docker /var/run/docker.sock
- sudo chmod g+w /var/run/docker.sock

# 会員登録・ログイン機能の開発

## 実行したコマンド

- sail composer require laravel/breeze --dev
- sail php artisan breeze:install
- sail php artisan migrate

# 💭 データベースの準備

> テーブル構造
> 
> 1. **`users`テーブル**:
>     - **`id`**: ユーザーID (プライマリキー)
>     - **`username`**: ユーザー名
>     - **`email`**: メールアドレス
>     - **`password`**: パスワード (ハッシュ化)
>     - **`created_at`**: 作成日時
>     - **`updated_at`**: 更新日時
> 2. **`recipes`テーブル**:
>     - **`id`**: レシピID (プライマリキー)
>     - **`user_id`**: ユーザーID (外部キー)
>     - **`title`**: レシピのタイトル
>     - **`description`**: レシピの説明
>     - **`image`**: 画像のパスまたはURL
>     - **`created_at`**: 作成日時
>     - **`updated_at`**: 更新日時
>     - **`deleted_at`**: 削除日時
> 3. **`ingredients`テーブル**:
>     - **`id`**: 材料ID (プライマリキー)
>     - **`recipe_id`**: レシピID (外部キー)
>     - **`name`**: 材料名
>     - `**quontity**`: 量
> 4. **`steps`テーブル**:
>     - **`id`**: 手順ID (プライマリキー)
>     - **`recipe_id`**: レシピID (外部キー)
>     - **`step_number`**: 手順の順番
>     - **`description`**: 手順の説明
> 5. **`reviews`テーブル**:
>     - **`id`**: レビューID (プライマリキー)
>     - **`user_id`**: ユーザーID (外部キー)
>     - **`recipe_id`**: レシピID (外部キー)
>     - **`rating`**: 評価 (例: 1-5)
>     - **`comment`**: コメント
>     - **`created_at`**: 作成日時
>     - **`updated_at`**: 更新日時
>     - **`deleted_at`**: 削除日時

[A Free Database Designer for Developers and Analysts](https://dbdiagram.io/d/CookpadLaravel10-6517b108ffbf5169f0c5f3c0)

- 

---

## 実行したコマンド

- sail php artisan make:migration create_categories_table
- sail php artisan make:migration create_recipes_table
- sail php artisan make:migration create_ingredients_table
- sail php artisan make:migration create_steps_table
- sail php artisan make:migration create_reviews_table

- sail composer require goldspecdigital/laravel-eloquent-uuid:^10.0

- sail php artisan migrate:rollback
- sail php artisan migrate

- sail php artisan make:seeder UsersTableSeeder
- sail php artisan make:seeder CategoriesTableSeeder
- sail php artisan make:seeder RecipesTableSeeder
- sail php artisan make:seeder IngredientsTableSeeder
- sail php artisan make:seeder StepsTableSeeder
- sail php artisan make:seeder ReviewsTableSeeder

## 当アプリケーションのカラー定義
- メインカラー: #FF3366
- 文字カラー: text-gray-600
- 見出しカラー: text-gray-800
- 背景カラー: #ede8d2
- アクセントカラー: green-700

# レシピ閲覧機能の開発

## 実行したコマンド
- sail php artisan make:controller RecipeController --resource
- sail php artisan make:model Recipe
- sail php artisan make:model Review
- sail php artisan make:model Category
- sail php artisan make:model Ingredient
- sail php artisan make:model Step

## Tailwind CSS grid
- [Tailwind CSS grid](https://tailwindcss.com/docs/grid-template-columns)

## アイコンはこちらから
- [HeroesIcon](https://heroicons.com/)

## パンくずリスト
- [Laravel Breadcrumbs](https://github.com/diglactic/laravel-breadcrumbs)
- sail composer require diglactic/laravel-breadcrumbs
- sail php artisan vendor:publish --tag=breadcrumbs-config

## ページネーションのドキュメント
- [Laravel 10.x Pagination - Laravel](https://readouble.com/laravel/10.x/ja/pagination.html)

# レシピ閲覧機能の開発

## リレーションのドキュメント
- [Eloquent: Relationships - Laravel](https://readouble.com/laravel/10.x/ja/eloquent-relationships.html)

# レシピ投稿機能の開発

## AWS関連リンク
- [アカウント登録](https://aws.amazon.com/jp/register-flow/)
- [S3利用料金](https://aws.amazon.com/jp/s3/pricing/)

## S3バケットポリシー
```
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "Statement1",
            "Effect": "Allow",
            "Principal": {
                "AWS": "arn:aws:iam::995962138333:user/s3user" //受講者様の値に変えてください
            },
            "Action": "s3:PutObject",
            "Resource": "arn:aws:s3:::バケット名/*"
        }
    ]
}
```

## AWS S3のcomposerパッケージをインストール

```
sail composer require league/flysystem-aws-s3-v3
```

## sortable.js
- [SortableJS](https://github.com/SortableJS/Sortable)

## flashメッセージ
- [ドキュメント](https://github.com/josegus/laravel-flash)
- sail composer require josegus/laravel-flash

## バリデーションのドキュメント
- [Laravel 10.x Validation - Laravel](https://readouble.com/laravel/10.x/ja/validation.html)
- sail php artisan make:request RecipeCreateRequest
- [日本語化解説](https://biz.addisteria.com/laravel_translation/)
- [日本語翻訳ファイル](https://github.com/askdkc/breezejp)
- sail php artisan make:request RecipeUpdateRequest
