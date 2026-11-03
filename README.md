# sevenx_dse: 7x Database Source Editor for Exponential

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-8892BF.png?logo=php&logoColor=white)](https://php.net)
[![Exponential](https://img.shields.io/badge/Exponential-6.x-orange.png)](https://github.com/se7enxweb/exponential)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2%2B-blue.png)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
[![GitHub issues](https://img.shields.io/badge/issues-GitHub-blue.png)](https://github.com/se7enxweb/sevenx_dse/issues)

**sevenx_dse** puts a full database editor into the Exponential administration. It embeds
[AdminNeo](https://www.adminneo.org/) (a maintained fork of Adminer) as a module of its own, behind the
administration's login and role policies, so an administrator can browse and edit tables, run SQL, look at the
structure and export or import data without a separate tool, an SSH session or a second login page on the web
server.

It is meant for site administrators and developers who already have full control of an installation and need direct
access to its database now and then: checking a row, repairing data after a failed import, exporting a table,
running a one-off query. It is not a tool for editors, and it bypasses every check the content model makes.

> **Warning.** The editor works on the live data directly. A dropped table or a deleted row is gone at once. Take a
> backup before you change anything, and give the policy only to people you would also give the database password.

## Table of contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage](#usage)
6. [Permissions](#permissions)
7. [Translations](#translations)
8. [Rebuilding the AdminNeo assets](#rebuilding-the-adminneo-assets)
9. [Upgrading](#upgrading)
10. [Known limits](#known-limits)
11. [Troubleshooting](#troubleshooting)
12. [Contributing](#contributing)
13. [License](#license)
14. [Credits and history](#credits-and-history)

## Features

- **In the Setup menu, and optionally a tab of its own.** A "DS Editor" link in the Setup menu opens
  `dse/dashboard`. The extension also defines a "DS Editor" admin tab with the `brush` icon, which you can switch on
  (see [Configuration](#the-admin-tab)).
- **A dashboard before anything connects.** Opening the editor from the menu always shows a dashboard first: what
  the editor does, a warning, the site's database (type, name, number of tables and how many of them belong to
  Exponential, server version, AdminNeo version; never any credentials), what can be done with it and how it is kept
  safe. Only the acknowledgement opens AdminNeo.
- **The full AdminNeo editor, inside the administration.** Browse, filter, sort and page through rows; edit, add and
  delete rows; run any SQL statement with syntax highlighting; look at columns, indexes, foreign keys, views,
  triggers and a schema diagram; create and alter tables.
- **Export and import.** Tables or the whole database as SQL, CSV or TSV, sent straight to the browser as a file
  download (not wrapped in the administration's page); import of an uploaded SQL file.
- **Every AdminNeo driver.** The site's own database is preselected; you can sign in to another server or open an
  SQLite file. The drivers shipped are MySQL/MariaDB, PostgreSQL, SQLite, MS SQL, Oracle, MongoDB, Elasticsearch,
  ClickHouse and SimpleDB (each needs its PHP extension, see `suggest` in `composer.json`).
- **Safe defaults.** The site's database password is not written into the login form unless you enable it on
  purpose. SQLite files are accepted only by a relative path inside the site directory. The "Change database" link
  carries a per-session token.
- **Follows the admin4 design.** AdminNeo's stylesheets are scoped to the editor (`.a4-dse-editor`), so they never
  touch the administration's menus and columns, and its colors come from the administration's tokens, so the editor
  follows light and dark mode. No inline CSS.
- **Translated.** The extension's own texts in 17 languages; AdminNeo uses the administration's language when the
  browser sends none.
- **Works under PHP-FPM and Exponential Velocity.** AdminNeo can run only once per PHP process; under Velocity's
  persistent workers the view asks for its worker to be replaced after it answers.

## Requirements

- Exponential 6.x, 6.0.15 or later. The views extend the kernel's `Exponential\Runnable\ModuleView`, which was added
  in the 6.0.15 line, and the dashboard uses the admin4 design's components and tokens.
- PHP 8.0 or later (used up to PHP 8.5), with `ext-json` and the PHP extension of each database driver you want to
  use (`mysqli` for MySQL/MariaDB, `pgsql` or `pdo_pgsql` for PostgreSQL, `sqlite3` or `pdo_sqlite` for SQLite ...).
- An administrator account whose role has the `dse` policy (see [Permissions](#permissions)).

## Installation

### With Composer

From the root of your Exponential installation:

```bash
composer require se7enxweb/sevenx_dse
```

The package type is `ezpublish-legacy-extension`, so the Exponential legacy installer places it in
`extension/sevenx_dse`. Then continue with step 2 below.

### By hand

1. Download the latest release from <https://github.com/se7enxweb/sevenx_dse/releases> (or clone the repository)
   into `extension/sevenx_dse` of your installation:

   ```bash
   cd /path/to/exponential
   git clone https://github.com/se7enxweb/sevenx_dse.git extension/sevenx_dse
   ```

2. Activate the extension in `settings/override/site.ini.append.php`:

   ```ini
   [ExtensionSettings]
   ActiveExtensions[]=sevenx_dse
   ```

3. Regenerate the extension autoloads, so the view classes are found:

   ```bash
   php bin/php/ezpgenerateautoloads.php -e
   ```

4. Clear the caches (all of them is simplest):

   ```bash
   php bin/php/ezcache.php --clear-all
   ```

   With PHP-FPM and OPcache, reload PHP-FPM as well; under Exponential Velocity restart it
   (`./console exp:velocity restart`), because its workers keep the classes they loaded at start-up.

5. Give the `dse` policy to the roles of the administrators who may use it (see [Permissions](#permissions)). The
   built-in Administrator role, with access to every module, already has it.

6. Sign in to the administration and open **Setup > DS Editor**.

## Configuration

The extension works without any configuration. It reads these settings:

### `dse.ini` (its own)

```ini
[DSESettings]
# Fill the password field of AdminNeo's login form with the site database
# password from site.ini [DatabaseSettings] Password.
# enabled  - the form is filled in (anyone who can open the page can read the
#            password in its source; for trusted development installations only)
# disabled - (default) AdminNeo asks for the password itself
InsecureUse=disabled

# SQLite: how many seconds the row counts of the database page may be shown as
# approximate ("~", with the time they were counted and a link that counts them
# again) after the database has changed. While nothing has been written since,
# the counts are shown as exact. 0 - always count again after a change.
RowCountCacheTTL=120
```

Override it in `settings/override/dse.ini.append.php` or in a siteaccess's settings. When it is enabled, the
dashboard shows a warning.

### `site.ini` (the kernel's, read only)

`[DatabaseSettings] Server`, `Port`, `Database`, `User` and `DatabaseImplementation` describe the site's database:
it is preselected in AdminNeo's login form and described on the dashboard. `Password` is used only for the login
form pre-fill above and for AdminNeo's SQLite password check; it is never handed to the dashboard template.

### The admin tab

The extension defines the tab in `menu.ini [Topmenu_dse]`, with `Icon=brush` (admin4 draws the icon before the tab's
name), but does not add it to the header by itself. To show it, add it to the tab list in
`settings/override/menu.ini.append.php` (or your admin siteaccess's `menu.ini.append.php`):

```ini
[TopAdminMenu]
Tabs[]=dse
```

The tab is shown to users whose role has `dse/dse` (`PolicyList[]=dse/dse`). An extension's tab can be left out
again with `HiddenTabs[]=dse` in the same group.

### Settings the extension adds

These come with the extension and normally need no change:

| File | Setting | Purpose |
|---|---|---|
| `menu.ini` | `[Topmenu_dse]` | The "DS Editor" tab: `Icon=brush`, `URL[default]=dse/dashboard`, `PolicyList[]=dse/dse` |
| `menu.ini` | `[Leftmenu_setup] Links[dse]` | The "DS Editor" link in the Setup menu |
| `menu.ini` | `[NavigationPart] Part[dsenavigationpart]` | The tab's navigation part |
| `module.ini` | `[ModuleSettings] ModuleList[]=dse` | The `dse` module |
| `design.ini` | `[StylesheetSettings] BackendCSSFileList[]` | `adminneo/adminneo-embedded.css` and `dse.css`, packed with the other admin stylesheets |
| `site.ini` | `[ContentSecurityPolicy] ReportOnlyViews[]=dse/adminneo` | `dse/adminneo` sends AdminNeo's own Content-Security-Policy (with the nonce of its inline scripts), so the kernel's policy for that view is report-only |
| `site.ini` | `[RegionalSettings] TranslationExtensions[]` | The extension's translations |

## Usage

1. Open **Setup > DS Editor** (or the **DS Editor** tab with the brush icon, when you switched it on). The address
   is `/dse/dashboard` in the admin siteaccess.
2. Read the dashboard. It shows the database of the site and the warning. Make a backup if you have not.
3. Confirm with the acknowledgement button. AdminNeo opens inside the administration with the site's database
   preselected; sign in with the database password (it is filled in only when `InsecureUse=enabled`).
4. Work in AdminNeo:
   - pick a table in the left panel to browse its rows; use **Select** to filter and sort, **Edit** to change a row,
     **New item** to add one;
   - **SQL command** runs a statement, for example
     `SELECT node_id, path_identification_string FROM ezcontentobject_tree WHERE depth = 2;`
   - **Export** downloads tables or the whole database (SQL, CSV or TSV); **Import** runs an uploaded SQL file;
   - **Database schema** draws the tables and their foreign keys.
5. To open another database, use **Change database** at the foot of AdminNeo's left panel. It opens the database
   chooser straight away (the warning is not asked again in the same session): **Open the database of this site**
   with one click, the logins remembered in this session (the current connection is marked "Connected now"), and
   AdminNeo's login form for another server or an SQLite file. An SQLite file is given as a path relative to the site
   directory, for example `var/storage/sqlite3/<name>.db`.
6. **Logout** in AdminNeo forgets the database logins of your session and lands on the database chooser.

### Views

| View | What it does |
|---|---|
| `dse/dashboard` | The dashboard, then AdminNeo inside the administration's page layout. File downloads (exports) and redirects are passed straight through. |
| `dse/adminneo` | AdminNeo's own complete page, without the administration's page layout. |

Both views need the `dse` function of the `dse` module. The extension has no template operators, fetch functions,
cronjobs or command line scripts of its own.

## Permissions

The `dse` module has two policy functions (`modules/dse/module.php`):

| Function | Used for |
|---|---|
| `dse` | Required by both views and by the admin tab. Give this one. |
| `dump` | Declared, but no view checks it yet. |

To give access to a role: **Users > Roles and policies**, edit the role, **New policy**, module `dse`, function
`dse` (or all functions), save. Only give it to people who may also have the database password: the editor can
read and change every table, including user accounts and password hashes.

## Translations

The extension's texts (dashboard, links, tab) are translated in `translations/<locale>/translation.ts` for
cro-HR, cze-CZ, dan-DK, dut-NL, eng-US, esl-ES, esl-MX, ger-DE, heb-IL, ita-IT, lat-VA, nor-NO, pol-PL, por-BR,
swe-SE, tlh-QO and tur-TR (context `extension/sevenx_dse`). AdminNeo's own interface uses AdminNeo's translations in
`adminneo/translations`.

## Rebuilding the AdminNeo assets

AdminNeo's stylesheets, scripts and images are served as static files from `design/standard/`. After changing
AdminNeo's sources, rebuild them:

```bash
php extension/sevenx_dse/adminneo/build-static-assets.php   # CSS, JS and images into design/standard/*/adminneo/
cd extension/sevenx_dse && php adminneo/build-embedded-css.php   # adminneo-embedded.css, scoped to .a4-dse-editor
```

Then clear the packed stylesheets and the template blocks
(`php bin/php/ezcache.php --clear-id=ezjscore-packer,template-block`) and reload the page without the browser cache.

## Upgrading

- **With Composer:** `composer update se7enxweb/sevenx_dse`, then clear the caches.
- **By hand:** replace `extension/sevenx_dse` with the new release (keep your own settings in `settings/override`,
  never in the extension), regenerate the autoloads (`php bin/php/ezpgenerateautoloads.php -e`) and clear the caches.
- **From 1.1.14 or earlier:** the tab now checks `dse/dse` instead of `dse/dashboard` (a function the module never
  had), so roles limited to `dse/dse` see it too. If you copied `[Topmenu_dse]` into an override, update its
  `PolicyList[]` there as well.

See [CHANGELOG.md](CHANGELOG.md) for what changed in each release.

## Known limits

- AdminNeo can run only once per PHP process. Under Exponential Velocity each request to the editor retires its
  worker after the answer, so every editor request costs a worker start there.
- There is no undo: the editor does not replace backups or migrations.
- Drivers other than MySQL/MariaDB, PostgreSQL and SQLite come from AdminNeo unchanged and are rarely used with
  this extension.
- The dashboard's figures (tables, server version) need the site's own database connection; when it fails, the
  dashboard still opens without them.

## Troubleshooting

- **No "DS Editor" in the Setup menu.** Check that `sevenx_dse` is in `ActiveExtensions[]` and that your role has
  `dse/dse` (or all `dse` functions), then clear the INI cache (`php bin/php/ezcache.php --clear-tag=ini`).
- **No "DS Editor" tab.** The tab is off by default: add `Tabs[]=dse` (see [The admin tab](#the-admin-tab)).
- **The tab has no icon.** The icon is drawn by the admin4 design; other admin designs show the name only.
- **"Module not found" for `/dse/dashboard`.** The module list is cached: clear the caches and regenerate the
  autoloads.
- **The editor is unstyled or overlaps the menus.** The packed stylesheets are stale: clear `ezjscore-packer` and
  `template-block` and reload without the browser cache.
- **AdminNeo's scripts are blocked (Content-Security-Policy errors on `dse/adminneo`).** Make sure
  `site.ini [ContentSecurityPolicy] ReportOnlyViews[]` still contains `dse/adminneo` after your overrides.

## Contributing

Bug reports and pull requests are welcome:

1. Open an issue at <https://github.com/se7enxweb/sevenx_dse/issues> describing the problem or the idea.
2. Fork the repository, create a branch (`git checkout -b fix/my-change`) and make your change.
3. Check every PHP file you touched with `php -l`, keep CSS in `design/standard/stylesheets/` (no inline CSS in
   templates) and add new texts to the translations.
4. Open a pull request against `main`.

Please report security issues privately to [security@exponential.earth](mailto:security@exponential.earth) instead
of opening an issue.

## License

sevenx_dse is free software, licensed under the GNU General Public License, version 2 or (at your option) any later
version. See [LICENSE](LICENSE) and [COPYRIGHT.md](COPYRIGHT.md).

AdminNeo, included in `adminneo/`, is copyright 2007 - 2025 Jakub Vrána and 2024 - 2025 Peter Knut, and is licensed
under the Apache License 2.0 or the GNU General Public License version 2.

## Credits and history

- **AdminNeo** by Peter Knut, <https://www.adminneo.org/>, a fork of **Adminer** by Jakub Vrána,
  <https://www.adminer.org/>. Their work is the editor itself; this extension embeds and integrates it.
- **sevenx_dse** by [7x](https://se7enx.com), who maintain it together with Exponential.
- Exponential continues the legacy kernel of eZ Publish, which its original creator discontinued after about 18
  years. sevenx_dse was written for Exponential; its first versions targeted the admin3 design.

### Screenshots of version 1.0 (admin3 design)

These show the first release, before the dashboard and the admin4 styling; the current version looks different.

<img width="1608" height="933" alt="sevenx_dse 1.0: AdminNeo inside the admin3 administration" src="https://github.com/user-attachments/assets/a04f105f-0a10-429e-970f-60baf7cc6491" />

<img width="1719" height="933" alt="sevenx_dse 1.0: browsing a table" src="https://github.com/user-attachments/assets/5b86a7a3-8cf3-462b-aacb-b8800822a697" />
