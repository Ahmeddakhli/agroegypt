@php
    $socials = [
        'facebook' => ['bg-facebook', 'M14 8h3V4h-3c-2.8 0-4 1.9-4 4.4V10H7v4h3v10h4V14h3l1-4h-4V8.6c0-.4.2-.6.6-.6z'],
        'instagram' => ['bg-instagram', 'M12 2.2c3.2 0 3.6 0 4.8.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8C2.4 3.9 3.9 2.4 7.2 2.3 8.4 2.2 8.8 2.2 12 2.2zM12 0C8.7 0 8.3 0 7.1.1 2.7.3.3 2.7.1 7.1 0 8.3 0 8.7 0 12s0 3.7.1 4.9c.2 4.4 2.6 6.8 7 7C8.3 24 8.7 24 12 24s3.7 0 4.9-.1c4.4-.2 6.8-2.6 7-7 .1-1.2.1-1.6.1-4.9s0-3.7-.1-4.9c-.2-4.4-2.6-6.8-7-7C15.7 0 15.3 0 12 0zm0 5.8a6.2 6.2 0 100 12.4 6.2 6.2 0 000-12.4zM12 16a4 4 0 110-8 4 4 0 010 8zm6.4-11.8a1.4 1.4 0 100 2.9 1.4 1.4 0 000-2.9z'],
        'linkedin' => ['bg-linkedin', 'M6.9 21H3V8.6h3.9V21zM4.9 6.9a2.2 2.2 0 110-4.5 2.2 2.2 0 010 4.5zM21 21h-3.9v-6c0-1.4 0-3.3-2-3.3s-2.3 1.6-2.3 3.2V21H9V8.6h3.7v1.7h.1c.5-1 1.8-2 3.7-2 3.9 0 4.6 2.6 4.6 5.9V21z'],
        'youtube' => ['bg-youtube', 'M23 7.2a3 3 0 00-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 001 7.2 31 31 0 00.5 12a31 31 0 00.5 4.8 3 3 0 002.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 002.1-2.1 31 31 0 00.5-4.8 31 31 0 00-.5-4.8zM9.8 15V9l5.8 3-5.8 3z'],
    ];
    $contactIcons = ['address' => 'M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z', 'phone' => 'M6.6 10.8a15 15 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.6 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1.02l-2.2 2.2z', 'fax' => 'M19 8H5a3 3 0 00-3 3v6h4v4h12v-4h4v-6a3 3 0 00-3-3zm-3 11H8v-5h8v5zm3-7a1 1 0 110-2 1 1 0 010 2zm-1-9H6v4h12V3z', 'email' => 'M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z'];
    $contacts = array_filter(['address' => $setting('contact.address'), 'phone' => $setting('contact.phone'), 'fax' => $setting('contact.fax'), 'email' => $setting('contact.email')]);
    $footerLogo = $setting('theme.footer_logo');
@endphp
<footer class="bg-dark text-white">
    <div class="container-site grid gap-10 pt-20 pb-14 sm:grid-cols-2 lg:grid-cols-[300px_200px_300px_1fr] lg:gap-x-0">
        <div>
            @if ($footerLogo)
                <img src="{{ asset('storage/'.$footerLogo) }}" alt="{{ $setting('company.name', config('app.name')) }}" width="200" height="76" loading="lazy" class="h-auto w-[200px]">
            @endif
            @if ($tagline = $setting('footer.tagline'))
                <p class="mt-8 max-w-[220px] text-[16px] leading-[28px]">{{ $tagline }}</p>
            @endif
        </div>

        @if ($footerMenu)
            <nav aria-labelledby="footer-links-title">
                <h3 id="footer-links-title" class="mb-6 text-[24px] leading-[34px]">{{ $setting('footer.links_title', __('site.useful_links')) }}</h3>
                <ul class="space-y-2.5 text-[18px]">
                    @foreach ($footerMenu as $item)
                        <li><a href="{{ $item['url'] }}" target="{{ $item['target'] }}" class="transition-colors hover:text-orange">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($contacts)
        <div>
            <h3 class="mb-6 text-[24px] leading-[34px]">{{ $setting('footer.contact_title', __('site.contact_info')) }}</h3>
            <ul class="space-y-3 text-[16px] leading-[26px]">
                @foreach ($contacts as $type => $value)
                    <li class="flex gap-2.5">
                        @if ($icon = $setting('footer.icon_'.$type))
                            <img src="{{ asset('storage/'.$icon) }}" alt="" class="mt-1 size-4 shrink-0" loading="lazy">
                        @else
                            <svg class="mt-1 size-4 shrink-0 fill-orange" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $contactIcons[$type] }}"/></svg>
                        @endif
                        <span class="sr-only">{{ __('site.contact.'.$type) }}:</span>
                        @if ($type === 'email')
                            <a href="mailto:{{ $value }}" class="hover:text-orange">{{ $value }}</a>
                        @elseif ($type === 'phone')
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $value) }}" class="hover:text-orange" dir="ltr">{{ $value }}</a>
                        @elseif ($type === 'fax')
                            <span dir="ltr">{{ $value }}</span>
                        @else
                            <span>{!! nl2br(e($value)) !!}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        @php($socialLinks = array_filter(array_map(fn ($network) => $setting('social.'.$network), array_combine(array_keys($socials), array_keys($socials)))))
        @if ($socialLinks)
        <div>
            <h3 class="mb-6 text-[24px] leading-[34px]">{{ $setting('footer.social_title', __('site.social_media')) }}</h3>
            <ul class="flex flex-col gap-2 sm:flex-row sm:flex-wrap lg:flex-nowrap">
                @foreach ($socials as $network => [$bg, $path])
                    @if ($url = $setting('social.'.$network))
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex h-11 w-full bg-heading-alt transition-opacity hover:opacity-85 sm:w-[69px]" aria-label="{{ ucfirst($network) }}">
                                <span class="flex size-11 items-center justify-center {{ $bg }}"><svg class="size-4 fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}"/></svg></span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <div class="border-t border-white/10">
        <div class="container-site flex flex-col items-center justify-between gap-2 py-4 text-[16px] text-white/85 sm:flex-row">
            <p>{{ $setting('footer.copyright', '© '.date('Y').' '.config('app.name').'.') }}</p>
            @if ($credit = $setting('footer.credit'))
                <p>{{ $credit }}</p>
            @endif
        </div>
    </div>
</footer>
