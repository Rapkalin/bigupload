## <a name="changelog"></a> CHANGELOG && COMING RELEASES

* [1.1 - Changelog history](#changelog)
* [1.2 - Next release to come](#next-release)
* [1.3 - backlog](#backlog)

### <a name="changelog"></a> >>> 1.1 - Change log
#### => Tag 1.6.0 (current) / 2026.10.23
- [Evol][BIGUPL-6] - Semver version (composer.json) displayed in the footer of every page, deployment refused if the tag differs from it
- [Evol][BIGUPL-6] - Non-regression tests (PHPUnit, 65 tests) run in CI on every pull request and before every deployment, with `composer audit`
- [Evol][BIGUPL-6] - Purge moved to the `app:purge-expired` command (based on the expiration date in database), `scripts/cron/cron.php` kept as an alias
- [Fix][BIGUPL-6] - Purge deleting files uploaded at the end of a month after one day (dates compared as text)
- [Fix][BIGUPL-6] - Icons not displayed (Font Awesome kit refused with a 403): icons now served by the site
- [Fix][BIGUPL-6] - Upload errors never shown to the user nor logged in production
- [Fix][BIGUPL-6] - A chunk retried by the browser was written twice in the file
- [Security][BIGUPL-6] - Uploads in progress could be read and modified by anybody knowing the file name; two uploads with the same name were mixed
- [Security][BIGUPL-6] - Uploaded files no longer served directly from /uploads, expired files no longer downloadable
- [Security][BIGUPL-6] - Unguessable download ids (random 128 bits instead of a time based id)
- [Security][BIGUPL-6] - Content-Security-Policy now sent in production, HTTPS forced with HSTS
- [Security][BIGUPL-6] - Rate limit of 20 new uploads per hour and per IP, 15 GB maximum size checked by the server
- [Security][BIGUPL-6] - Removal of unused Flash/Silverlight files (Moxie.swf, Moxie.xap), jQuery and third-party Font Awesome script
- [Perf][BIGUPL-6] - Zip without compression: 1 GB video zipped in 5.8 s instead of 21 s (measured locally), the biggest files no longer exceed the 5 minutes limit
- [Perf][BIGUPL-6] - Compression (gzip) and long term browser cache of the assets, jQuery removed (-285 KB), PLupload (126 KB) only loaded on the upload page
- [Perf][BIGUPL-6] - Resumable downloads (HTTP Range), database indexes on show_id and expiration_date

#### => Tag 1.5.2 / 2026.10.23
- [Fix][BIGUPL-5] - Run on PHP 8.4 (the version used by the production web server): PHP >= 8.4 with Composer platform pinned to 8.4, CI/CD and Docker image back on PHP 8.4

#### => Tag 1.5.1 / 2026.10.23
- [Fix][BIGUPL-5] - CI/CD: show the server command error in GitHub Actions when the deployment fails

#### => Tag 1.5.0 / 2026.10.23
- [Evol][BIGUPL-5] - Upgrade PHP to 8.5 and Symfony to 8.1
- [Evol][BIGUPL-5] - Upgrade all dependencies (Doctrine DBAL 4 / DoctrineBundle 3 / Migrations 4, PHPUnit 13, Stimulus & Turbo bundles 3, Turbo JS 8)
- [Evol][BIGUPL-5] - CI/CD: upgrade GitHub actions, Composer cache, prod boot check before deployment, deployment fails on server command errors
- [Evol][BIGUPL-5] - Docker image on PHP 8.5 (OPcache now built into PHP)
- [Fix][BIGUPL-5] - Uploads failing (500) when tinyurl.com is unavailable
- [Fix][BIGUPL-5] - 404 pages returning a 500
- [Fix][BIGUPL-5] - Download of an unknown or deleted file returning a 500 instead of a 404
- [Fix][BIGUPL-5] - Uppercase extensions (e.g. .JPG) uploaded but never saved
- [Fix][BIGUPL-5] - Possible infinite loop while cleaning temporary files
- [Security][BIGUPL-5] - Forbidden file types rejected (415) before being written on the server
- [Security][BIGUPL-5] - Prevent path traversal through the uploaded file name
- [Security][BIGUPL-5] - Fix reflected XSS on the 404 page

#### => Tag 1.3.5 / 2026.06.09
- [Fix] BIGUPL-4 - Fix CI/CD download/artifact version

#### => Tag 1.3.4 / 2026.06.09
- [Fix] BIGUPL-3 - Fix download button

#### => Tag 1.3.3 / 2026.06.09
- [Evol][BIGUPL-2] - Add local Docker configuration && upgrade symfony

#### Tag 1.3.2 / 2025.01.04
- [Evol] Add a message on the download page: Want to send a file? Clic here
- [Evol] Improve SEO
- [Evol] Add robots.txt and sitemap.xml
- [Evol] Add animation on download button
- [Evol] Improve JS performance
- [Evol] Improve accessibility
- [Evol] Translate french texts to english

#### Tag 1.3.1 / 2025.01.04
- [Bug] Cron fix old directory not deleted

#### Tag 1.3.0 / 2025.01.03
- [Changelog] Update Changelog
- [Bug] Fix Zip large file not working > 2Go
- [Bug] Fix download large file not working > 2Go
- [Evol] Add Debug logging logic in dev & prod mode
- [Evol] Improve the UI of the "Upload file" step
- [Evol] Improve homepage display performances

#### Tag 1.2.1 / 2024.12.17
- [Changelog] Update Changelog

#### Tag 1.2.0 / 2024.12.17
- [Evol] Add folders
- [Security] Add file creation with restricted permissions
- [Evol] Add a file size limit of 15GB
- [Evol] Add a favicon
- [Evol] Cron => Add deletion of files in the database that were removed
- [Evol] Cron => Add deletion of files that are not .zip and older than one hour
- [Evol] Add a counter on the front-end + a new table in the database to record the counter
- [Evol] 404 page template
- [Evol] 404 redirection if the link does not exist
- [Evol] 500 page template
- [Evol] Add logs in dev mode
- [Evol] Add a change log

#### Tag 1.1.3 / 2024.12.08
- [Evol] Add a small animation when clicking on the "copy the link" button

#### Tag 1.1.2 / 2024.11.12
- [Bug] Android => Downloaded file not working
- [Bug] iPhone => "Copy the link" button  not working

#### Tag 1.1.1 / 2024.11.12
- [Bug] Minor bug fixes

#### Tag 1.1.0 / 2024.11.12
- [Evol] Add database + create entities && migrations
- [Evol] Refacto css
- [Evol] Move current templates structure to a componant oriented project
- [Evol] Rename DownloadController to ItemController + refacto

#### Tag 1.0.2 / 2024.11.02
- [Evol] Cron refacto for debug log

#### Tag 1.0.1 / 2024.10.31
- [Evol] Add Cron to clean server with old files

#### Tag 1.0.0 / 2024.10.27
- [Evol] Project structure migration from PHP native to Symfony 7

#### Tag 0.0.6 / 2024.04.18
- [Evol] Minor display updates

#### Tag 0.0.5.1 / 2024.04.18
- [Evol] Update Github actions

#### Tag 0.0.5 / 2024.04.18
- [Evol] Minor Javascript refacto

#### Tag 0.0.4 / 2024.04.17
- [Evol] Responsive CSS refacto

#### Tag 0.0.3.1 / 2024.04.15
- [Evol] Add reload page for new file upload

#### Tag 0.0.3 / 2024.04.15
- [Evol] Refacto copy the link button

#### Tag 0.0.2 / 2024.04.15
- [Evol] Add copy the link button

#### Tag 0.0.1 / 2023.09.23
- [Evol] First tag && Add github actiond

### <a name="next-release"></a> >>> 1.2- Next release to come
#### => Tag 1.7.0
- [Evol][BIGUPL-5] - Add new tinyurl homemade mapper

### <a name="backlog"></a> >>> 1.3- Backlog
- [Evol] Review UI for uploaded file counter
- [Evol] Review UI for upload progress bar
- [Evol] UX/UI changes to pages => HomePage, Upload, Download
- [Evol] Add unit tests
- [Evol] Add observability for debugging production errors => create a dedicated mailbox
- [Evol] Add Tagging plan / Size attributes / schema.org
- [Evol] Enable uploading multiple files simultaneously
- [Evol] Add error message in case something goes wrong (500 then 403)
