{* The DSE dashboard (dse/dashboard before the editor opens): what the Database Source Editor does, the database
   it opens, what can be done with it, how it is kept safe, and the acknowledgement that opens it.

   Drawn with the administration's shared components (.a4-page, .a4-notice, .a4-figures, .a4-cards, .a4-card,
   .a4-check, .a4-toolbar, .a4-btn) and its tokens, so it follows the light and dark mode of admin4 and fits the classic
   admin design, which has the same components. No inline CSS; the little this page needs beyond the components is in
   stylesheets/dse.css (.a4-dse-*), loaded through design.ini [StylesheetSettings] BackendCSSFileList.

   Variables (from the dashboard view): dse_self_url (where the acknowledgement posts), dse_info (implementation,
   database, has_server, driver, version, tables, kernel_tables, adminneo, drivers), dse_remembered_servers (logins
   AdminNeo remembers in this session, a count), dse_prefill_password (InsecureUse is enabled), dse_has_main (the
   main database of site.ini can be opened with its login from the INI). No credentials are handed to this template.
   The form posts dse_ack_warning=1 as before, and with dse_has_main the button pressed as dse_connect: "main" opens
   the site's database straight away, "other" opens AdminNeo's login form with every driver. *}
{def $info = first_set( $dse_info, hash() )
     $remembered = first_set( $dse_remembered_servers, 0 )}

<div class="context-block a4-page a4-dse">

<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Database Source Editor'|i18n( 'extension/sevenx_dse' )}</h1>
</div></div>

<div class="box-bc"><div class="box-ml"><div class="box-content">

<p class="a4-intro">{'The Database Source Editor opens the database of this site in AdminNeo, inside the administration: browse and edit the rows of any table, run SQL, look at the structure, export and import. It works on the live data directly, past every check the administration makes.'|i18n( 'extension/sevenx_dse' )}</p>

<div class="a4-notice is-bad" role="alert">
    <h2 class="a4-h2">{'Direct database access: stop and read this'|i18n( 'extension/sevenx_dse' )}</h2>
    <p><strong>{'This tool provides raw, unrestricted access to the live database.'|i18n( 'extension/sevenx_dse' )}</strong> {'Mistakes made here (dropped tables, deleted rows, corrupted data) are immediate and irreversible.'|i18n( 'extension/sevenx_dse' )}</p>
    <p>{'Use of this tool voids all warranty and support for the affected installation. You take full responsibility for any changes made.'|i18n( 'extension/sevenx_dse' )}</p>
    <p><strong>{'We strongly urge you to stop now and take a complete backup (database and files) before proceeding. Do not continue unless a verified backup exists.'|i18n( 'extension/sevenx_dse' )}</strong></p>
</div>

<section class="a4-section" aria-labelledby="dse-db-title">
<h2 class="a4-sr-only" id="dse-db-title">{'The database'|i18n( 'extension/sevenx_dse' )}</h2>
<ul class="a4-figures">
    <li class="a4-figure"><strong>{first_set( $info.driver, '' )|wash}</strong><span>{'Database type'|i18n( 'extension/sevenx_dse' )}</span></li>
    <li class="a4-figure"><strong>{first_set( $info.database, '' )|wash}</strong><span>{'Database of this site'|i18n( 'extension/sevenx_dse' )}</span></li>
    <li class="a4-figure"><strong>{first_set( $info.tables, 0 )}</strong><span>{'Tables, %count of them Exponential tables'|i18n( 'extension/sevenx_dse',, hash( '%count', first_set( $info.kernel_tables, 0 ) ) )}</span></li>
    {if first_set( $info.version, '' )|ne( '' )}<li class="a4-figure"><strong>{$info.version|wash}</strong><span>{'Server version'|i18n( 'extension/sevenx_dse' )}</span></li>{/if}
    {if first_set( $info.adminneo, '' )|ne( '' )}<li class="a4-figure"><strong>{$info.adminneo|wash}</strong><span>{'AdminNeo version'|i18n( 'extension/sevenx_dse' )}</span></li>{/if}
</ul>
</section>

<section class="a4-section" aria-labelledby="dse-can-title">
<div class="a4-section-head">
    <h2 class="a4-h2" id="dse-can-title">{'What you can do with it'|i18n( 'extension/sevenx_dse' )}</h2>
