{* The look of the DSE dashboard (dse/dashboard before the editor opens), in the visual language of the redesigned
   Exponential administration pages (sections, sessions, cronjobs). Included once by dse/gate.tpl.

   Everything is scoped to .exp-dse and takes admin4's tokens where they exist (--a4-*), with values of its own for
   the older admin designs, so it fits every administration design without touching their stylesheets. *}
{literal}
<style>
.exp-dse {
    --sc-ink: var(--a4-ink, #1f2430);
    --sc-muted: var(--a4-muted, #5d6573);
    --sc-line: var(--a4-line, #e3e6eb);
    --sc-soft: var(--a4-soft, #f6f7f9);
    --sc-card: #fff;
    --sc-accent: #c2410c;          /* white text on it is 5.2:1 */
    --sc-accent-hover: #9a3412;
    --sc-ring: rgba(194, 65, 12, 0.45);
    --sc-ok: #166534;   --sc-ok-bg: #e7f5ea;
    --sc-warn: #8a4b00; --sc-warn-bg: #fff3df;
    --sc-bad: #b91c1c;  --sc-bad-bg: #fdecec;
    --sc-info: #1e4fa8; --sc-info-bg: #e8effd;
    --sc-radius: 12px;
    --sc-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    color: var(--sc-ink);
    font-size: 14px;
    line-height: 1.5;
}
.exp-dse *, .exp-dse *::before, .exp-dse *::after { box-sizing: border-box; }
.exp-dse [hidden] { display: none !important; }
.exp-dse .box-content { padding-bottom: 20px; }
.exp-dse h1.context-title { margin: 0; overflow-wrap: anywhere; }
.exp-dse h2.exp-h2 { margin: 0; padding: 0; border: 0; background: none; font-size: 16px; font-weight: 650; color: var(--sc-ink); }
.exp-dse h3 { overflow-wrap: anywhere; margin: 0; padding: 0; border: 0; background: none; font-size: 15px; font-weight: 650; color: var(--sc-ink); }
.exp-dse p { margin: 0; }
.exp-dse code { font-family: var(--sc-mono); font-size: 12.5px; overflow-wrap: anywhere; }
.exp-dse a { color: var(--sc-accent-hover); }
.exp-dse a:hover { color: var(--sc-ink); }
.exp-dse :focus-visible { outline: 3px solid var(--sc-ring); outline-offset: 2px; }
.exp-dse .exp-sr { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
.exp-dse .exp-muted { color: var(--sc-muted); }
.exp-dse ul.exp-plain { margin: 0; padding: 0; list-style: none; }

/* A page drawn without the admin's main card (the edit view) gets one of its own */
.exp-dse.exp-standalone { max-width: 980px; margin: 16px auto 24px; padding: 18px 20px; border-radius: 18px; background: var(--sc-card);
    box-shadow: 0 0 0 1px rgba(16, 24, 40, 0.06), 0 12px 32px -6px rgba(16, 24, 40, 0.12); }
#maincontent .exp-dse.exp-standalone { max-width: none; margin: 0; padding: 0; border-radius: 0; background: transparent; box-shadow: none; }

.exp-dse .exp-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; }
.exp-dse .exp-title-key { color: var(--sc-muted); }
.exp-dse .exp-intro { margin: 8px 0 18px; max-width: 78ch; color: var(--sc-muted); }
.exp-dse .exp-section { margin: 0 0 24px; }
.exp-dse .exp-section-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; margin: 0 0 10px; }
.exp-dse .exp-section-head p { flex: 1 1 100%; color: var(--sc-muted); max-width: 78ch; }

/* Messages */
.exp-dse .exp-feedback { margin: 0 0 14px; padding: 10px 14px; border: 1px solid; border-left-width: 4px; border-radius: 10px; }
.exp-dse .exp-feedback.is-ok { border-color: #b7dfc1; border-left-color: var(--sc-ok); background: var(--sc-ok-bg); color: var(--sc-ok); }
.exp-dse .exp-feedback.is-bad { border-color: #f1b4b4; border-left-color: var(--sc-bad); background: var(--sc-bad-bg); color: var(--sc-bad); }
.exp-dse .exp-feedback.is-warn { border-color: #f3d19c; border-left-color: var(--sc-warn); background: var(--sc-warn-bg); color: var(--sc-warn); }
.exp-dse .exp-feedback.is-info { border-color: #bcd0f5; border-left-color: var(--sc-info); background: var(--sc-info-bg); color: var(--sc-info); }
.exp-dse .exp-feedback strong { color: inherit; }
.exp-dse .exp-feedback p + p, .exp-dse .exp-feedback p + ul, .exp-dse .exp-feedback ul + p { margin-top: 6px; }
.exp-dse .exp-feedback ul { margin: 6px 0 0; padding-left: 20px; }
.exp-dse .exp-feedback h2.exp-h2 { color: inherit; }
.exp-dse .exp-reasons { margin-top: 8px; }
.exp-dse .exp-reasons li + li { margin-top: 4px; }

/* Overview figures */
.exp-dse .exp-figures { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 120px), 1fr)); gap: 10px; margin: 0 0 20px; padding: 0; list-style: none; }
.exp-dse .exp-figure { display: flex; flex-direction: column; gap: 2px; margin: 0; padding: 12px 14px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-card); }
.exp-dse .exp-figure strong { font-size: 22px; line-height: 1.15; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--sc-ink); }
.exp-dse .exp-figure span { font-size: 12.5px; color: var(--sc-muted); }
.exp-dse .exp-figure.is-attention strong { color: var(--sc-bad); }

/* Buttons */
.exp-dse .exp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; margin: 0;
    padding: 6px 14px; border: 1px solid #c9ced6; border-radius: 9px; background: #fff; color: var(--sc-ink);
    font: 600 13.5px/1.2 inherit; font-family: inherit; cursor: pointer; white-space: nowrap; text-decoration: none;
}
.exp-dse a.exp-btn { color: var(--sc-ink); }
.exp-dse .exp-btn:hover:not([disabled]):not(.is-disabled) { border-color: var(--sc-accent); color: var(--sc-accent-hover); }
.exp-dse .exp-btn[disabled], .exp-dse .exp-btn.is-disabled { opacity: .55; cursor: not-allowed; }
.exp-dse .exp-btn-primary, .exp-dse a.exp-btn-primary { border-color: var(--sc-accent); background: var(--sc-accent); color: #fff; }
.exp-dse .exp-btn-primary:hover:not([disabled]):not(.is-disabled) { border-color: var(--sc-accent-hover); background: var(--sc-accent-hover); color: #fff; }
.exp-dse .exp-btn-danger { border-color: var(--sc-bad); background: var(--sc-bad); color: #fff; }
.exp-dse .exp-btn-danger:hover:not([disabled]):not(.is-disabled) { border-color: #7f1d1d; background: #7f1d1d; color: #fff; }
.exp-dse .exp-btn-outline-danger { border-color: var(--sc-bad); color: var(--sc-bad); }
.exp-dse .exp-btn-outline-danger:hover:not([disabled]):not(.is-disabled) { background: var(--sc-bad); border-color: var(--sc-bad); color: #fff; }
.exp-dse .exp-btn-small { min-height: 30px; padding: 4px 10px; font-size: 12.5px; }
.exp-dse .exp-btn svg { flex: 0 0 auto; }
.exp-dse .exp-btn { max-width: 100%; }
/* Sorting, the settings snippet, the confirmation */
.exp-dse .exp-sortbar { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0 0 12px; font-size: 13px; color: var(--sc-muted); }
.exp-dse .exp-sortbar a, .exp-dse .exp-sortbar span.current { display: inline-flex; align-items: center; gap: 4px; min-height: 30px; padding: 2px 10px; border: 1px solid #c9ced6; border-radius: 999px; text-decoration: none; color: var(--sc-ink); background: var(--sc-card); }
.exp-dse .exp-sortbar span.current { border-color: var(--sc-accent); background: var(--sc-accent); color: #fff; font-weight: 650; }
.exp-dse pre.exp-code { margin: 8px 0 0; padding: 10px 12px; overflow-x: auto; border: 1px solid var(--sc-line); border-radius: 9px; background: var(--sc-soft); color: var(--sc-ink); font: 12.5px/1.6 var(--sc-mono); white-space: pre; }
.exp-dse ol.exp-steps { margin: 6px 0 0; padding-left: 22px; }
.exp-dse ol.exp-steps li + li { margin-top: 6px; }
.exp-dse .exp-confirm-list { margin: 10px 0 0; padding-left: 20px; }
.exp-dse .exp-confirm-list li + li { margin-top: 3px; }
.exp-dse .exp-row.is-self { box-shadow: inset 4px 0 0 var(--sc-info), 0 1px 2px rgba(16, 24, 40, 0.04); }
.exp-dse .exp-row.is-expired { background: var(--sc-soft); }
.exp-dse .exp-select input[disabled] { cursor: not-allowed; opacity: .5; }
.exp-dse details.exp-panel > summary { cursor: pointer; }
.exp-dse details.exp-panel > summary h2 { display: inline; }

@media (max-width: 600px) { .exp-dse .exp-btn { white-space: normal; text-align: center; } }
.exp-dse .exp-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.exp-dse .exp-actions form { display: contents; }
.exp-dse .exp-actionbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px;
    margin: 0 0 18px; padding: 12px 14px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-soft); }
.exp-dse .exp-actionbar .exp-meta { flex: 1 1 260px; }
.exp-dse .exp-meta { color: var(--sc-muted); font-size: 13px; }

/* Controls */
.exp-dse .exp-toolbar {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr)); gap: 14px 16px; align-items: end;
    margin: 0 0 20px; padding: 16px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-card);
}
.exp-dse .exp-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; margin: 0; padding: 0; border: 0; }
.exp-dse fieldset.exp-field { background: none; box-shadow: none; border-radius: 0; }
.exp-dse fieldset.exp-field > legend { float: left; width: 100%; margin: 0 0 5px; padding: 0; background: none; }
.exp-dse fieldset.exp-field > legend + * { clear: both; }
.exp-dse .exp-field > label, .exp-dse .exp-field > legend { padding: 0; font-size: 12.5px; font-weight: 650; color: var(--sc-ink); }
.exp-dse .exp-field select,
.exp-dse .exp-field input[type="search"],
.exp-dse .exp-field input[type="text"] {
    width: 100%; min-height: 38px; margin: 0; padding: 6px 10px; border: 1px solid #8f96a3; border-radius: 9px;
    background: #fff; color: var(--sc-ink); font: inherit; font-size: 14px; box-shadow: none;
}
.exp-dse .exp-field select:focus, .exp-dse .exp-field input:focus { border-color: var(--sc-accent); outline: 3px solid var(--sc-ring); outline-offset: 0; }
.exp-dse .exp-field input[aria-invalid="true"] { border-color: var(--sc-bad); box-shadow: inset 0 0 0 1px var(--sc-bad); }
.exp-dse .exp-field-wide { grid-column: 1 / -1; }
.exp-dse .exp-form-fields { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; max-width: 640px; }
.exp-dse .exp-help { font-size: 13px; color: var(--sc-muted); max-width: 72ch; }
.exp-dse .exp-field-error { font-size: 13px; font-weight: 650; color: var(--sc-bad); }
.exp-dse .exp-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.exp-dse .exp-chip { position: relative; display: inline-flex; margin: 0; }
.exp-dse .exp-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.exp-dse .exp-chip span { display: inline-flex; align-items: center; min-height: 32px; padding: 4px 12px; border: 1px solid #c9ced6; border-radius: 999px; background: #fff; color: var(--sc-ink); font-size: 13px; font-weight: 400; cursor: pointer; }
.exp-dse .exp-chip input:checked + span { border-color: var(--sc-accent); background: var(--sc-accent); color: #fff; font-weight: 650; }
.exp-dse .exp-chip input:focus-visible + span { outline: 3px solid var(--sc-ring); outline-offset: 2px; }
.exp-dse .exp-filter-count { grid-column: 1 / -1; margin: 0; font-size: 13px; color: var(--sc-muted); }

/* The section cards */
.exp-dse .exp-rows { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; margin: 0; padding: 0; list-style: none; }
.exp-dse .exp-row {
    min-width: 0; margin: 0; padding: 14px 16px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-card);
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
}
.exp-dse .exp-row.is-attention { box-shadow: inset 4px 0 0 var(--sc-warn), 0 1px 2px rgba(16, 24, 40, 0.04); }
.exp-dse .exp-row.is-selected { border-color: var(--sc-accent); }
.exp-dse .exp-row-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 10px 16px; }
.exp-dse .exp-row-title { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; min-width: 0; flex: 1 1 320px; }
.exp-dse .exp-row-title h3 a { color: var(--sc-ink); text-decoration: none; }
.exp-dse .exp-row-title h3 a:hover { color: var(--sc-accent-hover); text-decoration: underline; }
.exp-dse .exp-select { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; margin: -4px 0 -4px -6px; border-radius: 8px; cursor: pointer; }
.exp-dse .exp-select:hover { background: var(--sc-soft); }
.exp-dse .exp-select input { width: 18px; height: 18px; margin: 0; accent-color: var(--sc-accent); cursor: pointer; }
.exp-dse .exp-badges { display: inline-flex; flex-wrap: wrap; gap: 6px; margin: 0; padding: 0; list-style: none; }
.exp-dse .exp-badge { display: inline-flex; align-items: center; gap: 5px; margin: 0; padding: 2px 9px; border-radius: 999px; font-size: 12px; font-weight: 650; line-height: 1.5; white-space: nowrap; background: var(--sc-soft); color: var(--sc-muted); }
.exp-dse .exp-badge.is-ok { background: var(--sc-ok-bg); color: var(--sc-ok); }
.exp-dse .exp-badge.is-warn { background: var(--sc-warn-bg); color: var(--sc-warn); }
.exp-dse .exp-badge.is-bad { background: var(--sc-bad-bg); color: var(--sc-bad); }
.exp-dse .exp-badge.is-info { background: var(--sc-info-bg); color: var(--sc-info); }
.exp-dse .exp-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 165px), 1fr)); gap: 8px 18px; margin: 12px 0 0; }
.exp-dse .exp-facts > div { min-width: 0; }
.exp-dse .exp-facts dt { margin: 0; font-size: 12px; font-weight: 650; text-transform: uppercase; letter-spacing: .04em; color: var(--sc-muted); }
.exp-dse .exp-facts dd { margin: 2px 0 0; overflow-wrap: anywhere; }
.exp-dse .exp-facts dd code { color: var(--sc-muted); }
.exp-dse .exp-panel { margin: 0 0 18px; padding: 14px 16px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-card); }
.exp-dse .exp-panel > .exp-facts { margin-top: 0; }
.exp-dse .exp-fn { display: inline-block; margin: 0 4px 4px 0; padding: 1px 8px; border-radius: 6px; background: var(--sc-soft); color: var(--sc-ink); font: 12.5px/1.6 var(--sc-mono); white-space: nowrap; }
.exp-dse .exp-empty { margin: 0; padding: 18px 16px; border: 1px dashed #c9ced6; border-radius: var(--sc-radius); color: var(--sc-muted); text-align: center; }

/* Tables */
.exp-dse .exp-table-wrap { overflow-x: auto; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-card); }
.exp-dse .exp-table { width: 100%; margin: 0; border-collapse: collapse; }
.exp-dse .exp-table th, .exp-dse .exp-table td { padding: 9px 12px; border: 0; border-bottom: 1px solid var(--sc-line); text-align: left; vertical-align: top; background: transparent; color: var(--sc-ink); }
.exp-dse .exp-table th { font-size: 12px; font-weight: 650; text-transform: uppercase; letter-spacing: .04em; color: var(--sc-muted); background: var(--sc-soft); white-space: nowrap; }
.exp-dse .exp-table tr:last-child td { border-bottom: 0; }
.exp-dse .exp-table .exp-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.exp-dse .exp-table img { vertical-align: -3px; }

