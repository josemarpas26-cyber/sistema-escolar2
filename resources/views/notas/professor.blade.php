@extends('layouts.app')

@section('page-title', 'Lançamento de Notas')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════════
   DESIGN TOKENS
═══════════════════════════════════════════════════════════════ */
:root {
  --font-body:   'DM Sans',  system-ui, sans-serif;
  --font-mono:   'DM Mono',  monospace;

  /* Palette */
  --ink-900:  #0d1117;
  --ink-800:  #1c2333;
  --ink-700:  #2d3748;
  --ink-500:  #64748b;
  --ink-400:  #94a3b8;
  --ink-200:  #e2e8f0;
  --ink-100:  #f1f5f9;
  --ink-50:   #f8fafc;

  --blue-600: #2563eb;
  --blue-500: #3b82f6;
  --blue-100: #dbeafe;
  --blue-50:  #eff6ff;

  --green-600: #16a34a;
  --green-500: #22c55e;
  --green-100: #dcfce7;
  --green-50:  #f0fdf4;

  --red-600:  #dc2626;
  --red-500:  #ef4444;
  --red-100:  #fee2e2;
  --red-50:   #fef2f2;

  --amber-500: #f59e0b;
  --amber-100: #fef3c7;
  --amber-50:  #fffbeb;

  /* Surfaces */
  --surface-1: #ffffff;
  --surface-2: var(--ink-50);
  --surface-3: var(--ink-100);

  /* Shadows */
  --shadow-xs: 0 1px 2px rgba(0,0,0,.05);
  --shadow-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
  --shadow-md: 0 4px 6px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.05);
  --shadow-lg: 0 10px 15px rgba(0,0,0,.08), 0 4px 6px rgba(0,0,0,.04);
  --shadow-ring: 0 0 0 3px rgba(37,99,235,.15);

  /* Border */
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-xl: 20px;

  /* Transitions */
  --ease-out: cubic-bezier(0.16,1,0.3,1);
  --ease-in-out: cubic-bezier(0.45,0,0.55,1);
  --dur-fast: 120ms;
  --dur-normal: 220ms;
  --dur-slow: 380ms;
}

/* ═══ RESET SCOPE ═══ */
#np-root * { box-sizing: border-box; }
#np-root { font-size: 14px; color: var(--ink-800); font-family: var(--font-body); }

/* ═══════════════════════════════════════════════════════════════
   HEADER SELECTOR
═══════════════════════════════════════════════════════════════ */
.np-selector-bar {
  background: var(--surface-1);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  box-shadow: var(--shadow-sm);
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
}
.np-selector-bar::before {
  content: '';
  position: absolute;
  inset: 0 0 auto 0;
  height: 3px;
  background: linear-gradient(90deg, var(--blue-500), var(--blue-600));
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.np-selector-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--ink-400);
}

.np-select-wrap {
  position: relative;
  flex: 1;
  min-width: 180px;
}
.np-select-wrap i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-400);
  font-size: 13px;
  pointer-events: none;
  z-index: 1;
}
.np-select {
  width: 100%;
  height: 40px;
  padding: 0 12px 0 36px;
  border: 1.5px solid var(--ink-200);
  border-radius: var(--radius-md);
  background: var(--surface-2);
  font-size: 13.5px;
  font-weight: 500;
  color: var(--ink-800);
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  transition: border-color var(--dur-fast), box-shadow var(--dur-fast), background var(--dur-fast);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 32px;
}
.np-select:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: var(--shadow-ring);
  background-color: var(--surface-1);
}
.np-select:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.np-sep {
  width: 1px;
  height: 32px;
  background: var(--ink-200);
  flex-shrink: 0;
}

/* Restore badge */
.np-restore-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--blue-600);
  font-weight: 500;
  animation: np-fadein .4s var(--ease-out);
}
.np-restore-hint i { font-size: 10px; }

/* ═══════════════════════════════════════════════════════════════
   CONTEXT BANNER
═══════════════════════════════════════════════════════════════ */
.np-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: linear-gradient(135deg, var(--blue-600) 0%, #1d4ed8 100%);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 14px rgba(37,99,235,.25);
  animation: np-slidein .3s var(--ease-out);
}
.np-context-left { display: flex; flex-direction: column; gap: 3px; }
.np-context-title {
  font-size: 17px;
  font-weight: 700;
  color: #fff;
  line-height: 1.2;
}
.np-context-sub {
  font-size: 12px;
  color: rgba(255,255,255,.7);
}
.np-context-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.np-context-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(255,255,255,.18);
  color: #fff;
  border: 1px solid rgba(255,255,255,.25);
}

/* ═══════════════════════════════════════════════════════════════
   LAYOUT — 2 COLUNAS
═══════════════════════════════════════════════════════════════ */
.np-layout {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 20px;
  align-items: start;
}
@media (max-width: 1100px) {
  .np-layout { grid-template-columns: 1fr; }
}

/* ═══════════════════════════════════════════════════════════════
   PAINEL LATERAL — STATS
═══════════════════════════════════════════════════════════════ */
.np-sidebar {
  position: sticky;
  top: 88px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.np-stats-card {
  background: var(--surface-1);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.np-stats-head {
  padding: 14px 16px 10px;
  border-bottom: 1px solid var(--ink-100);
  display: flex;
  align-items: center;
  gap: 8px;
}
.np-stats-head-ico {
  width: 30px;
  height: 30px;
  border-radius: var(--radius-sm);
  background: var(--blue-50);
  color: var(--blue-600);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}
.np-stats-head-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ink-500);
}

.np-kpi-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: var(--ink-100);
}
.np-kpi {
  background: var(--surface-1);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  transition: background var(--dur-fast);
}
.np-kpi:hover { background: var(--ink-50); }
.np-kpi-val {
  font-size: 26px;
  font-weight: 700;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  font-family: var(--font-mono);
}
.np-kpi-val.blue  { color: var(--blue-600); }
.np-kpi-val.green { color: var(--green-600); }
.np-kpi-val.red   { color: var(--red-600); }
.np-kpi-val.amber { color: var(--amber-500); }
.np-kpi-lbl { font-size: 10.5px; font-weight: 600; color: var(--ink-400); text-transform: uppercase; letter-spacing: .04em; }

/* Progress bar */
.np-progress-wrap {
  padding: 14px 16px;
  border-top: 1px solid var(--ink-100);
}
.np-progress-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}
.np-progress-lbl { font-size: 11px; font-weight: 600; color: var(--ink-500); text-transform: uppercase; letter-spacing: .04em; }
.np-progress-pct { font-size: 12px; font-weight: 700; color: var(--blue-600); font-family: var(--font-mono); }
.np-progress-track {
  height: 6px;
  border-radius: 3px;
  background: var(--ink-100);
  overflow: hidden;
}
.np-progress-fill {
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(90deg, var(--blue-500), var(--blue-600));
  transition: width .6s var(--ease-out);
}

/* Trim status */
.np-trim-status {
  padding: 12px 16px;
  border-top: 1px solid var(--ink-100);
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.np-trim-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.np-trim-name { font-size: 12px; font-weight: 500; color: var(--ink-600); }
.np-trim-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10.5px;
  font-weight: 700;
}
.np-trim-badge.open  { background: var(--green-100); color: var(--green-600); }
.np-trim-badge.lock  { background: var(--red-100);   color: var(--red-600); }
.np-trim-badge.empty { background: var(--ink-100);   color: var(--ink-400); }

/* ═══════════════════════════════════════════════════════════════
   MAIN PANEL
═══════════════════════════════════════════════════════════════ */
.np-main {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Unsaved warning */
.np-unsaved-bar {
  display: none;
  align-items: center;
  gap: 10px;
  background: var(--amber-50);
  border: 1px solid var(--amber-100);
  border-bottom: none;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  padding: 10px 16px;
  font-size: 12.5px;
  color: #92400e;
  font-weight: 500;
}
.np-unsaved-bar.visible { display: flex; }
.np-unsaved-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--amber-500);
  animation: np-pulse 1.4s ease-in-out infinite;
  flex-shrink: 0;
}

/* Card principal */
.np-card {
  background: var(--surface-1);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.np-unsaved-bar.visible + .np-card {
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  border-top: none;
}

/* ═══ TAB NAV ═══ */
.np-tabs {
  display: flex;
  border-bottom: 1px solid var(--ink-200);
  background: var(--ink-50);
  padding: 0 6px;
  gap: 2px;
}
.np-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 16px;
  height: 48px;
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-400);
  border: none;
  background: transparent;
  cursor: pointer;
  border-bottom: 2.5px solid transparent;
  transition: color var(--dur-fast), border-color var(--dur-fast);
  position: relative;
  white-space: nowrap;
  letter-spacing: -.01em;
}
.np-tab:hover { color: var(--ink-700); }
.np-tab.active {
  color: var(--blue-600);
  border-bottom-color: var(--blue-600);
}
.np-tab-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  font-size: 10px;
  font-weight: 700;
  font-family: var(--font-mono);
  background: var(--ink-200);
  color: var(--ink-500);
  transition: background var(--dur-fast), color var(--dur-fast);
}
.np-tab.active .np-tab-num {
  background: var(--blue-100);
  color: var(--blue-600);
}
.np-tab-check {
  color: var(--green-600);
  font-size: 11px;
  display: none;
}
.np-tab.done .np-tab-check { display: inline; }
.np-tab.done .np-tab-num   { display: none; }

