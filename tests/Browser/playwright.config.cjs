// Browser tests against the running local site (see README "Browser tests").
const { defineConfig, devices } = require('playwright/test');

module.exports = defineConfig({
    testDir: '.',
    testMatch: '*.spec.cjs',
    outputDir: '../../storage/framework/testing/playwright',
    snapshotPathTemplate: '{testDir}/__screenshots__/{projectName}/{arg}{ext}',
    timeout: 60_000,
    fullyParallel: true,
    reporter: 'line',
    use: {
        baseURL: process.env.BROWSER_BASE_URL ?? 'http://agroegypt.local',
        // Stops the hero slider's autoplay so screenshots are stable.
        contextOptions: { reducedMotion: 'reduce' },
    },
    expect: { toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' } },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1440, height: 900 } } },
        { name: 'mobile', use: { ...devices['Pixel 7'], channel: 'chrome' } },
    ],
});
