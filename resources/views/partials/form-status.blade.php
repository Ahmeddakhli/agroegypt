@if (session('form_success') === $form)
    <div class="mb-6 border-s-4 border-primary bg-primary/15 px-4 py-3 text-[16px] text-white" role="status">{{ __('site.form.success_'.$form) }}</div>
@elseif ($errors->any() && old('_form') === $form)
    <div class="mb-6 border-s-4 border-red-400 bg-red-500/15 px-4 py-3 text-[16px] text-white" role="alert">
        <p class="font-bold">{{ __('site.form.error') }}</p>
        <ul class="mt-1 list-disc ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
