<x-section :section="$section" class="section-y">
    <div class="container-site prose-site">{!! \App\Support\Html::cleanCustom($section->body) !!}</div>
</x-section>
