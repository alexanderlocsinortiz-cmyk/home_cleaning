import { expect, test } from '@playwright/test';

test('staff can share a mocked browser location from a mobile viewport', async ({ page }) => {
    const baseUrl = process.env.E2E_BASE_URL || 'http://127.0.0.1:8001';
    const context = page.context();

    await context.grantPermissions(['geolocation'], { origin: baseUrl });
    await context.setGeolocation({ latitude: 7.9073, longitude: 125.092 });
    await page.setViewportSize({ width: 375, height: 812 });

    await page.goto('/login');
    await page.locator('#email').fill(process.env.E2E_STAFF_EMAIL || 'e2e-staff@cleanflow.local');
    await page.locator('#password').fill(process.env.E2E_STAFF_PASSWORD || 'password123');
    await page.locator('#login-submit-button').click();
    await page.waitForURL('**/staff/dashboard');

    const button = page.locator('[id^="track-btn-"]').first();
    await expect(button).toBeVisible();

    const locationRequest = page.waitForRequest((request) =>
        request.method() === 'POST' && request.url().includes('/location/update')
    );
    await button.click();

    const request = await locationRequest;
    const payload = JSON.parse(request.postData() || '{}');
    expect(payload.latitude).toBeCloseTo(7.9073, 4);
    expect(payload.longitude).toBeCloseTo(125.092, 4);
    await expect(button).toContainText('Location live');

    const currentLocationUrl = (await button.getAttribute('data-location-update-url'))
        .replace('/location/update', '/location/current');
    await expect.poll(async () => page.evaluate(async (url) => {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        return response.json();
    }, currentLocationUrl)).toMatchObject({
        tracking: true,
        latitude: 7.9073,
        longitude: 125.092,
    });
});
