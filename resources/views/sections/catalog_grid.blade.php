<x-section :section="$section" class="py-24">
    <div class="container-site">
        @if ($section->title)
            <h2 class="mb-12 text-center text-[36px]">{{ $section->title }}</h2>
        @endif
        @include('partials.catalog-cards', ['cards' => $data ?? collect()])
    </div>
</x-section>
