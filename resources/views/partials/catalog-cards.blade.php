{{-- Product / category cards: square photo + bold caption with hang hover. --}}
<ul class="flex flex-wrap justify-center gap-x-10 gap-y-14">
    @foreach ($cards as $card)
        @php($url = \App\Support\SiteIndex::url($card))
        @continue($url === null)
        <li class="w-[calc(50%-20px)] text-center sm:w-[260px] lg:w-[300px]">
            <a href="{{ $url }}" class="hover-hang inline-flex flex-col items-center gap-5">
                <x-img :media="$card->card" sizes="(min-width: 1024px) 300px, 50vw" class="aspect-square h-auto w-full object-contain" :alt="$card->name" />
                <span class="text-[24px] leading-[34px] font-bold">{{ $card->name }}</span>
            </a>
        </li>
    @endforeach
</ul>
