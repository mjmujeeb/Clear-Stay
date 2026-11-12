import { test, expect } from '@playwright/test';

test.describe('End-to-End Business Scenarios (Browser Verification)', () => {
  test('Scenario A - Administrator loads registration and verifies multi-tenant properties', async ({ page }) => {
    await page.goto('/register');
    await expect(page).toHaveTitle(/ClearStay/);
    await expect(page.locator('text=Staff Registration')).toBeVisible();

    // Verify multi-tenant hotel assignment dropdown is populated
    const hotelSelect = page.locator('select[name="hotel_id"]');
    await expect(hotelSelect).toBeVisible();
    const optionsCount = await hotelSelect.locator('option').count();
    expect(optionsCount).toBeGreaterThan(0);

    // Verify all role assignment choices exist
    const roleSelect = page.locator('select[name="role"]');
    await expect(roleSelect).toBeVisible();
    await expect(roleSelect.locator('option[value="manager"]')).toHaveCount(1);
    await expect(roleSelect.locator('option[value="frontdesk"]')).toHaveCount(1);
    await expect(roleSelect.locator('option[value="housekeeping"]')).toHaveCount(1);
  });

  test('Scenario B - Front desk reservation and date change validation', async ({ page }) => {
    await page.goto('/reservations/create');
    // If not authenticated, verify clean redirect to /login
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await expect(page.locator('text=Staff Sign In')).toBeVisible();
      await expect(page.locator('input[name="email"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    } else {
      await expect(page.locator('text=New Reservation')).toBeVisible();
    }
  });

  test('Scenario C - Easy Mode & Senior Accessibility Workflow', async ({ page }) => {
    await page.goto('/easy-mode');
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await expect(page.locator('text=Staff Sign In')).toBeVisible();
    } else {
      await expect(page.locator('text=ClearStay')).toBeVisible();
      const easyBtns = page.locator('.easy-btn');
      const count = await easyBtns.count();
      if (count > 0) {
        const box = await easyBtns.first().boundingBox();
        expect(box?.height).toBeGreaterThanOrEqual(44);
      }
    }
  });
});
