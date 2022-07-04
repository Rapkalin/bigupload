![node](https://img.shields.io/badge/nodejs-v8.17.0-83CD29.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.0-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/Composer-v2.3.7-644D31.svg?style=flat-square)

# GETTING STARTED

* [Project Installation](#installation)
* [Back-end installation](#back-installation)
* [Front-end installation](#front-installation)


Description
This project has been created for a search and development purpose.
It allows a user to download heavy files such as videos, jpgs, pdf etc. using the Plupload library.

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
   DocumentRoot "${INSTALL_DIR}/your-folder-name/bigupload"

   ServerAlias bigupload.local.*

   <Directory "${INSTALL_DIR}/your-folder-name/bigupload">
     Options Includes FollowSymLinks
     AllowOverride All
   </Directory>
</VirtualHost>

```

## <a name="back-installation"></a> 3/ BACK-END INSTALL
### 3.1- Dependencies installation
- From the root directory (/bigupload), enter the command:
```
compose install or compose i
```
- This command will install the back dependencies linked to the *composer.json*

## <a name="front-installation"></a>4/ FRONT-END INSTALL / BUILD
- Nothing needed for the Front end install
