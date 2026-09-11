import { expect, test } from '@playwright/test';

async function dispatchInstallPrompt(page) {
    await page.evaluate(() => {
        window.dispatchEvent(new Event('beforeinstallprompt', { cancelable: true }));
    });
}

test('install banner is limited to the homepage and remembers dismissal', async ({ page }) => {
    await page.goto('/');
    await page.evaluate(() => localStorage.removeItem('cleanflow-install-banner-dismissed-until'));
    await dispatchInstallPrompt(page);

    const banner = page.getByText('Install CleanFlow');

    await expect(banner).toBeVisible();
    await page.getByRole('button', { name: 'Not now' }).click();
    await expect(banner).toBeHidden();

    await page.reload();
    await dispatchInstallPrompt(page);
    await expect(page.getByText('Install CleanFlow')).toBeHidden();

    await page.goto('/map');
    await dispatchInstallPrompt(page);
    await expect(page.getByText('Install CleanFlow')).toBeHidden();
});

test('service worker cache version changes with the install prompt code', async ({ request }) => {
    const response = await request.get('/sw.js');

    expect(response.ok()).toBeTruthy();
    expect(await response.text()).toContain("cleanflow-static-v11");
});
