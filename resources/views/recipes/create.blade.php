<x-app-layout>
    <form action="{{ route('recipe.store') }}" method="POST" class="w-10/12 p-4 mx-auto bg-white rounded" enctype="multipart/form-data">
        @csrf
        {{ Breadcrumbs::render('create') }}
        <div class="grid grid-cols-2 rounded border border-gray-500 mt-4">
            <div class="col-span-1">
                <img class="object-cover w-full aspect-square"
                    src="/images/recipe-dummy.png" alt="recipe-image">
                <input type="file" name="image" class="border border-gray-300 p-2 mb-4 w-full rounded">
            </div>
            <div class="col-span-1 p-4">
                <input type="text" placeholder="レシピ名" name="title" class="border border-gray-300 p-2 mb-4 w-full rounded">
                <textarea name="description" placeholder="レシピの説明" class="border-gray-300 p-2 mb-4 w-full rounded"></textarea>
                <select name="category" class="border-gray-300 p-2 mb-4 w-full rounded">
                    <option value="">カテゴリー</option>
                    @foreach($categories as $category)
                        <option value="{{$category['id']}}">{{$category['name']}}</option>
                    @endforeach
                </select>
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        レシピを投稿する
                    </button>
                </div>
            </div>
        </div>
    </form>

</x-app-layout>