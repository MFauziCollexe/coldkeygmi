# Cold Storage History

Cold Storage snapshots are stored once per Jakarta calendar date. Re-running the capture command for the same date replaces that date's snapshot atomically.

## Install

Run these commands from the Laravel project directory. Use the PHP CLI executable installed on the server; it does not need to be part of XAMPP.

On Linux:

```bash
php artisan migrate --force
```

If `php` is not on the command path, replace it with the full path to the server's PHP CLI executable (for example, `/usr/bin/php`).

On Windows, if PHP is installed directly in `C:\php`, run:

```powershell
& 'C:\php\php.exe' artisan migrate --force
```

## Capture a snapshot manually

```bash
php artisan cold-storage:snapshot
```

To recapture a specific date:

```bash
php artisan cold-storage:snapshot --date=2026-10-29
```

## Schedule on Linux

Add a daily cron job to run at midnight Jakarta time. Replace `/path/to/coldkeygmi` with the deployed project directory and `/usr/bin/php` with the server's PHP CLI path if different:

```cron
CRON_TZ=Asia/Jakarta
0 0 * * * cd /path/to/coldkeygmi && /usr/bin/php artisan cold-storage:snapshot --no-interaction >> storage/logs/cold-storage-snapshot.log 2>&1
```

Ensure the cron user can read the application files and write to `storage/logs`. If the hosting provider offers a cron-job form instead of direct crontab access, configure the same command and schedule there.

## Schedule on Windows Server

If the server runs Windows and PHP is installed in `C:\php`, create a daily task in Task Scheduler at 00:00 Jakarta time. Set **Program/script** to `C:\Windows\System32\cmd.exe`. Replace `C:\path\to\coldkeygmi` with the actual project directory in **Add arguments**:

```text
/c "cd /d C:\path\to\coldkeygmi && C:\php\php.exe artisan cold-storage:snapshot --no-interaction >> storage\logs\cold-storage-snapshot.log 2>&1"
```

Set **Start in** to the project directory, and configure the task not to start a second instance if the previous capture is still running. You can also test it manually from that directory with:

```powershell
& 'C:\php\php.exe' artisan cold-storage:snapshot
```

Either approach runs PHP only once per day; neither requires Laravel Scheduler's every-minute trigger.

## Report periods

The Cold Storage page displays daily snapshots for the last 7, 30, or 365 days. Historical data begins with the first successful snapshot; previous Odoo history is not backfilled.
