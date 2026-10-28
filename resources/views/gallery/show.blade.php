@extends('layouts.site')

@section('content')
    <section class="py-20">
        <div class="container-site">
            @if ($gallery->images->isNotEmpty())
                @include('partials.lightbox-grid', ['images' => $gallery->images])
            @else
                <p class="text-center text-muted">{{ __('site.gallery_empty') }}</p>
            @endif
        </div>
    </section>
@endsection
