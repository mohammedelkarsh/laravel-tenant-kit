/**
 * Capture README screenshots and demo GIF frames.
 * Run: node scripts/capture-media.mjs
 * Requires: npx playwright install chromium (first run)
 */
import { chromium } from 'playwright';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const outDir = path.join(root, 'docs', 'screenshots');
const framesDir = path.join(outDir, 'gif-frames');

const centralHost = 'laravel-tenant-kit.test';
const demoHost = 'demo.laravel-tenant-kit.test';
const port = process.env.CAPTURE_PORT || '8080';
const centralBase = `http://${centralHost}:${port}`;
const demoBase = `http://${demoHost}:${port}`;

const view = { width: 1440, height: 900 };

function ensureDirs() {
    fs.mkdirSync(outDir, { recursive: true });
    fs.mkdirSync(framesDir, { recursive: true });
    for (const f of fs.readdirSync(framesDir)) {
        if (f.endsWith('.png')) fs.unlinkSync(path.join(framesDir, f));
    }
}

async function shot(page, file, fullPage = false) {
    await page.screenshot({ path: path.join(outDir, file), fullPage, animations: 'disabled' });
    console.log('  saved', file);
}

async function frame(page, name, fullPage = false) {
    const file = path.join(framesDir, `${name}.png`);
    await page.screenshot({ path: file, fullPage, animations: 'disabled' });
    console.log('  frame', name);
}

async function loginCentral(page) {
    await page.goto(`${centralBase}/login`, { waitUntil: 'networkidle' });
    const email = page.locator('input[name="email"]');
    if ((await email.count()) === 0) {
        return;
    }
    await email.fill('admin@laravel-tenant-kit.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(dashboard|admin)/, { timeout: 30000 });
}

async function loginTenant(page) {
    await page.goto(`${demoBase}/login`, { waitUntil: 'networkidle' });
    const email = page.locator('input[name="email"]');
    if ((await email.count()) === 0) {
        return;
    }
    await email.fill('demo@demo.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/dashboard/, { timeout: 30000 });
}

async function main() {
    ensureDirs();

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: view,
        deviceScaleFactor: 1,
    });
    const page = await context.newPage();

    console.log('Capturing GIF frames...');
    await page.goto(`${centralBase}/`, { waitUntil: 'networkidle' });
    await frame(page, '01-landing');

    await page.goto(`${centralBase}/workspaces/create`, { waitUntil: 'networkidle' });
    await frame(page, '02-signup');

    await page.goto(`${demoBase}/login`, { waitUntil: 'networkidle' });
    await frame(page, '03-tenant-login');

    await loginTenant(page);
    await page.goto(`${demoBase}/dashboard`, { waitUntil: 'networkidle' });
    await frame(page, '04-tenant-dashboard');

    await page.goto(`${demoBase}/team`, { waitUntil: 'networkidle' });
    await frame(page, '05-team');

    await loginCentral(page);
    await page.goto(`${centralBase}/admin`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await frame(page, '06-admin');

    console.log('Capturing static screenshots...');
    await page.goto(`${centralBase}/`, { waitUntil: 'networkidle' });
    await shot(page, 'landing.png');

    await page.goto(`${centralBase}/admin`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await shot(page, 'admin-panel.png');

    await page.goto(`${demoBase}/dashboard`, { waitUntil: 'networkidle' });
    await shot(page, 'tenant-dashboard.png');

    await page.goto(`${centralBase}/login`, { waitUntil: 'networkidle' });
    await loginCentral(page);
    await page.goto(`${centralBase}/billing/demo`, { waitUntil: 'networkidle' });
    await shot(page, 'billing.png');

    await page.goto(`${demoBase}/login`, { waitUntil: 'networkidle' });
    await loginTenant(page);
    await page.goto(`${demoBase}/team`, { waitUntil: 'networkidle' });
    await shot(page, 'team-management.png');

    await browser.close();

    const frames = fs.readdirSync(framesDir).filter((f) => f.endsWith('.png')).sort();
    if (frames.length === 0) {
        throw new Error('No GIF frames captured');
    }

    const listFile = path.join(framesDir, 'frames.txt');
    const listLines = [];
    for (const f of frames) {
        listLines.push(`file '${f.replace(/'/g, "'\\''")}'`);
        listLines.push('duration 2.5');
    }
    listLines.push(`file '${frames[frames.length - 1].replace(/'/g, "'\\''")}'`);
    fs.writeFileSync(listFile, listLines.join('\n'));

    const gifPath = path.join(outDir, 'demo.gif');
    console.log('Building demo.gif with ffmpeg...');
    execSync(
        `ffmpeg -y -f concat -safe 0 -i "${listFile}" -vf "scale=1200:-1:flags=lanczos,split[s0][s1];[s0]palettegen=stats_mode=diff[p];[s1][p]paletteuse=dither=bayer:bayer_scale=3" -loop 0 "${gifPath}"`,
        { cwd: framesDir, stdio: 'inherit' },
    );

    console.log('Done.');
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