/* ═══ TOOLBAR ═══ */
.np-toolbar {
  padding: 12px 16px;
  border-bottom: 1px solid var(--ink-100);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  background: var(--surface-1);
}
.np-toolbar-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.np-toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ─── BUTTONS ─── */
.np-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 14px;
  border-radius: var(--radius-md);
  font-size: 12.5px;
  font-weight: 600;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all var(--dur-fast) var(--ease-out);
  white-space: nowrap;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}
.np-btn:disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }

/* Ghost */
.np-btn-ghost {
  background: var(--surface-2);
  border-color: var(--ink-200);
  color: var(--ink-600);
}
.np-btn-ghost:hover {
  background: var(--surface-3);
  border-color: var(--ink-300);
  color: var(--ink-800);
}
.np-btn-outline {
  background: transparent;
  border-color: var(--ink-200);
  color: var(--ink-700);
}
.np-btn-outline:hover {
  background: var(--surface-2);
  border-color: var(--ink-300);
  color: var(--ink-800);
}

/* Primary */
.np-btn-primary {
  background: var(--blue-600);
  border-color: var(--blue-600);
  color: #fff;
  box-shadow: 0 2px 6px rgba(37,99,235,.25);
}
.np-btn-primary:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
  box-shadow: 0 4px 12px rgba(37,99,235,.35);
  transform: translateY(-1px);
}
.np-btn-primary:active { transform: translateY(0); }

/* Success */
.np-btn-success {
  background: var(--green-600);
  border-color: var(--green-600);
  color: #fff;
  box-shadow: 0 2px 6px rgba(22,163,74,.2);
}
.np-btn-success:hover {
  background: #15803d;
  box-shadow: 0 4px 12px rgba(22,163,74,.3);
  transform: translateY(-1px);
}

/* Danger */
.np-btn-danger {
  background: var(--surface-1);
  border-color: var(--red-200, #fecaca);
  color: var(--red-600);
}
.np-btn-danger:hover {
  background: var(--red-50);
  border-color: var(--red-300, #fca5a5);
}

/* Loading state */
.np-btn-loading { pointer-events: none; }
.np-btn-loading .np-btn-ico { display: none; }
.np-btn-loading::after {
  content: '';
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: np-spin .7s linear infinite;
}

/* Badge on button */
.np-btn-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  background: rgba(255,255,255,.25);
  color: #fff;
}

/* ═══ SEARCH BAR ═══ */
.np-search-wrap {
  position: relative;
}
.np-search-wrap i {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-400);
  font-size: 12px;
  pointer-events: none;
}
.np-search {
  height: 36px;
  width: 200px;
  padding: 0 10px 0 30px;
  border: 1.5px solid var(--ink-200);
  border-radius: var(--radius-md);
  font-size: 13px;
  background: var(--surface-2);
  color: var(--ink-800);
  transition: border-color var(--dur-fast), box-shadow var(--dur-fast), width var(--dur-slow) var(--ease-out);
}
.np-search:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: var(--shadow-ring);
  background: var(--surface-1);
  width: 260px;
}
.np-search::placeholder { color: var(--ink-400); }

/* ═══ TABLE WRAPPER ═══ */
.np-tbl-scroll {
  overflow-x: auto;
  overflow-y: visible;
  -webkit-overflow-scrolling: touch;
}
.np-tbl-scroll::-webkit-scrollbar { height: 8px; }
.np-tbl-scroll::-webkit-scrollbar-track { background: var(--ink-100); }
.np-tbl-scroll::-webkit-scrollbar-thumb { background: var(--ink-300, #cbd5e1); border-radius: 4px; }

/* ═══ TABLE ═══ */
.np-tbl {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}

/* Header */
.np-tbl thead tr { background: var(--ink-50); }
.np-tbl thead th {
  padding: 10px 12px;
  text-align: center;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--ink-400);
  border-bottom: 1.5px solid var(--ink-200);
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 3;
  background: var(--ink-50);
}
.np-tbl thead th.th-left { text-align: left; }
.np-tbl thead th.th-sticky {
  position: sticky;
  left: 0;
  z-index: 5;
  background: var(--ink-50);
  box-shadow: 2px 0 4px rgba(0,0,0,.06);
}

/* Tooltip on th */
.np-th-tooltip {
  position: relative;
  cursor: help;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.np-th-tooltip i { font-size: 9px; color: var(--ink-300, #cbd5e1); }
.np-th-tooltip::after {
  content: attr(data-tip);
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: var(--ink-900);
  color: #fff;
  font-size: 10.5px;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  padding: 5px 10px;
  border-radius: var(--radius-sm);
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity var(--dur-fast);
  z-index: 20;
}
.np-th-tooltip::before {
  content: '';
  position: absolute;
  bottom: calc(100% + 3px);
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: var(--ink-900);
  pointer-events: none;
  opacity: 0;
  transition: opacity var(--dur-fast);
  z-index: 20;
}
.np-th-tooltip:hover::after,
.np-th-tooltip:hover::before { opacity: 1; }

/* Rows */
.np-tbl tbody tr {
  border-bottom: 1px solid var(--ink-100);
  transition: background var(--dur-fast);
}
.np-tbl tbody tr:last-child { border-bottom: none; }
.np-tbl tbody tr:nth-child(even) { background: var(--ink-50); }
.np-tbl tbody tr:hover { background: var(--blue-50) !important; }
.np-tbl tbody tr.np-row-readonly { background: var(--ink-50) !important; }
.np-tbl tbody tr.np-row-readonly:hover { background: var(--ink-100) !important; }

/* Flash animation after save */
.np-tbl tbody tr.np-row-saved {
  animation: np-row-flash .9s var(--ease-out);
}
/* Filtered-out rows */
.np-tbl tbody tr.np-row-hidden { display: none; }

/* Cells */
.np-tbl tbody td {
  padding: 8px 6px;
  vertical-align: middle;
  text-align: center;
}
.np-tbl tbody td.td-left { text-align: left; padding-left: 12px; }
.np-tbl tbody td.td-sticky {
  position: sticky;
  left: 0;
  background: inherit;
  z-index: 2;
  box-shadow: 2px 0 4px rgba(0,0,0,.05);
}

/* Aluno cell */
.np-aluno-cell {
  display: flex;
  align-items: center;
  gap: 9px;
  min-width: 180px;
}
.np-aluno-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
  letter-spacing: -.01em;
}
.np-aluno-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}
.np-aluno-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-800);
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 160px;
}
.np-aluno-proc {
  font-size: 10.5px;
  color: var(--ink-400);
  font-family: var(--font-mono);
  font-weight: 400;
}

/* Lock indicator */
.np-lock-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 9.5px;
  font-weight: 700;
  background: var(--ink-100);
  color: var(--ink-400);
  white-space: nowrap;
}
.np-lock-badge.partial { background: var(--amber-50); color: #92400e; }

.np-notification-stack {
  margin-bottom: 18px;
  display: grid;
  gap: 10px;
}
.np-notification-item {
  background: var(--blue-50);
  border: 1px solid var(--blue-100);
  border-radius: var(--radius-md);
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}
.np-notification-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--blue-600);
}
.np-notification-desc {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: var(--ink-700);
}
.np-notification-meta {
  margin-top: 6px;
  font-size: 11px;
  color: var(--ink-500);
}

