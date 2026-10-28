<x-section :section="$section" class="pt-10 pb-24">
    <div id="careers-form" class="container-site scroll-mt-8">
        <div class="rounded-card bg-dark-2 px-4 pt-6 pb-16 text-white shadow-[var(--shadow-card)] md:px-[140px]">
            <h2 class="heading-display">{{ $section->title }}</h2>
            @if ($section->body)
                <div class="prose-site mt-4 text-[16px]">{!! \App\Support\Html::clean($section->body) !!}</div>
            @endif
            <form method="POST" action="{{ route('forms.careers') }}" enctype="multipart/form-data" class="mt-6 rounded-card bg-dark px-6 py-8 md:px-9" novalidate>
                @csrf
                @include('partials.honeypot', ['form' => 'careers'])
                @include('partials.form-status', ['form' => 'careers'])
                <div class="grid gap-x-12 gap-y-6 md:grid-cols-2">
                    @include('partials.form-field', ['form' => 'careers', 'name' => 'name', 'label' => __('site.form.name'), 'required' => true, 'autocomplete' => 'name'])
                    @include('partials.form-field', ['form' => 'careers', 'name' => 'email', 'type' => 'email', 'label' => __('site.form.email_address'), 'required' => true, 'autocomplete' => 'email'])
                    @include('partials.form-field', ['form' => 'careers', 'name' => 'phone', 'type' => 'tel', 'label' => __('site.form.phone'), 'required' => true, 'autocomplete' => 'tel'])
                    @include('partials.form-field', ['form' => 'careers', 'name' => 'job_title', 'label' => __('site.form.job_title'), 'required' => true])
                    <div class="md:col-span-2">
                        <label for="careers-cv" class="mb-2 block text-[16px]">{{ __('site.form.cv') }} *</label>
                        <input id="careers-cv" type="file" name="cv" required accept=".{{ implode(',.', \App\Http\Requests\CareerApplicationRequest::CV_EXTENSIONS) }}"
                               class="block w-full text-[14px] file:me-3 file:border-0 file:bg-white file:px-3 file:py-1 file:text-dark"
                               @if ($errors->has('cv')) aria-invalid="true" aria-describedby="careers-cv-error" @endif>
                        <p class="mt-1 text-[13px] text-white/70">{{ __('site.form.cv_hint') }}</p>
                        @if ($errors->has('cv') && old('_form') === 'careers')
                            <p id="careers-cv-error" class="mt-1 text-[14px] text-red-300">{{ $errors->first('cv') }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        @include('partials.form-field', ['form' => 'careers', 'name' => 'message', 'type' => 'textarea', 'label' => __('site.form.message')])
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-12">{{ $section->button_label ?: __('site.form.send') }}</button>
            </form>
        </div>
    </div>
</x-section>
