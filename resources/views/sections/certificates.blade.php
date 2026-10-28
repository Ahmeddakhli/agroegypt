{{-- Certificates carousel (documents of type "certificate"). --}}
@if ($data && $data->isNotEmpty())
    <x-section :section="$section" bg="olive" class="pt-6 pb-12">
        <div class="container-site" x-data="carousel({ autoplay: {{ $data->count() > 4 ? 'true' : 'false' }} })" @mouseenter="pause()" @focusin="pause()">
            @if ($section->title)
                <h2 class="mb-8 text-center text-[36px]">{{ $section->title }}</h2>
            @endif
            <div class="relative">
                <ul x-ref="track" class="flex snap-x snap-mandatory gap-8 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] sm:justify-center">
                    @foreach ($data as $certificate)
                        <li class="flex w-[45%] shrink-0 snap-start flex-col items-center gap-3 text-center sm:w-[200px]">
                            @php($href = $certificate->file?->url())
                            <a @if ($href) href="{{ $href }}" target="_blank" rel="noopener" @endif class="flex h-[160px] items-center justify-center">
                                <x-img :media="$certificate->image" sizes="200px" class="max-h-[160px] w-auto object-contain" :alt="$certificate->title" />
                            </a>
                            <span class="text-[16px] font-medium text-white">{{ $certificate->title }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-section>
@endif
