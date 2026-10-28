<x-section :section="$section" bg="dark" class="py-4">
    <div @class(['container-site' => $section->setting('boxed', true)])>
        <x-img :media="$section->media" sizes="(min-width: 1170px) 1170px, 100vw" class="mx-auto h-auto w-full" />
    </div>
</x-section>