/* ─── INPUT DE NOTA ─── */
.np-input-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.np-nota-input {
  width: 72px;
  height: 34px;
  border: 1.5px solid var(--ink-200);
  border-radius: var(--radius-sm);
  text-align: center;
  font-size: 13.5px;
  font-weight: 600;
  font-family: var(--font-mono);
  color: var(--ink-800);
  background: var(--surface-1);
  transition: border-color var(--dur-fast), box-shadow var(--dur-fast), background var(--dur-fast), color var(--dur-fast);
  -moz-appearance: textfield;
}
.np-nota-input::-webkit-inner-spin-button,
.np-nota-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.np-nota-input:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: var(--shadow-ring);
  background: var(--blue-50);
  z-index: 1;
}
.np-nota-input:disabled {
  background: var(--ink-100);
  border-color: var(--ink-100);
  color: var(--ink-400);
  cursor: not-allowed;
}
/* Color states while typing */
.np-nota-input.val-ok   { border-color: var(--green-500); color: var(--green-600); background: var(--green-50); }
.np-nota-input.val-fail { border-color: var(--red-400, #f87171); color: var(--red-600); background: var(--red-50); }

/* ─── COMPUTED CELL ─── */
.np-computed {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 58px;
  height: 34px;
  border-radius: var(--radius-sm);
  font-size: 13.5px;
  font-weight: 700;
  font-family: var(--font-mono);
  border: 1.5px solid transparent;
  transition: all var(--dur-normal);
  padding: 0 8px;
}
.np-computed.c-ok    { background: var(--green-100); color: var(--green-600); border-color: var(--green-200, #bbf7d0); }
.np-computed.c-fail  { background: var(--red-100);   color: var(--red-600);   border-color: var(--red-200, #fecaca); }
.np-computed.c-empty { background: var(--ink-100);   color: var(--ink-400);   border-color: var(--ink-200); }

/* ─── STATUS BADGE ─── */
.np-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 10.5px;
  font-weight: 700;
  white-space: nowrap;
}
.np-status-badge.ok   { background: var(--green-100); color: var(--green-600); }
.np-status-badge.fail { background: var(--red-100);   color: var(--red-600); }
.np-status-badge.pend { background: var(--ink-100);   color: var(--ink-400); }

/* ─── FOOTER DE MÉDIAS ─── */
.np-tbl tfoot tr { background: var(--blue-50); }
.np-tbl tfoot td {
  padding: 10px 6px;
  border-top: 1.5px solid var(--blue-100);
  font-size: 11px;
  font-weight: 700;
  text-align: center;
  color: var(--blue-600);
  font-family: var(--font-mono);
}
.np-tbl tfoot td.td-left {
  text-align: left;
  padding-left: 12px;
  font-family: var(--font-body);
  font-size: 10.5px;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--ink-400);
}
.np-tbl tfoot td.td-sticky {
  position: sticky;
  left: 0;
  background: var(--blue-50);
  z-index: 2;
  box-shadow: 2px 0 4px rgba(0,0,0,.05);
}

/* ─── FORM FOOTER ─── */
.np-form-footer {
  padding: 14px 16px;
  border-top: 1px solid var(--ink-100);
  background: var(--ink-50);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
.np-form-footer-info {
  font-size: 12px;
  color: var(--ink-400);
  display: flex;
  align-items: center;
  gap: 6px;
}
.np-form-footer-info strong { color: var(--ink-600); }

/* ═══ INIT PAUTA CARD ═══ */
.np-init-card {
  background: var(--surface-1);
  border: 1.5px dashed var(--ink-200);
  border-radius: var(--radius-lg);
  padding: 48px 24px;
  text-align: center;
  animation: np-fadein .4s var(--ease-out);
}
.np-init-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--blue-50);
  color: var(--blue-500);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin: 0 auto 16px;
}
.np-init-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--ink-800);
  margin-bottom: 6px;
}
.np-init-sub {
  font-size: 13.5px;
  color: var(--ink-500);
  max-width: 360px;
  margin: 0 auto 20px;
  line-height: 1.55;
}

/* ═══ EMPTY STATE ═══ */
.np-empty {
  padding: 48px 24px;
  text-align: center;
}
.np-empty-icon {
  font-size: 2.5rem;
  color: var(--ink-300, #cbd5e1);
  margin-bottom: 14px;
}
.np-empty-title { font-size: 15px; font-weight: 600; color: var(--ink-600); margin-bottom: 4px; }
.np-empty-sub { font-size: 13px; color: var(--ink-400); }

/* ═══ OP PANEL — Finalizar / Reabrir ═══ */
.np-op-panel {
  background: var(--surface-1);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.np-op-panel-head {
  padding: 12px 16px;
  background: var(--ink-50);
  border-bottom: 1px solid var(--ink-200);
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ink-500);
}
.np-op-body {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.np-op-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.np-op-lbl {
  font-size: 10.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--ink-400);
}
.np-op-select {
  height: 34px;
  padding: 0 10px;
  border: 1.5px solid var(--ink-200);
  border-radius: var(--radius-sm);
  font-size: 12.5px;
  font-weight: 500;
  background: var(--surface-2);
  color: var(--ink-800);
  width: 100%;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
}
.np-op-select:focus {
  outline: none;
  border-color: var(--blue-500);
  box-shadow: var(--shadow-ring);
}
.np-op-divider {
  height: 1px;
  background: var(--ink-100);
  margin: 2px 0;
}

/* ═══ KEYBOARD HINT ═══ */
.np-kbd-hint {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--ink-400);
}
.np-kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1px 5px;
  border: 1px solid var(--ink-300, #cbd5e1);
  border-radius: 4px;
  font-size: 10px;
  font-family: var(--font-mono);
  font-weight: 600;
  color: var(--ink-500);
  background: var(--ink-50);
  box-shadow: 0 1px 0 var(--ink-200);
}

/* ═══ ANIMATIONS ═══ */
@keyframes np-fadein {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes np-slidein {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes np-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: .4; }
}
@keyframes np-spin {
  to { transform: rotate(360deg); }
}
@keyframes np-row-flash {
  0%   { background: var(--green-100) !important; }
  60%  { background: var(--green-50) !important; }
  100% { background: inherit; }
}
@keyframes np-shake {
  0%,100% { transform: translateX(0); }
  20%     { transform: translateX(-4px); }
  60%     { transform: translateX(4px); }
}

/* Entrada de tab */
.np-tab-content { animation: np-fadein .2s var(--ease-out); }
</style>
@endpush

@section('content')
@php
    $podeReabrirNotas   = auth()->user()->can('notas.reabrir');
    $podeFinalizarNotas = auth()->user()->can('notas.editar');

    /* ── Turma/disciplina seleccionadas ── */
    $turmaId      = request('turma_id');
    $disciplinaId = request('disciplina_id');

    /* ── Tab activa (preserva estado após submit) ── */
    $activeTab = request('_tab', old('_tab', '1'));

    /* ── Cálculos de resumo ── */
    $totalAlunos = $notas ? $notas->count() : 0;

    /* Progresso: % de células preenchidas (todos os 9 campos por aluno) */
    $totalCampos = $totalAlunos * 9;
    $camposPreenchidos = 0;
    $aprovados   = 0;
    $reprovados  = 0;

    if ($notas) {
        foreach ($notas as $n) {
            foreach (['mac1','pp1','pt1','mac2','pp2','pt2','mac3','pp3','pg'] as $c) {
                if ($n->$c !== null) $camposPreenchidos++;
            }
            if ($n->cfd !== null) {
                if ($n->cfd >= 10) $aprovados++;
                else $reprovados++;
            }
        }
    }

    $progresso = $totalCampos > 0 ? round(($camposPreenchidos / $totalCampos) * 100) : 0;

    /* Cores por initial do nome */
    $avatarColors = [
        '#2563eb','#7c3aed','#db2777','#0891b2',
        '#059669','#d97706','#dc2626','#0d9488'
    ];

    /* ── Opções para os dropdowns de operações ── */
    $opcoesAlunos = $notas
        ? $notas->pluck('aluno')->filter()->unique('id')->sortBy('name')->values()
        : collect();

    /* ── Guarda se cada trimestre está bloqueado para todos ── */
    $t1AllLocked = $notas && $notas->count() > 0 && !$podeReabrirNotas
        && $notas->every(fn($n) => $n->status === 'finalizado' || ($n->bloqueado_t1 ?? false));
    $t2AllLocked = $notas && $notas->count() > 0 && !$podeReabrirNotas
        && $notas->every(fn($n) => $n->status === 'finalizado' || ($n->bloqueado_t2 ?? false));
    $t3AllLocked = $notas && $notas->count() > 0 && !$podeReabrirNotas
        && $notas->every(fn($n) => $n->status === 'finalizado' || ($n->bloqueado_t3 ?? false));
@endphp

<div id="np-root">
  <div class="mb-4 flex justify-end">
    <a href="{{ route('notas.avaliacoes-continuas.index', ['turma_id' => request('turma_id'), 'disciplina_id' => request('disciplina_id')]) }}"
       class="btn btn-outline">
      <i class="fas fa-list-ol mr-2"></i>
      Abrir tabela de avaliações contínuas
    </a>
  </div>

  {{-- ═══════════════════════════════════════════════
       SELECTOR BAR
  ═══════════════════════════════════════════════ --}}
  <form id="np-selector-form"
        method="GET"
        action="{{ route('notas.index') }}"
        x-data="npSelectorData()">

    <div class="np-selector-bar">

      {{-- Turma --}}
      <div class="np-select-wrap">
        <i class="fas fa-chalkboard"></i>
        <select name="turma_id"
                class="np-select"
                x-model="turmaId"
                @change="onTurmaChange"
                required>
          <option value="">Selecionar turma…</option>
          @foreach($atribuicoes->groupBy('turma_id') as $tId => $items)
            @php $t = $items->first()->turma; @endphp
            <option value="{{ $tId }}" {{ request('turma_id') == $tId ? 'selected' : '' }}>
              {{ $t->nome_completo }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Disciplina --}}
      <div class="np-select-wrap">
        <i class="fas fa-book-open"></i>
        <select name="disciplina_id"
                class="np-select"
                x-model="disciplinaId"
                @change="onDisciplinaChange"
                :disabled="!turmaId"
                required>
          <option value="">Selecionar disciplina…</option>
          @if(request('turma_id'))
            @foreach($atribuicoes->where('turma_id', request('turma_id')) as $atrib)
              <option value="{{ $atrib->disciplina_id }}"
                      {{ request('disciplina_id') == $atrib->disciplina_id ? 'selected' : '' }}>
                {{ $atrib->disciplina->nome }}
              </option>
            @endforeach
          @else
            {{-- Todas as disciplinas do professor para JS preencher --}}
            @foreach($atribuicoes as $atrib)
              <option value="{{ $atrib->disciplina_id }}"
                      data-turma="{{ $atrib->turma_id }}"
                      class="disc-option-all"
                      style="display:none">
                {{ $atrib->disciplina->nome }}
              </option>
            @endforeach
          @endif
        </select>
      </div>

      {{-- Tab oculta para preservar estado --}}
      <input type="hidden" name="_tab" :value="activeTab">

      {{-- Restore hint --}}
      <div class="np-restore-hint" x-show="restored" x-cloak>
        <i class="fas fa-magic"></i>
        <span>Última pauta restaurada</span>
      </div>

      {{-- Botão submeter --}}
      <button type="submit" class="np-btn np-btn-primary" :disabled="!turmaId || !disciplinaId">
        <i class="fas fa-arrow-right np-btn-ico"></i>
        Carregar
      </button>

    </div>
  </form>

  @if(($notificacoesDesbloqueio ?? collect())->isNotEmpty())
    <div class="np-notification-stack">
      @foreach($notificacoesDesbloqueio as $notificacao)
        @php $dadosNotificacao = $notificacao->data; @endphp
        <div class="np-notification-item">
          <div>
            <p class="np-notification-title">
              <i class="fas fa-bell mr-1"></i>
              {{ $dadosNotificacao['titulo'] ?? 'Pauta desbloqueada' }}
            </p>
            <p class="np-notification-desc">
              {{ $dadosNotificacao['descricao'] ?? 'A secretaria desbloqueou uma pauta para edição.' }}
            </p>
            <p class="np-notification-meta">
              {{ $dadosNotificacao['turma_nome'] ?? 'Turma' }} · {{ $dadosNotificacao['disciplina_nome'] ?? 'Disciplina' }}
              @if(!empty($dadosNotificacao['motivo']))
                · Motivo: {{ $dadosNotificacao['motivo'] }}
              @endif
            </p>
          </div>
          <div class="flex items-center gap-2">
            @if(!empty($dadosNotificacao['link']))
              <a href="{{ $dadosNotificacao['link'] }}" class="np-btn np-btn-primary">Abrir pauta</a>
            @endif
            <form method="POST" action="{{ route('notas.notificacoes.marcar-lida', $notificacao->id) }}">
              @csrf
              <button type="submit" class="np-btn np-btn-outline">Marcar como lida</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- ═══════════════════════════════════════════════
       CONTEXT BANNER (só quando pauta carregada)
  ═══════════════════════════════════════════════ --}}
  @if($notas && $turma && $disciplina)
  <div class="np-context-banner">
    <div class="np-context-left">
      <div class="np-context-title">{{ $turma->nome_completo }}</div>
      <div class="np-context-sub">{{ $disciplina->nome }} · {{ $disciplina->codigo }}</div>
    </div>
    <div class="np-context-right">
      <div class="np-context-pill">
        <i class="fas fa-graduation-cap"></i>
        {{ $turma->classe }}ª Classe
      </div>
      <div class="np-context-pill">
        <i class="fas fa-users"></i>
        {{ $totalAlunos }} alunos
      </div>
      @if($progresso === 100)
      <div class="np-context-pill" style="background:rgba(134,239,172,.25); border-color:rgba(134,239,172,.4)">
        <i class="fas fa-check-circle"></i>
        Pauta completa
      </div>
      @endif
    </div>
  </div>
  @endif

  
  {{-- ═══════════════════════════════════════════════
       CORPO — 2 colunas quando há pauta
  ═══════════════════════════════════════════════ --}}
  @if($notas && $turma && $disciplina)
  <div class="np-layout"
       x-data="npPautaData()"
       x-init="init">

    {{-- ────────────────────────────────────────────
         PAINEL PRINCIPAL
    ──────────────────────────────────────────── --}}
    <div class="np-main">

      {{-- Barra de "alterações não salvas" --}}
      <div class="np-unsaved-bar" :class="{ visible: dirty }">
        <span class="np-unsaved-dot"></span>
        <span>Alterações não salvas — <strong>clique em "Guardar" para não perder os dados.</strong></span>
      </div>

      {{-- Card principal --}}
      <div class="np-card">

        {{-- Tabs --}}
        <div class="np-tabs" role="tablist">
          @foreach ([1 => '1º Trimestre', 2 => '2º Trimestre', 3 => '3º Trimestre'] as $t => $label)
          @php
            $isDone = match($t) {
              1 => $notas->every(fn($n) => $n->mt1 !== null),
              2 => $notas->every(fn($n) => $n->mt2 !== null),
              3 => $notas->every(fn($n) => $n->mt3 !== null),
              default => false,
            };
          @endphp
          <button type="button"
                  class="np-tab {{ $isDone ? 'done' : '' }}"
                  :class="{ active: activeTab === '{{ $t }}' }"
                  @click="switchTab('{{ $t }}')"
                  role="tab">
            <i class="fas fa-check-circle np-tab-check"></i>
            <span class="np-tab-num">{{ $t }}</span>
            {{ $label }}
          </button>
          @endforeach
        </div>

        {{-- ════════════════════════════════
             1º TRIMESTRE
        ════════════════════════════════ --}}
        <div x-show="activeTab === '1'" x-cloak class="np-tab-content">

          <form id="form-t1"
                method="POST"
                action="{{ route('notas.lancarTrimestre', 1) }}"
                @submit="onFormSubmit($event, '1')">
            @csrf
            <input type="hidden" name="_tab" value="1">

            {{-- Toolbar --}}
            <div class="np-toolbar">
              <div class="np-toolbar-left">
                <div class="np-search-wrap">
                  <i class="fas fa-search"></i>
                  <input type="text"
                         class="np-search"
                         placeholder="Filtrar aluno…"
                         @input="filterRows($event.target.value)">
                </div>
                @if($t1AllLocked)
                <div class="np-lock-badge partial">
                  <i class="fas fa-lock"></i>
                  Trimestre bloqueado
                </div>
                @endif
              </div>
              <div class="np-toolbar-right">
                <div class="np-kbd-hint">
                  <span class="np-kbd">Tab</span>
                  <span>entre campos</span>
                </div>
                <button type="submit"
                        class="np-btn np-btn-success"
                        id="btn-save-t1"
                        :class="{ 'np-btn-loading': saving }"
                        :disabled="{{ $t1AllLocked ? 'true' : 'false' }} || saving">
                  <i class="fas fa-save np-btn-ico"></i>
                  Guardar 1º Trimestre
                </button>
              </div>
            </div>

            {{-- Tabela --}}
            <div class="np-tbl-scroll">
              <table class="np-tbl">
                <thead>
                  <tr>
                    <th class="th-left th-sticky" style="min-width:200px;width:200px">Aluno</th>
                    <th>
                      <span class="np-th-tooltip" data-tip="Média de Avaliações Contínuas — 1º Trimestre">
                        MAC1 <i class="fas fa-info-circle"></i>
                      </span>
                    </th>
                    <th>
                      <span class="np-th-tooltip" data-tip="Prova do Professor — 1º Trimestre">
                        PP1 <i class="fas fa-info-circle"></i>
                      </span>
                    </th>
                    <th>
                      <span class="np-th-tooltip" data-tip="Prova Trimestral — 1º Trimestre">
                        PT1 <i class="fas fa-info-circle"></i>
                      </span>
                    </th>
                    <th>
                      <span class="np-th-tooltip" data-tip="Média do 1º Trimestre (calculada automaticamente)">
                        MT1 <i class="fas fa-info-circle"></i>
                      </span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($notas as $idx => $nota)
                  @php
                    $t1Disponivel = $nota->trimestreEstaDisponivel(1);
                    $locked1 = !$t1Disponivel || (!$podeReabrirNotas && ($nota->status === 'finalizado' || ($nota->bloqueado_t1 ?? false)));
                    $initials = strtoupper(substr($nota->aluno->name ?? 'A', 0, 1));
                    $avatarColor = $avatarColors[crc32($nota->aluno->name ?? '') % count($avatarColors)];
                    $alunoTemFoto = filled($nota->aluno->foto_perfil ?? null);
                  @endphp
                  <tr class="{{ $locked1 ? 'np-row-readonly' : '' }}"
                      data-aluno="{{ strtolower($nota->aluno->name ?? '') }}">

                    <td class="td-left td-sticky">
                      <input type="hidden" name="notas[{{ $idx }}][id]" value="{{ $nota->id }}">
                      <div class="np-aluno-cell">
                        <div class="np-aluno-avatar" style="{{ $alunoTemFoto ? '' : 'background:'.$avatarColor }}">
                          @if($alunoTemFoto)
                            <img src="{{ $nota->aluno->foto_perfil_url }}" alt="Foto de {{ $nota->aluno->name }}" class="np-aluno-avatar-img">
                          @else
                            {{ $initials }}
                          @endif
                        </div>
                        <div>
                          <div class="np-aluno-name">{{ $nota->aluno->name }}</div>
                          <div class="np-aluno-proc">{{ $nota->aluno->numero_processo ?? '—' }}</div>
                        </div>
                      </div>
                    </td>

                    <td>
                      <div class="np-input-wrap">
                        <input type="number" step="0.01" min="-1" max="20"
                               name="notas[{{ $idx }}][mac1]" readonly title="Calculada automaticamente pela média das avaliações contínuas"
                               value="{{ $nota->mac1 }}"
                               class="np-nota-input {{ $nota->mac1 !== null ? ($nota->mac1 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                               {{ $locked1 ? 'disabled' : '' }}
                               @input="onNotaInput($event, {{ $idx }}, 't1')"
                               @blur="formatNotaInput($event)"
                               placeholder="—">
                      </div>
                    </td>
                    <td>
                      <div class="np-input-wrap">
                        <input type="number" step="0.01" min="-1" max="20"
                               name="notas[{{ $idx }}][pp1]"
                               value="{{ $nota->pp1 }}"
                               class="np-nota-input {{ $nota->pp1 !== null ? ($nota->pp1 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                               {{ $locked1 ? 'disabled' : '' }}
                               @input="onNotaInput($event, {{ $idx }}, 't1')"
                               @blur="formatNotaInput($event)"
                               placeholder="—">
                      </div>
                    </td>
                    <td>
                      <div class="np-input-wrap">
                        <input type="number" step="0.01" min="-1" max="20"
                               name="notas[{{ $idx }}][pt1]"
                               value="{{ $nota->pt1 }}"
                               class="np-nota-input {{ $nota->pt1 !== null ? ($nota->pt1 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                               {{ $locked1 ? 'disabled' : '' }}
                               @input="onNotaInput($event, {{ $idx }}, 't1')"
                               @blur="formatNotaInput($event)"
                               placeholder="—">
                      </div>
                    </td>

                    <td>
                      <span class="np-computed {{ $nota->mt1 !== null ? ($nota->mt1 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="mt1-{{ $idx }}">
                        {{ $nota->mt1 !== null ? number_format($nota->mt1, 2) : '—' }}
                      </span>
                    </td>

                  </tr>
                  @endforeach
                </tbody>
                {{-- Rodapé com médias da turma --}}
                @php
                  $medMac1 = $notas->whereNotNull('mac1')->avg('mac1');
                  $medPp1  = $notas->whereNotNull('pp1')->avg('pp1');
                  $medPt1  = $notas->whereNotNull('pt1')->avg('pt1');
                  $medMt1  = $notas->whereNotNull('mt1')->avg('mt1');
                @endphp
                <tfoot>
                  <tr>
                    <td class="td-left td-sticky">Média da turma</td>
                    <td>{{ $medMac1 ? number_format($medMac1, 2) : '—' }}</td>
                    <td>{{ $medPp1  ? number_format($medPp1,  2) : '—' }}</td>
                    <td>{{ $medPt1  ? number_format($medPt1,  2) : '—' }}</td>
                    <td>{{ $medMt1  ? number_format($medMt1,  2) : '—' }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            {{-- Form footer --}}
            <div class="np-form-footer">
              <div class="np-form-footer-info">
                <i class="fas fa-info-circle"></i>
                MT1 = (MAC1 + PP1 + PT1) ÷ 3 — calculado ao guardar
              </div>
              <div style="display:flex;align-items:center;gap:8px">
                @if(!$t1AllLocked)
                <button type="submit"
                        class="np-btn np-btn-success"
                        :class="{ 'np-btn-loading': saving }"
                        :disabled="saving">
                  <i class="fas fa-save np-btn-ico"></i>
                  Guardar 1º Trimestre
                </button>
                @else
                <div class="np-lock-badge partial"><i class="fas fa-lock"></i> Bloqueado</div>
                @endif
              </div>
            </div>
          </form>
        </div>

        {{-- ════════════════════════════════
             2º TRIMESTRE
        ════════════════════════════════ --}}
        <div x-show="activeTab === '2'" x-cloak class="np-tab-content">
          <form id="form-t2"
                method="POST"
                action="{{ route('notas.lancarTrimestre', 2) }}"
                @submit="onFormSubmit($event, '2')">
            @csrf
            <input type="hidden" name="_tab" value="2">

            <div class="np-toolbar">
              <div class="np-toolbar-left">
                <div class="np-search-wrap">
                  <i class="fas fa-search"></i>
                  <input type="text" class="np-search" placeholder="Filtrar aluno…"
                         @input="filterRows($event.target.value)">
                </div>
                @if($t2AllLocked)
                <div class="np-lock-badge partial"><i class="fas fa-lock"></i> Trimestre bloqueado</div>
                @endif
              </div>
              <div class="np-toolbar-right">
                <div class="np-kbd-hint"><span class="np-kbd">Tab</span><span>entre campos</span></div>
                <button type="submit" class="np-btn np-btn-success"
                        :class="{ 'np-btn-loading': saving }"
                        :disabled="{{ $t2AllLocked ? 'true' : 'false' }} || saving">
                  <i class="fas fa-save np-btn-ico"></i>
                  Guardar 2º Trimestre
                </button>
              </div>
            </div>

            <div class="np-tbl-scroll">
              <table class="np-tbl">
                <thead>
                  <tr>
                    <th class="th-left th-sticky" style="min-width:200px;width:200px">Aluno</th>
                    <th><span class="np-th-tooltip" data-tip="Média do 1º Trimestre (referência)">MT1 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Média de Avaliações Contínuas — 2º Trimestre">MAC2 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Prova do Professor — 2º Trimestre">PP2 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Prova Trimestral — 2º Trimestre">PT2 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Média do 2º Trimestre (calculada automaticamente)">MT2 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Média Final até 2º Trimestre = (MT1+MT2)÷2">MFT2 <i class="fas fa-info-circle"></i></span></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($notas as $idx => $nota)
                  @php
                    $locked2 = !$podeReabrirNotas && ($nota->status === 'finalizado' || ($nota->bloqueado_t2 ?? false));
                    $initials = strtoupper(substr($nota->aluno->name ?? 'A', 0, 1));
                    $avatarColor = $avatarColors[crc32($nota->aluno->name ?? '') % count($avatarColors)];
                    $alunoTemFoto = filled($nota->aluno->foto_perfil ?? null);
                  @endphp
                  <tr class="{{ $locked2 ? 'np-row-readonly' : '' }}"
                      data-aluno="{{ strtolower($nota->aluno->name ?? '') }}">
                    <td class="td-left td-sticky">
                      <input type="hidden" name="notas[{{ $idx }}][id]" value="{{ $nota->id }}">
                      <div class="np-aluno-cell">
                        <div class="np-aluno-avatar" style="{{ $alunoTemFoto ? '' : 'background:'.$avatarColor }}">
                          @if($alunoTemFoto)
                            <img src="{{ $nota->aluno->foto_perfil_url }}" alt="Foto de {{ $nota->aluno->name }}" class="np-aluno-avatar-img">
                          @else
                            {{ $initials }}
                          @endif
                        </div>
                        <div>
                          <div class="np-aluno-name">{{ $nota->aluno->name }}</div>
                          <div class="np-aluno-proc">{{ $nota->aluno->numero_processo ?? '—' }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="np-computed {{ $nota->mt1 !== null ? ($nota->mt1 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}">
                        {{ $nota->mt1 !== null ? number_format($nota->mt1, 2) : '—' }}
                      </span>
                    </td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][mac2]" readonly title="Calculada automaticamente pela média das avaliações contínuas" value="{{ $nota->mac2 }}"
                             class="np-nota-input {{ $nota->mac2 !== null ? ($nota->mac2 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked2 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't2')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][pp2]" value="{{ $nota->pp2 }}"
                             class="np-nota-input {{ $nota->pp2 !== null ? ($nota->pp2 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked2 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't2')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][pt2]" value="{{ $nota->pt2 }}"
                             class="np-nota-input {{ $nota->pt2 !== null ? ($nota->pt2 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked2 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't2')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td>
                      <span class="np-computed {{ $nota->mt2 !== null ? ($nota->mt2 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="mt2-{{ $idx }}">
                        {{ $nota->mt2 !== null ? number_format($nota->mt2, 2) : '—' }}
                      </span>
                    </td>
                    <td>
                      <span class="np-computed {{ $nota->mft2 !== null ? ($nota->mft2 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="mft2-{{ $idx }}">
                        {{ $nota->mft2 !== null ? number_format($nota->mft2, 2) : '—' }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
                @php
                  $medMac2 = $notas->whereNotNull('mac2')->avg('mac2');
                  $medPp2  = $notas->whereNotNull('pp2')->avg('pp2');
                  $medPt2  = $notas->whereNotNull('pt2')->avg('pt2');
                  $medMt2  = $notas->whereNotNull('mt2')->avg('mt2');
                  $medMft2 = $notas->whereNotNull('mft2')->avg('mft2');
                @endphp
                <tfoot>
                  <tr>
                    <td class="td-left td-sticky">Média da turma</td>
                    <td>{{ $medMt1  ? number_format($medMt1,  2) : '—' }}</td>
                    <td>{{ $medMac2 ? number_format($medMac2, 2) : '—' }}</td>
                    <td>{{ $medPp2  ? number_format($medPp2,  2) : '—' }}</td>
                    <td>{{ $medPt2  ? number_format($medPt2,  2) : '—' }}</td>
                    <td>{{ $medMt2  ? number_format($medMt2,  2) : '—' }}</td>
                    <td>{{ $medMft2 ? number_format($medMft2, 2) : '—' }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="np-form-footer">
              <div class="np-form-footer-info">
                <i class="fas fa-info-circle"></i>
                MT2 = (MAC2+PP2+PT2)÷3 &nbsp;·&nbsp; MFT2 = (MT1+MT2)÷2
              </div>
              <div style="display:flex;gap:8px">
                @if(!$t2AllLocked)
                <button type="submit" class="np-btn np-btn-success"
                        :class="{ 'np-btn-loading': saving }" :disabled="saving">
                  <i class="fas fa-save np-btn-ico"></i>Guardar 2º Trimestre
                </button>
                @else
                <div class="np-lock-badge partial"><i class="fas fa-lock"></i> Bloqueado</div>
                @endif
              </div>
            </div>
          </form>
        </div>

        {{-- ════════════════════════════════
             3º TRIMESTRE
        ════════════════════════════════ --}}
        <div x-show="activeTab === '3'" x-cloak class="np-tab-content">
          <form id="form-t3"
                method="POST"
                action="{{ route('notas.lancarTrimestre', 3) }}"
                @submit="onFormSubmit($event, '3')">
            @csrf
            <input type="hidden" name="_tab" value="3">
            <input type="hidden" name="turma_id" value="{{ $turma->id }}">
            <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">

            <div class="np-toolbar">
              <div class="np-toolbar-left">
                <div class="np-search-wrap">
                  <i class="fas fa-search"></i>
                  <input type="text" class="np-search" placeholder="Filtrar aluno…"
                         @input="filterRows($event.target.value)">
                </div>
                @if($t3AllLocked)
                <div class="np-lock-badge partial"><i class="fas fa-lock"></i> Trimestre bloqueado</div>
                @endif
              </div>
              <div class="np-toolbar-right">
                <div class="np-kbd-hint"><span class="np-kbd">Tab</span><span>entre campos</span></div>
                @if($turma->classe != '10')
                <form method="POST" action="{{ route('notas.importar-cas') }}" style="display:inline">
                  @csrf
                  <input type="hidden" name="turma_id" value="{{ $turma->id }}">
                  <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
                  <button type="submit" class="np-btn np-btn-ghost" {{ $t3AllLocked ? 'disabled' : '' }}>
                    <i class="fas fa-download np-btn-ico"></i> Importar CAs
                  </button>
                </form>
                @endif
                <button type="submit" class="np-btn np-btn-success"
                        :class="{ 'np-btn-loading': saving }"
                        :disabled="{{ $t3AllLocked ? 'true' : 'false' }} || saving">
                  <i class="fas fa-save np-btn-ico"></i>Guardar 3º Trimestre
                </button>
              </div>
            </div>

            <div class="np-tbl-scroll">
              <table class="np-tbl">
                <thead>
                  <tr>
                    <th class="th-left th-sticky" style="min-width:200px;width:200px">Aluno</th>
                    <th><span class="np-th-tooltip" data-tip="Média Final até 2º Trimestre (referência)">MFT2 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Média de Avaliações Contínuas — 3º Trimestre">MAC3 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Prova do Professor — 3º Trimestre">PP3 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Prova Global">PG <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Média do 3º Trimestre = (MAC3+PP3)÷2">MT3 <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Classificação Final = (MFT2+MT3)÷2">CF <i class="fas fa-info-circle"></i></span></th>
                    <th><span class="np-th-tooltip" data-tip="Classificação Final da Disciplina (resultado final)">CFD <i class="fas fa-info-circle"></i></span></th>
                    <th>Resultado</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($notas as $idx => $nota)
                  @php
                    $locked3 = !$podeReabrirNotas && ($nota->status === 'finalizado' || ($nota->bloqueado_t3 ?? false));
                    $initials = strtoupper(substr($nota->aluno->name ?? 'A', 0, 1));
                    $avatarColor = $avatarColors[crc32($nota->aluno->name ?? '') % count($avatarColors)];
                    $alunoTemFoto = filled($nota->aluno->foto_perfil ?? null);
                  @endphp
                  <tr class="{{ $locked3 ? 'np-row-readonly' : '' }}"
                      data-aluno="{{ strtolower($nota->aluno->name ?? '') }}">
                    <td class="td-left td-sticky">
                      <input type="hidden" name="notas[{{ $idx }}][id]" value="{{ $nota->id }}">
                      <div class="np-aluno-cell">
                        <div class="np-aluno-avatar" style="{{ $alunoTemFoto ? '' : 'background:'.$avatarColor }}">
                          @if($alunoTemFoto)
                            <img src="{{ $nota->aluno->foto_perfil_url }}" alt="Foto de {{ $nota->aluno->name }}" class="np-aluno-avatar-img">
                          @else
                            {{ $initials }}
                          @endif
                        </div>
                        <div>
                          <div class="np-aluno-name">{{ $nota->aluno->name }}</div>
                          <div class="np-aluno-proc">{{ $nota->aluno->numero_processo ?? '—' }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="np-computed {{ $nota->mft2 !== null ? ($nota->mft2 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}">
                        {{ $nota->mft2 !== null ? number_format($nota->mft2, 2) : '—' }}
                      </span>
                    </td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][mac3]" readonly title="Calculada automaticamente pela média das avaliações contínuas" value="{{ $nota->mac3 }}"
                             class="np-nota-input {{ $nota->mac3 !== null ? ($nota->mac3 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked3 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't3')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][pp3]" value="{{ $nota->pp3 }}"
                             class="np-nota-input {{ $nota->pp3 !== null ? ($nota->pp3 >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked3 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't3')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td><div class="np-input-wrap">
                      <input type="number" step="0.01" min="-1" max="20"
                             name="notas[{{ $idx }}][pg]" value="{{ $nota->pg }}"
                             class="np-nota-input {{ $nota->pg !== null ? ($nota->pg >= 10 ? 'val-ok' : 'val-fail') : '' }}"
                             {{ $locked3 ? 'disabled' : '' }}
                             @input="onNotaInput($event, {{ $idx }}, 't3')"
                             @blur="formatNotaInput($event)" placeholder="—">
                    </div></td>
                    <td>
                      <span class="np-computed {{ $nota->mt3 !== null ? ($nota->mt3 >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="mt3-{{ $idx }}">
                        {{ $nota->mt3 !== null ? number_format($nota->mt3, 2) : '—' }}
                      </span>
                    </td>
                    <td>
                      <span class="np-computed {{ $nota->cf !== null ? ($nota->cf >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="cf-{{ $idx }}">
                        {{ $nota->cf !== null ? number_format($nota->cf, 2) : '—' }}
                      </span>
                    </td>
                    <td>
                      <span class="np-computed {{ $nota->cfd !== null ? ($nota->cfd >= 10 ? 'c-ok' : 'c-fail') : 'c-empty' }}"
                            id="cfd-{{ $idx }}" style="font-size:14.5px">
                        {{ $nota->cfd !== null ? number_format($nota->cfd, 2) : '—' }}
                      </span>
                    </td>
                    <td>
                      @if($nota->cfd !== null)
                        <span class="np-status-badge {{ $nota->cfd >= 10 ? 'ok' : 'fail' }}">
                          <i class="fas {{ $nota->cfd >= 10 ? 'fa-check' : 'fa-times' }}"></i>
                          {{ $nota->cfd >= 10 ? 'Aprovado' : 'Reprovado' }}
                        </span>
                      @else
                        <span class="np-status-badge pend">Pendente</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
                @php
                  $medMac3 = $notas->whereNotNull('mac3')->avg('mac3');
                  $medPp3  = $notas->whereNotNull('pp3')->avg('pp3');
                  $medPg   = $notas->whereNotNull('pg')->avg('pg');
                  $medMt3  = $notas->whereNotNull('mt3')->avg('mt3');
                  $medCf   = $notas->whereNotNull('cf')->avg('cf');
                  $medCfd  = $notas->whereNotNull('cfd')->avg('cfd');
                @endphp
                <tfoot>
                  <tr>
                    <td class="td-left td-sticky">Média da turma</td>
                    <td>{{ $medMft2 ? number_format($medMft2, 2) : '—' }}</td>
                    <td>{{ $medMac3 ? number_format($medMac3, 2) : '—' }}</td>
                    <td>{{ $medPp3  ? number_format($medPp3,  2) : '—' }}</td>
                    <td>{{ $medPg   ? number_format($medPg,   2) : '—' }}</td>
                    <td>{{ $medMt3  ? number_format($medMt3,  2) : '—' }}</td>
                    <td>{{ $medCf   ? number_format($medCf,   2) : '—' }}</td>
                    <td>{{ $medCfd  ? number_format($medCfd,  2) : '—' }}</td>
                    <td>—</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="np-form-footer">
              <div class="np-form-footer-info">
                <i class="fas fa-info-circle"></i>
                MT3 = (MAC3+PP3)÷2 &nbsp;·&nbsp; CF = (MFT2+MT3)÷2 &nbsp;·&nbsp; CA = 0.6×CF + 0.4×PG
              </div>
              <div style="display:flex;gap:8px">
                @if(!$t3AllLocked)
                <button type="submit" class="np-btn np-btn-success"
                        :class="{ 'np-btn-loading': saving }" :disabled="saving">
                  <i class="fas fa-save np-btn-ico"></i>Guardar 3º Trimestre
                </button>
                @else
                <div class="np-lock-badge partial"><i class="fas fa-lock"></i> Bloqueado</div>
                @endif
              </div>
            </div>
          </form>
        </div>

      </div>{{-- /np-card --}}
    </div>{{-- /np-main --}}

    {{-- ────────────────────────────────────────────
         PAINEL LATERAL
    ──────────────────────────────────────────── --}}
    <aside class="np-sidebar">

      {{-- Stats card --}}
      <div class="np-stats-card">
        <div class="np-stats-head">
          <div class="np-stats-head-ico"><i class="fas fa-chart-bar"></i></div>
          <span class="np-stats-head-title">Resumo da Pauta</span>
        </div>

        <div class="np-kpi-grid">
          <div class="np-kpi">
            <span class="np-kpi-val blue">{{ $totalAlunos }}</span>
            <span class="np-kpi-lbl">Alunos</span>
          </div>
          <div class="np-kpi">
            <span class="np-kpi-val amber">{{ $progresso }}%</span>
            <span class="np-kpi-lbl">Completo</span>
          </div>
          <div class="np-kpi">
            <span class="np-kpi-val green">{{ $aprovados }}</span>
            <span class="np-kpi-lbl">Aprovados</span>
          </div>
          <div class="np-kpi">
            <span class="np-kpi-val red">{{ $reprovados }}</span>
            <span class="np-kpi-lbl">Reprovados</span>
          </div>
        </div>

        {{-- Progress --}}
        <div class="np-progress-wrap">
          <div class="np-progress-header">
            <span class="np-progress-lbl">Preenchimento</span>
            <span class="np-progress-pct">{{ $progresso }}%</span>
          </div>
          <div class="np-progress-track">
            <div class="np-progress-fill" style="width:{{ $progresso }}%"></div>
          </div>
        </div>

        {{-- Trim status --}}
        <div class="np-trim-status">
          @foreach([1,2,3] as $t)
          @php
            $trimLock = match($t) {
              1 => $t1AllLocked,
              2 => $t2AllLocked,
              3 => $t3AllLocked,
            };
            $trimHasData = match($t) {
              1 => $notas->whereNotNull('mt1')->count() > 0,
              2 => $notas->whereNotNull('mt2')->count() > 0,
              3 => $notas->whereNotNull('mt3')->count() > 0,
            };
          @endphp
          <div class="np-trim-row">
            <span class="np-trim-name">{{ $t }}º Trimestre</span>
            @if($trimLock)
              <span class="np-trim-badge lock"><i class="fas fa-lock"></i> Bloqueado</span>
            @elseif($trimHasData)
              <span class="np-trim-badge open"><i class="fas fa-check"></i> Com dados</span>
            @else
              <span class="np-trim-badge empty"><i class="fas fa-minus"></i> Vazio</span>
            @endif
          </div>
          @endforeach
        </div>
      </div>

      {{-- Acções operacionais (finalizar/reabrir) --}}
      @if($podeFinalizarNotas || $podeReabrirNotas)
      <div class="np-op-panel">
        <div class="np-op-panel-head">
          <i class="fas fa-cog"></i>
          Operações da Pauta
        </div>
        <div class="np-op-body">

          @if($podeFinalizarNotas)
          <form method="POST" action="{{ route('notas.finalizar') }}">
            @csrf
            <input type="hidden" name="turma_id" value="{{ $turma->id }}">
            <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
            <div class="np-op-row">
              <label class="np-op-lbl">Bloquear</label>
              <select name="trimestre" class="np-op-select">
                <option value="">Todos os trimestres</option>
                <option value="1">Apenas 1º Trimestre</option>
                <option value="2">Apenas 2º Trimestre</option>
                <option value="3">Apenas 3º Trimestre</option>
              </select>
            </div>
            <div class="np-op-row">
              <label class="np-op-lbl">Aluno</label>
              <select name="aluno_id" class="np-op-select">
                <option value="">Todos os alunos</option>
                @foreach($opcoesAlunos as $al)
                <option value="{{ $al->id }}">{{ $al->name }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="np-btn np-btn-danger" style="width:100%"
                    onclick="return confirm('Finalizar e bloquear esta pauta?')">
              <i class="fas fa-lock np-btn-ico"></i>
              Finalizar / Bloquear
            </button>
          </form>
          @endif

          @if($podeReabrirNotas)
          @if($podeFinalizarNotas)
          <div class="np-op-divider"></div>
          @endif
          <form method="POST" action="{{ route('notas.reabrir') }}"
                onsubmit="return confirm('Deseja reabrir esta pauta?')">
            @csrf
            <input type="hidden" name="turma_id" value="{{ $turma->id }}">
            <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
            <div class="np-op-row">
              <label class="np-op-lbl">Desbloquear</label>
              <select name="trimestre" class="np-op-select">
                <option value="">Todos os trimestres</option>
                <option value="1">Apenas 1º Trimestre</option>
                <option value="2">Apenas 2º Trimestre</option>
                <option value="3">Apenas 3º Trimestre</option>
              </select>
            </div>
            <div class="np-op-row">
              <label class="np-op-lbl">Aluno</label>
              <select name="aluno_id" class="np-op-select">
                <option value="">Todos os alunos</option>
                @foreach($opcoesAlunos as $al)
                <option value="{{ $al->id }}">{{ $al->name }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="np-btn np-btn-ghost" style="width:100%">
              <i class="fas fa-lock-open np-btn-ico"></i>
              Reabrir / Desbloquear
            </button>
          </form>
          @endif

        </div>
      </div>
      @endif

      {{-- Inicializar pauta --}}
      <div class="np-op-panel">
        <div class="np-op-panel-head">
          <i class="fas fa-plus-circle"></i>
          Inicializar Pauta
        </div>
        <div class="np-op-body">
          <p style="font-size:12px;color:var(--ink-500);line-height:1.5;margin-bottom:10px">
            Se algum aluno estiver em falta, inicializar cria os registos em falta.
          </p>
          <form method="POST" action="{{ route('notas.inicializar-pauta') }}">
            @csrf
            <input type="hidden" name="turma_id" value="{{ $turma->id }}">
            <input type="hidden" name="disciplina_id" value="{{ $disciplina->id }}">
            <button type="submit" class="np-btn np-btn-ghost" style="width:100%">
              <i class="fas fa-sync-alt np-btn-ico"></i>
              Inicializar / Sincronizar
            </button>
          </form>
        </div>
      </div>

    </aside>

  </div>{{-- /np-layout --}}

  @else
  {{-- Estado vazio — sem pauta seleccionada --}}
  <div class="np-init-card">
    <div class="np-init-icon"><i class="fas fa-clipboard-list"></i></div>
    <div class="np-init-title">Selecione a turma e disciplina</div>
    <div class="np-init-sub">
      Escolha a turma e a disciplina nos campos acima para carregar a pauta de lançamento de notas.
    </div>
    @if($atribuicoes->isEmpty())
    <div style="font-size:13px;color:var(--amber-500);font-weight:500;margin-top:8px">
      <i class="fas fa-exclamation-triangle" style="margin-right:5px"></i>
      Não tem atribuições no ano letivo ativo.
    </div>
    @endif
  </div>
  @endif

  <br>
@if(false && $notas && $turma && $disciplina)
  <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-800">Divisão aritmética por 2 (casos de ausência)</h3>
        <p class="text-xs text-slate-500">
          Use <strong>-1</strong> como sentinela de ausência no trimestre. Professores podem solicitar e apenas o coordenador do curso pode aprovar.
        </p>
      </div>
      @if(auth()->user()->isCoordenadorCurso() && $turma->curso?->coordenador_id === auth()->id())
      <span class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
        <i class="fas fa-bell"></i> {{ $solicitacoesPendentes->count() }} solicitação(ões) pendente(s)
      </span>
      @endif
    </div>

    <div class="mt-3 grid gap-2">
      @foreach($notas as $notaResumo)
        @php
          $solicitacaoAtual = $statusSolicitacoesPorNota->get($notaResumo->id);
          $statusAtual = $solicitacaoAtual?->status;
        @endphp
        <div class="border border-slate-100 rounded-lg p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
          <div>
            <div class="text-sm font-medium text-slate-700">{{ $notaResumo->aluno->name }}</div>
            <div class="text-xs text-slate-500">
              Estado: {{ $notaResumo->usar_divisao_aritmetica_por_2 ? 'Divisão por 2 liberada' : 'Divisão padrão' }}
              @if($statusAtual)
                · Solicitação {{ $statusAtual }}
              @endif
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            @if(!$notaResumo->usar_divisao_aritmetica_por_2)
              <form method="POST" action="{{ route('notas.solicitar-divisao-por-dois', $notaResumo) }}">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100"
                        @disabled($statusAtual === 'pendente')>
                  <i class="fas fa-paper-plane"></i> Solicitar autorização
                </button>
              </form>
            @endif

            @if(auth()->user()->isCoordenadorCurso() && $turma->curso?->coordenador_id === auth()->id() && $statusAtual === 'pendente')
              <form method="POST" action="{{ route('notas.solicitacoes-divisao.responder', $solicitacaoAtual) }}" class="inline-flex">
                @csrf
                <input type="hidden" name="acao" value="aprovar">
                <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-semibold text-green-700 bg-green-50 border border-green-200 hover:bg-green-100">
                  <i class="fas fa-check"></i> Aprovar
                </button>
              </form>
              <form method="POST" action="{{ route('notas.solicitacoes-divisao.responder', $solicitacaoAtual) }}" class="inline-flex">
                @csrf
                <input type="hidden" name="acao" value="rejeitar">
                <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100">
                  <i class="fas fa-times"></i> Rejeitar
                </button>
              </form>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @if($notas && $turma && $disciplina)
  <x-card class="mt-6" title="Avaliações Contínuas (cálculo automático da MAC)" icon="fas fa-list-ol">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-3 py-2 text-left">Aluno</th>
            <th class="px-3 py-2 text-left">T1</th>
            <th class="px-3 py-2 text-left">T2</th>
            <th class="px-3 py-2 text-left">T3</th>
            <th class="px-3 py-2 text-center">MACs</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          @foreach($notas as $nota)
            <tr>
              <td class="px-3 py-3 align-top">
                <div class="font-medium text-slate-800">{{ $nota->aluno->name }}</div>
                <div class="text-xs text-slate-500">{{ $nota->aluno->numero_processo ?? '—' }}</div>
              </td>

              @for($trimestre = 1; $trimestre <= 3; $trimestre++)
                @php
                  $avaliacoesTri = $nota->avaliacoesContinuas->where('trimestre', $trimestre);
                  $bloqueadoTri = (bool) $nota->{"bloqueado_t{$trimestre}"};
                @endphp
                <td class="px-3 py-3 align-top">
                  <div class="space-y-2">
                    @forelse($avaliacoesTri as $avaliacao)
                      <div class="rounded border border-slate-200 px-2 py-1 text-xs flex items-center justify-between gap-2">
                        <div>
                          <div class="font-medium text-slate-700">{{ $avaliacao->descricao }}</div>
                          <div class="text-slate-500">{{ number_format($avaliacao->valor, 2) }} valores</div>
                        </div>
                      </div>
                    @empty
                      <span class="text-xs text-slate-400">Sem avaliações</span>
                    @endforelse

                    @if($nota->trimestreEstaDisponivel($trimestre) && ! $bloqueadoTri)
                    <form method="POST" action="{{ route('notas.avaliacoes-continuas.store') }}" class="grid grid-cols-12 gap-2">
                      @csrf
                      <input type="hidden" name="nota_id" value="{{ $nota->id }}">
                      <input type="hidden" name="trimestre" value="{{ $trimestre }}">
                      <input type="text" name="descricao" required maxlength="120" placeholder="Descrição" class="col-span-7 form-input text-xs h-8">
                      <input type="number" step="0.01" min="0" max="20" name="valor" required placeholder="Valor" class="col-span-3 form-input text-xs h-8">
                      <button type="submit" class="col-span-2 btn btn-primary text-xs h-8">+</button>
                    </form>
                    @endif
                  </div>
                </td>
              @endfor

              <td class="px-3 py-3 align-top text-center">
                <div class="text-xs text-slate-600">T1: <strong>{{ $nota->mac1 !== null ? number_format($nota->mac1, 2) : '—' }}</strong></div>
                <div class="text-xs text-slate-600">T2: <strong>{{ $nota->mac2 !== null ? number_format($nota->mac2, 2) : '—' }}</strong></div>
                <div class="text-xs text-slate-600">T3: <strong>{{ $nota->mac3 !== null ? number_format($nota->mac3, 2) : '—' }}</strong></div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <p class="mt-3 text-xs text-slate-500">A MAC de cada trimestre é calculada automaticamente pela média das avaliações contínuas lançadas.</p>
  </x-card>
  @endif

</div>{{-- /np-root --}}

@endif  {{-- ← ADICIONA ESTE — fecha o @if(false && $notas...) --}}

@endsection


@push('scripts')
<script>
/* ═══════════════════════════════════════════════════════════════
   SELECTOR DATA — Alpine component
═══════════════════════════════════════════════════════════════ */
function npSelectorData() {
  const STORAGE_KEY = 'siga_np_last';

  return {
    turmaId:      '{{ request("turma_id") ?? "" }}',
    disciplinaId: '{{ request("disciplina_id") ?? "" }}',
    activeTab:    '{{ $activeTab }}',
    restored: false,

    init() {
      /* Restaurar última pauta se a URL não tiver parâmetros */
      if (!this.turmaId && !this.disciplinaId) {
        const saved = this._load();
        if (saved?.turmaId && saved?.disciplinaId) {
          this.turmaId      = saved.turmaId;
          this.disciplinaId = saved.disciplinaId;
          this.activeTab    = saved.tab ?? '1';
          this.restored = true;
          /* Submeter automaticamente após 500ms */
          setTimeout(() => document.getElementById('np-selector-form').submit(), 500);
        }
      } else if (this.turmaId && this.disciplinaId) {
        this._save();
      }
    },

    onTurmaChange() {
      this.disciplinaId = '';
      /* Filtrar disciplinas visíveis para a turma selecionada */
      document.querySelectorAll('.disc-option-all').forEach(opt => {
        opt.style.display = opt.dataset.turma === this.turmaId ? '' : 'none';
      });
    },

    onDisciplinaChange() {
      if (this.turmaId && this.disciplinaId) this._save();
    },

    _save() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
          turmaId:      this.turmaId,
          disciplinaId: this.disciplinaId,
          tab:          this.activeTab,
        }));
      } catch(e) {}
    },

    _load() {
      try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); }
      catch(e) { return null; }
    },
  };
}