</div>
<ul class="a4-cards a4-dse-cando">
    <li class="a4-card"><h3 class="a4-h3">{'Browse and edit tables'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'Filter, sort and page through the rows of a table, change or delete single rows, add new ones.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="a4-card"><h3 class="a4-h3">{'Run SQL'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'Type any statement and see its result. A SELECT reads; every other statement changes the live data at once.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="a4-card"><h3 class="a4-h3">{'Look at the structure'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'Columns, indexes, foreign keys, views and triggers of each table, and a schema diagram.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="a4-card"><h3 class="a4-h3">{'Export and import'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'Download tables or the whole database as SQL, CSV or TSV, straight to a file; run an SQL file you upload.'|i18n( 'extension/sevenx_dse' )}</p></li>
    {if first_set( $dse_has_main, false() )}
    <li class="a4-card"><h3 class="a4-h3">{'Database of this site'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'With one click the editor opens the database this site runs on, with the login from its settings (site.ini [DatabaseSettings]); that login is never typed in or shown.'|i18n( 'extension/sevenx_dse' )}</p></li>
    {/if}
    <li class="a4-card"><h3 class="a4-h3">{'Open another database'|i18n( 'extension/sevenx_dse' )}</h3><p class="a4-help">{'Sign in to another server or an SQLite file inside the site directory. Drivers: %drivers.'|i18n( 'extension/sevenx_dse',, hash( '%drivers', first_set( $info.drivers, array() )|implode( ', ' )|wash ) )}</p></li>
</ul>
</section>

<section class="a4-section" aria-labelledby="dse-safe-title">
<div class="a4-section-head">
    <h2 class="a4-h2" id="dse-safe-title">{'How it is kept safe'|i18n( 'extension/sevenx_dse' )}</h2>
</div>
<div class="a4-card">
<ul class="a4-dse-list">
    <li>{'Only users whose role has a policy for the dse module (its function dse, or all of its functions) can open it.'|i18n( 'extension/sevenx_dse' )}</li>
    <li>{'This page comes first every time the editor is opened from the menu; nothing is connected before you confirm.'|i18n( 'extension/sevenx_dse' )}</li>
    {if first_set( $dse_has_main, false() )}
    <li>{'The editor signs in with the login of the site database from site.ini. It is not typed in, not written into any form and not shown.'|i18n( 'extension/sevenx_dse' )}</li>
    {/if}
    {if first_set( $dse_prefill_password, false() )|not}<li>{'AdminNeo asks for the database login itself. The password of the site database is not written into the form.'|i18n( 'extension/sevenx_dse' )}</li>{/if}
    <li>{'SQLite files are accepted only by a relative path inside the site directory.'|i18n( 'extension/sevenx_dse' )}</li>
    {if $remembered|gt( 0 )}<li>{'AdminNeo remembers %count database logins in your session; they are offered after you confirm. Log out in the editor to forget them.'|i18n( 'extension/sevenx_dse',, hash( '%count', $remembered ) )}</li>{/if}
</ul>
{if first_set( $dse_prefill_password, false() )}
<div class="a4-notice is-warn a4-dse-after" role="alert"><p><strong>{'InsecureUse is enabled in dse.ini: the login form is filled with the database password, which anyone who can open this page can read in the page source. Disable it outside a development installation.'|i18n( 'extension/sevenx_dse' )}</strong></p></div>
{/if}
</div>
</section>

<section class="a4-section" aria-labelledby="dse-go-title">
<div class="a4-section-head">
    <h2 class="a4-h2" id="dse-go-title">{'Open the editor'|i18n( 'extension/sevenx_dse' )}</h2>
    <p>{'Prefer the pages of the administration where they exist: they keep caches, search and the audit trail in step, which a change made here does not.'|i18n( 'extension/sevenx_dse' )}</p>
</div>
<form method="post" action="{$dse_self_url|wash}">
<input type="hidden" name="dse_ack_warning" value="1" />
<div class="a4-card">
    <label class="a4-check" for="dse-ack-backup"><input type="checkbox" id="dse-ack-backup" name="DseBackupConfirmed" value="1" required="required" /> <span>{'I have taken a full backup and accept full responsibility.'|i18n( 'extension/sevenx_dse' )}</span></label>
</div>
<div class="a4-toolbar is-bottom">
    <div class="a4-actions">
        {if first_set( $dse_has_main, false() )}
        <button class="a4-btn a4-btn-danger" type="submit" id="dse-proceed" name="dse_connect" value="main">{'Open the database of this site'|i18n( 'extension/sevenx_dse' )}</button>
        <button class="a4-btn" type="submit" id="dse-connect-other" name="dse_connect" value="other">{'Connect to another database'|i18n( 'extension/sevenx_dse' )}</button>
        {else}
        <button class="a4-btn a4-btn-danger" type="submit" id="dse-proceed">{'Proceed to the database'|i18n( 'extension/sevenx_dse' )}</button>
        {/if}
        <a class="a4-btn" href={'/'|ezurl}>{'Cancel and go back'|i18n( 'extension/sevenx_dse' )}</a>
    </div>
</div>
</form>
</section>

</div></div></div>
</div>
{undef $info $remembered}
