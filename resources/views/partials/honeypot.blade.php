{{-- Spam trap: hidden from people, filled in by bots; the request is rejected when it has a value. --}}
<div class="absolute -start-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
    <label for="{{ $form }}-website">Website</label>
    <input id="{{ $form }}-website" type="text" name="website" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="_form" value="{{ $form }}">
