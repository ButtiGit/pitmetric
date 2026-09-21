import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

export default async function globalSetup() {
    const databasePath = path.resolve(process.cwd(), 'database/e2e.sqlite');

    fs.rmSync(databasePath, { force: true });
    fs.writeFileSync(databasePath, '');

    const env = {
        ...process.env,
        APP_ENV: 'testing',
        APP_URL: 'http://127.0.0.1:8000',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: databasePath,
        CACHE_STORE: 'array',
        SESSION_DRIVER: 'file',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
    };

    execFileSync('php', ['artisan', 'migrate:fresh', '--force', '--no-interaction'], {
        cwd: process.cwd(),
        env,
        stdio: 'inherit',
    });

    execFileSync('php', ['artisan', 'db:seed', '--class=E2ESeeder', '--force', '--no-interaction'], {
        cwd: process.cwd(),
        env,
        stdio: 'inherit',
    });
}
