import { expect, test } from '@playwright/test';

test('client booking flow remains usable across common viewport sizes', async ({ page }) => {
    const email = process.env.E2E_CLIENT_EMAIL || 'e2e-client@cleanflow.local';
    const password = process.env.E2E_CLIENT_PASSWORD || 'password123';

    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#login-submit-button').click();

    for (const viewport of [
        { name: 'mobile', width: 375, height: 812 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'desktop', width: 1440, height: 1000 },
    ]) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto('/bookings');

        await expect(page).toHaveTitle(/My Bookings/);
        if (viewport.width < 1024) {
            await expect(page.locator('[aria-label="Mobile booking list"]')).toBeVisible();
            await expect(page.getByText('View details').first()).toBeVisible();
        } else {
            await expect(page.locator('[aria-label="Mobile booking list"]')).toBeHidden();
            await expect(page.locator('table').first()).toBeVisible();
        }

        await page.goto('/bookings/create');

        await expect(page).toHaveTitle(/Book a Service/);
        await expect(page.locator('#booking-form')).toBeVisible();
        await expect(page.locator('[data-booking-progress-step]')).toHaveCount(6);
        const overflow = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            viewportWidth: window.innerWidth,
            elements: Array.from(document.querySelectorAll('body *'))
                .map((element) => ({
                    tag: element.tagName,
                    id: element.id,
                    className: typeof element.className === 'string' ? element.className : '',
                    right: Math.ceil(element.getBoundingClientRect().right),
                }))
                .filter((element) => element.right > window.innerWidth)
                .slice(-8),
        }));
        expect(overflow.scrollWidth, JSON.stringify(overflow)).toBeLessThanOrEqual(viewport.width + 1);

        await page.locator('[data-booking-progress-step="6"]').click();
        await expect(page.locator('#booking-step-6')).toBeInViewport();
        await expect(page.locator('[data-booking-progress-step="6"]')).toHaveAttribute('aria-current', 'step');

        await page.screenshot({
            path: `test-results/client-booking-${viewport.name}.png`,
            fullPage: true,
        });
    }
});
