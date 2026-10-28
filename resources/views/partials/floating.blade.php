@php($whatsapp = preg_replace('/\D/', '', (string) $setting('social.whatsapp')))
<div class="fixed end-5 bottom-5 z-40 flex flex-col items-center gap-3">
    @if ($whatsapp)
        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="flex size-12 items-center justify-center rounded-full bg-[#25d366] shadow-lg transition-transform hover:scale-105" aria-label="{{ __('site.chat_whatsapp') }}">
            <svg class="size-6 fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5.3-.5c.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8a9.8 9.8 0 01-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1112 21.8zM20.5 3.5A11.8 11.8 0 001.7 17.7L0 24l6.4-1.7A11.8 11.8 0 0024 12a11.7 11.7 0 00-3.5-8.5z"/></svg>
        </a>
    @endif
    <button type="button" data-back-to-top class="flex size-11 items-center justify-center bg-primary text-white opacity-0 transition-opacity hover:bg-primary-hover" aria-label="{{ __('site.back_to_top') }}">
        <svg class="size-4" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
    </button>
</div>
