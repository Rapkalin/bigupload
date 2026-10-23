![node](https://img.shields.io/badge/nodejs-v8.17.0-122D05.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.4-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/Composer-v2.10-644D31.svg?style=flat-square)
![symfony](https://img.shields.io/badge/Symfony-v8.1-122D53.svg?style=flat-square)

# GETTING STARTED

* [Local development with Docker (recommended)](#docker)
* [Project Installation](#installation)
* [Back-end installation](#back-installation)
* [Front-end installation](#front-installation)
* [Uploads](#uploads)
* [Tests](#tests)
* [Versioning](#versioning)
* [Deployment (CI/CD)](#deployment)

#### Description
This project has been created for a search and development purpose.
It allows a user to download heavy files such as videos, jpgs, pdf etc. using the PLupload library.
This is a component oriented project based on Symfony 8 based project.

# <a name="docker"></a>LOCAL DEVELOPMENT WITH DOCKER (RECOMMENDED)

The full stack (Nginx + PHP-FPM 8.4 + MySQL 8) runs in Docker, no MAMP/local PHP needed.

### Quick start

```bash
docker compose up -d --build                                        # build the PHP image and start the stack
docker compose exec php composer install                            # install PHP dependencies
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console importmap:install           # install JS dependencies (Stimulus, Turbo, jQuery...)
```

The app is then available at <http://localhost:8000>.
MySQL is exposed on the host at port **3307** (to avoid conflicts with a local MySQL) — inside the network, containers use `mysql:3306`.

### Architecture

```
Browser ──:8000──> [nginx]  ──fastcgi :9000──> [php-fpm]  ──:3306──> [mysql]
                   serves public/              runs index.php        mysql_data volume
```

### Files

| File | Purpose |
|---|---|
| `Dockerfile` | Multi-stage PHP 8.4-FPM Alpine image: `base` (extensions intl, opcache, pdo_mysql, zip, apcu + Composer) → `development` (source mounted as volume) / `production` (source copied, prod deps, warmed cache) |
| `compose.yaml` | The 3 services (mysql, php, nginx), their network, the `mysql_data` volume and env defaults (overridable via `.env`) |
| `docker/php.ini` | PHP settings: `upload_max_filesize`/`post_max_size` 300M (PLupload sends 250 MB chunks — the 15 GB max file size never travels in a single request), 300s timeouts |
| `docker/opcache.ini` | OPcache enabled with `validate_timestamps=1`/`revalidate_freq=0` so code changes are picked up instantly in dev |
| `docker/nginx.conf` | Symfony front-controller vhost: static files served directly, everything else through `public/index.php`, `client_max_body_size 300M`, direct `.php` execution blocked, `/uploads` not served (same rules as `public/.htaccess` in production) |

### Useful commands

```bash
docker compose logs -f php          # tail PHP logs
docker compose exec php sh          # shell inside the PHP container
docker compose exec php php bin/console cache:clear
docker compose down                 # stop the stack (add -v to also drop the database)
```

# <a name="installation"></a>PROJECT INSTALLATION
### Requirements (without Docker)
- PHP **8.4** (8.4.1 minimum) with the `ctype`, `curl`, `iconv`, `intl`, `pdo_mysql` and `zip` extensions
- Composer 2
- MySQL 8
- The `zip` command line tool (used to build the archives)

### 1/ GET PROJECT FROM GIT

```git
git clone https://github.com/Rapkalin/bigupload.git
git fetch
git checkout master
```

### 2/ VHOST configuration
#### Update your /etc/hosts
Add host on your local OS (On Windows, files is locate to `C:\windows\System32\drivers\etc\`)

```
127.0.0.1   bigupload.local
```

#### Update your /Applications/MAMP/conf/apache/extra/httpd-vhosts.conf
for <http://bigupload.local>

```

<VirtualHost *:80>
   ServerName bigupload.local
   DocumentRoot "${INSTALL_DIR}/your-folder-name/bigupload/public"

   ServerAlias bigupload.local.*

   <Directory "${INSTALL_DIR}/your-folder-name/bigupload/public">
     Options Includes FollowSymLinks
     AllowOverride All
   </Directory>
</VirtualHost>

```

## <a name="back-installation"></a> 3/ BACK-END INSTALL
### 3.1- Dependencies installation
- From the root directory (/bigupload/), enter the command:
- This command will install the back dependencies linked to the *composer.json*
```
composer install
```
- Add your .env file using the .env.example file as a base with at least the below settings:

```
APP_ENV=dev
APP_SECRET=xxx
APP_DEBUG=1

### This is used to set download urls
APP_DOMAINE_LOCAL=https://example.local
APP_DOMAINE_PREPROD=https://preprod.example.com
APP_DOMAINE_PROD=https://example.com

DATABASE_URL="mysql://username:password@127.0.0.1:3306/databaseName?serverVersion=8.0.32&charset=utf8mb4"

### This is used for cron script purpose
DB_HOST=host_example
DB_NAME=name_example
DB_USERNAME=username_example
DB_PASSWORD=password_example
```

## <a name="front-installation"></a>4/ FRONT-END INSTALL / BUILD
- From the root directory (/bigupload/) enter the following commands to build the frontend project:
- asset-map will compile copy and past the files in the assets dir into the public dir.
- importmap will install all js dependencies in assets/vendor

```
php bin/console cache:clear 
php bin/console doctrine:migrations:migrate
php bin/console asset-map:compile
php bin/console importmap:install
```

# <a name="uploads"></a>UPLOADS

All the upload rules (chunk size, max size, allowed extensions, expiration, rate limit) live in **`src/Constant/UploadConfig.php`**:
the back-end, the purge command, the templates and `upload.js` (through data attributes) all read them from there.

- Files are sent by PLupload to `POST /handleFile` in **250 MB chunks**, max file size **15 GB**.
- The first chunk returns a random `upload_id`: the following chunks must send it, so two uploads never share a temp file and nobody can write into somebody else's upload.
  Each chunk is written at its own offset: a chunk retried by PLupload is never duplicated.
- Allowed extensions (case insensitive): `jpg`, `jpeg`, `png`, `gif`, `mp4`, `mov`, `pdf`, `mp3`, `vtt`, `srt`, `zip`.
  Any other file type is rejected with a **415** before anything is written on the disk.
- **20 new uploads per hour and per IP** (429 beyond).
- Once the last chunk is received the file is zipped **without compression** (the allowed files are already compressed) in `public/uploads/<showId>.zip`, saved in database and the download page is available at `/download/<showId>` (link shortened with tinyurl.com, or the direct link if tinyurl is unavailable).
- Files expire after **7 days**: expired files are no longer downloadable. `public/uploads` is never served directly, files are only downloaded through `/downloadFile/<showId>` (resumable downloads supported).
- Refused uploads (warning) and errors are logged in `var/log/upload.log` (one file per day, kept 14 days in prod).

### Purge (cron)
`bin/console app:purge-expired` deletes the expired uploads (file + database row), the zips without database row older than 7 days
and the temporary files (`.part`, interrupted zips) untouched for 5 hours. Run it every hour on the server:

```
0 * * * * /usr/bin/php8.4-cli ~/bigupload/website/bin/console app:purge-expired --no-interaction
```
`scripts/cron/cron.php` is kept so that an existing crontab calling it keeps working (it runs the same command).

# <a name="tests"></a>TESTS

Functional and unit tests (PHPUnit) cover the upload, download, purge, pages and security headers. They need a MySQL user allowed to create the `bigupload_test` database:

```bash
docker compose exec -e APP_ENV=test -e DATABASE_URL="mysql://root:root_secret@mysql:3306/bigupload?serverVersion=8.0.36&charset=utf8mb4" php composer test
```
`composer test` (re)creates the test database, runs the migrations and the tests. In the test env the chunks are 8 bytes and the files 20 bytes max (`config/services.yaml`), and tinyurl.com is mocked.
The tests run in GitHub Actions (`.github/workflows/tests.yml`) on every pull request and before every deployment: no deployment if they fail.

# <a name="versioning"></a>VERSIONING

The project follows [semver](https://semver.org). The version is the `version` field of `composer.json`; it is displayed in the footer of every page.
When releasing, update it with the CHANGELOG: **the production deployment fails if the pushed tag differs from the `composer.json` version**.

# <a name="deployment"></a>DEPLOYMENT (CI/CD)

| Workflow | Trigger | Target |
|---|---|---|
| `.github/workflows/preprod.yml` | push on `develop` | `~/preprod-bigupload/website` |
| `.github/workflows/main.yml` | push of any tag | `~/bigupload/website` (production) |

Each workflow:
0. **tests** — `composer audit`, tag = `composer.json` version (production), PHPUnit with a MySQL service.
1. **build** — installs the prod dependencies with PHP 8.4 (`composer install --no-dev`), checks the application boots in prod (`lint:container`), then zips the release.
2. **deploy** — backs up the current release to `old_website`, rsyncs the new one, links the shared `.env`, `.htaccess`, `.htpasswd` and `uploads`, then runs `cache:clear`, `doctrine:migrations:migrate`, `importmap:install` and `asset:compile`. Any failing command fails the job; output is written to `log_deploy.log` on the server.

**Server requirement:** PHP 8.4 must be available at `/usr/bin/php8.4-cli` and the web server must run PHP 8.4 (set in the IONOS PHP settings of the domain). Composer is pinned to the PHP 8.4 platform (`config.platform.php` in `composer.json`) so `composer update` never installs packages the server cannot run.

### Releasing a new version
```bash
git checkout master && git merge develop
# update CHANGELOG.md and the "version" of composer.json (then `composer update --lock`)
git tag x.y.z && git push origin master --tags   # the tag triggers the production deployment
```
