@extends('layouts.site')

@php
    $monthLabels = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Carbon::create(2000, $m, 1)->locale(app()->getLocale())->translatedFormat('M')]);
@endphp

@section('content')
    <article class="py-24">
        <div class="container-site grid gap-12 lg:grid-cols-[minmax(0,8fr)_minmax(0,4fr)] lg:gap-8">
            <div class="px-0 sm:px-6">
                @if ($product->subtitle)
                    <h2 class="text-[24px] leading-[34px] font-bold">{{ $product->subtitle }}</h2>
                @endif
                @if ($product->description)
                    <div class="prose-site leading-[36px]">{!! \App\Support\Html::clean($product->description) !!}</div>
                @endif

                @if ($product->varieties->isNotEmpty())
                    <h3 class="mt-10 text-[24px] leading-[34px] font-normal">{{ __('site.product.variety') }}</h3>
                    <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[16px]">
                        @foreach ($product->varieties as $variety)
                            <li class="flex items-center gap-2"><svg class="size-3.5 shrink-0 fill-orange" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h18v18H3V3zm2 2v14h14V5H5zm4.6 9.6l6.4-6.4 1.4 1.4-7.8 7.8-4-4 1.4-1.4 2.6 2.6z"/></svg>{{ $variety->name }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($product->packagings->isNotEmpty())
                    <h3 class="mt-10 px-2.5 text-[24px] leading-[34px] font-normal">{{ __('site.product.packaging') }}</h3>
                    <div class="mt-8 grid gap-10 sm:grid-cols-2">
                        @foreach ($product->packagings as $packaging)
                            <div class="text-center">
                                <x-img :media="$packaging->icon" sizes="100px" class="mx-auto h-[110px] w-auto" alt="" />
                                <p class="mt-6 text-[18px] leading-[26px] font-bold">{{ $packaging->title }}</p>
                                @if ($packaging->specs->isNotEmpty())
                                    <dl class="mt-6 grid grid-cols-2 gap-4">
                                        @foreach ($packaging->specs as $spec)
                                            <div class="flex flex-col items-center">
                                                <dt class="text-[14px] leading-[20px] font-light text-muted">{{ $spec->label }}</dt>
                                                <dd class="mt-1 flex items-center gap-4 text-[22px]">
                                                    <x-img :media="$spec->icon" sizes="60px" class="h-[60px] w-auto" alt="" />
                                                    <span class="self-start">{{ $spec->value }}</span>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($product->seasons->isNotEmpty())
                    <h3 class="mt-14 px-2.5 text-[24px] leading-[34px] font-normal">{{ __('site.product.seasonality') }}</h3>
                    <div class="mt-8 space-y-8">
                        @foreach ($product->seasons as $season)
                            <div class="grid items-start gap-4 sm:grid-cols-[240px_1fr]">
                                <p class="px-6 text-[24px] leading-[34px] font-bold text-orange">{{ $season->label }}</p>
                                <ul class="flex flex-wrap gap-x-10 gap-y-4 md:gap-x-[84px]">
                                    @foreach ($season->months ?? [] as $month)
                                        <li class="flex w-[60px] flex-col items-center gap-3">
                                            <span class="text-[17px] uppercase">{{ $monthLabels[(int) $month] ?? '' }}</span>
                                            <x-img :media="$product->seasonIcon" sizes="45px" class="h-[45px] w-auto" alt="" />
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>

            <aside class="flex items-start justify-center lg:pt-24">
                <x-img :media="$product->image" sizes="(min-width: 1024px) 330px, 80vw" :lazy="false" class="h-auto w-full max-w-[330px]" :alt="$product->name" />
            </aside>
        </div>

        {{-- Spans the full width below the image, as on the reference, so long size lists stay on one row. --}}
        @if ($product->spec_values)
            <div class="container-site mt-14 grid items-start gap-4 sm:grid-cols-[130px_1fr] sm:px-6">
                <h3 class="text-[24px] leading-[34px]">{{ $product->spec_label ?: __('site.product.counts') }}</h3>
                <ul class="flex flex-wrap gap-y-6 text-[17px]">
                    @foreach ($product->spec_values as $value)
                        <li class="flex w-[72px] flex-col items-center gap-3 md:w-[100px]">{{ $value }}<svg class="size-6 fill-orange" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 15.6l-3.9-3.9L3 13.8l6 6L21.6 7.2 19.5 5.1z"/></svg></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($product->images->isNotEmpty())
            <div class="container-site mt-16">
                @include('partials.lightbox-grid', ['images' => $product->images])
            </div>
        @endif
    </article>
@endsection
