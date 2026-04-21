<link rel="stylesheet" href="/extension/sevenx_dse/design/standard/stylesheets/adminneo/default-blue.css">
<link rel="stylesheet" href="/extension/sevenx_dse/design/standard/stylesheets/adminneo/jush.css">
<link rel="stylesheet" href="/extension/sevenx_dse/design/standard/stylesheets/adminneo/jush-dark.css">
<script src="/extension/sevenx_dse/design/standard/javascript/adminneo/main.js"></script>
<script src="/extension/sevenx_dse/design/standard/javascript/adminneo/jush.js"></script>

{literal}
<style>
/*
 * AdminNeo embedded in eZ Publish admin UI.
 *
 * DOM structure (children of #adminneo-root):
 *   #help              - tooltip, position:absolute
 *   #content           - breadcrumb .header + page content
 *   #navigation-button - hamburger button, hidden
 *   #navigation-panel  - left nav column (contains .footer at bottom)
 *
 * Layout: flex row on #adminneo-root; nav uses order:-1 to appear left
 * despite being after #content in DOM. All position:fixed overridden.
 */

/*
 * ── GLOBAL REPAIRS ───────────────────────────────────────────────────────
 * default-blue.css had html{font-size:125%}, body{background:#fff},
 * a{color:var(--link-text)}, ol/ul{list-style:none} as unscoped globals.
 * The html/body rules are now removed from the source CSS file.
 * Only sidebar-scoped repairs remain here.
 */

/* 1a. AdminNeo styles scoped to #adminneo-root — set font base in px */
#adminneo-root {
    font-size: 14px;
    color: var(--body-text, #333);
    font-family: var(--font-family, Helvetica, Arial, sans-serif);
    line-height: 1.4;
    background: var(--body-bg, #fff);
}

/* 1b. Restore body background (AdminNeo's body rule removed; restore eZ value) */
body { background: url('/design/admin3/images/3/dark_back.png') !important; }

.leftmenu-items {
    font-size: 1.05em;
}

/* 1c. Restore bullet points + indentation in eZ sidebars.
       AdminNeo's default-blue.css has ol,ul{list-style:none;margin:15px 20px;padding:0}
       which kills bullets and uses wrong margins.
       #rightmenu has overflow-x:hidden — bullets rendered with list-style:outside
       sit outside the padding box and get clipped. Fix: use padding-left (not margin-left)
       so the bullet marker lands inside the ul's content box and is never clipped. */
#leftmenu ul, #leftmenu ol {
    list-style: disc !important;
    margin: 0.75em 0 1em 2.5em !important;
    padding: 0 !important;
}
#rightmenu ul, #rightmenu ol {
    list-style: disc !important;
    margin: 0.75em 0 1em 0 !important;
    padding: 0 0 0 1.5em !important;
}
#leftmenu li,
#rightmenu li {
    list-style: inherit !important;
}

/* 1d. Restore eZ sidebar link colour + remove AdminNeo's a{padding:3px 0}
       which inflates every link vertically, breaking the user avatar+name layout
       and adding unwanted spacing throughout both sidebars. */
#leftmenu a,
#rightmenu a {
    color: #005b7f !important;
    padding: 0 !important;
}
/* Keep inline display on anchors inside paragraphs (AdminNeo doesn't change this,
   but the padding removal needs the element to stay inline) */
#rightmenu p a {
    display: inline !important;
}

div#leftmenu-design ul.leftmenu-items li.current div a {
    color: #E24602 !important;
}
#leftmenu li.current > a,
#leftmenu li.current > a:hover {
    color: #E24602 !important;
}

/* 1e. nobullet: eZ uses li.nobullet to suppress bullets. No CSS defines this
       class — it relied on AdminNeo's ol,ul{list-style:none} being absent.
       Since we restore disc bullets for #rightmenu, explicitly cancel them
       for .nobullet items. */
#rightmenu li.nobullet,
#leftmenu li.nobullet {
    list-style: none !important;
}

/* 1f. Restore native select appearance inside right sidebar.
       AdminNeo's global select{appearance:none; background:custom-arrow}
       makes the Clear cache and Siteaccess dropdowns look like AdminNeo widgets.
       Revert to browser-native dropdowns for eZ chrome. */
