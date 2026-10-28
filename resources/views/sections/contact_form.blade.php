<x-section :section="$section" class="pb-24">
    <div id="contact-form" class="container-site max-w-[1140px] scroll-mt-8">
        <div class="relative rounded-card bg-dark-2 px-6 py-16 text-white shadow-[var(--shadow-card)] md:px-[136px]">
            <h2 class="heading-display">{{ $section->title }}</h2>
            @if ($section->body)
                <div class="prose-site mt-6 text-[16px] leading-[24px]">{!! \App\Support\Html::clean($section->body) !!}</div>
            @endif
            <form method="POST" action="{{ route('forms.contact') }}" class="mt-8" novalidate>
                @csrf
                @include('partials.honeypot', ['form' => 'contact'])
                @include('partials.form-status', ['form' => 'contact'])
                <div class="grid gap-x-10 gap-y-6 md:grid-cols-2">
                    @include('partials.form-field', ['form' => 'contact', 'name' => 'name', 'label' => __('site.form.name'), 'required' => true, 'autocomplete' => 'name'])
                    @include('partials.form-field', ['form' => 'contact', 'name' => 'email', 'type' => 'email', 'label' => __('site.form.email'), 'required' => true, 'autocomplete' => 'email'])
                    <div class="md:col-span-2">
                        @include('partials.form-field', ['form' => 'contact', 'name' => 'message', 'type' => 'textarea', 'label' => __('site.form.message')])
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-10">{{ $section->button_label ?: __('site.form.send') }}</button>
            </form>
        </div>
    </div>
</x-section>
