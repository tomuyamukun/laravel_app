<x-app-layout>
    <div class="grid grid-cols-3 gap-4">
        <div class="col-span-2 bg-white rounded p-4">
            @foreach($recipes as $recipe)
                <!-- レシピカードをコンポーネント化して使い回せるようにしている  -->
                @include('recipes..partial.horizontal-card')
            @endforeach
        </div>
        <div class="col-span-1 bg-white">FORM</div>
    </div>
</x-app-layout>