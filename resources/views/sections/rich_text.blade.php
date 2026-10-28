{{-- Free text block (Brands intro). settings: divider (bool), align start|center --}}
<x-section :section="$section" class="pt-16 pb-6">
    <div class="container-site" @style(['text-align: center' => $section->setting('align') === 'center'])>
        @if ($section->title)
            <h2 class="text-[40px] leading-[1.3] font-bold">{{ $section->title }}</h2>
        @endif
        <div class="prose-site mt-8 font-medium">{!! \App\Support\Html::clean($section->body) !!}</div>
        @if ($section->setting('divider'))
            @include('partials.divider', ['class' => 'mt-12'])
        @endif
    </div>
</x-section>
