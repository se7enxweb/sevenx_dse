{* The DSE dashboard (dse/dashboard before the editor opens): what the Database Source Editor does, the database
   it opens, what can be done with it, how it is kept safe, and the acknowledgement that opens it.

   Variables (from the dashboard view): dse_self_url (where the acknowledgement posts), dse_info (implementation,
   database, has_server, driver, version, tables, kernel_tables, adminneo, drivers), dse_remembered_servers (logins
   AdminNeo remembers in this session, a count), dse_prefill_password (InsecureUse is enabled). No credentials are
   handed to this template. The form posts dse_ack_warning=1 as before. *}
{include uri='design:dse/exp_style.tpl'}

{def $info = first_set( $dse_info, hash() )
     $remembered = first_set( $dse_remembered_servers, 0 )}

<div class="context-block exp-dse">

<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Database Source Editor'|i18n( 'extension/sevenx_dse' )}</h1>
</div></div>

<div class="box-bc"><div class="box-ml"><div class="box-content">

<p class="exp-intro">{'The Database Source Editor opens the database of this site in AdminNeo, inside the administration: browse and edit the rows of any table, run SQL, look at the structure, export and import. It works on the live data directly, past every check the administration makes.'|i18n( 'extension/sevenx_dse' )}</p>

<div class="exp-feedback is-bad" role="alert">
    <h2 class="exp-h2">{'Direct database access: stop and read this'|i18n( 'extension/sevenx_dse' )}</h2>
    <p><strong>{'This tool provides raw, unrestricted access to the live database.'|i18n( 'extension/sevenx_dse' )}</strong> {'Mistakes made here (dropped tables, deleted rows, corrupted data) are immediate and irreversible.'|i18n( 'extension/sevenx_dse' )}</p>
    <p>{'Use of this tool voids all warranty and support for the affected installation. You take full responsibility for any changes made.'|i18n( 'extension/sevenx_dse' )}</p>
    <p><strong>{'We strongly urge you to stop now and take a complete backup (database and files) before proceeding. Do not continue unless a verified backup exists.'|i18n( 'extension/sevenx_dse' )}</strong></p>
</div>

<section aria-labelledby="dse-db-title">
<h2 class="exp-sr" id="dse-db-title">{'The database'|i18n( 'extension/sevenx_dse' )}</h2>
<ul class="exp-figures">
    <li class="exp-figure"><strong>{first_set( $info.driver, '' )|wash}</strong><span>{'Database type'|i18n( 'extension/sevenx_dse' )}</span></li>
    <li class="exp-figure"><strong>{first_set( $info.database, '' )|wash}</strong><span>{'Database of this site'|i18n( 'extension/sevenx_dse' )}</span></li>
    <li class="exp-figure"><strong>{first_set( $info.tables, 0 )}</strong><span>{'Tables, %count of them Exponential tables'|i18n( 'extension/sevenx_dse',, hash( '%count', first_set( $info.kernel_tables, 0 ) ) )}</span></li>
    {if first_set( $info.version, '' )|ne( '' )}<li class="exp-figure"><strong>{$info.version|wash}</strong><span>{'Server version'|i18n( 'extension/sevenx_dse' )}</span></li>{/if}
    {if first_set( $info.adminneo, '' )|ne( '' )}<li class="exp-figure"><strong>{$info.adminneo|wash}</strong><span>{'AdminNeo version'|i18n( 'extension/sevenx_dse' )}</span></li>{/if}
</ul>
</section>

<section class="exp-section" aria-labelledby="dse-can-title">
<div class="exp-section-head">
    <h2 class="exp-h2" id="dse-can-title">{'What you can do with it'|i18n( 'extension/sevenx_dse' )}</h2>
