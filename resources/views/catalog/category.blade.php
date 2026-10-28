@extends('layouts.site')

@section('content')
    <section class="py-24">
        <div class="container-site">
            @if ($category->description)
                <div class="prose-site mx-auto mb-14 max-w-3xl text-center">{!! \App\Support\Html::clean($category->description) !!}</div>
            @endif
            @include('partials.catalog-cards', ['cards' => $cards])
        </div>
    </section>
@endsection