#rightmenu select {
    -webkit-appearance: auto !important;
    appearance: auto !important;
    background-image: none !important;
    padding-inline-end: initial !important;
    cursor: default !important;
    border: 1px solid #ccc !important;
    border-radius: 0 !important;
    background-color: #fff !important;
}

/* 1g. AdminNeo's .right{text-align:right} matches #rightmenu.sidebar.right.
       Target every non-heading element to stay left-aligned.
       Use #rightmenu * with exceptions rather than listing each element,
       because label/input/p etc. all need overriding. */
#rightmenu *:not(h4):not(h3):not(h2):not(h1) {
    text-align: left !important;
}

/* 1h. AdminNeo's p{margin:15px 20px} removes 40px of width from every paragraph
       inside the sidebar, causing "Administrator User" to wrap below the avatar.
       Reset to a small vertical margin only. */
#rightmenu p,
#leftmenu p {
    margin: 0 0 4px 0 !important;
}

/* 1i. AdminNeo's label{padding:5px 0 3px; cursor:pointer; line-height:1.357}
       misaligns Quick settings checkbox+label rows. Restore normal label behaviour. */
#rightmenu label {
    padding: 0 !important;
    line-height: inherit !important;
    cursor: default !important;
}

/* 1j. Prevent right sidebar from scrolling horizontally. */
#rightmenu {
    overflow-x: hidden;
}
/* ── END GLOBAL REPAIRS ──────────────────────────────────────────────── */

/* 2. Root: flex row, fills the eZ content area seamlessly. */
#adminneo-root {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    position: relative;
    width: 100%;
    min-height: 500px;
    background: url('/design/admin3/images/3/dark_back.png');
    color: var(--body-text, #333);
    line-height: 1.4;
}

/* 3. Navigation panel: left column — white bg to match eZ .sidebar.left */
#adminneo-root #navigation-panel {
    order: -1;
    position: static !important;
    left: auto !important;
    top: auto !important;
    bottom: auto !important;
    width: var(--menu-width, 13rem) !important;
    flex-shrink: 0;
    display: flex !important;
    flex-direction: column;
    overflow-y: auto;
    border-right: 1px solid var(--panel-border, #e0e0e0);
    background: #fff;
    box-shadow: none !important;
    z-index: 1 !important;
}

/* Nav panel: bullet points for server/table list items — match eZ leftmenu */
#adminneo-root #navigation-panel menu {
    padding-inline-start: 20px;
}
#adminneo-root #navigation-panel menu li {
    display: list-item !important;
    list-style: disc !important;
}

/* Nav panel logo/title row */
#adminneo-root #navigation-panel .header {
    position: static !important;
    width: auto !important;
    border-bottom: 1px solid var(--panel-border, #e0e0e0);
}

/* Footer (logout) is INSIDE #navigation-panel - fix position:fixed */
#adminneo-root #navigation-panel .footer {
    position: static !important;
    top: auto !important;
    right: auto !important;
    height: auto !important;
    line-height: normal !important;
    padding: 8px 10px !important;
    border-top: 1px solid var(--panel-border, #e0e0e0) !important;
    border-bottom: none !important;
    flex-flow: column !important;
    align-items: flex-start !important;
    margin-top: auto;
    background: #fff !important;
}

/* 4. Content area: right column — white bg */
#adminneo-root #content {
    flex: 1 1 0%;
    min-width: 0;
    margin-inline-start: 0 !important;
    padding-top: 0 !important;
    overflow-x: auto;
    background: #fff;
}

/* 5. Breadcrumb header inside #content: static */
#adminneo-root #content > .header {
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    padding: 6px 10px;
    border-bottom: 1px solid var(--panel-border, #e0e0e0);
    box-sizing: border-box;
    overflow: hidden;
}

/* sticky thead: top:0 instead of top:2rem */
#adminneo-root thead {
    top: 0 !important;
}

/* 6. Hamburger: always hidden */
#adminneo-root #navigation-button {
    display: none !important;
}

/* 7. Help tooltip: relative positioning */
#adminneo-root #help {
    position: relative;
    z-index: 25;
}

/* 8. Tables: don't bust the column */
#adminneo-root table {
    max-width: 100%;
}
</style>
{/literal}

{$neo_body}
