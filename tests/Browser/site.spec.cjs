const { test, expect } = require('playwright/test');

const pages = {
    home: '',
    about: '/about-us',
    products: '/our-products',
    category: '/our-products/citrus',
    product: '/our-products/citrus/orange/navel',
    brands: '/brands',
    markets: '/markets',
    gallery: '/gallery',
    album: '/gallery/citrus',
    careers: '/careers',
    contact: '/contact-us',
};

/**
 * Fails the test on console errors and on failed same-origin requests (missing assets, broken images).
 */
function watchForErrors(page, baseURL) {
    const problems = [];
    page.on('console', (message) => message.type() === 'error' && problems.push(`console: ${message.text()}`));
    page.on('pageerror', (error) => problems.push(`page error: ${error.message}`));
    page.on('response', (response) => {
        if (response.url().startsWith(baseURL) && response.status() >= 400) {
            problems.push(`${response.status()} ${response.url()}`);
        }
    });
    page.on('requestfailed', (request) => request.url().startsWith(baseURL) && problems.push(`failed ${request.url()}`));

    return problems;
}

async function scrollThrough(page) {
    await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += window.innerHeight / 2) {
            window.scrollTo(0, y);
            await new Promise((resolve) => setTimeout(resolve, 60));
        }
        window.scrollTo(0, 0);
        await document.fonts.ready;
        await Promise.all([...document.images].filter((img) => img.complete).map((img) => img.decode().catch(() => null)));
    });
}

for (const locale of ['en', 'ar']) {
    for (const [name, path] of Object.entries(pages)) {
        test(`${name} (${locale}) renders cleanly`, async ({ page, baseURL }) => {
            const problems = watchForErrors(page, baseURL);
            const response = await page.goto(`/${locale}${path}`);

            expect(response.status()).toBe(200);
            await expect(page.locator('html')).toHaveAttribute('lang', locale);
            await expect(page.locator('html')).toHaveAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
            await expect(page.locator('h1, [aria-roledescription="carousel"]').first()).toBeVisible();

            await scrollThrough(page);
            await page.waitForLoadState('networkidle');

            const broken = await page.$$eval('img', (images) =>
                images.filter((img) => img.complete && img.naturalWidth === 0 && img.getAttribute('src')).map((img) => img.currentSrc || img.src),
            );
            expect(broken, 'broken images').toEqual([]);

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            expect(overflow, 'horizontal overflow (px)').toBeLessThanOrEqual(1);

            expect(problems).toEqual([]);

            // The floating buttons follow the scroll position, which a full-page capture changes.
            await expect(page).toHaveScreenshot(`${name}-${locale}.png`, { fullPage: true, mask: [page.locator('[data-back-to-top]').locator('..')] });
        });
    }
}

test('unknown pages show the localized 404', async ({ page }) => {
    const response = await page.goto('/ar/does-not-exist');
    expect(response.status()).toBe(404);
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
});

test('language switcher keeps the visitor on the same page', async ({ page, isMobile }) => {
    await page.goto('/en/our-products/citrus');
    const switcher = page.locator(isMobile ? 'header .nav\\:hidden' : 'header nav').first();
    await switcher.getByRole('button', { name: /language/i }).click();
    await switcher.locator('a[hreflang="ar"]').click();

    await expect(page).toHaveURL(/\/ar\/our-products\/citrus$/);
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
});

test('mobile menu opens, expands submenus and closes with Escape', async ({ page, isMobile }) => {
    test.skip(!isMobile, 'mobile navigation only');
    await page.goto('/en');
    const menu = page.locator('#mobile-menu');
    await expect(menu).toBeHidden();

    await page.getByRole('button', { name: /menu/i }).click();
    await expect(menu).toBeVisible();
    await expect(menu.getByRole('link').first()).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(menu).toBeHidden();
});

test('desktop dropdown reveals product categories on hover', async ({ page, isMobile }) => {
    test.skip(isMobile, 'desktop navigation only');
    await page.goto('/en');
    const products = page.locator('header nav li.group').filter({ has: page.locator('a[href$="/en/our-products"]') }).first();
    await products.hover();
    await expect(products.locator('a[href*="/en/our-products/citrus"]').first()).toBeVisible();
});

test('gallery lightbox opens, pages with the keyboard and closes', async ({ page }) => {
    await page.goto('/en/gallery/citrus');
    const dialog = page.getByRole('dialog');
    await page.locator('[x-data^="lightbox"] ul button').first().click();

    await expect(dialog).toBeVisible();
    await expect(dialog.locator('p')).toHaveText(/^1 \/ \d+$/);
    await page.keyboard.press('ArrowRight');
    await expect(dialog.locator('p')).toHaveText(/^2 \/ \d+$/);
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
});

test('hero slider moves with its arrows', async ({ page, isMobile }) => {
    test.skip(isMobile, 'arrows are hidden on small screens');
    await page.goto('/en');
    const slider = page.locator('[aria-roledescription="carousel"]');
    const slide = (number) => slider.getByRole('group', { name: new RegExp(`^${number} /`) });
    await expect(slide(1)).toBeVisible();

    await slider.locator('button').last().click();
    await expect(slide(2)).toBeVisible();
    await expect(slide(1)).toBeHidden();
});

test('back-to-top appears after scrolling and returns to the top', async ({ page }) => {
    await page.goto('/en/about-us');
    const button = page.locator('[data-back-to-top]');
    await expect(button).toHaveClass(/opacity-0/);

    await page.evaluate(() => window.scrollTo(0, 1500));
    await expect(button).not.toHaveClass(/opacity-0/);
    await button.click();
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);
});

test('contact form reports validation errors in the visitor language', async ({ page, isMobile }) => {
    test.skip(isMobile, 'one submission is enough (the form is rate limited)');
    await page.goto('/ar/contact-us');
    await page.locator('#contact-email').fill('not-an-email');
    await page.locator('form[action$="/forms/contact"] button[type="submit"]').click();

    const alert = page.getByRole('alert');
    await expect(alert).toBeVisible();
    await expect(page.locator('#contact-email')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#contact-email')).toHaveValue('not-an-email');
    await expect(alert).toContainText(/[؀-ۿ]/);
});
