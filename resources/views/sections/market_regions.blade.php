{{-- Markets: regions (item title) with their countries (item body, one per line). --}}
<x-section :section="$section" bg="dark" class="border-y border-white/5 py-6">
    <div class="container-site grid grid-cols-2 gap-x-6 gap-y-10 py-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($section->items as $region)
            <div>
                <h3 class="text-[16px] leading-[24px] font-bold text-orange">{{ $region->title }} :</h3>
                <ul class="mt-4 space-y-1.5 ps-4">
                    @foreach (preg_split('/\R/', trim((string) $region->body)) as $country)
                        @continue(blank($country))
                        <li class="flex items-center gap-2 text-[14px] leading-[24px] text-orange">
                            <svg class="size-3 shrink-0 fill-orange" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/></svg>
                            {{ trim($country) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-section>