</div>
<ul class="exp-rows exp-cando">
    <li class="exp-row"><h3>{'Browse and edit tables'|i18n( 'extension/sevenx_dse' )}</h3><p class="exp-help">{'Filter, sort and page through the rows of a table, change or delete single rows, add new ones.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="exp-row"><h3>{'Run SQL'|i18n( 'extension/sevenx_dse' )}</h3><p class="exp-help">{'Type any statement and see its result. A SELECT reads; every other statement changes the live data at once.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="exp-row"><h3>{'Look at the structure'|i18n( 'extension/sevenx_dse' )}</h3><p class="exp-help">{'Columns, indexes, foreign keys, views and triggers of each table, and a schema diagram.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="exp-row"><h3>{'Export and import'|i18n( 'extension/sevenx_dse' )}</h3><p class="exp-help">{'Download tables or the whole database as SQL, CSV or TSV, straight to a file; run an SQL file you upload.'|i18n( 'extension/sevenx_dse' )}</p></li>
    <li class="exp-row"><h3>{'Open another database'|i18n( 'extension/sevenx_dse' )}</h3><p class="exp-help">{'Sign in to another server or an SQLite file inside the site directory. Drivers: %drivers.'|i18n( 'extension/sevenx_dse',, hash( '%drivers', first_set( $info.drivers, array() )|implode( ', ' )|wash ) )}</p></li>
</ul>
</section>

<section class="exp-section" aria-labelledby="dse-safe-title">
<div class="exp-section-head">
    <h2 class="exp-h2" id="dse-safe-title">{'How it is kept safe'|i18n( 'extension/sevenx_dse' )}</h2>
</div>
<div class="exp-panel">
<ul class="exp-confirm-list">
    <li>{'Only users whose role has a policy for the dse module (its function dse, or all of its functions) can open it.'|i18n( 'extension/sevenx_dse' )}</li>
    <li>{'This page comes first every time the editor is opened from the menu; nothing is connected before you confirm.'|i18n( 'extension/sevenx_dse' )}</li>
    {if first_set( $dse_prefill_password, false() )|not}<li>{'AdminNeo asks for the database login itself. The password of the site database is not written into the form.'|i18n( 'extension/sevenx_dse' )}</li>{/if}
    <li>{'SQLite files are accepted only by a relative path inside the site directory.'|i18n( 'extension/sevenx_dse' )}</li>
    {if $remembered|gt( 0 )}<li>{'AdminNeo remembers %count database logins in your session; they are offered after you confirm. Log out in the editor to forget them.'|i18n( 'extension/sevenx_dse',, hash( '%count', $remembered ) )}</li>{/if}
</ul>
{if first_set( $dse_prefill_password, false() )}
<div class="exp-feedback is-warn" role="alert"><p><strong>{'InsecureUse is enabled in dse.ini: the login form is filled with the database password, which anyone who can open this page can read in the page source. Disable it outside a development installation.'|i18n( 'extension/sevenx_dse' )}</strong></p></div>
{/if}
</div>
</section>

<section class="exp-section" aria-labelledby="dse-go-title">
<div class="exp-section-head">
    <h2 class="exp-h2" id="dse-go-title">{'Open the editor'|i18n( 'extension/sevenx_dse' )}</h2>
    <p>{'Prefer the pages of the administration where they exist: they keep caches, search and the audit trail in step, which a change made here does not.'|i18n( 'extension/sevenx_dse' )}</p>
</div>
<form method="post" action="{$dse_self_url|wash}">
<input type="hidden" name="dse_ack_warning" value="1" />
<div class="exp-panel">
    <label><input type="checkbox" id="dse-ack-backup" name="DseBackupConfirmed" value="1" required="required" /> {'I have taken a full backup and accept full responsibility.'|i18n( 'extension/sevenx_dse' )}</label>
</div>
<div class="exp-bottombar">
    <div class="exp-actions">
        <button class="exp-btn exp-btn-danger" type="submit" id="dse-proceed">{'Proceed to the database'|i18n( 'extension/sevenx_dse' )}</button>
        <a class="exp-btn" href={'/'|ezurl}>{'Cancel and go back'|i18n( 'extension/sevenx_dse' )}</a>
    </div>
</div>
</form>
</section>

</div></div></div>
</div>
{undef $info $remembered}