/* ═══════════════════════════════════════════════════════════════
   PAUTA DATA — Alpine component (tabela interactiva)
═══════════════════════════════════════════════════════════════ */
function npPautaData() {
  const STORAGE_KEY = 'siga_np_last';

  return {
    activeTab: '{{ $activeTab }}',
    dirty: false,
    saving: false,

    init() {
      /* Proteção "alterações não salvas" */
      window.addEventListener('beforeunload', (e) => {
        if (this.dirty) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
    },

    switchTab(tab) {
      if (this.dirty) {
        const ok = confirm('Tem alterações não guardadas. Mudar de tab sem guardar vai perder os dados desta tab. Continuar?');
        if (!ok) return;
        this.dirty = false;
      }
      this.activeTab = tab;
      /* Persistir tab escolhida */
      try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        saved.tab = tab;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
      } catch(e) {}
    },

    onNotaInput(event, idx, trimestre) {
      const input = event.target;
      const val   = parseFloat(input.value);

      /* Colorir input em tempo real */
      input.classList.remove('val-ok', 'val-fail');
      if (input.value !== '' && !isNaN(val)) {
        if (val < 0 || val > 20) {
          input.style.animation = 'np-shake .3s ease';
          setTimeout(() => input.style.animation = '', 350);
          input.value = val < 0 ? '0' : '20';
        }
        const clamped = Math.min(20, Math.max(0, val));
        input.classList.add(clamped >= 10 ? 'val-ok' : 'val-fail');
      }

      this.dirty = true;
    },

    formatNotaInput(event) {
      const input = event.target;
      if (input.value === '' || input.value === null) return;
      const val = parseFloat(input.value.replace(',', '.'));
      if (isNaN(val)) { input.value = ''; return; }
      const clamped = Math.min(20, Math.max(0, val));
      input.value = clamped.toFixed(2);
      input.classList.remove('val-ok', 'val-fail');
      input.classList.add(clamped >= 10 ? 'val-ok' : 'val-fail');
    },

    filterRows(query) {
      const q = (query || '').toLowerCase().trim();
      document.querySelectorAll('tr[data-aluno]').forEach(row => {
        const name = row.dataset.aluno || '';
        row.classList.toggle('np-row-hidden', q.length > 0 && !name.includes(q));
      });
    },

    onFormSubmit(event, tab) {
      this.saving = true;
      this.dirty  = false;
      /* Deixar o form submeter normalmente (POST) */
      /* Flash nas linhas após redirect é tratado via session */
    },
  };
}

/* ═══════════════════════════════════════════════════════════════
   FLASH de sucesso por linha (após redirect)
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
  /* Se há mensagem de sucesso, animar todas as linhas salvas */
  const hasSuccess = document.querySelector('.alert-success') ||
                     document.querySelector('[data-flash-success]');
  if (hasSuccess) {
    setTimeout(() => {
      document.querySelectorAll('tr[data-aluno]').forEach((row, i) => {
        setTimeout(() => row.classList.add('np-row-saved'), i * 30);
      });
    }, 200);
  }

  /* Corrigir dismiss dos alerts para 4 segundos (bug original: 60s) */
  document.querySelectorAll('.auto-dismiss').forEach(el => {
    el.dataset.dismissAfter = '4000';
  });
});
</script>
@endpush
