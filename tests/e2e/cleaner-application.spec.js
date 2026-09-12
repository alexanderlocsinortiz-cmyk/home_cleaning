import { expect, test } from '@playwright/test';

async function fillContactDetails(page, { dateOfBirth = '1990-01-01', phone = '09171234567' } = {}) {
    await page.locator('#individual_name').fill('Browser Test Applicant');
    await page.locator('#date_of_birth').fill(dateOfBirth);
    await page.locator('#individual_current_address').fill('Valencia City, Bukidnon');
    await page.locator('#email').fill('browser-test@example.com');
    await page.locator('#phone').fill(phone);
}

async function fillProviderLocation(page) {
    const map = page.locator('#provider-location-map');

    await expect(map).toBeVisible();
    await page.locator('#location_area').selectOption({ index: 1 });

    if (await map.locator('.gm-style').count()) {
        await expect(map.locator('.gm-style')).toBeVisible();
        await map.click({ position: { x: 160, y: 160 } });
        await expect(page.locator('#location_latitude')).not.toHaveValue('');
        await expect(page.locator('#location_longitude')).not.toHaveValue('');
        await expect(page.locator('[data-provider-location-confirm]')).toBeEnabled();
        await page.locator('[data-provider-location-confirm]').click();
    } else {
        // The testing environment intentionally has no paid Google Maps key.
        await expect(map).toContainText('Google Maps is not available');
        await page.locator('#location_latitude').evaluate((input) => { input.value = '7.9047000'; });
        await page.locator('#location_longitude').evaluate((input) => { input.value = '125.0940000'; });
        await page.locator('#location_confirmed').evaluate((input) => { input.value = '1'; });
    }

    await expect(page.locator('#location_confirmed')).toHaveValue('1');
    await page.locator('#experience_unit').selectOption('months');
    await page.locator('#years_experience').fill('18');
}

test('applicant can complete the cleaner application review flow', async ({ page }) => {
    await page.goto('/cleaners/apply');

    await expect(page).toHaveTitle(/Apply as Cleaner/);
    await expect(page.getByText('Personal contact and identity details are never saved in the browser draft.')).toBeVisible();

    await fillContactDetails(page);
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="2"]')).toBeVisible();
    await fillProviderLocation(page);
    await page.locator('input[name="services_offered[]"]').first().check();
    await page.locator('input[name="available_days[]"]').first().check();
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="3"]')).toBeVisible();
    await page.locator('#government_id_type').selectOption({ index: 1 });
    await page.locator('#government_id_number').fill('N01-12-123456');
    await page.locator('#government_id_front_document').setInputFiles({
        name: 'government-id-front.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('browser-test-id'),
    });
    await page.locator('#government_id_back_document').setInputFiles({
        name: 'government-id-back.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('browser-test-id-back'),
    });
    await page.locator('#selfie_with_id').setInputFiles({
        name: 'selfie.png',
        mimeType: 'image/png',
        buffer: Buffer.from('browser-test-selfie'),
    });
    for (const name of [
        'worked_as_cleaner_before',
        'worked_for_cleaning_company_before',
        'has_cleaning_certifications',
        'owns_cleaning_equipment',
    ]) {
        await page.locator(`input[name="${name}"][value="0"]`).check();
    }
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="4"]')).toBeVisible();
    await expect(page.locator('[data-summary-value="applicant"]')).toHaveText('Browser Test Applicant');
    await expect(page.locator('[data-summary-value="email"]')).toHaveText('browser-test@example.com');
    await expect(page.locator('[data-summary-value="files"]')).toContainText('government-id-front.pdf');
    await expect(page.locator('[data-summary-value="files"]')).toContainText('government-id-back.pdf');
    await expect(page.locator('[data-summary-value="files"]')).toContainText('selfie.png');
});

test('applicant cannot continue with an invalid Philippine mobile number', async ({ page }) => {
    await page.goto('/cleaners/apply');
    await fillContactDetails(page, { phone: '0917123456' });
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="1"]')).toBeVisible();
    await expect(page.locator('[data-form-warning]')).toHaveText('Fix the red warning field before continuing.');
    await expect(page.locator('#phone')).toHaveClass(/cleaner-apply-invalid/);
    await expect(page.locator('#phone ~ p[data-client-error]')).toHaveText('Enter an 11-digit Philippine mobile number starting with 09.');
});

