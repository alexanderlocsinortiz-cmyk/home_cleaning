import { expect, test } from '@playwright/test';

test('instant quote summaries stay in normal page flow', async ({ page }) => {
    for (const viewport of [
        { width: 375, height: 812 },
        { width: 1440, height: 1000 },
    ]) {
        await page.setViewportSize(viewport);
        await page.goto('/');

        const selector = viewport.width < 1024
            ? '[data-instant-quote-mobile-summary]'
            : '.instant-quote-summary';
        const summary = page.locator(selector);

        await expect(summary).toBeVisible();
        await expect(summary).toHaveCSS('position', 'static');

        if (viewport.width < 1024) {
            await expect(summary).not.toContainText('Sticky');
        }
    }
});
