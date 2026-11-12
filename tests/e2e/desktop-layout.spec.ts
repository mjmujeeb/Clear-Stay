import { test, expect } from '@playwright/test';
import * as path from 'path';

test.describe('Desktop Shell & Demo Centre Layout (1648x928)', () => {
  test.use({ viewport: { width: 1648, height: 928 } });

  test('Verify /demo-centre header layout, uncompressed title, and grid reflow', async ({ page }) => {
    await page.goto('/demo-centre', { waitUntil: 'domcontentloaded' });
    
    // 1. Verify page status & body
    await expect(page.locator('body')).toBeVisible();

    // 2. Verify global search modal is NOT covering the screen on load
    const searchModal = page.locator('#globalSearchModal');
    if (await searchModal.count() > 0) {
      await expect(searchModal).toBeHidden();
    }

    // 3. Verify header title is visible and has adequate width (> 250px)
    const headerTitle = page.locator('.top-header .header-title, .top-header h2');
    await expect(headerTitle.first()).toBeVisible();
    const titleBox = await headerTitle.first().boundingBox();
    expect(titleBox?.width).toBeGreaterThan(250);

    // 4. Verify no horizontal page overflow
    const isOverflowing = await page.evaluate(() => {
      return document.documentElement.scrollWidth > window.innerWidth + 2;
    });
    expect(isOverflowing).toBe(false);

    // 5. Capture screenshot for visual inspection
    await page.screenshot({
      path: path.resolve('docs/screenshots/desktop_1648x928_demo_centre.png'),
      fullPage: false,
    });
  });

  test('Verify /login layout and ClearShield human verification box at 1648x928', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();

    // Verify login form is centered and visible
    await expect(page.locator('form[action*="login"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();

    // Verify anti-bot verification box is rendered cleanly
    const botBox = page.locator('.clearshield-verification-box');
    await expect(botBox).toBeVisible();

    // Capture screenshot
    await page.screenshot({
      path: path.resolve('docs/screenshots/desktop_1648x928_login.png'),
      fullPage: false,
    });
  });
});
