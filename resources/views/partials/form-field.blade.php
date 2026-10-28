@php($id = $form.'-'.$name)
<div>
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    @if (($type ?? 'text') === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="4" maxlength="2000" placeholder="{{ $label }}" @class(['field resize-y', 'border-red-400' => $errors->has($name) && old('_form') === $form])
                  @if ($errors->has($name) && old('_form') === $form) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ old('_form') === $form ? old($name) : '' }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" placeholder="{{ $label }}{{ ($required ?? false) ? ' *' : '' }}"
               value="{{ old('_form') === $form ? old($name) : '' }}" @if ($required ?? false) required @endif maxlength="400"
               @if (($autocomplete ?? null)) autocomplete="{{ $autocomplete }}" @endif
               @class(['field', 'border-red-400' => $errors->has($name) && old('_form') === $form])
               @if ($errors->has($name) && old('_form') === $form) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @endif
    @if ($errors->has($name) && old('_form') === $form)
        <p id="{{ $id }}-error" class="mt-1 text-[14px] text-red-300">{{ $errors->first($name) }}</p>
    @endif
</div>
