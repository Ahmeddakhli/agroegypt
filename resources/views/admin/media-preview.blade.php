@php($media = $getRecord())
@if ($media?->isImage())
    <img src="{{ $media->url(768) }}" alt="" class="w-full rounded-lg bg-gray-100 object-contain" style="max-height: 260px">
@elseif ($media)
    <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="text-primary-600 underline">{{ __('Open :name', ['name' => $media->name]) }}</a>
@endif