test('applicant cannot continue when under 18', async ({ page }) => {
    await page.goto('/cleaners/apply');
    await fillContactDetails(page, { dateOfBirth: '2010-01-01' });
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="1"]')).toBeVisible();
    await expect(page.locator('[data-form-warning]')).toHaveText('Fix the red warning field before continuing.');
    await expect(page.locator('#date_of_birth')).toHaveClass(/cleaner-apply-invalid/);
    await expect(page.locator('#date_of_birth ~ p[data-client-error]')).toHaveText('Date of Birth is too high.');
});

test('switching to a team ignores hidden individual fields', async ({ page }) => {
    await page.goto('/cleaners/apply');
    await fillContactDetails(page, { dateOfBirth: '2010-01-01' });
    await page.locator('label').filter({ hasText: 'Cleaning Team / Business' }).first().click();
    await page.locator('#team_business_name').fill('Browser Test Team');
    await page.locator('#contact_person').fill('Team Contact');
    await page.locator('#business_address').fill('Valencia City, Bukidnon');
    await page.locator('#team_size').fill('2');
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="2"]')).toBeVisible();
});

test('step two shows selection progress and explains missing choices', async ({ page }) => {
    await page.goto('/cleaners/apply');
    await fillContactDetails(page);
    await page.locator('[data-step-next]').click();

    await page.locator('input[name="coverage_mode"][value="specific"]').check();
    await page.locator('[data-coverage-picker-toggle]').click();
    await page.locator('[data-coverage-option][value="Malaybalay City"]').check();
    await page.locator('[data-coverage-picker-confirm]').click();
    await expect(page.locator('[data-coverage-selection-count]')).toHaveText('1 area selected');
    await expect(page.locator('[data-service-count]')).toHaveText('0 selected');
    await expect(page.locator('[data-day-count]')).toHaveText('0 selected');

    await fillProviderLocation(page);

    await page.locator('[data-step-next]').click();
    await expect(page.locator('[data-step-panel="2"]')).toBeVisible();
    await expect(page.locator('[data-form-warning]')).toHaveText('Choose at least one or more services before continuing.');

    await page.locator('input[name="services_offered[]"]').first().check();
    await page.locator('[data-step-next]').click();
    await expect(page.locator('[data-form-warning]')).toHaveText('Choose at least one or more available days before continuing.');
});

test('applicant cannot continue with an identity file larger than 5 MB', async ({ page }) => {
    await page.goto('/cleaners/apply');
    await fillContactDetails(page);
    await page.locator('[data-step-next]').click();
    await fillProviderLocation(page);
    await page.locator('input[name="services_offered[]"]').first().check();
    await page.locator('input[name="available_days[]"]').first().check();
    await page.locator('[data-step-next]').click();

    await page.locator('#government_id_type').selectOption({ index: 1 });
    await page.locator('#government_id_number').fill('N01-12-123456');
    await page.locator('#government_id_front_document').setInputFiles({
        name: 'oversized-government-id-front.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.alloc(5 * 1024 * 1024 + 1),
    });
    await page.locator('#government_id_back_document').setInputFiles({
        name: 'government-id-back.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('browser-test-id-back'),
    });
    await page.locator('#selfie_with_id').setInputFiles({
        name: 'selfie.png',
        mimeType: 'image/png',
        buffer: Buffer.from('browser-test-selfie'),
    });
    for (const name of [
        'worked_as_cleaner_before',
        'worked_for_cleaning_company_before',
        'has_cleaning_certifications',
        'owns_cleaning_equipment',
    ]) {
        await page.locator(`input[name="${name}"][value="0"]`).check();
    }
    await page.locator('[data-step-next]').click();

    await expect(page.locator('[data-step-panel="3"]')).toBeVisible();
    await expect(page.locator('[data-form-warning]')).toHaveText('Remove files larger than 5 MB before continuing.');
    await expect(page.locator('#government_id_front_document')).toHaveClass(/cleaner-apply-invalid/);
});
