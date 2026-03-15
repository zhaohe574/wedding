import { test, chromium, devices } from '@playwright/test';

const captures = [
  {
    name: 'dynamic-square',
    url: 'http://10.37.6.210:8991/mobile/#/pages/dynamic/dynamic',
    screenshot: 'd:/2025-10-31/wedding-management/docs/dynamic-square.png',
    har: 'd:/2025-10-31/wedding-management/docs/har/dynamic-square.har',
    readyText: '暂无符合条件的动态'
  },
  {
    name: 'search-dynamic-empty',
    url: 'http://10.37.6.210:8991/mobile/#/pages/search/search?type=dynamic&keyword=%E5%A9%9A%E7%A4%BC',
    screenshot: 'd:/2025-10-31/wedding-management/docs/search-dynamic-empty.png',
    har: 'd:/2025-10-31/wedding-management/docs/har/search-dynamic-empty.har',
    readyText: '暂无搜索结果'
  }
];

for (const capture of captures) {
  test(`capture ${capture.name}`, async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: false, slowMo: 80 });
    const context = await browser.newContext({
      ...devices['iPhone 14 Pro Max'],
      recordHar: { path: capture.har }
    });
    const page = await context.newPage();

    await page.goto(capture.url, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(4000);
    await page.getByText(capture.readyText).waitFor({ timeout: 15000 }).catch(() => null);
    await page.waitForTimeout(1500);
    await page.screenshot({ path: capture.screenshot, fullPage: true });

    await context.close();
    await browser.close();
  });
}
