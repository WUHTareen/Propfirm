# Non-root deploy user — one-time setup

Run these **once** on the droplet, logged in as `root` (DigitalOcean console is
fine). After this, you never run Composer or deploys as root again — which
removes the "Do not run Composer as root" warning and lets Composer plugins
(e.g. Filament's asset publisher) run normally.

The goal:

- A `deploy` user owns the code and runs `git pull`, `composer`, `artisan`.
- `php-fpm` keeps running as `www-data`.
- Both can write to `storage/` and `bootstrap/cache/` via the shared
  `www-data` **group** (setgid keeps new files in that group).
- `deploy` gets passwordless `sudo` for **only** the supervisor restart command.

Project dir is assumed to be `/var/www/propfirm-platform` — adjust if different.

---

## 1. Create the user

```bash
# Create the user with a home dir and bash shell.
adduser --gecos "" deploy          # set a password when prompted (or use --disabled-password and SSH keys only)

# Let deploy share the web-server group so it can write shared files.
usermod -aG www-data deploy
```

## 2. Hand the code over to deploy

```bash
cd /var/www/propfirm-platform

# Own the tree as deploy, group www-data.
chown -R deploy:www-data /var/www/propfirm-platform

# setgid on directories → every new file/dir inherits the www-data group.
find /var/www/propfirm-platform -type d -exec chmod 2775 {} \;
find /var/www/propfirm-platform -type f -exec chmod 664 {} \;

# deploy.sh stays executable.
chmod 775 /var/www/propfirm-platform/deploy.sh

# storage + cache must be group-writable (php-fpm writes logs/uploads here).
chmod -R 2775 storage bootstrap/cache
```

> A default `umask 002` is set for the deploy user by `deploy.sh` itself, so
> files it creates stay group-writable. Nothing else to configure.

## 3. Passwordless sudo for the worker restart (only)

```bash
cp /var/www/propfirm-platform/ops/propfirm-deploy.sudoers /etc/sudoers.d/propfirm-deploy
chmod 440 /etc/sudoers.d/propfirm-deploy
visudo -cf /etc/sudoers.d/propfirm-deploy      # must print: parsed OK
```

## 4. SSH access for deploy (optional but recommended)

So you can log in directly as `deploy` instead of `root`:

```bash
# As root, seed deploy's authorized_keys from your existing key.
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys   # or paste your public key
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

## 5. Move the scheduler cron to deploy

```bash
# Remove the root crontab entry if you added one earlier:
crontab -e          # (as root) delete the schedule:run line

# Add it for deploy instead:
sudo -u deploy crontab -e
```

Add:

```cron
* * * * * cd /var/www/propfirm-platform && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Point Supervisor's worker at the deploy user

Edit `/etc/supervisor/conf.d/propfirm-worker.conf` and make sure it has:

```ini
user=deploy
```

(The template in `ops/propfirm-worker.conf` already uses `user=deploy`.) Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart propfirm-worker:*
```

## 7. From now on, deploy as `deploy` — never root

```bash
ssh deploy@YOUR_DROPLET_IP        # or: su - deploy
cd /var/www/propfirm-platform
./deploy.sh
```

No more root warning, and Filament assets publish correctly.

---

## If something can't write (permission denied)

Almost always a file got created by the wrong user. Fix ownership/group:

```bash
sudo chown -R deploy:www-data /var/www/propfirm-platform/storage /var/www/propfirm-platform/bootstrap/cache
sudo chmod -R 2775 /var/www/propfirm-platform/storage /var/www/propfirm-platform/bootstrap/cache
```
