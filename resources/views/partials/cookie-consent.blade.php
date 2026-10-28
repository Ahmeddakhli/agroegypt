@if ($setting('cookies.enabled', '0') === '1')
    <div x-data="{ show: ! document.cookie.includes('cookie_consent=') }" x-cloak x-show="show" role="dialog" aria-live="polite" aria-label="{{ __('site.cookies.title') }}"
         class="fixed inset-x-0 bottom-0 z-50 bg-deep/95 text-white">
        <div class="container-site flex flex-col items-center gap-4 py-4 sm:flex-row sm:justify-between">
            <p class="text-[15px] leading-6">{{ $setting('cookies.message', __('site.cookies.message')) }}</p>
            <div class="flex gap-3">
                <button type="button" class="btn-primary !px-5 !py-2" @click="document.cookie = 'cookie_consent=accepted;path=/;max-age=31536000;SameSite=Lax'; show = false; location.reload()">{{ __('site.cookies.accept') }}</button>
                <button type="button" class="px-4 py-2 text-[15px] underline" @click="document.cookie = 'cookie_consent=declined;path=/;max-age=31536000;SameSite=Lax'; show = false">{{ __('site.cookies.decline') }}</button>
            </div>
        </div>
    </div>
@endif
