import Alpine from 'alpinejs';

document.documentElement.classList.add('js');

const reducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('no-animations');

const isRtl = () => document.documentElement.dir === 'rtl';

/**
 * Hero slider (replaces the reference's Revolution Slider): fade / slide / zoom, autoplay, arrows,
 * swipe and keyboard. Autoplay is disabled for reduced-motion users.
 */
Alpine.data('slider', ({ count, interval = 5000, autoplay = true }) => ({
    current: 0,
    count,
    timer: null,
    touchX: null,

    init() {
        this.play();
        document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.play()));
    },
    play() {
        this.stop();
        if (autoplay && this.count > 1 && !reducedMotion()) {
            this.timer = setInterval(() => this.next(), interval);
        }
    },
    stop() {
        clearInterval(this.timer);
    },
    go(index) {
        this.current = (index + this.count) % this.count;
        this.play();
    },
    next() {
        this.go(this.current + 1);
    },
    prev() {
        this.go(this.current - 1);
    },
    touchStart(event) {
        this.touchX = event.touches[0].clientX;
    },
    touchEnd(event) {
        if (this.touchX === null) return;
        const delta = event.changedTouches[0].clientX - this.touchX;
        if (Math.abs(delta) > 50) {
            (delta < 0) !== isRtl() ? this.next() : this.prev();
        }
        this.touchX = null;
    },
}));

/**
 * Horizontal scroll-snap carousel (certificates). Arrows scroll one card; optional autoplay loops.
 */
Alpine.data('carousel', ({ autoplay = true, interval = 3000 } = {}) => ({
    timer: null,

    init() {
        if (autoplay && !reducedMotion()) {
            this.timer = setInterval(() => this.scroll(1, true), interval);
        }
    },
    scroll(direction, loop = false) {
        const track = this.$refs.track;
        const card = track.firstElementChild;
        if (!card) return;
        const step = (card.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 0)) * direction * (isRtl() ? -1 : 1);
        const atEnd = Math.abs(track.scrollLeft) + track.clientWidth >= track.scrollWidth - 2;
        if (loop && atEnd) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            track.scrollBy({ left: step, behavior: 'smooth' });
        }
    },
    pause() {
        clearInterval(this.timer);
    },
}));

/**
 * Gallery lightbox with next/prev, Escape and arrow keys; focus returns to the opening thumbnail.
 */
Alpine.data('lightbox', (images) => ({
    images,
    index: null,
    opener: null,

    get isOpen() {
        return this.index !== null;
    },
    get image() {
        return this.images[this.index] ?? null;
    },
    open(index, event) {
        this.opener = event?.currentTarget ?? null;
        this.index = index;
        document.body.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.close?.focus());
    },
    close() {
        this.index = null;
        document.body.style.overflow = '';
        this.opener?.focus();
    },
    next() {
        this.index = (this.index + 1) % this.images.length;
    },
    prev() {
        this.index = (this.index - 1 + this.images.length) % this.images.length;
    },
}));

Alpine.start();

// Scroll-triggered reveals and progress bars.
const observer = new IntersectionObserver(
    (entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const el = entry.target;
            el.classList.add('is-revealed');
            observer.unobserve(el);
        }
    },
    { threshold: 0.15 },
);

document.querySelectorAll('[data-reveal], [data-progress]').forEach((el) => observer.observe(el));

// Back-to-top button.
const backToTop = document.querySelector('[data-back-to-top]');
if (backToTop) {
    const toggle = () => backToTop.classList.toggle('opacity-0', window.scrollY < 400);
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' }));
}
