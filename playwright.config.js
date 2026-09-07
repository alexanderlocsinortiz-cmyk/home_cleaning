import fs from 'node:fs';
import { defineConfig } from '@playwright/test';

const systemChromePath = process.platform === 'win32'
    ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
    : '/usr/bin/google-chrome';
const chromePath = process.env.E2E_CHROME_PATH
    || (fs.existsSync(systemChromePath) ? systemChromePath : undefined);
const e2ePort = process.env.E2E_PORT || '8001';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 120_000,
    fullyParallel: false,
    reporter: 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL || `http://127.0.0.1:${e2ePort}`,
        browserName: 'chromium',
        ...(chromePath
            ? { launchOptions: { executablePath: chromePath } }
            : {}),
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'node scripts/serve-e2e.mjs',
        url: `http://127.0.0.1:${e2ePort}/up`,
        env: { E2E_PORT: e2ePort },
        reuseExistingServer: false,
        timeout: 120_000,
    },
});
