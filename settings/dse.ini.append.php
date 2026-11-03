<?php /* #?ini charset="utf-8"?

[DSESettings]
# Controls whether the AdminNeo login form password field is pre-populated
# with the eZ database password from site.ini.
# Set to "enabled" only in trusted development environments.
# Only AdminNeo's free-form login form ("Connect to another database") uses it: the
# site's main database is opened with the login from site.ini and shows no login field.
# SECURITY: leaving this enabled in production exposes the database password
# to anyone who can view the browser's form source.
InsecureUse=disabled

# SQLite: the database page counts the rows of every table (COUNT(*) reads a
# whole table) and keeps the counts in the cache directory (var/<site>/cache/dse/).
# While nothing has been written to the database since, they are shown as exact.
# After it has changed, they are shown for up to this many seconds as
# approximate ("~"), with the time they were counted and a link that counts
# them again; then they are counted again. 0 = never show approximate counts.
RowCountCacheTTL=120

*/ ?>
