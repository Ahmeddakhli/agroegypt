@extends('layouts.site')

@section('content')
    @foreach ($page->sections as $section)
        @includeIf($section->type->view(), ['section' => $section, 'data' => $sectionData[$section->id] ?? null])
    @endforeach
@endsection
