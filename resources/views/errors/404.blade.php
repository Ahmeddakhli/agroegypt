@extends('layouts.site', ['seo' => new \App\Support\Seo(__('site.not_found_title'), null, url()->current(), noindex: true), 'breadcrumbs' => []])

@section('content')
    <section class="py-32 text-center">
        <div class="container-site">
            <p class="text-[120px] leading-none font-bold text-orange">404</p>
            <h1 class="mt-4 text-[36px]">{{ __('site.not_found_title') }}</h1>
            <p class="mt-4">{{ __('site.not_found_text') }}</p>
            <a href="{{ \App\Support\SiteIndex::localizedUrl(app()->getLocale()) }}" class="btn-primary mt-10">{{ __('site.back_home') }}</a>
        </div>
    </section>
@endsection
