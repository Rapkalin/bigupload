![node](https://img.shields.io/badge/nodejs-v8.17.0-122D05.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.2-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/Composer-v2.3.7-644D31.svg?style=flat-square)
![symfony](https://img.shields.io/badge/Symfony-v7-122D53.svg?style=flat-square)

# GETTING STARTED

* [Project Installation](#installation)
* [Back-end installation](#back-installation)
* [Front-end installation](#front-installation)
* [Backlog](#backlog)

#### Description
This project has been created for a search and development purpose.
It allows a user to download heavy files such as videos, jpgs, pdf etc. using the PLupload library.
This is a component oriented project based on Symfony 7 based project.

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
APP_DOMAINE_LOCAL=https://example.local
APP_DOMAINE_PREPROD=https://preprod.example.com
APP_DOMAINE_PROD=https://example.com

DATABASE_URL="mysql://username:password@127.0.0.1:3306/databaseName?serverVersion=8.0.32&charset=utf8mb4"
```

## <a name="front-installation"></a>4/ FRONT-END INSTALL / BUILD
- From the root directory (/bigupload/) enter the following commands to build the frontend project:
- asset-map will compile copy and past the files in the assets dir into the public dir.
- importmap will install all js dependencies in assets/vendor

```
php bin/console cache:clear 
php bin/console asset-map:compile
php bin/console importmap:install
```

## <a name="backlog"></a> 5/ BACKLOG
### 5.1- Next tag to come => Tag 1.2.0
- [Evol] Downloaded files are placed into folders and then zipped => DONE
- [Evol] [Security] All files/folders are created with restricted permissions => DONE
- [Evol] Add a file size limit of 15GB => DONE
- [Evol] Add a favicon => DONE
- [Evol] Cron => Add deletion of removed files from the database (BDD)
- [Evol] Add a front-end counter for the number of uploaded files
- [Evol] Improve display performance (loading issue with JavaScript?)
- [Evol] Add logs during uploads when not in prod => DONE

### 5.2- Backlog
- [Evol] UX/UI corrections: update "bigupload" text, tweak the download page, etc.
- [Evol] Improve the "Upload a new file" process => change the button after clicking?
- [Evol] Add unit tests
- [Evol] Add observability for debugging production errors => create a dedicated mailbox
- [Evol] Add SEO / Tagging plan / Size attributes / schema.org
- [Evol] Enable uploading multiple files simultaneously
- [Evol] UX/UI changes to pages => HomePage, Upload, Download
