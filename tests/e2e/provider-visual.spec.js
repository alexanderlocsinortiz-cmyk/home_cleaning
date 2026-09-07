import { expect, test } from '@playwright/test';

test('provider portal remains usable across common viewport sizes', async ({ page }) => {
    const email = process.env.E2E_PROVIDER_EMAIL || 'e2e-provider@cleanflow.local';
    const password = process.env.E2E_PROVIDER_PASSWORD || 'password123';

    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#login-submit-button').click();
    await page.waitForURL('**/provider/dashboard');

    for (const viewport of [
        { name: 'mobile', width: 375, height: 812 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'desktop', width: 1440, height: 1000 },
    ]) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });

        for (const screen of [
            { name: 'dashboard', path: '/provider/dashboard' },
            { name: 'bookings', path: '/provider/bookings' },
            { name: 'payouts', path: '/provider/payouts' },
        ]) {
            await page.goto(screen.path);
            await expect(page.locator('body')).toContainText('Cleaner');
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(viewport.width + 1);
            await page.screenshot({
                path: `test-results/provider-${screen.name}-${viewport.name}.png`,
                fullPage: true,
            });
        }
    }
});
