# Changelog

All notable changes to sevenx_dse. Earlier releases are described on
<https://github.com/se7enxweb/sevenx_dse/releases>.

## Unreleased

- Updated: The dashboard opens the main database of the installation with one click ("Open the database of this
  site"), read from `site.ini [DatabaseSettings]` of the siteaccess the way the kernel reads it, as AdminNeo's
  configured server `main` (`classes/sevenxdsemaindatabase.php`). Its login comes from the INI and is never typed in,
  written into a form or shown; only the server key `main` is in the address. Before, the login form preselected
  MySQL, and an SQLite site showed no tables: the form offered the bare `Database` value (`sqlite.db`), which the
  kernel finds in `var/storage/sqlite3/` but the editor looked for in the installation root, so the file was
  refused (#16641).
- Updated: Any other database is opened as before: "Connect to another database" on the dashboard, and the "Change
  database" link (`dse_switch`) of the editor, lead to AdminNeo's login form with every driver it has (MySQL/MariaDB,
  PostgreSQL, SQLite, Oracle, MS SQL, MongoDB ... as far as their PHP extensions are loaded) and the free-form server,
  login and database fields, or any SQLite file inside the site directory, with the logins remembered in the session.
  `main` is AdminNeo's configured server only for requests about it. The first version of #16641 offered only the
  site's engine and no other database; that restriction is gone.
- Updated: The editor's state is in view parameters (`/dse/dashboard/(sqlite)/main/(db)/~dmFy...`), never in a query
  string (`classes/sevenxdseviewparameters.php`): AdminNeo's links, form actions, script addresses and redirects are
  rewritten, values that are not plain words travel base64url-encoded after `~`, parameters with bracketed names
  (filters, columns) together in `(qs)`. AdminNeo's GET forms (search, filter, database selector) are posted and
  redirected to their view-parameter address; an old `?key=` address is redirected the same way. The SQL command form
  is only posted (it no longer puts the statement into the address). AdminNeo's background requests may append
  `&page=2` or `&script=db` to the path, which is read as before.
- Updated: SQLite: the table list and the database page leave out SQLite's own tables (`sqlite_sequence`,
  `sqlite_stat1` ...).

## 1.1.20 (2026-11-01)

- Updated: SQLite: every page of the editor with a database selected opens in a fraction of a second instead of
  several seconds on a large database. The navigation panel asks the SQLite driver for its table list without row
  counts (`table_status('', true)`), as the other drivers already answered, but the driver ignored the request and
  ran `COUNT(*)` on every table and view each time (257 tables and 3 GB on the reference site: 2.7-3.1 s per page).
- Updated: SQLite: the row counts of the database page are kept for reuse in `var/<site>/cache/dse/`, keyed by the
  database file and the size and time of the file and its -wal file. While nothing has been written since, they are
  shown as exact; after a change they are shown for up to `dse.ini [DSESettings] RowCountCacheTTL` seconds (default
  120, 0 = never) marked "~" and "approximate, as of <time>", then counted again. The footer of the Rows column says
  when they were counted and links to counting them again. Views are no longer counted (their row was never shown),
  and the request that counts sets `PRAGMA query_only` on its connection.
- Updated: The editor's own scripts run again inside the administration. Its inline scripts carried a nonce of their
  own, which the administration's Content-Security-Policy does not list, so the browser blocked all of them: the
  table figures stayed "?", and the check boxes, the sticky footer and the confirmations before Drop and Truncate did
  not work. Embedded, the editor now uses the nonce of the policy the kernel has set for the response.

## 1.1.15 (2026-10-22)

- Updated: The "DS Editor" admin tab has an icon: `Icon=brush` in `menu.ini [Topmenu_dse]`, drawn by the admin4
  design before the tab's name.
- Fixed: The tab checked the policy `dse/dashboard`, a function the `dse` module does not have, so a role limited to
  `dse/dse` could open the editor but never saw the tab. It now checks `dse/dse`.
- Updated: README.md rewritten as a complete guide: features, requirements, installation with Composer and by hand,
  every setting, how to switch the tab on, usage step by step, permissions, translations, rebuilding the AdminNeo
  assets, upgrading, known limits, troubleshooting, contributing, license and credits.
- Updated: composer.json states the support links, PHP 8.0 or later, `extra.installer-name` and current keywords;
  obsolete suggestions (`ext-mysql`, `ext-suhosin`) removed.
- Updated: ezinfo.php names the included AdminNeo with its version, copyright and license; ezinfo.php and
  extension.xml state version 1.1.15.
- Added: This CHANGELOG.md.
