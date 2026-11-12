import { defineConfig, devices } from '@playwright/test';

/**
 * ClearStay 4.1 Enterprise Cross-Device & Responsive Playwright Configuration
 * Validates zero horizontal overflow, touch accessibility, and viewport reflow.
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
  use: {
    baseURL: process.env.APP_URL || 'http://127.0.0.1:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    // 1. Android Phones
    {
      name: 'Android - Small (320x568)',
      use: {
        viewport: { width: 320, height: 568 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
        userAgent: 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
      },
    },
    {
      name: 'Android - Standard (360x800)',
      use: {
        viewport: { width: 360, height: 800 },
        deviceScaleFactor: 2.5,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'Android - Pixel 7 (393x873)',
      use: {
        viewport: { width: 393, height: 873 },
        deviceScaleFactor: 2.75,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'Android - Large (412x915)',
      use: {
        viewport: { width: 412, height: 915 },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
      },
    },

    // 2. Apple iPhones
    {
      name: 'iPhone SE (375x667)',
      use: {
        viewport: { width: 375, height: 667 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'iPhone 14/15 Pro (393x852)',
      use: {
        viewport: { width: 393, height: 852 },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'iPhone 15 Pro Max (430x932)',
      use: {
        viewport: { width: 430, height: 932 },
        deviceScaleFactor: 3,
        isMobile: true,
        hasTouch: true,
      },
    },

    // 3. Foldables (Cover & Unfolded Inner)
    {
      name: 'Foldable Cover (280x653)',
      use: {
        viewport: { width: 280, height: 653 },
        deviceScaleFactor: 2.6,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'Foldable Inner (884x1104)',
      use: {
        viewport: { width: 884, height: 1104 },
        deviceScaleFactor: 2.5,
        isMobile: true,
        hasTouch: true,
      },
    },

    // 4. Tablets (Portrait & Landscape)
    {
      name: 'Tablet Portrait - iPad Mini (768x1024)',
      use: {
        viewport: { width: 768, height: 1024 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'Tablet Landscape - iPad Air (1112x834)',
      use: {
        viewport: { width: 1112, height: 834 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
      },
    },
    {
      name: 'Tablet Landscape - iPad Pro 12.9 (1366x1024)',
      use: {
        viewport: { width: 1366, height: 1024 },
        deviceScaleFactor: 2,
        isMobile: false,
        hasTouch: true,
      },
    },

    // 5. Laptops & Desktops
    {
      name: 'Laptop - 1366x768',
      use: {
        viewport: { width: 1366, height: 768 },
        deviceScaleFactor: 1,
      },
    },
    {
      name: 'Laptop - 1440x900',
      use: {
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 1,
      },
    },
    {
      name: 'Desktop - 1648x928',
      use: {
        viewport: { width: 1648, height: 928 },
        deviceScaleFactor: 1,
      },
    },
    {
      name: 'Desktop - 1080p FHD (1920x1080)',
      use: {
        viewport: { width: 1920, height: 1080 },
        deviceScaleFactor: 1,
      },
    },
    {
      name: 'Ultrawide Desktop - (3440x1440)',
      use: {
        viewport: { width: 3440, height: 1440 },
        deviceScaleFactor: 1,
      },
    },
  ],
  webServer: {
    command: 'php artisan serve --port=8000',
    url: 'http://127.0.0.1:8000',
    reuseExistingServer: !process.env.CI,
    timeout: 30000,
  },
});
