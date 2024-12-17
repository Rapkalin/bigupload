## <a name="changelog"></a> CHANGELOG && COMING RELEASES
### 1.1 - Change log
#### Tag 1.2.0 (current) / 2024.12.17
- [Evol] Add folders
- [Evol] [Security] Add file creation with restricted permissions
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

### 1.2- Next release to come => Tag 1.2.0
- [Evol] Downloaded files are placed into folders and then zipped => DONE
- [Evol] [Security] All files/folders are created with restricted permissions => DONE
- [Evol] Add a file size limit of 15GB => DONE
- [Evol] Add a favicon => DONE
- [Evol] Cron => Add deletion of removed files from the database (BDD) => DONE
- [Evol] Add a front-end counter for the number of uploaded files
- [Evol] Improve display performance (loading issue with JavaScript?)
- [Evol] Add logs during uploads when not in prod => DONE

### 1.3- Backlog
- [Evol] UX/UI corrections: update "bigupload" text, tweak the download page, etc.
- [Evol] Improve the "Upload a new file" process => change the button after clicking?
- [Evol] Add unit tests
- [Evol] Add observability for debugging production errors => create a dedicated mailbox
- [Evol] Add SEO / Tagging plan / Size attributes / schema.org
- [Evol] Enable uploading multiple files simultaneously
- [Evol] UX/UI changes to pages => HomePage, Upload, Download