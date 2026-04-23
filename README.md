# sevenx_dse — 7x Database Source Editor (Stable; Open Source; eZ Publish Legacy Extension)

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-8892BF?logo=php&logoColor=white)](https://php.net)
[![Platform](https://img.shields.io/badge/Exponential%20Platform-Legacy-orange)](https://github.com/se7enxweb/exponential-platform-legacy)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
[![GitHub issues](https://img.shields.io/github/issues/se7enxweb/sevenx_dse)](https://github.com/se7enxweb/sevenx_dse/issues)

> **sevenx_dse** is a downstream fork of [AdminNeo](https://www.adminneo.org/) embedded as a native eZ Publish Legacy extension — delivering raw SQL access, table browsing, schema inspection, import/export, and multi-driver database support (MySQL, SQLite, PostgreSQL) directly inside the Exponential Platform Legacy admin interface, scoped behind eZ Publish role-based permissions.

## Screenshots

<img width="1608" height="933" alt="Screenshot 2026-04-21 at 19-25-52 Database Source Editor - 7x Alpha" src="https://github.com/user-attachments/assets/a04f105f-0a10-429e-970f-60baf7cc6491" />

<img width="1719" height="933" alt="Screenshot 2026-04-21 at 19-11-23 Database Source Editor - 7x Alpha" src="https://github.com/user-attachments/assets/5b86a7a3-8cf3-462b-aacb-b8800822a697" />

<img width="1719" height="4945" alt="Screenshot 2026-04-21 at 19-12-16 Database Source Editor - 7x Alpha" src="https://github.com/user-attachments/assets/91e9136b-8fac-480d-beeb-a174e5fc6fdb" />

<img width="1608" height="933" alt="Screenshot 2026-04-21 at 19-26-04 Database Source Editor - 7x Alpha" src="https://github.com/user-attachments/assets/0494a593-0482-4b0f-9cbf-fd6bed2e0311" />


---

## Table of Contents

1. [Project Notice](#project-notice)
2. [Project Status](#project-status)
3. [Who is 7x](#who-is-7x)
4. [What is sevenx_dse?](#what-is-sevenx_dse)
5. [Technology Stack](#technology-stack)
6. [Requirements](#requirements)
7. [Installation](#installation)
8. [Main Features](#main-features)
9. [Issue Tracker](#issue-tracker)
10. [Where to Get More Help](#where-to-get-more-help)
11. [How to Contribute](#how-to-contribute)
12. [Credits](#credits)
13. [Copyright](#copyright)
14. [License](#license)

---

## Project Notice

> "Please Note: This project is not associated with the original eZ Publish software or its original developer, eZ Systems."

sevenx_dse is an independent, 7x-driven extension for Exponential (100% eZ Publish Compatible). It embeds [AdminNeo](https://www.adminneo.org/) — a downstream fork of the original Adminer database tool — as a fully integrated module inside the legacy admin UI. The extension is stewarded by [7x (se7enx.com)](https://se7enx.com).

---

## Project Status

**sevenx_dse 1.0.x is the current stable release line.**

The extension is under active development targeting Exponential Platform Legacy 6.x / eZ Publish 5.x with PHP 8.3+. Ongoing work focuses on:

- Stable embedding of AdminNeo 5.x inside the eZ Publish admin3 design
- Multi-driver support: MySQL/MariaDB, SQLite, PostgreSQL
- Session-safe gate and CSRF-protected database switching
- CSS isolation to prevent AdminNeo global styles from damaging eZ admin chrome
- Export/download passthrough without eZ template wrapping
- PHP 8.3 and 8.5 compatibility

---

## Who is 7x

[7x](https://se7enx.com) is the North American corporation driving the continued general use, support, development, hosting, and design of Exponential Platform Legacy Enterprise Open Source Content Management System.

7x has been in business supporting Exponential Platform website customers and projects for over 24 years. Previously before 2022, 7x was called Brookins Consulting.

**7x offers:**
- Commercial support subscriptions for Exponential Platform Legacy deployments
- Hosting on the Exponential Platform cloud infrastructure (`exponential.earth`)
- Custom development, migrations, upgrades, and training
- Community stewardship via [share.exponential.earth](https://share.exponential.earth)

---

## What is sevenx_dse?

sevenx_dse is a direct database source editor for Exponential Platform Legacy. It embeds the full [AdminNeo](https://www.adminneo.org/) database UI as a native eZ Publish module, accessible at `/dse/dashboard` inside the eZ admin interface.

### What Does sevenx_dse Provide?

- **Raw SQL execution** — run arbitrary queries against the live database
- **Table browsing** — browse, filter, sort, and paginate table rows
- **Schema inspection** — view table structure, indexes, foreign keys
- **Data import/export** — SQL dump, CSV, TSV export and import with direct download passthrough (no eZ template wrapping)
- **Multi-driver support** — MySQL/MariaDB, SQLite, PostgreSQL
- **Safety gate** — mandatory disclaimer + backup acknowledgement screen on every bare access
- **CSRF-protected database switching** — "Change database" link uses a per-session token
- **Session-aware** — preserves AdminNeo server sessions (`$_SESSION['pwds']`) across gate acknowledgement
- **Permission-gated** — access controlled by eZ Publish role policy (`dse/dashboard`)
- **CSS-isolated** — AdminNeo's global stylesheet rules are neutralised so they cannot damage eZ admin chrome

### Access URL

```
/dse/dashboard
```

---

## Technology Stack

| Component | Value |
|---|---|
| Language | PHP 8.3+ |
| CMS Core | Exponential Platform Legacy (eZ Publish 5.x / 6.x) |
| Embedded DB UI | AdminNeo 5.3-dev (downstream fork) |
| Admin Design | admin3 (eZ Publish Legacy) |
| Supported Databases | MySQL 8.0+ · MariaDB 10.3+ · PostgreSQL 14+ · SQLite 3.35+ |
| Dependency Mgmt | Composer 2.x |

---

## Requirements

- PHP 8.3+ (PHP 8.3 or 8.5 recommended)
- Exponential Platform Legacy 6.x (eZ Publish 5.x or higher)
- A supported database: MySQL 8.0+, MariaDB 10.3+, PostgreSQL 14+, or SQLite 3.35+
- Composer 2.x

---

## Installation

### Via Composer

```bash
composer require se7enxweb/sevenx_dse
```

### Manual

1. Copy the `sevenx_dse` directory into your `extension/` folder.
2. Activate the extension in `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=sevenx_dse
```

3. Regenerate autoloads:

```bash
php bin/php/ezpgenerateautoloads.php
```

4. Clear caches via the admin interface or:

```bash
php bin/php/ezcache.php --clear-all
```

5. Assign the `dse/dashboard` policy to the roles that should have access.

---

## Main Features

- Embedded AdminNeo 5.x database UI inside eZ Publish admin3 design
- Raw SQL editor with syntax highlighting (JUSH)
- Table list, row browsing, filtering, sorting, pagination
- Schema editor: create/alter/drop tables, columns, indexes
- Import: SQL, CSV, TSV
- Export: SQL dump, CSV, TSV — direct file download (bypasses eZ template)
- Multi-driver: MySQL/MariaDB, SQLite, PostgreSQL
- Safety gate with mandatory backup acknowledgement on every bare GET
- Session-based "return to last database" without storing URLs
- CSRF token on "Change database" link (`dse_switch`)
- AdminNeo server password sessions preserved across gate
- CSS repair block isolating AdminNeo global styles from eZ admin chrome

---

## Issue Tracker

Submitting bugs, improvements, and stories is possible on
https://github.com/se7enxweb/sevenx_dse/issues

If you discover a security issue, please responsibly report it via email to
[security@exponential.earth](mailto:security@exponential.earth)

---

## Where to Get More Help

| Resource | URL |
|---|---|
| Platform Website | platform.exponential.earth |
| Documentation Hub | doc.exponential.earth |
| Community Forums | share.exponential.earth |
| GitHub Organisation | github.com/se7enxweb |
| This Repository | github.com/se7enxweb/sevenx_dse |
| Issue Tracker | [Issues](https://github.com/se7enxweb/sevenx_dse/issues) |
| 7x Corporate | se7enx.com |
| Support Subscriptions | support.exponential.earth |
| Sponsor 7x | sponsor.se7enx.com |

---

## How to Contribute

Everyone is encouraged to contribute to the development of new features and bugfixes for sevenx_dse.

1. Fork the repository: [github.com/se7enxweb/sevenx_dse](https://github.com/se7enxweb/sevenx_dse)
2. Clone your fork and create a feature branch: `git checkout -b feature/my-improvement`
3. Make your changes
4. Push your branch and open a Pull Request against `main`
5. Participate in the review

Bug reports, feature requests, and discussion are all welcome via the [issue tracker](https://github.com/se7enxweb/sevenx_dse/issues).

---

## Credits

sevenx_dse is a downstream fork and embedding of **AdminNeo**, which is itself a fork of **Adminer**.

| Credit | Person / Organisation |
|---|---|
| **AdminNeo** — embedded database UI core | [AdminNeo Project](https://www.adminneo.org/) |
| **AdminNeo original author** | [Jakub Vrána](https://www.vrana.cz/) |
| **Adminer** — upstream original | [Jakub Vrána](https://www.vrana.cz/) |
| **sevenx_dse extension** — eZ Publish integration | [7x (se7enx.com)](https://se7enx.com) |
| **Exponential Platform Legacy** | [7x (se7enx.com)](https://se7enx.com) + community |

We are deeply grateful to Jakub Vrána for creating and maintaining Adminer and AdminNeo — the core database UI that makes this extension possible.

---

## Copyright

Copyright (C) 1998 - 2026 7x. All rights reserved.

AdminNeo and Adminer are copyright their respective authors. See [AdminNeo](https://www.adminneo.org/) for full credits.

---

## License

sevenx_dse is licensed under the GNU General Public License v2 (or any later version).

sevenx_dse is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

sevenx_dse is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

The full license text is available at https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Copyright © 1998 – 2026 7x (se7enx.com). All rights reserved unless otherwise noted.
