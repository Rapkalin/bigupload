![node](https://img.shields.io/badge/nodejs-v8.17.0-122D05.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.2-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/Composer-v2.3.7-644D31.svg?style=flat-square)
![symfony](https://img.shields.io/badge/Symfony-v7-122D53.svg?style=flat-square)

# GETTING STARTED

* [Local development with Docker (recommended)](#docker)
* [Project Installation](#installation)
* [Back-end installation](#back-installation)
* [Front-end installation](#front-installation)

#### Description
This project has been created for a search and development purpose.
It allows a user to download heavy files such as videos, jpgs, pdf etc. using the PLupload library.
This is a component oriented project based on Symfony 7 based project.

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
| `Dockerfile` | Multi-stage PHP 8.4-FPM Alpine image: `base` (extensions intl, opcache, pdo_mysql, apcu + Composer) → `development` (source mounted as volume) / `production` (source copied, prod deps, warmed cache) |
| `compose.yaml` | The 3 services (mysql, php, nginx), their network, the `mysql_data` volume and env defaults (overridable via `.env`) |
| `docker/php.ini` | PHP settings: `upload_max_filesize`/`post_max_size` 300M (PLupload sends 250 MB chunks — the 15 GB max file size never travels in a single request), 300s timeouts |
| `docker/opcache.ini` | OPcache enabled with `validate_timestamps=1`/`revalidate_freq=0` so code changes are picked up instantly in dev |
| `docker/nginx.conf` | Symfony front-controller vhost: static files served directly, everything else through `public/index.php`, `client_max_body_size 300M`, direct `.php` execution blocked |

### Useful commands

```bash
docker compose logs -f php          # tail PHP logs
docker compose exec php sh          # shell inside the PHP container
docker compose exec php php bin/console cache:clear
docker compose down                 # stop the stack (add -v to also drop the database)
```

# <a name="installation"></a>PROJECT INSTALLATION
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
compose install or compose i
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
