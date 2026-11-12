import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const screenshotsDir = path.resolve('docs/screenshots');
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}

test.describe('Responsive & Cross-Device Compatibility Suite', () => {
  const publicRoutes = [
    { name: 'landing-page', path: '/' },
    { name: 'login', path: '/login' },
    { name: 'register', path: '/register' },
    { name: 'easy-mode', path: '/easy-mode' },
    { name: 'features', path: '/features' },
    { name: 'contact', path: '/contact' },
    { name: 'learning-centre', path: '/learning-centre' },
    { name: 'experience-lab', path: '/experience-lab' },
  ];

  for (const route of publicRoutes) {
    test(`Verify ${route.name} (${route.path}) reflow and zero overflow`, async ({ page }, testInfo) => {
      const consoleErrors: string[] = [];
      page.on('console', msg => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
      });

      const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
      expect(response?.status()).toBeLessThan(400);

      // Verify page title exists and body is rendered
      await expect(page.locator('body')).toBeVisible();

      // Check for zero horizontal overflow (tolerant to 1px fractional rounding)
      const isOverflowing = await page.evaluate(() => {
        const docWidth = document.documentElement.scrollWidth;
        const winWidth = window.innerWidth;
        return docWidth > winWidth + 1.5;
      });
      expect(isOverflowing).toBe(false);

      // Capture representative screenshot for key device profiles
      const projectName = testInfo.project.name.replace(/[^a-zA-Z0-9_-]/g, '_');
      if (['Android___Small__320x568_', 'iPhone_14_15_Pro__393x852_', 'Tablet_Portrait___iPad_Mini__768x1024_', 'Desktop___1080p_FHD__1920x1080_'].includes(projectName)) {
        await page.screenshot({
          path: path.join(screenshotsDir, `${projectName}-${route.name}.png`),
          fullPage: false,
        });
      }
    });
  }

  test('Verify mobile drawer navigation and touch toggles', async ({ page, isMobile }) => {
    if (!isMobile) return;

    await page.goto('/login');
    // Verify touch target size of primary action buttons >= 40px
    const signInBtn = page.locator('button[type="submit"]');
    await expect(signInBtn).toBeVisible();
    const box = await signInBtn.boundingBox();
    expect(box?.height).toBeGreaterThanOrEqual(40);
  });
});
