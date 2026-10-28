{{-- About "Our Vision": image collage, heading, text, animated progress bars and a button. --}}
<x-section :section="$section" bg="white" class="pb-16">
    <div class="container-site grid items-center gap-8 md:grid-cols-[485px_1fr] md:gap-5">
        @if ($section->media)
            <x-img :media="$section->media" sizes="(min-width: 768px) 485px, 100vw" class="h-auto w-full md:-mt-[50px]" />
        @endif
        <div>
            <h2 class="heading-display">{{ $section->title }}</h2>
            <div class="prose-site mt-10 text-justify">{!! \App\Support\Html::clean($section->body) !!}</div>
            <dl class="mt-8 space-y-6">
                @foreach ($section->items as $item)
                    @php($value = max(0, min(100, (int) $item->value)))
                    <div>
                        <div class="flex items-baseline justify-between">
                            <dt class="text-[24px] leading-[34px] font-bold text-heading-alt">{{ $item->title }}</dt>
                            <dd class="font-mono text-[22px] font-bold tracking-widest text-heading-alt">{{ $value }}%</dd>
                        </div>
                        <div class="mt-2 h-1.5 bg-dark/5" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $item->title }}">
                            <div class="h-full bg-primary" style="width: {{ $value }}%" data-progress></div>
                        </div>
                    </div>
                @endforeach
            </dl>
            @if ($section->button_label && $section->button_url)
                <div class="mt-10 text-end"><a href="{{ \App\Support\SiteIndex::link($section->button_url) }}" class="btn-primary !px-1 !py-0.5">{{ $section->button_label }}</a></div>
            @endif
        </div>
    </div>
</x-section>