/* Page size and pages */
.exp-dse .exp-listfoot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 18px; margin: 14px 0 0; }
.exp-dse .exp-sizes { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0; font-size: 13px; color: var(--sc-muted); }
.exp-dse .exp-sizes a, .exp-dse .exp-sizes span.current { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; min-height: 30px; padding: 2px 8px; border: 1px solid #c9ced6; border-radius: 8px; text-decoration: none; }
.exp-dse .exp-sizes span.current { border-color: var(--sc-accent); background: var(--sc-accent); color: #fff; font-weight: 650; }
.exp-dse .exp-pager { min-width: 0; }
.exp-dse .exp-pager .pagenavigator { margin: 0; }
.exp-dse .exp-pager a { color: var(--sc-accent-hover); }
.exp-dse .exp-pager span.text, .exp-dse .exp-pager span.text a { color: var(--sc-accent-hover); }
.exp-dse .exp-pager span.disabled, .exp-dse .exp-pager span.text.disabled { color: var(--sc-muted); }
.exp-dse .exp-pager span.current { color: var(--sc-ink); }

/* The buttons under the list */
.exp-dse .exp-bottombar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; margin: 18px 0 0; padding: 12px 14px; border: 1px solid var(--sc-line); border-radius: var(--sc-radius); background: var(--sc-soft); }
.exp-dse .exp-bottombar .exp-meta { flex: 1 1 280px; }

@media (max-width: 600px) {
    .exp-dse.exp-standalone { margin: 8px; padding: 14px 12px; }
    .exp-dse .exp-row { padding: 12px; }
    .exp-dse .exp-row-head .exp-actions { width: 100%; }
    .exp-dse .exp-row-head .exp-actions .exp-btn { flex: 1 1 0; }
    .exp-dse .exp-actionbar .exp-actions, .exp-dse .exp-bottombar .exp-actions { width: 100%; }
    .exp-dse .exp-actionbar .exp-btn, .exp-dse .exp-bottombar .exp-btn { flex: 1 1 auto; }
    .exp-dse .exp-table th, .exp-dse .exp-table td { padding: 8px 9px; }
}
/* The DSE dashboard: what the editor does, side by side */
.exp-dse .exp-rows.exp-cando { grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); }
.exp-dse .exp-cando h3 { margin-bottom: 4px; }
</style>
{/literal}
