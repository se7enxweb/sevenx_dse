{* The database chooser of the editor (dse/dashboard/(dse_choose)/1): where "Change database" (dse_switch), "Connect to
   another database" and AdminNeo's "Logout" land once the warning was confirmed in this session. It stands above
   AdminNeo's login page, whose form (every driver, server, login, database or SQLite file) opens another database.

   Drawn with the administration's shared components (.a4-section, .a4-cards, .a4-card, .a4-tag, .a4-btn) and its
   tokens, light and dark. No inline CSS; the little more it needs is in stylesheets/dse.css (.a4-dse-*).

   Variables (from the dashboard view): dse_self_url (where "Open the database of this site" posts), dse_has_main,
   dse_main_name (engine and database name of the site's database, no login), dse_logins (the logins remembered in
   this session: driver, server, username, db, db_name, is_main, current, url). No password is handed to this template. *}
{def $logins = first_set( $dse_logins, array() )}
<section class="a4-section a4-dse-chooser" aria-labelledby="dse-choose-title">
<div class="a4-section-head">
    <h1 class="a4-h2" id="dse-choose-title">{'Choose a database'|i18n( 'extension/sevenx_dse' )}</h1>
    <p>{'You confirmed the warning in this session. Open the database of this site, switch to a login remembered in this session, or sign in to another database with the form below.'|i18n( 'extension/sevenx_dse' )}</p>
</div>
<div class="a4-cards a4-dse-choices">
    {if first_set( $dse_has_main, false() )}
    <div class="a4-card">
        <h2 class="a4-h3">{'Database of this site'|i18n( 'extension/sevenx_dse' )}</h2>
        <p class="a4-help">{first_set( $dse_main_name, '' )|wash}</p>
        <form method="post" action="{$dse_self_url|wash}">
            <button class="a4-btn a4-btn-danger" type="submit" id="dse-open-main" name="dse_open_main" value="1">{'Open the database of this site'|i18n( 'extension/sevenx_dse' )}</button>
        </form>
    </div>
    {/if}
    <div class="a4-card">
        <h2 class="a4-h3">{'Logins in this session'|i18n( 'extension/sevenx_dse' )}</h2>
        {if $logins|count|gt( 0 )}
        <ul class="a4-dse-logins">
        {foreach $logins as $login}
            <li{if $login.current} class="is-current"{/if}><a href="{$login.url|wash}"{if $login.current} aria-current="true"{/if}>{if $login.is_main}{'Database of this site'|i18n( 'extension/sevenx_dse' )}{else}{$login.driver|wash}{if or( $login.username|ne( '' ), $login.server|ne( '' ) )} - {$login.username|wash}{if and( $login.username|ne( '' ), $login.server|ne( '' ) )}@{/if}{$login.server|wash}{/if}{/if}{if $login.db|ne( '' )} - {$login.db_name|wash}{/if}</a>{if $login.current} <span class="a4-tag">{'Connected now'|i18n( 'extension/sevenx_dse' )}</span>{/if}</li>
        {/foreach}
        </ul>
        {else}
        <p class="a4-help">{'No logins are remembered in this session.'|i18n( 'extension/sevenx_dse' )}</p>
        {/if}
    </div>
    <div class="a4-card">
        <h2 class="a4-h3">{'Connect to another database'|i18n( 'extension/sevenx_dse' )}</h2>
        <p class="a4-help">{'Sign in with the form below: every system AdminNeo has here, any server, login and database, or an SQLite file inside the site directory.'|i18n( 'extension/sevenx_dse' )}</p>
    </div>
</div>
</section>
{undef $logins}
