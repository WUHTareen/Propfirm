# Server / ops notes

One-time setup on the DigitalOcean droplet (`/var/www/propfirm-platform`).

## 1. Queue worker (Supervisor)

```bash
sudo cp ops/propfirm-worker.conf /etc/supervisor/conf.d/propfirm-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start propfirm-worker:*
```

## 2. Laravel scheduler (cron)

Add for the deploy user (`crontab -e`):

```cron
* * * * * cd /var/www/propfirm-platform && php artisan schedule:run >> /dev/null 2>&1
```

## 3. Deploys

After the first setup, every deploy is just:

```bash
cd /var/www/propfirm-platform
./deploy.sh
```

## 4. Still to do (from project notes)

- [ ] SSH key auth from local machine
- [ ] Domain + SSL via certbot (`certbot --nginx -d domain.com`)
- [ ] UptimeRobot monitoring
- [ ] Business email (Hostinger / Google Workspace) — do NOT run a mail server here
- [ ] Non-root deploy user
- [ ] Resize droplet to 2 GB before production; 4 GB when MT5 automation lands
- [ ] Configure S3-compatible storage for KYC docs / certificates
- [ ] Automated off-site DB backups (spatie/laravel-backup)

## Portability principles

1. All code in Git — never edit files directly on the server.
2. Never store uploads on local disk — use S3-compatible storage.
3. Automated off-site DB backups.
4. Keep a safe copy of `.env` (it is never committed to Git).
