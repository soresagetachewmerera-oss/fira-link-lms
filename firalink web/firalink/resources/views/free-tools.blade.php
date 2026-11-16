@extends('layouts.app')

@push('styles')
<style>
/* ==========================================================================
   FIRA LINK FREE TRADE TOOLS — CLEAN, CALM & PROFESSIONAL DESIGN SYSTEM
   Tokens: --brand: #6D1A95, --brand-soft: #F4ECF8, --text: #1A1A2E, --muted: #6B7280
           --border: #E8E8EF, --bg: #FAFAFC, --up: #15803D, --down: #B91C1C, --radius: 12px
   ========================================================================== */

:root {
    --brand: #6D1A95;
    --brand-dark: #531372;
    --brand-soft: #F4ECF8;
    --brand-border: #E8D5F2;
    --text: #1A1A2E;
    --text-secondary: #4A4D5E;
    --muted: #6B7280;
    --border: #E8E8EF;
    --border-light: #F4F4F8;
    --bg: #FAFAFC;
    --card-bg: #FFFFFF;
    --up: #15803D;
    --up-soft: #ECFDF5;
    --down: #B91C1C;
    --down-soft: #FEF2F2;
    --radius: 12px;
    --radius-sm: 8px;
    --focus-ring: 0 0 0 2px rgba(109, 26, 149, 0.30);
}

/* Base Layout & Container */
.tools-page-root {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    padding-bottom: 64px;
    overflow-x: hidden;
}

.tools-page-root .container {
    max-width: 1280px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 24px;
    padding-right: 24px;
}

/* ==========================================================================
   1. COMPACT COMMAND HEADER (Under 180px total height with tabs)
   ========================================================================== */
.tools-command-header {
    background: #FFFFFF;
    border-bottom: 1px solid var(--border);
    padding: 16px 0 14px 0;
}

.tools-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.tools-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.tools-page-title {
    font-size: 26px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.4px;
}

.tools-badge-neutral {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tools-page-subtitle {
    font-size: 13.5px;
    color: var(--muted);
    margin: 4px 0 0 0;
    line-height: 1.4;
    max-width: 840px;
}

.btn-brand-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 600;
    background: var(--brand);
    color: #FFFFFF !important;
    border: 1px solid var(--brand);
    border-radius: var(--radius-sm);
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.16s ease;
    white-space: nowrap;
}

.btn-brand-primary:hover {
    background: var(--brand-dark);
    border-color: var(--brand-dark);
}

.btn-brand-primary:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

/* Quiet Ghost Action Button */
.btn-ghost-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    background: transparent;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none !important;
}

.btn-ghost-action:hover {
    color: var(--text);
    background: #FFFFFF;
    border-color: #D1D5DB;
}

.btn-ghost-action:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

/* Secondary Button */
.btn-secondary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text);
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-secondary-action:hover {
    background: var(--bg);
    border-color: #D1D5DB;
}

.btn-secondary-action:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

/* ==========================================================================
   2. SEGMENTED TAB DOCK
   ========================================================================== */
.tools-dock-wrap {
    background: #FFFFFF;
    border-bottom: 1px solid var(--border);
    padding: 6px 0;
}

.segmented-dock {
    display: flex;
    align-items: center;
    gap: 4px;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}

.segmented-dock::-webkit-scrollbar {
    display: none;
}

.dock-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--radius-sm);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}

.dock-tab-btn i {
    font-size: 14px;
    opacity: 0.8;
}

.dock-tab-btn:hover {
    color: var(--text);
    background: var(--bg);
}

.dock-tab-btn.active {
    color: var(--brand);
    background: var(--brand-soft);
    border-color: var(--brand-soft);
    font-weight: 600;
}

.dock-tab-btn.active i {
    opacity: 1;
}

.dock-tab-btn:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

/* ==========================================================================
   3. CLEAN THIN TICKER (Rendered ONLY in Market Intelligence Tab)
   ========================================================================== */
.tools-thin-ticker {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    margin-bottom: 20px;
    font-size: 12px;
    color: var(--text);
}

.ticker-scroll-items {
    display: flex;
    align-items: center;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    white-space: nowrap;
}

.ticker-scroll-items::-webkit-scrollbar {
    display: none;
}

.ticker-unit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.ticker-unit .sym {
    color: var(--muted);
    font-weight: 500;
}

.ticker-unit .val {
    font-weight: 600;
    color: var(--text);
}

.ticker-unit .delta-pos {
    color: var(--up);
    font-weight: 600;
    font-size: 11px;
}

.ticker-unit .delta-neg {
    color: var(--down);
    font-weight: 600;
    font-size: 11px;
}

.ticker-timestamp {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: var(--muted);
    white-space: nowrap;
    border-left: 1px solid var(--border);
    padding-left: 12px;
    margin-left: 12px;
}

/* ==========================================================================
   4. CARDS & PANELS (24px Spacing, 24px Padding, 1px Border)
   ========================================================================== */
.workspace-pane {
    display: none;
}

.workspace-pane.active {
    display: block;
}

.clean-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 24px;
    transition: border-color 0.16s ease;
}

.clean-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
}

.clean-card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
    line-height: 1.3;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clean-card-subtitle {
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
}

/* Market Top Stat Cards */
.market-stat-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stat-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.stat-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
}

.stat-badge-neutral {
    font-size: 11px;
    font-weight: 600;
    color: #4B5563;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-hero-number {
    font-size: 26px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.1;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}

.stat-hero-number small {
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
}

.stat-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
}

.stat-delta-pos {
    color: var(--up);
    font-weight: 600;
}

.stat-delta-neg {
    color: var(--down);
    font-weight: 600;
}

.stat-ref-muted {
    color: var(--muted);
}

/* ==========================================================================
   5. TABLES (Clean, Minimal, No Horizontal Scroll at 1440px)
   ========================================================================== */
.table-responsive-clean {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.clean-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.clean-table th {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    background: #FAFAFC;
    text-align: left;
    white-space: nowrap;
}

.clean-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: middle;
}

.clean-table tbody tr:last-child td {
    border-bottom: none;
}

.clean-table tbody tr:hover {
    background: #F9FAFB;
}

.table-coffee-title {
    font-weight: 600;
    color: var(--text);
}

.table-coffee-sub {
    font-size: 12px;
    color: var(--muted);
    margin-top: 1px;
}

/* ==========================================================================
   6. PROFIT CALCULATOR (Two-Column, Light Results Panel)
   ========================================================================== */
.calc-preset-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.calc-preset-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-secondary);
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.15s ease;
}

.calc-preset-btn:hover {
    color: var(--text);
    border-color: #D1D5DB;
}

.calc-preset-btn.active {
    color: var(--brand);
    background: var(--brand-soft);
    border-color: var(--brand-border);
    font-weight: 600;
}

.calc-section-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--muted);
    margin: 20px 0 12px 0;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--border-light);
}

.calc-section-label:first-of-type {
    margin-top: 0;
}

.input-field-group {
    margin-bottom: 14px;
}

.input-field-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--text);
    margin-bottom: 5px;
}

.input-field-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.form-control-clean {
    width: 100%;
    height: 40px;
    padding: 8px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text);
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    transition: all 0.15s ease;
}

.form-control-clean:focus {
    outline: none;
    border-color: var(--brand);
    box-shadow: var(--focus-ring);
}

.form-control-clean.has-suffix {
    padding-right: 64px;
}

.form-control-clean.has-prefix {
    padding-left: 28px;
}

.input-prefix {
    position: absolute;
    left: 12px;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    pointer-events: none;
}

.input-suffix {
    position: absolute;
    right: 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    pointer-events: none;
}

/* Light P&L Results Card */
.results-panel-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    position: sticky;
    top: 24px;
}

.results-hero-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}

.results-hero-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--muted);
    margin-bottom: 6px;
}

.results-hero-val {
    font-size: 32px;
    font-weight: 700;
    color: var(--brand);
    line-height: 1.1;
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}

.results-hero-etb {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text-secondary);
}

.margin-pill-clean {
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
    margin-top: 8px;
}

/* Results Ratio Bar */
.results-ratio-bar {
    height: 8px;
    border-radius: 999px;
    display: flex;
    overflow: hidden;
    background: #E5E7EB;
    margin: 16px 0 8px 0;
}

.ratio-seg {
    height: 100%;
    transition: width 0.25s ease;
}

.ratio-seg-sourcing { background: #94A3B8; }
.ratio-seg-corridor { background: #CBD5E1; }
.ratio-seg-processing { background: #E2E8F0; }
.ratio-seg-margin { background: var(--brand); }

.results-data-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 0;
    border-bottom: 1px solid var(--border-light);
    font-size: 13px;
}

.results-data-row:last-of-type {
    border-bottom: none;
}

.results-data-label {
    color: var(--muted);
}

.results-data-val {
    font-weight: 600;
    color: var(--text);
}

/* ==========================================================================
   7. QUOTATION BUILDER (A4 Sheet Preview, Min 13px Font)
   ========================================================================== */
.quotation-sheet-preview {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 32px;
    font-size: 13px;
    line-height: 1.6;
    position: sticky;
    top: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.quotation-corner-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    font-size: 10px;
    font-weight: 700;
    color: #9CA3AF;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    padding: 2px 8px;
    border-radius: 4px;
    letter-spacing: 0.8px;
}

.quotation-header {
    border-bottom: 2px solid var(--border);
    padding-bottom: 16px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.quotation-brand {
    font-size: 18px;
    font-weight: 700;
    color: var(--brand);
    letter-spacing: -0.3px;
}

.quotation-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.quotation-table-wrap {
    margin-bottom: 20px;
    border: 1px solid var(--border);
    border-radius: 6px;
    overflow: hidden;
}

.quotation-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.quotation-table th {
    background: #F9FAFB;
    padding: 10px 12px;
    font-weight: 600;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
    text-align: left;
    white-space: nowrap;
}

.quotation-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: top;
}

.quotation-table tr:last-child td {
    border-bottom: none;
}

/* ==========================================================================
   8. EXPORT CHECKLIST (Clickable cards, Clear progress bar)
   ========================================================================== */
.checklist-item-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}

.checklist-item-card:hover {
    border-color: #D1D5DB;
    background: #FAFAFC;
}

.checklist-item-card.is-checked {
    background: #F9FAFB;
    border-color: #E5E7EB;
}

.checklist-checkbox {
    width: 18px;
    height: 18px;
    accent-color: var(--brand);
    cursor: pointer;
    margin-top: 2px;
    flex-shrink: 0;
}

.checklist-item-title {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 2px;
}

.checklist-item-desc {
    font-size: 12px;
    color: var(--muted);
    line-height: 1.45;
}

.checklist-progress-outer {
    height: 8px;
    background: var(--border);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 24px;
}

.checklist-progress-inner {
    height: 100%;
    background: var(--brand);
    border-radius: 999px;
    width: 0%;
    transition: width 0.25s ease;
}

/* ==========================================================================
   9. QUALITY & ORIGINS (Left-aligned, Unified 80–83 SCA score)
   ========================================================================== */
.origin-profile-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    height: 100%;
}

.origin-profile-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 10px;
}

/* ==========================================================================
   10. FIRA AI ADVISOR (Inline Input, 2x2 Suggestion Grid)
   ========================================================================== */
.advisor-input-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.advisor-input-row .form-control-clean {
    height: 44px;
    font-size: 14px;
}

.advisor-input-row .btn-brand-primary {
    height: 44px;
    padding: 0 20px;
}

.advisor-grid-2x2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 24px;
}

.advisor-suggestion-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    font-size: 13px;
    color: var(--text);
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.advisor-suggestion-card:hover {
    border-color: var(--brand);
    background: var(--brand-soft);
    color: var(--brand);
}

.advisor-suggestion-card i {
    color: var(--muted);
    font-size: 14px;
}

.advisor-suggestion-card:hover i {
    color: var(--brand);
}

.advisor-answer-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-top: 20px;
    display: none;
}

/* Mobile Sticky Bottom Summary Bar (ONLY below 1024px) */
.mobile-sticky-pl-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #FFFFFF;
    border-top: 1px solid var(--border);
    padding: 12px 20px;
    box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.05);
    z-index: 999;
    display: none;
    align-items: center;
    justify-content: space-between;
}

@media (max-width: 1023px) {
    .mobile-sticky-pl-bar.is-visible {
        display: flex;
    }
}

@media (min-width: 1024px) {
    .mobile-sticky-pl-bar {
        display: none !important;
    }
}

/* Responsive adjustments for Quotation & Advisor before intelligence system */
@media (max-width: 768px) {
    .tools-page-title {
        font-size: 20px;
    }
    .tools-command-header {
        padding: 12px 0 10px 0;
    }
    .advisor-grid-2x2 {
        grid-template-columns: 1fr;
    }
    .quotation-meta-grid {
        grid-template-columns: 1fr;
    }
}

/* ==========================================================================
   FIRA AI MARKET INTELLIGENCE // INSTITUTIONAL BLOOMBERG-STYLE SYSTEM
   ========================================================================== */
.market-intel-banner {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 22px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.intel-meta-strip {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 12px;
    color: var(--muted);
}

.intel-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    font-size: 11.5px;
    font-weight: 500;
    color: var(--text-secondary);
}

.intel-meta-pill.brand {
    background: var(--brand-soft);
    border-color: var(--brand-border);
    color: var(--brand);
    font-weight: 600;
}

/* Multi-Stage AI Analysis Progress Experience */
.ai-stage-progress-wrapper {
    background: #FFFFFF;
    border: 1px solid var(--brand-border);
    border-radius: var(--radius);
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(109, 26, 149, 0.06);
}

.stage-step-row {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 16px 0 8px 0;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 6px;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}

.stage-step-row::-webkit-scrollbar {
    display: none;
}

.stage-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    min-width: 90px;
}

.stage-dot {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #F3F4F6;
    border: 2px solid #E5E7EB;
    color: #9CA3AF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 4px;
    transition: all 0.25s ease;
}

.stage-item.completed .stage-dot {
    background: #10B981;
    border-color: #10B981;
    color: #FFFFFF;
}

.stage-item.current .stage-dot {
    background: var(--brand);
    border-color: var(--brand);
    color: #FFFFFF;
    box-shadow: 0 0 0 4px rgba(109, 26, 149, 0.25);
    animation: pulseStage 1.4s infinite;
}

.stage-label {
    font-size: 10.5px;
    font-weight: 600;
    color: #6B7280;
    line-height: 1.2;
}

.stage-item.current .stage-label {
    color: var(--brand);
}

.stage-item.completed .stage-label {
    color: #10B981;
}

@keyframes pulseStage {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

/* ==========================================================================
   MARKET PULSE BAR & WEB-APP METRIC STRIP (No Book-Like Essays)
   ========================================================================== */
.market-pulse-bar {
    background: #FAFAFD;
    border: 1px solid var(--border);
    border-left: 4px solid var(--brand) !important;
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 20px;
}

.pulse-metrics-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ECECF2;
    margin-bottom: 10px;
}

.pulse-metric-unit {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.pulse-metric-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

.pulse-metric-val {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text);
}

.pulse-takeaway {
    font-size: 13px;
    line-height: 1.5;
    color: var(--text-secondary);
}

/* ==========================================================================
   HIGH-DENSITY INSIGHT CARDS (Clean Glanceable Web Widgets)
   ========================================================================== */
.insight-analytic-box {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 16px 18px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.16s ease;
}

.insight-analytic-box:hover {
    border-color: var(--brand-border);
    box-shadow: 0 2px 8px rgba(109, 26, 149, 0.05);
}

.insight-analytic-box.leader-accent { border-top: 3px solid #10B981; }
.insight-analytic-box.floor-accent { border-top: 3px solid var(--brand); }
.insight-analytic-box.movement-accent { border-top: 3px solid #2563EB; }
.insight-analytic-box.pattern-accent { border-top: 3px solid #F59E0B; }

.insight-box-top {
    margin-bottom: 10px;
}

.insight-box-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.insight-card-hero {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.insight-hero-val {
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.1;
    letter-spacing: -0.4px;
}

.insight-hero-val small {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--muted);
}

.insight-hero-spec {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-secondary);
}

.insight-compact-details {
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-top: 1px dashed var(--border);
    padding-top: 10px;
    margin-top: auto;
}

.compact-detail-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    font-size: 12px;
    line-height: 1.4;
}

.compact-detail-row .detail-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--muted);
    min-width: 48px;
    flex-shrink: 0;
}

.compact-detail-row .detail-value {
    color: var(--text);
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Status Badges */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
}

.status-pill.increasing {
    background: #ECFDF5;
    color: #15803D;
    border: 1px solid #A7F3D0;
}

.status-pill.decreasing {
    background: #FEF2F2;
    color: #B91C1C;
    border: 1px solid #FECACA;
}

.status-pill.stable {
    background: #F3F4F6;
    color: #4B5563;
    border: 1px solid #E5E7EB;
}

.status-pill.new {
    background: var(--brand-soft);
    color: var(--brand);
    border: 1px solid var(--brand-border);
}

.status-pill.warning {
    background: #FFFBEB;
    color: #B45309;
    border: 1px solid #FDE68A;
}

/* Signals & Recommendations */
.signal-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 10px 14px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    transition: background 0.15s ease;
}

.signal-card:hover {
    background: #FAFAFD;
}

.signal-card.opportunity { border-left: 3px solid #10B981; }
.signal-card.risk { border-left: 3px solid #EF4444; }
.signal-card.guidance { border-left: 3px solid var(--brand); }

/* Filter Bar Styling */
.prices-filter-bar {
    background: #F9FAFB;
    border-bottom: 1px solid var(--border);
    padding: 12px 16px;
}

/* Origin Spec Details in Quality Tab */
.origin-spec-strip {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-top: 8px;
    border-top: 1px dashed var(--border);
}

/* Mobile spec cards */
.cards-mobile-view {
    display: none;
}

.mobile-spec-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.mobile-spec-card .head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 6px;
}

.mobile-spec-card .price-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 8px 0;
    border-top: 1px dashed var(--border);
    border-bottom: 1px dashed var(--border);
    margin: 6px 0;
}

.mobile-spec-card .foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: var(--muted);
}

/* ==========================================================================
   ULTRA-RESPONSIVE MEDIA QUERIES (Mobile, Tablet, Desktop)
   ========================================================================== */

/* Tablet & Mobile (<= 991px) */
@media (max-width: 991px) {
    .tools-page-root .container {
        padding-left: 16px;
        padding-right: 16px;
    }
    .clean-card {
        padding: 18px 16px;
        margin-bottom: 18px;
    }
    .results-panel-card {
        position: static !important;
        margin-top: 16px;
    }
    .quotation-sheet-preview {
        position: static !important;
        margin-top: 16px;
        padding: 20px 16px;
    }
}

/* Mobile & Small Tablet (<= 768px) */
@media (max-width: 768px) {
    .tools-page-root .container {
        padding-left: 12px;
        padding-right: 12px;
    }
    .tools-command-header {
        padding: 12px 0 10px 0;
    }
    .tools-header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .tools-page-title {
        font-size: 20px;
        letter-spacing: -0.3px;
    }
    .tools-page-subtitle {
        font-size: 12px;
    }
    .tools-thin-ticker {
        height: auto;
        padding: 8px 10px;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    .ticker-scroll-items {
        width: 100%;
        gap: 14px;
    }
    .ticker-timestamp {
        display: none !important;
    }
    .clean-card {
        padding: 16px 12px;
        border-radius: 10px;
        margin-bottom: 14px;
    }
    .clean-card-title {
        font-size: 16px;
    }
    .clean-card-subtitle {
        font-size: 11.5px;
    }
    .market-stat-card {
        padding: 14px 12px;
    }
    .stat-hero-number {
        font-size: 20px;
    }
    .pulse-metrics-strip {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .calc-preset-bar {
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 4px;
    }
    .calc-preset-btn {
        flex-shrink: 0;
        font-size: 11px;
        padding: 5px 10px;
    }
    .quotation-meta-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .quotation-header {
        flex-direction: column;
        gap: 8px;
    }
    .quotation-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .advisor-grid-2x2 {
        grid-template-columns: 1fr;
    }
    .table-desktop-view {
        display: none !important;
    }
    .cards-mobile-view {
        display: flex !important;
        flex-direction: column;
        gap: 10px;
    }
    .prices-filter-bar {
        padding: 10px 12px;
    }
}

/* Extra Small Phones (<= 480px) */
@media (max-width: 480px) {
    .tools-page-root .container {
        padding-left: 8px;
        padding-right: 8px;
    }
    .dock-tab-btn {
        padding: 6px 10px;
        font-size: 12px;
    }
    .advisor-input-row {
        flex-direction: column;
        align-items: stretch;
    }
    .advisor-input-row .btn-brand-primary {
        width: 100%;
        justify-content: center;
    }
}

@media (min-width: 769px) {
    .cards-mobile-view {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="tools-page-root">

    <!-- ====================================================================
         1. COMPACT COMMAND HEADER (Under 180px Total Height with Tabs)
         ==================================================================== -->
    <header class="tools-command-header">
        <div class="container">
            <div class="tools-header-row">
                <div>
                    <div class="tools-title-group">
                        <h1 class="tools-page-title">Free Tools</h1>
                        <span class="tools-badge-neutral">Ethiopian Coffee Export Suite</span>
                    </div>
                    <p class="tools-page-subtitle">
                        Authoritative Ethiopian coffee export benchmark pricing, corridor costing, and trade compliance.
                    </p>
                </div>

                <div>
                    @if(Auth::check())
                        <a href="{{ route('student.dashboard') }}" class="btn-brand-primary">
                            <i class="fa-solid fa-bookmark"></i>
                            <span>Saved Deals ({{ $savedCalculationsCount ?? 0 }})</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-brand-primary">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            <span>Sign In to Save Deals</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- ====================================================================
         2. SEGMENTED TAB DOCK
         ==================================================================== -->
    <nav class="tools-dock-wrap" aria-label="Trade Tools Navigation">
        <div class="container">
            <div class="segmented-dock" id="workspace-dock" role="tablist">
                <button class="dock-tab-btn active" data-target="workspace-market" role="tab" aria-selected="true">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Market Intelligence</span>
                </button>
                <button class="dock-tab-btn" data-target="workspace-calculator" role="tab" aria-selected="false">
                    <i class="fa-solid fa-calculator"></i>
                    <span>Profit Calculator</span>
                </button>
                <button class="dock-tab-btn" data-target="workspace-quotation" role="tab" aria-selected="false">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Quotation Builder</span>
                </button>
                <button class="dock-tab-btn" data-target="workspace-compliance" role="tab" aria-selected="false">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Export Checklist</span>
                </button>
                <button class="dock-tab-btn" data-target="workspace-quality" role="tab" aria-selected="false">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Quality &amp; Origins</span>
                </button>
                <button class="dock-tab-btn" data-target="workspace-advisor" role="tab" aria-selected="false">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>Fira AI Advisor</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- ====================================================================
         MAIN CONTENT CONTAINER (Centered, Max-Width 1280px, 24px padding)
         ==================================================================== -->
    <main class="container mt-4">

        <!-- ================================================================
             TAB 1: LIVE MARKET INTELLIGENCE
             ================================================================ -->
        <div class="workspace-pane active" id="workspace-market" role="tabpanel">

            @php($ftMoney = fn ($v) => $v === null ? '—' : '$' . number_format((float) $v, 3))
            <!-- Sourced coffee prices (same data as /api/market/overview). The old ticker had fixed
                 prices and a generated chart; both are gone. -->
            <div style="margin-bottom:24px">
                @include('market-prices.partials.market-card', ['overview' => $marketOverview])
            </div>

            <!-- Institutional Dataset Header Banner with Source, Effective Date & Action Buttons -->
            <div class="market-intel-banner mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="status-pill new">Official Regulatory Benchmark</span>
                            <span class="text-muted" style="font-size: 11.5px;">Schedule ID: #ECTA-{{ $latestPriceDataset->id ?? 'CURRENT' }}</span>
                        </div>
                        <h2 class="mb-1" style="font-size: 19px; font-weight: 700; color: var(--text); letter-spacing: -0.3px;">
                            {{ $latestPriceDataset->title ?? 'Ethiopian Coffee Minimum Export Prices — Reference Schedule' }}
                        </h2>
                        <div class="intel-meta-strip mt-2">
                            <span class="intel-meta-pill">
                                <i class="fa-solid fa-building-columns text-muted"></i>
                                <strong>Source:</strong> {{ $latestPriceDataset->source ?? 'Ethiopian Coffee and Tea Authority (ECTA)' }}
                            </span>
                            <span class="intel-meta-pill">
                                <i class="fa-regular fa-calendar text-muted"></i>
                                <strong>Effective:</strong> {{ $latestPriceDataset?->effective_date?->format('d M Y') ?? '—' }}
                            </span>
                            <span class="intel-meta-pill">
                                <i class="fa-regular fa-clock text-muted"></i>
                                <strong>Updated:</strong> {{ $latestPriceDataset?->updated_at?->format('d M Y H:i') ?? '—' }}
                            </span>
                            <span class="intel-meta-pill brand">
                                <i class="fa-solid fa-code-branch"></i>
                                <strong>Version:</strong> {{ $latestPriceDataset->version ?? 'v1.0' }}
                            </span>
                            @if(!empty($latestPriceDataset->file_name))
                                <span class="intel-meta-pill">
                                    <i class="fa-regular fa-file-excel text-muted"></i>
                                    {{ $latestPriceDataset->file_name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button class="btn-brand-primary" id="btn-analyze-coffee-prices" title="Trigger comprehensive Fira AI market synthesis on verified records">
                            <i class="fa-solid fa-sparkles"></i>
                            <span id="ai-btn-label">Analyze with Fira AI</span>
                        </button>
                        <a href="{{ route('free-tools.coffee-prices.export') }}" class="btn-secondary-action" title="Export complete verified minimum price dataset as CSV">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Export CSV</span>
                        </a>
                        <button class="btn-ghost-action" id="btn-global-refresh" title="Query latest market observation">
                            <i class="fa-solid fa-arrows-rotate" id="global-refresh-icon"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Multi-Stage AI Analysis Progress Experience (Shown during synthesis) -->
            <div class="ai-stage-progress-wrapper" id="ai-stage-progress-container" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-grow spinner-grow-sm text-primary" role="status" style="width: 12px; height: 12px;"></span>
                            <h3 class="m-0" style="font-size: 15px; font-weight: 700; color: var(--text);">
                                Fira AI Market Intelligence Synthesis in Progress
                            </h3>
                        </div>
                        <p class="text-muted m-0 mt-1" style="font-size: 12.5px;">
                            Applying institutional analytical framework to official regulatory minimum export benchmarks...
                        </p>
                    </div>
                    <span class="status-pill warning" id="ai-stage-pct">Step 1 of 5</span>
                </div>

                <div class="stage-step-row" id="ai-stages-tracker">
                    <div class="stage-item current" id="stage-step-1">
                        <div class="stage-dot">1</div>
                        <div class="stage-label">Reading records</div>
                    </div>
                    <div class="stage-item" id="stage-step-2">
                        <div class="stage-dot">2</div>
                        <div class="stage-label">Normalizing prices</div>
                    </div>
                    <div class="stage-item" id="stage-step-3">
                        <div class="stage-dot">3</div>
                        <div class="stage-label">Comparing values</div>
                    </div>
                    <div class="stage-item" id="stage-step-4">
                        <div class="stage-dot">4</div>
                        <div class="stage-label">Identifying trends</div>
                    </div>
                    <div class="stage-item" id="stage-step-5">
                        <div class="stage-dot">5</div>
                        <div class="stage-label">Generating insights</div>
                    </div>
                </div>

                <div class="p-2 rounded bg-light border text-muted mt-2 d-flex align-items-center gap-2" style="font-size: 12px;">
                    <i class="fa-solid fa-circle-info text-primary"></i>
                    <span id="ai-stage-status-msg">Ingesting official minimum price records...</span>
                </div>
            </div>

            <!-- 4 Market Overview Cards (Key Indicators) -->
            <div class="row g-3 mb-4" id="market-overview-stat-cards">
                <div class="col-6 col-lg-3">
                    <div class="market-stat-card">
                        <div class="stat-head">
                            <span class="stat-label">Highest Minimum Price</span>
                            <span class="status-pill increasing">Ceiling</span>
                        </div>
                        <div class="stat-hero-number" id="overview-highest-price">
                            {{ $ftMoney($marketSynthesis['snapshot']['highest_price'] ?? $coffeePricesSnapshot['highest_price'] ?? null) }}
                            <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>
                        </div>
                        <div class="stat-foot">
                            <span class="fw-semibold text-truncate" id="overview-highest-spec" style="max-width: 170px;" title="{{ $marketSynthesis['snapshot']['highest_spec'] ?? 'Yirgacheffe Grade 1' }}">
                                {{ $marketSynthesis['snapshot']['highest_spec'] ?? 'Yirgacheffe Grade 1' }}
                            </span>
                            <span class="stat-ref-muted">Regulatory Peak</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="market-stat-card">
                        <div class="stat-head">
                            <span class="stat-label">Lowest Minimum Price</span>
                            <span class="status-pill stable">Floor</span>
                        </div>
                        <div class="stat-hero-number" id="overview-lowest-price">
                            {{ $ftMoney($marketSynthesis['snapshot']['lowest_price'] ?? $coffeePricesSnapshot['lowest_price'] ?? null) }}
                            <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>
                        </div>
                        <div class="stat-foot">
                            <span class="fw-semibold text-truncate" id="overview-lowest-spec" style="max-width: 170px;" title="{{ $marketSynthesis['snapshot']['lowest_spec'] ?? 'Jimma Grade 5' }}">
                                {{ $marketSynthesis['snapshot']['lowest_spec'] ?? 'Jimma Grade 5' }}
                            </span>
                            <span class="stat-ref-muted">Baseline Floor</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="market-stat-card">
                        <div class="stat-head">
                            <span class="stat-label">Average Minimum Price</span>
                            <span class="status-pill brand">Mean</span>
                        </div>
                        <div class="stat-hero-number" id="overview-average-price">
                            {{ $ftMoney($marketSynthesis['snapshot']['average_price'] ?? $coffeePricesSnapshot['average_price'] ?? null) }}
                            <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>
                        </div>
                        <div class="stat-foot">
                            <span class="fw-semibold" id="overview-median-spread">
                                Spread: {{ $ftMoney($marketSynthesis['snapshot']['price_spread'] ?? $coffeePricesSnapshot['price_spread'] ?? null) }}
                            </span>
                            <span class="stat-ref-muted">Market Width</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="market-stat-card">
                        <div class="stat-head">
                            <span class="stat-label">Records Analyzed</span>
                            <span class="stat-badge-neutral">Scope</span>
                        </div>
                        <div class="stat-hero-number" id="overview-records-count">
                            {{ $marketSynthesis['snapshot']['records_analyzed'] ?? $coffeePricesSnapshot['total_records'] ?? count($coffeePricesRecords) }}
                            <small style="font-size: 12px; font-weight: 500; color: var(--muted);">Specs</small>
                        </div>
                        <div class="stat-foot">
                            <span class="fw-semibold" id="overview-effective-date">
                                {{ $latestPriceDataset?->effective_date?->format('d M Y') ?? '—' }}
                            </span>
                            <span class="stat-ref-muted">Active Schedule</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fira AI Market Intelligence Dashboard (High-Density Institutional Dashboard) -->
            <div class="clean-card mb-4" id="fira-ai-intelligence-dashboard">
                <div class="clean-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="status-pill brand"><i class="fa-solid fa-sparkles"></i> Fira AI Synthesis</span>
                            <span class="text-muted" style="font-size: 11px;">FOB Djibouti Schedule &bull; Institutional Analysis</span>
                        </div>
                        <h2 class="clean-card-title m-0">Market Intelligence &amp; Trade Synthesis</h2>
                    </div>
                    <span class="intel-meta-pill" id="ai-synthesis-timestamp">
                        <i class="fa-regular fa-clock"></i>
                        <span>Analyzed: {{ isset($marketSynthesis['analyzed_at']) ? \Carbon\Carbon::parse($marketSynthesis['analyzed_at'])->format('d M Y H:i T') : date('d M Y') }}</span>
                    </span>
                </div>

                <!-- Modern Web Market Pulse Bar (Replaces verbose book essay) -->
                <div class="market-pulse-bar mb-3">
                    <div class="pulse-metrics-strip">
                        <div class="pulse-metric-unit">
                            <span class="pulse-metric-label"><i class="fa-solid fa-circle-check text-success"></i> REGULATORY STATUS</span>
                            <span class="pulse-metric-val">ECTA Floor Active</span>
                        </div>
                        <div class="pulse-metric-unit">
                            <span class="pulse-metric-label"><i class="fa-solid fa-arrows-left-right text-primary"></i> ACTIVE TRADING CORRIDOR</span>
                            <span class="pulse-metric-val font-monospace" id="pulse-corridor-val">{{ $ftMoney($marketSynthesis['snapshot']['lowest_price'] ?? null) }} &ndash; {{ $ftMoney($marketSynthesis['snapshot']['highest_price'] ?? null) }} / lb</span>
                        </div>
                        <div class="pulse-metric-unit">
                            <span class="pulse-metric-label"><i class="fa-solid fa-scale-balanced text-warning"></i> MANDATORY TRADING RULE</span>
                            <span class="pulse-metric-val">FOB Djibouti &ge; Statutory Floor</span>
                        </div>
                    </div>
                    <div class="pulse-takeaway" id="ai-executive-summary-text">
                        <strong class="me-2 text-dark"><i class="fa-solid fa-bolt text-warning"></i> Executive Summary:</strong> {{ $marketSynthesis['insights']['executive_summary'] ?? 'Active statutory benchmark: All export contracts must meet or exceed published minimum prices to obtain NBE and customs export permits.' }}
                    </div>
                </div>

                <!-- 4 Structured Insight Cards (Grid) - High density web widgets -->
                <div class="row g-3 mb-3" id="ai-structured-insights-grid">
                    <!-- 1. Price Leader -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="insight-analytic-box leader-accent">
                            <div class="insight-box-top">
                                <div class="insight-box-title">
                                    <span class="text-success"><i class="fa-solid fa-trophy me-1"></i> Price Leader</span>
                                    <span class="status-pill increasing">Ceiling</span>
                                </div>
                                <div class="insight-card-hero">
                                    <div class="insight-hero-val font-monospace" id="insight-leader-price">{{ $ftMoney($marketSynthesis['snapshot']['highest_price'] ?? null) }} <small>/ lb</small></div>
                                    <div class="insight-hero-spec text-truncate" id="insight-leader-title" title="{{ $marketSynthesis['insights']['price_leader']['title'] ?? 'Highest Benchmark' }}">{{ $marketSynthesis['insights']['price_leader']['title'] ?? 'Highest Benchmark' }}</div>
                                </div>
                            </div>
                            <div class="insight-compact-details">
                                <div class="compact-detail-row">
                                    <span class="detail-label">Trigger</span>
                                    <span class="detail-value text-secondary" id="insight-leader-obs">{{ $marketSynthesis['insights']['price_leader']['observation'] ?? 'Top reference level established across active schedule.' }}</span>
                                </div>
                                <div class="compact-detail-row">
                                    <span class="detail-label">Action</span>
                                    <span class="detail-value text-dark fw-semibold" id="insight-leader-action">{{ $marketSynthesis['insights']['price_leader']['action'] ?? 'Verify buyer willingness to absorb regulatory differentials.' }}</span>
                                </div>
                                <span id="insight-leader-evidence" class="d-none">{{ $marketSynthesis['insights']['price_leader']['evidence'] ?? '' }}</span>
                                <span id="insight-leader-implication" class="d-none">{{ $marketSynthesis['insights']['price_leader']['commercial_implication'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Lowest Reference -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="insight-analytic-box floor-accent">
                            <div class="insight-box-top">
                                <div class="insight-box-title">
                                    <span style="color: var(--brand);"><i class="fa-solid fa-shield-halved me-1"></i> Lowest Reference</span>
                                    <span class="status-pill stable">Floor</span>
                                </div>
                                <div class="insight-card-hero">
                                    <div class="insight-hero-val font-monospace" id="insight-floor-price">{{ $ftMoney($marketSynthesis['snapshot']['lowest_price'] ?? null) }} <small>/ lb</small></div>
                                    <div class="insight-hero-spec text-truncate" id="insight-floor-title" title="{{ $marketSynthesis['insights']['lowest_reference']['title'] ?? 'Baseline Floor Benchmark' }}">{{ $marketSynthesis['insights']['lowest_reference']['title'] ?? 'Baseline Floor Benchmark' }}</div>
                                </div>
                            </div>
                            <div class="insight-compact-details">
                                <div class="compact-detail-row">
                                    <span class="detail-label">Trigger</span>
                                    <span class="detail-value text-secondary" id="insight-floor-obs">{{ $marketSynthesis['insights']['lowest_reference']['observation'] ?? 'Commercial grade reference floor across active schedule.' }}</span>
                                </div>
                                <div class="compact-detail-row">
                                    <span class="detail-label">Action</span>
                                    <span class="detail-value text-dark fw-semibold" id="insight-floor-action">{{ $marketSynthesis['insights']['lowest_reference']['action'] ?? 'Ensure low-cost commercial parcels do not breach floor.' }}</span>
                                </div>
                                <span id="insight-floor-evidence" class="d-none">{{ $marketSynthesis['insights']['lowest_reference']['evidence'] ?? '' }}</span>
                                <span id="insight-floor-implication" class="d-none">{{ $marketSynthesis['insights']['lowest_reference']['commercial_implication'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Largest Movement -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="insight-analytic-box movement-accent">
                            <div class="insight-box-top">
                                <div class="insight-box-title">
                                    <span class="text-primary"><i class="fa-solid fa-arrow-trend-up me-1"></i> Largest Movement</span>
                                    <span class="status-pill brand">Shift</span>
                                </div>
                                <div class="insight-card-hero">
                                    <div class="insight-hero-val font-monospace" id="insight-movement-price">{{ $marketSynthesis['snapshot']['movement_badge'] ?? 'Active' }}</div>
                                    <div class="insight-hero-spec text-truncate" id="insight-movement-title" title="{{ $marketSynthesis['insights']['largest_movement']['title'] ?? 'Shift vs Previous Benchmark' }}">{{ $marketSynthesis['insights']['largest_movement']['title'] ?? 'Shift vs Previous Benchmark' }}</div>
                                </div>
                            </div>
                            <div class="insight-compact-details">
                                <div class="compact-detail-row">
                                    <span class="detail-label">Trigger</span>
                                    <span class="detail-value text-secondary" id="insight-movement-obs">{{ $marketSynthesis['insights']['largest_movement']['observation'] ?? 'Largest observed variance against prior published dataset.' }}</span>
                                </div>
                                <div class="compact-detail-row">
                                    <span class="detail-label">Action</span>
                                    <span class="detail-value text-dark fw-semibold" id="insight-movement-action">{{ $marketSynthesis['insights']['largest_movement']['action'] ?? 'Compare quotation date against historical adjustment interval.' }}</span>
                                </div>
                                <span id="insight-movement-evidence" class="d-none">{{ $marketSynthesis['insights']['largest_movement']['evidence'] ?? '' }}</span>
                                <span id="insight-movement-implication" class="d-none">{{ $marketSynthesis['insights']['largest_movement']['commercial_implication'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Emerging Pattern / Regional Spread -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="insight-analytic-box pattern-accent">
                            <div class="insight-box-top">
                                <div class="insight-box-title">
                                    <span class="text-warning"><i class="fa-solid fa-diagram-project me-1"></i> Regional Spread</span>
                                    <span class="status-pill warning">Structure</span>
                                </div>
                                <div class="insight-card-hero">
                                    <div class="insight-hero-val font-monospace" id="insight-pattern-price">{{ $ftMoney($marketSynthesis['snapshot']['price_spread'] ?? null) }} <small>/ lb spread</small></div>
                                    <div class="insight-hero-spec text-truncate" id="insight-pattern-title" title="{{ $marketSynthesis['insights']['emerging_pattern']['title'] ?? 'Origin & Grade Differentials' }}">{{ $marketSynthesis['insights']['emerging_pattern']['title'] ?? 'Origin & Grade Differentials' }}</div>
                                </div>
                            </div>
                            <div class="insight-compact-details">
                                <div class="compact-detail-row">
                                    <span class="detail-label">Trigger</span>
                                    <span class="detail-value text-secondary" id="insight-pattern-obs">{{ $marketSynthesis['insights']['emerging_pattern']['observation'] ?? 'Market spreads between washed and natural processing methods.' }}</span>
                                </div>
                                <div class="compact-detail-row">
                                    <span class="detail-label">Action</span>
                                    <span class="detail-value text-dark fw-semibold" id="insight-pattern-action">{{ $marketSynthesis['insights']['emerging_pattern']['action'] ?? 'Align procurement strategy to high-yield grade spreads.' }}</span>
                                </div>
                                <span id="insight-pattern-evidence" class="d-none">{{ $marketSynthesis['insights']['emerging_pattern']['evidence'] ?? '' }}</span>
                                <span id="insight-pattern-implication" class="d-none">{{ $marketSynthesis['insights']['emerging_pattern']['commercial_implication'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Market Signals Grid (Opportunities vs Risks vs Guidance) - High density chips -->
                <div class="row g-3 mb-3" id="ai-market-signals-container">
                    <!-- Column 1: Opportunity Signals -->
                    <div class="col-12 col-lg-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                <span class="fw-bold" style="font-size: 12px; color: #15803D;">
                                    <i class="fa-solid fa-arrow-trend-up me-1"></i> Opportunity Indicators
                                </span>
                                <span class="status-pill increasing">Favorable</span>
                            </div>
                            <div class="d-flex flex-column gap-2" id="opportunity-signals-list">
                                @forelse($marketSynthesis['opportunity_signals'] ?? [] as $opp)
                                    <div class="signal-card opportunity">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 12px;">{{ $opp['title'] ?? 'Market Opportunity' }}</div>
                                            <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">{{ $opp['description'] ?? $opp['message'] ?? '' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted text-center py-2" style="font-size: 11.5px;">Active regulatory floor provides firm export quotation support.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Risk Indicators -->
                    <div class="col-12 col-lg-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                <span class="fw-bold" style="font-size: 12px; color: #B91C1C;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Risk Indicators
                                </span>
                                <span class="status-pill decreasing">Watch</span>
                            </div>
                            <div class="d-flex flex-column gap-2" id="risk-signals-list">
                                @forelse($marketSynthesis['risk_signals'] ?? [] as $risk)
                                    <div class="signal-card risk">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 12px;">{{ $risk['title'] ?? 'Regulatory Risk' }}</div>
                                            <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">{{ $risk['description'] ?? $risk['message'] ?? '' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted text-center py-2" style="font-size: 11.5px;">Contracts below published minimums risk rejection by ECTA registration desks.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Commercial Guidance -->
                    <div class="col-12 col-lg-4">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                <span class="fw-bold" style="font-size: 12px; color: var(--brand);">
                                    <i class="fa-solid fa-handshake me-1"></i> Buyer &amp; Seller Guidance
                                </span>
                                <span class="status-pill brand">Action</span>
                            </div>
                            <div class="d-flex flex-column gap-2" id="buyer-seller-signals-list">
                                @forelse($marketSynthesis['buyer_seller_signals'] ?? [] as $sig)
                                    <div class="signal-card guidance">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 12px;">{{ $sig['target'] ?? (is_string($sig) ? 'Direct Guidance' : 'Commercial Rule') }}</div>
                                            <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">{{ is_string($sig) ? $sig : ($sig['signal'] ?? $sig['guidance'] ?? '') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted text-center py-2" style="font-size: 11.5px;">Exporters should confirm minimum prices prior to locking buyer price commitments.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directives Row -->
                <div class="p-2.5 rounded-3 border bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-clipboard-check text-muted" style="font-size: 12px;"></i>
                        <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text);">
                            Commercial Action Directives
                        </span>
                    </div>
                    <div class="row g-2" id="ai-recommended-actions-list">
                        @forelse($marketSynthesis['insights']['recommended_actions'] ?? [] as $idx => $act)
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center gap-2 bg-white p-2 rounded border" style="font-size: 12px;">
                                    <i class="fa-solid fa-circle-check text-success" style="font-size: 12px; flex-shrink: 0;"></i>
                                    <span class="text-secondary text-truncate">{{ $act }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted" style="font-size: 11.5px;">1. Compare active buyer contracts against schedule. 2. Verify corridor logistics margin before booking.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Regional Comparisons & Grade Spread Matrix -->
            <div class="clean-card mb-4" id="regional-comparison-section">
                <div class="clean-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h2 class="clean-card-title m-0">Regional Reference Price Comparison &amp; Spread Matrix</h2>
                        <div class="clean-card-subtitle">Aggregated minimum price spread across verified Ethiopian production origins</div>
                    </div>
                    <span class="stat-badge-neutral">FOB Djibouti Basis</span>
                </div>

                <div class="table-responsive-clean">
                    <table class="clean-table" id="regional-comparison-table">
                        <thead>
                            <tr>
                                <th>Origin Region</th>
                                <th class="text-center">Specifications</th>
                                <th class="text-end">Floor Price ($/lb)</th>
                                <th class="text-end">Ceiling Price ($/lb)</th>
                                <th class="text-end">Regional Average ($/lb)</th>
                                <th class="text-end">Spread vs Baseline</th>
                            </tr>
                        </thead>
                        <tbody id="regional-comparison-tbody">
                            @forelse($marketSynthesis['regional_breakdown'] ?? [] as $region)
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="font-size: 13.5px; color: var(--text);">{{ $region['origin'] ?? 'Ethiopian Origin' }}</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Arabica Specialty &amp; Commercial Grades</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="stat-badge-neutral">{{ $region['total_specs'] ?? $region['count'] ?? 1 }} Specs</span>
                                    </td>
                                    <td class="text-end font-monospace" style="font-size: 13px;">
                                        ${{ number_format($region['min_price'] ?? 0, 3) }}
                                    </td>
                                    <td class="text-end font-monospace" style="font-size: 13px;">
                                        ${{ number_format($region['max_price'] ?? 0, 3) }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold" style="font-size: 13.5px; color: var(--brand);">
                                        ${{ number_format($region['avg_price'] ?? 0, 3) }}
                                    </td>
                                    <td class="text-end font-monospace" style="font-size: 13px;">
                                        @php($baseFloor = $marketSynthesis['snapshot']['lowest_price'] ?? ($region['min_price'] ?? 0))
                                        @php($premium = ($region['avg_price'] ?? 0) - $baseFloor)
                                        <span class="{{ $premium > 0 ? 'text-success' : 'text-muted' }} fw-semibold">
                                            +${{ number_format(max(0, $premium), 3) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Awaiting regional dataset aggregation...</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dedicated Coffee Minimum Prices Table Section -->
            <div class="clean-card mb-4" id="coffee-prices-section">
                <div class="clean-card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="status-pill brand">Official ECTA Schedule</span>
                            <span class="text-muted" style="font-size: 11.5px;">All Registered Commercial &amp; Specialty Lines</span>
                        </div>
                        <h2 class="clean-card-title m-0">Coffee Minimum Reference Prices Table</h2>
                        <div class="clean-card-subtitle">Search, filter, and review mandated minimum floor prices for export contracts</div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('free-tools.coffee-prices.export') }}" class="btn-secondary-action btn-sm" title="Export entire table as CSV">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Export CSV</span>
                        </a>
                        <button class="btn-brand-primary btn-sm btn-analyze-coffee-prices" title="Analyze this dataset with Fira AI">
                            <i class="fa-solid fa-sparkles"></i>
                            <span>Analyze with Fira AI</span>
                        </button>
                    </div>
                </div>

                <!-- Modern Interactive Filtering Bar -->
                <div class="prices-filter-bar">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="input-field-wrapper">
                                <span class="input-prefix" style="left: 10px;"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="prices-search-input" class="form-control-clean has-prefix" placeholder="Search coffee type, origin, grade..." style="height: 38px; font-size: 12.5px;">
                            </div>
                        </div>

                        <div class="col-6 col-md-2 col-lg-2">
                            <select class="form-control-clean" id="prices-origin-filter" style="height: 38px; font-size: 12px; font-weight: 500;">
                                <option value="all">All Origins</option>
                                <option value="Sidama">Sidama</option>
                                <option value="Yirgacheffe">Yirgacheffe</option>
                                <option value="Guji">Guji</option>
                                <option value="Limu">Limu</option>
                                <option value="Jimma">Jimma</option>
                                <option value="Harar">Harar</option>
                                <option value="Teppi">Teppi</option>
                                <option value="Bebeka">Bebeka</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2 col-lg-2">
                            <select class="form-control-clean" id="prices-grade-filter" style="height: 38px; font-size: 12px; font-weight: 500;">
                                <option value="all">All Grades</option>
                                <option value="Grade 1">Grade 1</option>
                                <option value="Grade 2">Grade 2</option>
                                <option value="Grade 3">Grade 3</option>
                                <option value="Grade 4">Grade 4</option>
                                <option value="Grade 5">Grade 5</option>
                                <option value="Undergrade">Undergrade</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2 col-lg-2">
                            <select class="form-control-clean" id="prices-process-filter" style="height: 38px; font-size: 12px; font-weight: 500;">
                                <option value="all">All Processing</option>
                                <option value="Washed">Washed</option>
                                <option value="Natural">Natural / Unwashed</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2 col-lg-2">
                            <select class="form-control-clean" id="prices-movement-filter" style="height: 38px; font-size: 12px; font-weight: 500;">
                                <option value="all">All Movements</option>
                                <option value="increasing">Increasing</option>
                                <option value="decreasing">Decreasing</option>
                                <option value="stable">Stable</option>
                                <option value="new">New Record</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-auto col-lg-1 text-end">
                            <button class="btn-ghost-action w-100" id="btn-reset-price-filters" title="Reset filters" style="height: 38px; font-size: 12px; padding: 0 10px; justify-content: center;">
                                <i class="fa-solid fa-arrow-rotate-left"></i> <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="table-responsive-clean table-desktop-view">
                    <table class="clean-table" id="coffee-prices-table">
                        <thead>
                            <tr>
                                <th>Coffee Spec / Type</th>
                                <th>Origin</th>
                                <th>Grade</th>
                                <th>Processing</th>
                                <th class="text-end">Current Minimum</th>
                                <th class="text-end">Previous Price</th>
                                <th class="text-center">Change / Movement</th>
                                <th>Effective Date</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="coffee-prices-tbody">
                            @forelse($coffeePricesRecords ?? [] as $record)
                                <tr data-origin="{{ $record->origin }}" data-grade="{{ $record->grade }}" data-process="{{ $record->processing_method }}" data-movement="{{ $record->price_change_direction }}">
                                    <td>
                                        <div class="fw-bold" style="font-size: 13.5px; color: var(--text);">{{ $record->coffee_type }}</div>
                                        <div class="text-muted" style="font-size: 11px;">Ref: #{{ $record->id }} &bull; Currency: {{ $record->currency }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $record->origin }}</span>
                                    </td>
                                    <td>
                                        <span class="stat-badge-neutral">{{ $record->grade }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary" style="font-size: 12px;">{{ $record->processing_method }}</span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold" style="font-size: 14px; color: var(--text);">
                                        ${{ number_format($record->minimum_price, 3) }} <small class="text-muted" style="font-size: 10px;">/lb</small>
                                    </td>
                                    <td class="text-end font-monospace" style="font-size: 12.5px;">
                                        @if(!is_null($record->previous_price))
                                            ${{ number_format($record->previous_price, 3) }}
                                        @else
                                            <span class="text-muted" style="font-size: 11px;" title="No prior benchmark found for this exact origin/grade">No previous record</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php($dir = $record->price_change_direction)
                                        @if($dir === 'increasing')
                                            <span class="status-pill increasing">
                                                <i class="fa-solid fa-arrow-trend-up"></i> +${{ number_format(abs($record->price_change), 3) }}
                                            </span>
                                        @elseif($dir === 'decreasing')
                                            <span class="status-pill decreasing">
                                                <i class="fa-solid fa-arrow-trend-down"></i> -${{ number_format(abs($record->price_change), 3) }}
                                            </span>
                                        @elseif($dir === 'stable')
                                            <span class="status-pill stable">
                                                <i class="fa-solid fa-minus"></i> Stable
                                            </span>
                                        @else
                                            <span class="status-pill new">
                                                <i class="fa-solid fa-asterisk"></i> New
                                            </span>
                                        @endif
                                    </td>
                                    <td style="font-size: 12px; color: var(--muted);">
                                        {{ $record->effective_date?->format('d M Y') ?? 'N/A' }}
                                    </td>
                                    <td style="font-size: 11.5px; color: var(--muted);">
                                        {{ $latestPriceDataset->source ?? 'ECTA' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No coffee minimum price records found in this dataset.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Responsive Mobile Cards View (< 768px) -->
                <div class="cards-mobile-view p-3" id="coffee-prices-mobile-cards">
                    @forelse($coffeePricesRecords ?? [] as $record)
                        <div class="mobile-spec-card" data-origin="{{ $record->origin }}" data-grade="{{ $record->grade }}" data-process="{{ $record->processing_method }}" data-movement="{{ $record->price_change_direction }}">
                            <div class="head">
                                <div>
                                    <div class="fw-bold" style="font-size: 14px; color: var(--text);">{{ $record->coffee_type }}</div>
                                    <div class="text-muted" style="font-size: 12px;">{{ $record->origin }} &bull; {{ $record->grade }} &bull; {{ $record->processing_method }}</div>
                                </div>
                                <div>
                                    @php($dir = $record->price_change_direction)
                                    @if($dir === 'increasing')
                                        <span class="status-pill increasing"><i class="fa-solid fa-arrow-trend-up"></i> +${{ number_format(abs($record->price_change), 3) }}</span>
                                    @elseif($dir === 'decreasing')
                                        <span class="status-pill decreasing"><i class="fa-solid fa-arrow-trend-down"></i> -${{ number_format(abs($record->price_change), 3) }}</span>
                                    @elseif($dir === 'stable')
                                        <span class="status-pill stable"><i class="fa-solid fa-minus"></i> Stable</span>
                                    @else
                                        <span class="status-pill new"><i class="fa-solid fa-asterisk"></i> New</span>
                                    @endif
                                </div>
                            </div>

                            <div class="price-row">
                                <div>
                                    <div class="text-muted" style="font-size: 11px;">MINIMUM FLOOR</div>
                                    <div class="fw-bold font-monospace" style="font-size: 17px; color: var(--brand);">
                                        ${{ number_format($record->minimum_price, 3) }} <small style="font-size: 11px; color: var(--muted);">/ lb</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted" style="font-size: 11px;">PREVIOUS BENCHMARK</div>
                                    <div class="font-monospace text-secondary" style="font-size: 13px;">
                                        @if(!is_null($record->previous_price))
                                            ${{ number_format($record->previous_price, 3) }}
                                        @else
                                            <span class="text-muted" style="font-size: 11px;">No previous record</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="foot">
                                <span><i class="fa-regular fa-calendar me-1"></i> {{ $record->effective_date?->format('d M Y') ?? 'N/A' }}</span>
                                <span><i class="fa-solid fa-building-columns me-1"></i> {{ $latestPriceDataset->source ?? 'ECTA' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No records available.</div>
                    @endforelse
                </div>

                <!-- Footer Summary Counter -->
                <div class="p-3 border-top bg-light d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 12px; color: var(--muted);">
                    <div>
                        Showing <strong id="price-records-count" class="text-dark">{{ count($coffeePricesRecords) }}</strong> active regulatory specifications
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span>Basis: FOB Djibouti (USD/lb)</span>
                        <span>&bull;</span>
                        <span>Official Source: {{ $latestPriceDataset->source ?? 'Ethiopian Coffee and Tea Authority' }}</span>
                    </div>
                </div>
            </div>

            <!-- Historical Monthly Trend Chart & FOB Djibouti Differentials -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="clean-card h-100 mb-0">
                        <div class="clean-card-header">
                            <div>
                                <h2 class="clean-card-title">Arabica Indicator Historical Trend</h2>
                                <div class="clean-card-subtitle">Published monthly ICO composite benchmarks (US¢/lb)</div>
                            </div>
                            <span class="stat-badge-neutral">12-Month Period</span>
                        </div>
                        <div style="height: 250px; position: relative;">
                            <canvas id="marketTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="clean-card h-100 mb-0">
                        <div class="clean-card-header">
                            <div>
                                <h2 class="clean-card-title">Ethiopian Origin Export Differentials &amp; ECX References</h2>
                                <div class="clean-card-subtitle">Indicative FOB Djibouti export quotations vs domestic Commodity Exchange references</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-control-clean" id="table-origin-filter" style="width: auto; height: 32px; font-size: 11.5px; font-weight: 500;">
                                    <option value="all">All Origins</option>
                                    <option value="Sidama">Sidama</option>
                                    <option value="Yirgacheffe">Yirgacheffe</option>
                                    <option value="Guji">Guji</option>
                                    <option value="Limu">Limu</option>
                                    <option value="Djimmah">Jimma / Djimmah</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive-clean" style="max-height: 250px; overflow-y: auto;">
                            <table class="clean-table" id="market-origins-table">
                                <thead>
                                    <tr>
                                        <th>Coffee Spec</th>
                                        <th>Origin</th>
                                        <th class="text-end">FOB Djibouti</th>
                                        <th class="text-end">Differential</th>
                                        <th class="text-end">ECX Ref</th>
                                    </tr>
                                </thead>
                                <tbody id="market-table-body">
                                    @forelse($marketData['origins'] ?? [] as $origin)
                                        <tr data-origin="{{ $origin['origin'] ?? '' }}" data-process="{{ $origin['processing'] ?? '' }}">
                                            <td>
                                                <div class="fw-semibold" style="font-size: 12.5px;">{{ $origin['name'] ?? 'Ethiopian Coffee' }}</div>
                                                <div class="text-muted" style="font-size: 11px;">{{ $origin['grade'] ?? 'Grade 2' }}</div>
                                            </td>
                                            <td><span class="stat-badge-neutral" style="font-size: 11px;">{{ $origin['origin'] ?? 'Ethiopia' }}</span></td>
                                            <td class="text-end fw-bold font-monospace" style="font-size: 13px;">
                                                ${{ number_format($origin['price_usd_per_lb'] ?? $origin['price'] ?? 0, 3) }}
                                            </td>
                                            @php($diff = trim(str_ireplace('(indicative)', '', $origin['differential'] ?? '+0 US¢/lb')) )
                                            @php($diff = str_replace(['+-', '-'], '−', $diff))
                                            @php($isNeg = str_contains($diff, '−'))
                                            <td class="text-end fw-semibold font-monospace {{ $isNeg ? 'text-danger' : 'text-success' }}" style="font-size: 12px;">
                                                {{ $diff }}
                                            </td>
                                            <td class="text-end text-muted font-monospace" style="font-size: 11.5px;">
                                                @if(!empty($origin['ecx_reference_etb_per_kg']))
                                                    {{ number_format($origin['ecx_reference_etb_per_kg'], 0) }} ETB
                                                @else
                                                    <span>—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">{{ __('market.web.origins_removed') }} <a href="{{ route('market-prices.index') }}">{{ __('market.web.breadcrumb') }}</a></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================
             TAB 2: COFFEE EXPORT PROFIT CALCULATOR
             ================================================================ -->
        <div class="workspace-pane" id="workspace-calculator" role="tabpanel">

            <!-- Presets Bar -->
            <div class="calc-preset-bar">
                <span class="text-muted fw-semibold me-1" style="font-size: 12px; text-transform: uppercase;">Quick Presets:</span>
                <button class="calc-preset-btn active" data-preset="sidama-g2">
                    <i class="fa-solid fa-coffee-bean text-muted"></i> 1 FCL Sidama Washed G2
                </button>
                <button class="calc-preset-btn" data-preset="yirga-g1">
                    <i class="fa-solid fa-sparkles text-muted"></i> 1 FCL Yirgacheffe Specialty G1
                </button>
                <button class="calc-preset-btn" data-preset="guji-nat1">
                    <i class="fa-solid fa-sun text-muted"></i> 1 FCL Guji Natural G1
                </button>
                <button class="calc-preset-btn" data-preset="limu-g3">
                    <i class="fa-solid fa-box text-muted"></i> 2 FCL Commercial Limu G3
                </button>
            </div>

            <div class="row g-4">
                <!-- Inputs Column (Left) -->
                <div class="col-lg-7">
                    <div class="clean-card mb-0">
                        <div class="clean-card-header">
                            <div>
                                <h2 class="clean-card-title">Corridor Cost &amp; Cargo Inputs</h2>
                                <div class="clean-card-subtitle">Addis Ababa &bull; Modjo Dry Port &bull; Djibouti Corridor</div>
                            </div>
                            <button class="btn-ghost-action" id="btn-reset-calculator">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                            </button>
                        </div>

                        <!-- Section 1: Volume & Packaging -->
                        <div class="calc-section-label">Shipment Volume &amp; Packaging</div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-bags" class="input-field-label">Quantity of Bags</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-bags" class="form-control-clean has-suffix" value="320" min="1" step="1" />
                                        <span class="input-suffix">bags</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-bag-weight" class="input-field-label">Bag Net Weight</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-bag-weight" class="form-control-clean has-suffix" value="60" min="1" step="0.5" />
                                        <span class="input-suffix">kg</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Domestic Sourcing & Logistics Costs (ETB) -->
                        <div class="calc-section-label">Domestic Purchase &amp; Transport Costs (ETB)</div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-purchase-etb-kg" class="input-field-label">ECX / Farm Cherry Price</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-purchase-etb-kg" class="form-control-clean has-suffix" value="340" min="0" step="5" />
                                        <span class="input-suffix">ETB/kg</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-processing-bag" class="input-field-label">Milling &amp; Grading</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-processing-bag" class="form-control-clean has-suffix" value="180" min="0" step="5" />
                                        <span class="input-suffix">ETB/bag</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-packaging-bag" class="input-field-label">Export Packaging (Jute + GrainPro)</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-packaging-bag" class="form-control-clean has-suffix" value="450" min="0" step="10" />
                                        <span class="input-suffix">ETB/bag</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-field-group">
                                    <label for="calc-transport-corridor" class="input-field-label">Djibouti Corridor Freight</label>
                                    <div class="input-field-wrapper">
                                        <input type="text" inputmode="numeric" id="calc-transport-corridor" class="form-control-clean has-suffix" value="380,000" />
                                        <span class="input-suffix">ETB</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="input-field-group">
                                    <label for="calc-handling-customs" class="input-field-label">Customs, ECTA Inspection &amp; Port Handling</label>
                                    <div class="input-field-wrapper">
                                        <input type="text" inputmode="numeric" id="calc-handling-customs" class="form-control-clean has-suffix" value="165,000" />
                                        <span class="input-suffix">ETB</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Commercial Terms & FX -->
                        <div class="calc-section-label">International Selling Price &amp; FX Rate</div>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="input-field-group">
                                    <label for="calc-selling-usd-lb" class="input-field-label">Selling Price (USD)</label>
                                    <div class="input-field-wrapper">
                                        <span class="input-prefix">$</span>
                                        <input type="number" id="calc-selling-usd-lb" class="form-control-clean has-prefix has-suffix" value="3.10" min="0.1" step="0.05" />
                                        <span class="input-suffix">/ lb</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="input-field-group">
                                    <label for="calc-exchange-rate" class="input-field-label">Exchange Rate</label>
                                    <div class="input-field-wrapper">
                                        <input type="number" id="calc-exchange-rate" class="form-control-clean has-suffix" value="140.0" min="1" step="0.5" />
                                        <span class="input-suffix">ETB</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="input-field-group">
                                    <label for="calc-incoterm" class="input-field-label">Incoterm 2020</label>
                                    <select id="calc-incoterm" class="form-control-clean">
                                        <option value="FOB" selected>FOB Djibouti</option>
                                        <option value="CIF">CIF Destination</option>
                                        <option value="CFR">CFR Destination</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Light Results Panel (Right) -->
                <div class="col-lg-5">
                    <div class="results-panel-card" id="target-pl-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-semibold" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px;">
                                Deal P&amp;L Analysis
                            </span>
                            <span class="margin-pill-clean text-success" id="calc-margin-pill" style="background: var(--up-soft);">
                                60.4% Margin
                            </span>
                        </div>

                        <!-- Hero Metric (Single Net Margin Number in Purple) -->
                        <div class="results-hero-box">
                            <div class="results-hero-label">Estimated Net Export Margin</div>
                            <div class="results-hero-val" id="res-net-profit-usd">$79,258</div>
                            <div class="results-hero-etb" id="res-net-profit-etb">11,096,060 ETB</div>
                        </div>

                        <!-- Cost Distribution Ratio Bar -->
                        <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 11.5px;">
                            <span>Cost Breakdown</span>
                            <span id="res-margin-ratio-label">60.4% Profit Margin</span>
                        </div>
                        <div class="results-ratio-bar">
                            <div class="ratio-seg ratio-seg-sourcing" id="bar-sourcing" style="width: 35%;" title="Sourcing"></div>
                            <div class="ratio-seg ratio-seg-corridor" id="bar-corridor" style="width: 3%;" title="Corridor Freight"></div>
                            <div class="ratio-seg ratio-seg-processing" id="bar-milling" style="width: 1%;" title="Milling & Packaging"></div>
                            <div class="ratio-seg ratio-seg-margin" id="bar-margin" style="width: 61%;" title="Net Margin"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted mb-3" style="font-size: 11px;">
                            <span>■ Sourcing</span>
                            <span>■ Freight</span>
                            <span>■ Processing</span>
                            <span>■ Net Margin</span>
                        </div>

                        <!-- Breakdown Data Rows -->
                        <div class="results-data-row">
                            <span class="results-data-label">Net Shipment Cargo:</span>
                            <span class="results-data-val" id="res-net-weight">19,200 kg (42,329 lbs)</span>
                        </div>
                        <div class="results-data-row">
                            <span class="results-data-label">Gross Export Revenue (USD):</span>
                            <span class="results-data-val" id="res-gross-revenue-usd">$131,219</span>
                        </div>
                        <div class="results-data-row">
                            <span class="results-data-label">Gross Export Revenue (ETB):</span>
                            <span class="results-data-val" id="res-gross-revenue-etb">18,370,660 ETB</span>
                        </div>
                        <div class="results-data-row">
                            <span class="results-data-label">Total Corridor Cost (ETB):</span>
                            <span class="results-data-val" id="res-total-cost-etb">7,274,600 ETB</span>
                        </div>
                        <div class="results-data-row">
                            <span class="results-data-label">Break-Even Selling Price:</span>
                            <span class="results-data-val" id="res-breakeven-price">$1.23 / lb FOB</span>
                        </div>
                        <div class="results-data-row">
                            <span class="results-data-label">Landed Cost per Kg:</span>
                            <span class="results-data-val" id="res-cost-per-kg">378.89 ETB / kg</span>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="mt-4 pt-3 border-top d-flex gap-2">
                            <button class="btn-secondary-action w-100" onclick="window.print()">
                                <i class="fa-solid fa-print me-1"></i> Print P&amp;L
                            </button>
                            @if(Auth::check())
                                <button class="btn-brand-primary w-100 justify-content-center" id="btn-save-calc-action">
                                    <i class="fa-solid fa-bookmark me-1"></i> Save Deal
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="btn-brand-primary w-100 justify-content-center">
                                    Sign In to Save
                                </a>
                            @endif
                        </div>

                        <!-- Mandatory Muted Note -->
                        <div class="text-muted mt-3 text-center" style="font-size: 11.5px;">
                            * Excludes financing, bank charges, insurance, and shrinkage.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================================================================
             TAB 3: EXPORT QUOTATION BUILDER
             ================================================================ -->
        <div class="workspace-pane" id="workspace-quotation" role="tabpanel">
            <div class="row g-4">
                <!-- Quotation Form (Left) -->
                <div class="col-lg-5">
                    <div class="clean-card mb-0">
                        <div class="clean-card-header">
                            <div>
                                <h2 class="clean-card-title">Offer Parameters</h2>
                                <div class="clean-card-subtitle">Commercial Contract Specifications</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="quot-exporter-name" class="input-field-label">Exporter Company Entity</label>
                                <input type="text" class="form-control-clean" id="quot-exporter-name" placeholder="e.g. Oromia Coffee Farmers Cooperative Union" />
                            </div>
                            <div class="col-12">
                                <label for="quot-buyer-name" class="input-field-label">Buyer &amp; Destination Port</label>
                                <input type="text" class="form-control-clean" id="quot-buyer-name" placeholder="e.g. Hanseatic Specialty Coffee GmbH (Hamburg, Germany)" />
                            </div>
                            <div class="col-6">
                                <label for="quot-origin" class="input-field-label">Origin Region</label>
                                <select class="form-control-clean" id="quot-origin">
                                    <option value="Sidama" selected>Sidama</option>
                                    <option value="Yirgacheffe (Gedeo)">Yirgacheffe</option>
                                    <option value="Guji">Guji</option>
                                    <option value="Limu">Limu</option>
                                    <option value="Kaffa">Kaffa</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="quot-grade" class="input-field-label">Grade &amp; Process</label>
                                <select class="form-control-clean" id="quot-grade">
                                    <option value="Grade 1 Fully Washed">Grade 1 Washed</option>
                                    <option value="Grade 2 Fully Washed" selected>Grade 2 Washed</option>
                                    <option value="Grade 1 Natural / Sun-Dried">Grade 1 Natural</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="quot-bags" class="input-field-label">Bags (60kg Net)</label>
                                <input type="number" class="form-control-clean" id="quot-bags" value="320" min="1" />
                            </div>
                            <div class="col-6">
                                <label for="quot-price" class="input-field-label">Price (USD/lb)</label>
                                <input type="number" class="form-control-clean" id="quot-price" value="3.25" step="0.05" min="0.01" />
                            </div>
                            <div class="col-6">
                                <label for="quot-incoterm" class="input-field-label">Incoterm 2020</label>
                                <select class="form-control-clean" id="quot-incoterm">
                                    <option value="FOB Port of Djibouti" selected>FOB Djibouti</option>
                                    <option value="CIF Hamburg Port">CIF Hamburg</option>
                                    <option value="CIF Yokohama Port">CIF Yokohama</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="quot-payment" class="input-field-label">Payment Term</label>
                                <select class="form-control-clean" id="quot-payment">
                                    <option value="100% Irrevocable LC at Sight" selected>100% LC at Sight</option>
                                    <option value="Cash Against Documents (CAD)">CAD</option>
                                    <option value="30% Advance, 70% vs B/L">30% Advance, 70% BL</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="quot-tasting-notes" class="input-field-label">Cup Notes &amp; Tasting Profile</label>
                                <textarea class="form-control-clean" id="quot-tasting-notes" rows="2" style="height: auto;">Bergamot citrus, floral jasmine, honey sweetness, clean cup (SCA 80–83).</textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- A4 Sheet Preview (Right, Sticky, Readable Min 13px) -->
                <div class="col-lg-7">
                    <div class="quotation-sheet-preview" id="quotation-print-target">
                        <!-- Subtle corner watermark badge -->
                        <span class="quotation-corner-badge">DRAFT OFFER</span>

                        <!-- Sheet Header -->
                        <div class="quotation-header">
                            <div>
                                <div class="quotation-brand">FIRA LINK</div>
                                <div class="fw-bold" style="font-size: 13.5px; color: var(--text);">GREEN COFFEE EXPORT PROFORMA</div>
                                <div class="text-muted" style="font-size: 12px;">Addis Ababa, Ethiopia &bull; Registered Green Coffee Exporter</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-muted" style="font-size: 12px;">REF: FL-EXP-{{ date('Y') }}-{{ strtoupper(Str::random(5)) }}</div>
                                <div class="text-muted" style="font-size: 12px;">Date: {{ date('F d, Y') }}</div>
                            </div>
                        </div>

                        <!-- Exporter & Buyer Entities -->
                        <div class="quotation-meta-grid">
                            <div>
                                <span class="text-muted fw-semibold" style="font-size: 11.5px; text-transform: uppercase;">Seller / Exporter:</span>
                                <div class="fw-bold" id="prev-seller">Export Entity Placeholder</div>
                                <div class="text-muted" style="font-size: 12px;">Federal Democratic Republic of Ethiopia</div>
                            </div>
                            <div>
                                <span class="text-muted fw-semibold" style="font-size: 11.5px; text-transform: uppercase;">Buyer / Consignee:</span>
                                <div class="fw-bold" id="prev-buyer">Buyer Entity Placeholder</div>
                                <div class="text-muted" style="font-size: 12px;">Destination Port As Specified</div>
                            </div>
                        </div>

                        <!-- Quotation Table (No Awkward Wraps) -->
                        <div class="quotation-table-wrap">
                            <table class="quotation-table">
                                <thead>
                                    <tr>
                                        <th>Specification</th>
                                        <th>Origin</th>
                                        <th style="white-space: nowrap;">Quantity</th>
                                        <th style="white-space: nowrap;">Unit Price</th>
                                        <th class="text-end" style="white-space: nowrap;">Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-bold" id="prev-grade-desc">Sidama Grade 2 Fully Washed</div>
                                            <div class="text-muted" style="font-size: 12px;" id="prev-notes">Bergamot citrus, floral jasmine, honey sweetness</div>
                                        </td>
                                        <td id="prev-origin" style="white-space: nowrap;">Sidama</td>
                                        <td id="prev-qty" style="white-space: nowrap;">320 Bags (19,200 kg)</td>
                                        <td class="fw-bold" id="prev-price" style="white-space: nowrap;">$3.25 / lb</td>
                                        <td class="fw-bold text-end" id="prev-total" style="white-space: nowrap; color: var(--brand);">$137,568.27</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Quotation Terms Grid -->
                        <div class="row g-2 mb-3" style="font-size: 12.5px;">
                            <div class="col-6"><strong>Delivery Term:</strong> <span id="prev-incoterm">FOB Port of Djibouti</span></div>
                            <div class="col-6"><strong>Payment Term:</strong> <span id="prev-payment">100% Irrevocable LC at Sight</span></div>
                            <div class="col-6"><strong>Packaging:</strong> 60kg Jute with GrainPro hermetic liners</div>
                            <div class="col-6"><strong>Offer Validity:</strong> 14 Days from issuance</div>
                        </div>

                        <!-- Sheet Footer & Print Action -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
                            <span class="text-muted" style="font-size: 11.5px;">Subject to pre-shipment sample (PSS) approval &amp; ECTA contract registration.</span>
                            <button class="btn-brand-primary" onclick="window.print()">
                                <i class="fa-solid fa-print"></i> Print / PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================
             TAB 4: EXPORT CHECKLIST & COMPLIANCE
             ================================================================ -->
        <div class="workspace-pane" id="workspace-compliance" role="tabpanel">
            <div class="clean-card">
                <div class="clean-card-header">
                    <div>
                        <h2 class="clean-card-title">Ethiopian Coffee Export Statutory Checklist</h2>
                        <div class="clean-card-subtitle">ECTA regulatory filings, NBE FX clearance, and international destination compliance</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-semibold text-muted" id="checklist-progress-text" style="font-size: 13px;">0 of 8 completed</span>
                        <button class="btn-ghost-action" id="btn-reset-checklist">Reset</button>
                    </div>
                </div>

                <!-- Progress Bar (8px, Rounded) -->
                <div class="checklist-progress-outer">
                    <div class="checklist-progress-inner" id="checklist-progress-bar"></div>
                </div>

                <div class="row g-3">
                    <!-- Column 1: Statutory Filings -->
                    <div class="col-12 col-md-6">
                        <div class="text-muted fw-semibold mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            1. Statutory Government &amp; Banking Filings
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-1')">
                            <input type="checkbox" id="chk-doc-1" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">ECTA Export Contract Registration</div>
                                <div class="checklist-item-desc">Mandatory price validation against published regulatory floors.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-2')">
                            <input type="checkbox" id="chk-doc-2" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">NBE Foreign Exchange Permit (FX Approval)</div>
                                <div class="checklist-item-desc">Foreign exchange permit issued via commercial banks under LC or CAD.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-3')">
                            <input type="checkbox" id="chk-doc-3" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">Certificate of Origin (Chamber of Commerce)</div>
                                <div class="checklist-item-desc">Preferential tariff certificate validating pure Ethiopian origin.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-4')">
                            <input type="checkbox" id="chk-doc-4" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">Phytosanitary Certificate (MoA)</div>
                                <div class="checklist-item-desc">Ministry of Agriculture quarantine inspection &amp; sanitary clearance.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Logistics & Destination -->
                    <div class="col-12 col-md-6">
                        <div class="text-muted fw-semibold mb-2" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            2. Logistics &amp; Destination Regulations
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-5')">
                            <input type="checkbox" id="chk-doc-5" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">Clean Ocean Bill of Lading (B/L 3/3 Set)</div>
                                <div class="checklist-item-desc">Port of Djibouti 3/3 original negotiable title set for bank release.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-6')">
                            <input type="checkbox" id="chk-doc-6" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">Commercial Invoice &amp; Detailed Packing List</div>
                                <div class="checklist-item-desc">Itemized gross/net weights and container seal declarations.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-7')">
                            <input type="checkbox" id="chk-doc-7" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">EUDR Geolocation &amp; Deforestation Compliance</div>
                                <div class="checklist-item-desc">GPS polygon farm mapping verifying post-2020 non-deforestation.</div>
                            </div>
                        </div>

                        <div class="checklist-item-card" onclick="window.toggleChecklistItem('chk-doc-8')">
                            <input type="checkbox" id="chk-doc-8" class="checklist-checkbox" />
                            <div>
                                <div class="checklist-item-title">Pre-Shipment Sample (PSS) Approval Notice</div>
                                <div class="checklist-item-desc">Signed cupping signoff on moisture (10.5–11.5%) &amp; SCA score.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ================================================================
             TAB 5: QUALITY STANDARDS & REGIONAL ORIGINS
             ================================================================ -->
        <div class="workspace-pane" id="workspace-quality" role="tabpanel">
            <!-- 3 Regional Origin Cards (High Density Specifications) -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="origin-profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="origin-profile-title m-0">Yirgacheffe &amp; Sidama</h3>
                            <span class="status-pill increasing">Specialty</span>
                        </div>
                        <div class="origin-spec-strip">
                            <div class="compact-detail-row">
                                <span class="detail-label">Altitude</span>
                                <span class="detail-value font-monospace">1,750 – 2,200m ASL</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Profile</span>
                                <span class="detail-value text-secondary">Floral jasmine, bergamot citrus, crisp acidity</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Grades</span>
                                <span class="detail-value">Washed Grade 1 &amp; 2 (SCA 80–83+)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="origin-profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="origin-profile-title m-0">Guji &amp; Harar</h3>
                            <span class="status-pill brand">Natural</span>
                        </div>
                        <div class="origin-spec-strip">
                            <div class="compact-detail-row">
                                <span class="detail-label">Altitude</span>
                                <span class="detail-value font-monospace">1,500 – 2,100m ASL</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Profile</span>
                                <span class="detail-value text-secondary">Heavy ripe berries, blueberries, dark chocolate</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Grades</span>
                                <span class="detail-value">Sun-Dried Natural Grade 1 &amp; 2</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="origin-profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="origin-profile-title m-0">Limu &amp; Jimma</h3>
                            <span class="status-pill stable">Commercial</span>
                        </div>
                        <div class="origin-spec-strip">
                            <div class="compact-detail-row">
                                <span class="detail-label">Altitude</span>
                                <span class="detail-value font-monospace">1,400 – 1,950m ASL</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Profile</span>
                                <span class="detail-value text-secondary">Balanced milk chocolate, brown sugar, red apple</span>
                            </div>
                            <div class="compact-detail-row">
                                <span class="detail-label">Grades</span>
                                <span class="detail-value">Commercial &amp; Specialty Washed G2/G3, Jimma G5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ECTA Green Coffee Grading Standards Table -->
            <div class="clean-card">
                <div class="clean-card-header">
                    <div>
                        <h2 class="clean-card-title">ECTA Green Coffee Grading &amp; Moisture Tolerances</h2>
                        <div class="clean-card-subtitle">Official Ethiopian Coffee &amp; Tea Authority export grade criteria</div>
                    </div>
                </div>

                <div class="table-responsive-clean">
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Grade Tier</th>
                                <th>Defect Count (per 300g sample)</th>
                                <th>Sensory Cup Standard</th>
                                <th>Target Moisture Range</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Grade 1 (Specialty)</strong></td>
                                <td>0 – 3 primary defects</td>
                                <td>Floral jasmine, intense sweetness, SCA 84+</td>
                                <td>10.5% – 11.5%</td>
                            </tr>
                            <tr>
                                <td><strong>Grade 2 (Specialty / Premium)</strong></td>
                                <td>4 – 12 full defects</td>
                                <td>Clean cup, bright acidity, balanced sweetness, SCA 80–83</td>
                                <td>10.5% – 11.5%</td>
                            </tr>
                            <tr>
                                <td><strong>Grade 3 (Standard Export)</strong></td>
                                <td>13 – 25 defects</td>
                                <td>Good commercial cup, mild flavor attributes</td>
                                <td>10.5% – 12.0%</td>
                            </tr>
                            <tr>
                                <td><strong>Grade 4 &amp; 5 (Commercial Natural)</strong></td>
                                <td>26 – 86 defects</td>
                                <td>Commercial body, suitable for roasting &amp; blending</td>
                                <td>11.0% – 12.5%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================
             TAB 6: FIRA AI EXPORT ADVISOR
             ================================================================ -->
        <div class="workspace-pane" id="workspace-advisor" role="tabpanel">
            <div class="clean-card">
                <div class="clean-card-header">
                    <div>
                        <h2 class="clean-card-title">Fira AI Export Advisor</h2>
                        <div class="clean-card-subtitle">Instant export trade intelligence, regulatory guidance, and corridor pricing insights</div>
                    </div>
                </div>

                <!-- Input Row: Input + Button Inline at Same 44px Height -->
                <div class="advisor-input-row">
                    <input type="text" id="advisor-query-input" class="form-control-clean" placeholder="Ask any question regarding Ethiopian coffee export regulations, ECTA rules, or corridors..." />
                    <button class="btn-brand-primary" id="btn-ask-advisor">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Ask</span>
                    </button>
                </div>

                <!-- 2x2 Suggestion Cards Grid -->
                <div class="advisor-grid-2x2">
                    <div class="advisor-suggestion-card" onclick="window.useAdvisorPrompt('What are current ECTA export contract registration rules?')">
                        <i class="fa-solid fa-file-contract"></i>
                        <span>ECTA Export Contract Registration Guidelines</span>
                    </div>
                    <div class="advisor-suggestion-card" onclick="window.useAdvisorPrompt('How do I calculate FOB Djibouti price from local ECX prices?')">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Calculating FOB Djibouti Pricing from ECX References</span>
                    </div>
                    <div class="advisor-suggestion-card" onclick="window.useAdvisorPrompt('What documents are required for coffee export to the EU under EUDR?')">
                        <i class="fa-solid fa-leaf"></i>
                        <span>EUDR Deforestation Compliance Documentation</span>
                    </div>
                    <div class="advisor-suggestion-card" onclick="window.useAdvisorPrompt('What is the difference between Grade 1 and Grade 2 Ethiopian Washed coffee?')">
                        <i class="fa-solid fa-award"></i>
                        <span>Grade 1 vs Grade 2 Quality &amp; Sensory Specs</span>
                    </div>
                </div>

                <!-- Advisor Reply Card (Empty/Loading/Loaded States) -->
                <div class="advisor-answer-card" id="advisor-reply-box">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <span class="fw-semibold text-dark" style="font-size: 13.5px;">Advisor Analysis</span>
                        <button class="btn-ghost-action" onclick="window.copyAdvisorReply()">
                            <i class="fa-regular fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div id="advisor-reply-text" style="font-size: 14px; line-height: 1.7; color: var(--text);"></div>
                </div>

            </div>
        </div>

    </main>

    <!-- Mobile Sticky Bottom Floating Bar (ONLY below 1024px) -->
    <div class="mobile-sticky-pl-bar" id="mobile-pnl-pill" onclick="window.scrollToPnlCard()">
        <div>
            <span class="fw-bold" id="m-pill-profit" style="font-size: 16px; color: var(--brand);">$79,258</span>
            <span class="margin-pill-clean text-success ms-2" id="m-pill-margin" style="background: var(--up-soft); font-size: 11.5px;">60.4% Margin</span>
        </div>
        <div class="text-muted" style="font-size: 12px; font-weight: 500;">
            <span>View P&amp;L <i class="fa-solid fa-chevron-up ms-1"></i></span>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    const CONFIG = {
        pollIntervalMs: 5000,
        marketDataUrl: '{{ route("free-tools.market-data") }}',
        aiAnalysisUrl: '{{ route("free-tools.ai-analysis") }}',
        coffeePricesUrl: '{{ route("free-tools.coffee-prices") }}',
        calculateUrl: '{{ route("free-tools.calculate") }}',
        saveCalculationUrl: '{{ route("free-tools.save-calculation") }}',
        askAdvisorUrl: '{{ route("free-tools.ask-advisor") }}',
        csrfToken: '{{ csrf_token() }}',
    };

    let pollTimer = null;
    let isRequestInFlight = false;
    let lastEtag = null;
    let trendChartInstance = null;
    let currentMarketData = @json($marketData ?? []);

    // 1. DOCK TAB SWITCHING WITH DEEP LINKING
    const dockTabs = document.querySelectorAll('.dock-tab-btn');
    const workspaces = document.querySelectorAll('.workspace-pane');

    function switchWorkspace(targetId) {
        dockTabs.forEach(tab => {
            const isMatch = tab.getAttribute('data-target') === targetId;
            tab.classList.toggle('active', isMatch);
            tab.setAttribute('aria-selected', isMatch ? 'true' : 'false');
        });
        workspaces.forEach(pane => {
            const isMatch = pane.id === targetId;
            pane.classList.toggle('active', isMatch);
        });

        // Toggle mobile sticky P&L bar visibility ONLY on calculator tab
        const mobileBar = document.getElementById('mobile-pnl-pill');
        if (mobileBar) {
            if (targetId === 'workspace-calculator') {
                mobileBar.classList.add('is-visible');
            } else {
                mobileBar.classList.remove('is-visible');
            }
        }

        // Trigger chart resize if switching to market tab
        if (targetId === 'workspace-market' && trendChartInstance) {
            setTimeout(() => trendChartInstance.resize(), 50);
        }
    }

    dockTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            switchWorkspace(targetId);
            window.location.hash = targetId.replace('workspace-', '');
        });
    });

    // Check initial hash
    const initialHash = window.location.hash.replace('#', '');
    if (initialHash) {
        const matchingPane = document.getElementById(`workspace-${initialHash}`);
        if (matchingPane) switchWorkspace(`workspace-${initialHash}`);
    }

    window.scrollToPnlCard = function() {
        const target = document.getElementById('target-pl-card');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    // 2. REAL-TIME 5-SECOND POLLING WITH CONDITIONAL 304
    function startPolling() {
        stopPolling();
        pollTimer = setInterval(fetchMarketData, CONFIG.pollIntervalMs);
    }

    function stopPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
    }

    async function fetchMarketData(force = false) {
        if (isRequestInFlight) return;
        if (document.hidden && !force) return;

        isRequestInFlight = true;
        const icon = document.getElementById('global-refresh-icon');
        if (icon) icon.classList.add('fa-spin');

        try {
            const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            if (lastEtag && !force) headers['If-None-Match'] = lastEtag;

            const url = force ? `${CONFIG.marketDataUrl}?force=1` : CONFIG.marketDataUrl;
            const res = await fetch(url, { headers });

            if (res.status === 304) {
                return;
            }
            // The old market feed is retired (410): stop polling instead of asking every few seconds.
            if (res.status === 410) {
                stopPolling();
                return;
            }

            if (res.ok) {
                const etag = res.headers.get('ETag');
                if (etag) lastEtag = etag;

                const json = await res.json();
                if (json.success && json.data) {
                    currentMarketData = json.data;
                    renderMarketData(json.data);
                }
            }
        } catch (err) {
            console.warn('Market stream issue:', err);
        } finally {
            isRequestInFlight = false;
            if (icon) icon.classList.remove('fa-spin');
        }
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
            fetchMarketData();
        }
    });

    const btnGlobalRefresh = document.getElementById('btn-global-refresh');
    if (btnGlobalRefresh) btnGlobalRefresh.addEventListener('click', () => fetchMarketData(true));

    function renderMarketData(data) {
        if (!data) return;

        const obsEl = document.getElementById('ticker-obs-date');
        if (obsEl && data.observed_at) obsEl.textContent = data.observed_at;

        const usd = (data.ico_composite_usd_lb || 0).toFixed(4);
        const usc = (data.ico_composite_usc_lb || 0).toFixed(2);

        const tickerIco = document.getElementById('ticker-ico-val');
        const tickerUsc = document.getElementById('ticker-ico-cents');
        const statIcoUsd = document.getElementById('stat-ico-usd');
        const statIcoUsc = document.getElementById('stat-ico-usc');

        if (tickerIco) tickerIco.textContent = `$${usd}`;
        if (tickerUsc) tickerUsc.textContent = `${usc}¢`;
        if (statIcoUsd) statIcoUsd.textContent = `$${usd}`;
        if (statIcoUsc) statIcoUsc.innerHTML = `<i class="fa-solid fa-arrow-trend-up"></i> ${usc}¢`;

        if (data.origins && data.origins.length > 0) {
            const tbody = document.getElementById('market-table-body');
            if (tbody) {
                let html = '';
                data.origins.forEach(origin => {
                    const price = parseFloat(origin.price_usd_per_lb || origin.price || 0).toFixed(3);
                    const rawDiff = (origin.differential || '+0 US¢/lb').replace(/\s*\(indicative\)/gi, '').trim();
                    const diff = rawDiff.replace('+-', '−').replace('-', '−');
                    const isNeg = diff.includes('−');
                    const diffClass = isNeg ? 'text-danger' : 'text-success';

                    const trendBadge = (origin.trend === 'bearish') 
                        ? `<span class="text-danger fw-semibold" style="font-size: 12px;"><i class="fa-solid fa-arrow-trend-down me-1"></i> Bearish</span>`
                        : `<span class="text-success fw-semibold" style="font-size: 12px;"><i class="fa-solid fa-arrow-trend-up me-1"></i> Bullish</span>`;

                    const ecxRef = origin.ecx_reference_etb_per_kg 
                        ? `${Math.round(origin.ecx_reference_etb_per_kg).toLocaleString()} ETB`
                        : `<span>—</span>`;

                    html += `
                        <tr data-origin="${origin.origin || ''}" data-process="${origin.processing || ''}">
                            <td>
                                <div class="table-coffee-title">${origin.name || 'Ethiopian Coffee'}</div>
                                <div class="table-coffee-sub">${origin.grade || 'Grade 2'} &bull; ${origin.processing || 'Fully Washed'}</div>
                            </td>
                            <td>${origin.origin || 'Ethiopia'}</td>
                            <td><span class="stat-badge-neutral">${origin.processing || 'Fully Washed'}</span></td>
                            <td class="text-end fw-bold" style="font-size: 14px;">$${price}</td>
                            <td class="text-end fw-semibold ${diffClass}">${diff}</td>
                            <td class="text-end text-muted">${ecxRef}</td>
                            <td>${trendBadge}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }
        }
    }

    // 3. CHART.JS AREA CHART (Light Grid, Hover Dots, Every 3rd Month Label)
    function initTrendChart(history) {
        const canvas = document.getElementById('marketTrendChart');
        if (!canvas) return;

        const labels = history.map(h => h.date ? h.date.substring(0, 7) : '');
        const values = history.map(h => h.composite_usc || 0);

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, 'rgba(109, 26, 149, 0.14)');
        gradient.addColorStop(1, 'rgba(109, 26, 149, 0.00)');

        trendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'ICO Composite (US¢/lb)',
                    data: values,
                    borderColor: '#6D1A95',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#6D1A95',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A1A2E',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: ctx => `Indicator: ${ctx.parsed.y.toFixed(1)} US¢/lb ($${(ctx.parsed.y/100).toFixed(4)}/lb)`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            color: '#6B7280',
                            autoSkip: false,
                            callback: function(val, index) {
                                return index % 3 === 0 ? this.getLabelForValue(val) : '';
                            }
                        }
                    },
                    y: {
                        grid: { color: '#F3F4F6' },
                        ticks: {
                            font: { size: 11 },
                            color: '#6B7280',
                            callback: val => `${val}¢`
                        }
                    }
                }
            }
        });
    }

    // 4. REAL-TIME PROFIT CALCULATOR ARITHMETIC & PRESETS
    const KG_TO_LBS = 2.20462262;
    const calcBags = document.getElementById('calc-bags');
    const calcBagWeight = document.getElementById('calc-bag-weight');
    const calcPurchaseEtbKg = document.getElementById('calc-purchase-etb-kg');
    const calcProcessingBag = document.getElementById('calc-processing-bag');
    const calcPackagingBag = document.getElementById('calc-packaging-bag');
    const calcTransport = document.getElementById('calc-transport-corridor');
    const calcHandling = document.getElementById('calc-handling-customs');
    const calcSellingUsdLb = document.getElementById('calc-selling-usd-lb');
    const calcExchangeRate = document.getElementById('calc-exchange-rate');

    function parseNum(val, defaultVal = 0) {
        if (val === null || val === undefined) return defaultVal;
        const clean = val.toString().replace(/,/g, '').trim();
        const parsed = parseFloat(clean);
        return isNaN(parsed) ? defaultVal : parsed;
    }

    function updateCalculator() {
        const bags = Math.max(1, parseNum(calcBags?.value, 320));
        const bagWeight = Math.max(1, parseNum(calcBagWeight?.value, 60));
        const purchaseKg = Math.max(0, parseNum(calcPurchaseEtbKg?.value, 340));
        const processingBag = Math.max(0, parseNum(calcProcessingBag?.value, 180));
        const packagingBag = Math.max(0, parseNum(calcPackagingBag?.value, 450));
        const transportEtb = Math.max(0, parseNum(calcTransport?.value, 380000));
        const handlingEtb = Math.max(0, parseNum(calcHandling?.value, 165000));
        const sellingUsdLb = Math.max(0.01, parseNum(calcSellingUsdLb?.value, 3.10));
        const exchangeRate = Math.max(1, parseNum(calcExchangeRate?.value, 140));

        const netWeightKg = bags * bagWeight;
        const netWeightLbs = netWeightKg * KG_TO_LBS;

        const purchaseCostEtb = netWeightKg * purchaseKg;
        const processingCostEtb = bags * processingBag;
        const packagingCostEtb = bags * packagingBag;

        const totalCostEtb = purchaseCostEtb + processingCostEtb + packagingCostEtb + transportEtb + handlingEtb;
        const totalCostUsd = exchangeRate > 0 ? (totalCostEtb / exchangeRate) : 0;

        const grossRevenueUsd = netWeightLbs * sellingUsdLb;
        const grossRevenueEtb = grossRevenueUsd * exchangeRate;

        const netProfitEtb = grossRevenueEtb - totalCostEtb;
        const netProfitUsd = grossRevenueUsd - totalCostUsd;
        const marginPct = grossRevenueEtb > 0 ? ((netProfitEtb / grossRevenueEtb) * 100) : 0;

        const breakEvenUsdLb = (netWeightLbs > 0 && exchangeRate > 0) ? (totalCostEtb / (netWeightLbs * exchangeRate)) : 0;
        const costPerKg = netWeightKg > 0 ? (totalCostEtb / netWeightKg) : 0;

        // UI Outputs
        const resNetProfitUsd = document.getElementById('res-net-profit-usd');
        const resNetProfitEtb = document.getElementById('res-net-profit-etb');
        const resNetWeight = document.getElementById('res-net-weight');
        const resGrossUsd = document.getElementById('res-gross-revenue-usd');
        const resGrossEtb = document.getElementById('res-gross-revenue-etb');
        const resTotalCostEtb = document.getElementById('res-total-cost-etb');
        const resBreakEven = document.getElementById('res-breakeven-price');
        const resCostKg = document.getElementById('res-cost-per-kg');
        const marginPill = document.getElementById('calc-margin-pill');
        const marginRatioLabel = document.getElementById('res-margin-ratio-label');

        // Segmented Stacked Bar
        const barSourcing = document.getElementById('bar-sourcing');
        const barCorridor = document.getElementById('bar-corridor');
        const barMilling = document.getElementById('bar-milling');
        const barMargin = document.getElementById('bar-margin');

        if (grossRevenueEtb > 0) {
            const pSourcing = Math.max(0, Math.min(100, (purchaseCostEtb / grossRevenueEtb) * 100));
            const pCorridor = Math.max(0, Math.min(100, ((transportEtb + handlingEtb) / grossRevenueEtb) * 100));
            const pMilling = Math.max(0, Math.min(100, ((processingCostEtb + packagingCostEtb) / grossRevenueEtb) * 100));
            const pMargin = Math.max(0, Math.min(100, marginPct));

            if (barSourcing) barSourcing.style.width = `${pSourcing.toFixed(1)}%`;
            if (barCorridor) barCorridor.style.width = `${pCorridor.toFixed(1)}%`;
            if (barMilling) barMilling.style.width = `${pMilling.toFixed(1)}%`;
            if (barMargin) barMargin.style.width = `${pMargin.toFixed(1)}%`;
        }

        if (resNetProfitUsd) {
            resNetProfitUsd.textContent = `$${Math.round(netProfitUsd).toLocaleString()}`;
            resNetProfitUsd.style.color = netProfitUsd >= 0 ? 'var(--brand)' : 'var(--down)';
        }
        if (resNetProfitEtb) resNetProfitEtb.textContent = `${Math.round(netProfitEtb).toLocaleString()} ETB`;
        if (resNetWeight) resNetWeight.textContent = `${netWeightKg.toLocaleString()} kg (${Math.round(netWeightLbs).toLocaleString()} lbs)`;
        if (resGrossUsd) resGrossUsd.textContent = `$${Math.round(grossRevenueUsd).toLocaleString()}`;
        if (resGrossEtb) resGrossEtb.textContent = `${Math.round(grossRevenueEtb).toLocaleString()} ETB`;
        if (resTotalCostEtb) resTotalCostEtb.textContent = `${Math.round(totalCostEtb).toLocaleString()} ETB`;
        if (resBreakEven) resBreakEven.textContent = `$${breakEvenUsdLb.toFixed(2)} / lb FOB`;
        if (resCostKg) resCostKg.textContent = `${costPerKg.toFixed(2)} ETB / kg`;

        if (marginPill) {
            marginPill.textContent = `${marginPct.toFixed(1)}% Margin`;
            marginPill.className = marginPct >= 12 ? 'margin-pill-clean text-success' : (marginPct >= 0 ? 'margin-pill-clean text-warning' : 'margin-pill-clean text-danger');
            marginPill.style.backgroundColor = marginPct >= 12 ? 'var(--up-soft)' : (marginPct >= 0 ? '#FFFBEB' : 'var(--down-soft)');
        }
        if (marginRatioLabel) {
            marginRatioLabel.textContent = `${marginPct.toFixed(1)}% Profit Margin`;
        }

        // Mobile bottom summary bar
        const mPillProfit = document.getElementById('m-pill-profit');
        const mPillMargin = document.getElementById('m-pill-margin');
        if (mPillProfit) mPillProfit.textContent = `$${Math.round(netProfitUsd).toLocaleString()}`;
        if (mPillMargin) {
            mPillMargin.textContent = `${marginPct.toFixed(1)}% Margin`;
            mPillMargin.style.backgroundColor = marginPct >= 12 ? 'var(--up-soft)' : (marginPct >= 0 ? '#FFFBEB' : 'var(--down-soft)');
            mPillMargin.style.color = marginPct >= 12 ? 'var(--up)' : (marginPct >= 0 ? '#B45309' : 'var(--down)');
        }
    }

    [calcBags, calcBagWeight, calcPurchaseEtbKg, calcProcessingBag, calcPackagingBag, calcTransport, calcHandling, calcSellingUsdLb, calcExchangeRate].forEach(el => {
        if (el) el.addEventListener('input', updateCalculator);
    });

    if (calcTransport) {
        calcTransport.addEventListener('blur', function() {
            const v = parseNum(this.value, 0);
            if (v > 0) this.value = v.toLocaleString();
        });
    }
    if (calcHandling) {
        calcHandling.addEventListener('blur', function() {
            const v = parseNum(this.value, 0);
            if (v > 0) this.value = v.toLocaleString();
        });
    }

    // Preset Buttons
    const presetButtons = document.querySelectorAll('.calc-preset-btn');
    const presets = {
        'sidama-g2': { bags: 320, weight: 60, purchase: 340, proc: 180, pack: 450, freight: 380000, hand: 165000, price: 3.10, fx: 140.0 },
        'yirga-g1': { bags: 320, weight: 60, purchase: 450, proc: 220, pack: 480, freight: 380000, hand: 175000, price: 3.60, fx: 140.0 },
        'guji-nat1': { bags: 320, weight: 60, purchase: 420, proc: 150, pack: 460, freight: 390000, hand: 165000, price: 3.45, fx: 140.0 },
        'limu-g3': { bags: 640, weight: 60, purchase: 290, proc: 160, pack: 420, freight: 720000, hand: 300000, price: 2.85, fx: 140.0 },
    };

    presetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            presetButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const pKey = this.getAttribute('data-preset');
            const p = presets[pKey];
            if (p) {
                if (calcBags) calcBags.value = p.bags;
                if (calcBagWeight) calcBagWeight.value = p.weight;
                if (calcPurchaseEtbKg) calcPurchaseEtbKg.value = p.purchase;
                if (calcProcessingBag) calcProcessingBag.value = p.proc;
                if (calcPackagingBag) calcPackagingBag.value = p.pack;
                if (calcTransport) calcTransport.value = Number(p.freight).toLocaleString();
                if (calcHandling) calcHandling.value = Number(p.hand).toLocaleString();
                if (calcSellingUsdLb) calcSellingUsdLb.value = p.price;
                if (calcExchangeRate) calcExchangeRate.value = p.fx;
                updateCalculator();
            }
        });
    });

    const btnResetCalc = document.getElementById('btn-reset-calculator');
    if (btnResetCalc) {
        btnResetCalc.addEventListener('click', function() {
            const defaultPreset = presets['sidama-g2'];
            if (calcBags) calcBags.value = defaultPreset.bags;
            if (calcBagWeight) calcBagWeight.value = defaultPreset.weight;
            if (calcPurchaseEtbKg) calcPurchaseEtbKg.value = defaultPreset.purchase;
            if (calcProcessingBag) calcProcessingBag.value = defaultPreset.proc;
            if (calcPackagingBag) calcPackagingBag.value = defaultPreset.pack;
            if (calcTransport) calcTransport.value = Number(defaultPreset.freight).toLocaleString();
            if (calcHandling) calcHandling.value = Number(defaultPreset.hand).toLocaleString();
            if (calcSellingUsdLb) calcSellingUsdLb.value = defaultPreset.price;
            if (calcExchangeRate) calcExchangeRate.value = defaultPreset.fx;
            updateCalculator();
        });
    }

    // 5. QUOTATION BUILDER PREVIEW
    const quotExporter = document.getElementById('quot-exporter-name');
    const quotBuyer = document.getElementById('quot-buyer-name');
    const quotOrigin = document.getElementById('quot-origin');
    const quotGrade = document.getElementById('quot-grade');
    const quotBags = document.getElementById('quot-bags');
    const quotPrice = document.getElementById('quot-price');
    const quotIncoterm = document.getElementById('quot-incoterm');
    const quotPayment = document.getElementById('quot-payment');
    const quotNotes = document.getElementById('quot-tasting-notes');

    function updateQuotationPreview() {
        const prevSeller = document.getElementById('prev-seller');
        const prevBuyer = document.getElementById('prev-buyer');
        const prevOrigin = document.getElementById('prev-origin');
        const prevGradeDesc = document.getElementById('prev-grade-desc');
        const prevQty = document.getElementById('prev-qty');
        const prevPrice = document.getElementById('prev-price');
        const prevTotal = document.getElementById('prev-total');
        const prevIncoterm = document.getElementById('prev-incoterm');
        const prevPayment = document.getElementById('prev-payment');
        const prevNotes = document.getElementById('prev-notes');

        const bags = Math.max(1, parseInt(quotBags?.value || 320));
        const price = Math.max(0.01, parseFloat(quotPrice?.value || 3.25));
        const netKg = bags * 60;
        const netLbs = netKg * KG_TO_LBS;
        const totalUsd = netLbs * price;

        if (prevSeller) prevSeller.textContent = quotExporter?.value?.trim() || 'Export Entity Placeholder';
        if (prevBuyer) prevBuyer.textContent = quotBuyer?.value?.trim() || 'Buyer Entity Placeholder';
        if (prevOrigin) prevOrigin.textContent = quotOrigin?.value || 'Sidama';
        if (prevGradeDesc) prevGradeDesc.textContent = `${quotOrigin?.value || 'Sidama'} ${quotGrade?.value || 'Grade 2'}`;
        if (prevQty) prevQty.textContent = `${bags.toLocaleString()} Bags (${netKg.toLocaleString()} kg)`;
        if (prevPrice) prevPrice.textContent = `$${price.toFixed(2)} / lb`;
        if (prevTotal) prevTotal.textContent = `$${totalUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        if (prevIncoterm) prevIncoterm.textContent = quotIncoterm?.value || 'FOB Port of Djibouti';
        if (prevPayment) prevPayment.textContent = quotPayment?.value || '100% Irrevocable LC at Sight';
        if (prevNotes) prevNotes.textContent = quotNotes?.value || '';
    }

    [quotExporter, quotBuyer, quotOrigin, quotGrade, quotBags, quotPrice, quotIncoterm, quotPayment, quotNotes].forEach(el => {
        if (el) el.addEventListener('input', updateQuotationPreview);
    });

    // 6. CHECKLIST INTERACTIVITY & LOCALSTORAGE PERSISTENCE
    const checklistBoxes = document.querySelectorAll('.checklist-checkbox');
    const checklistProgressText = document.getElementById('checklist-progress-text');
    const checklistProgressBar = document.getElementById('checklist-progress-bar');
    const btnResetChecklist = document.getElementById('btn-reset-checklist');

    window.toggleChecklistItem = function(id) {
        const chk = document.getElementById(id);
        if (chk) {
            chk.checked = !chk.checked;
            updateChecklistProgress();
        }
    };

    function updateChecklistProgress() {
        const total = checklistBoxes.length;
        let checked = 0;
        const state = {};

        checklistBoxes.forEach(chk => {
            const card = chk.closest('.checklist-item-card');
            state[chk.id] = chk.checked;
            if (chk.checked) {
                checked++;
                if (card) card.classList.add('is-checked');
            } else {
                if (card) card.classList.remove('is-checked');
            }
        });

        const pct = Math.round((checked / total) * 100);
        if (checklistProgressText) {
            if (checked === 0) {
                checklistProgressText.textContent = `0 of ${total} completed`;
                checklistProgressText.className = 'fw-semibold text-muted';
            } else {
                checklistProgressText.textContent = `${checked} of ${total} completed (${pct}%)`;
                checklistProgressText.className = 'fw-semibold text-success';
            }
        }
        if (checklistProgressBar) checklistProgressBar.style.width = `${pct}%`;

        try { localStorage.setItem('fira_export_checklist', JSON.stringify(state)); } catch (e) {}
    }

    // Load saved checklist state
    try {
        const saved = JSON.parse(localStorage.getItem('fira_export_checklist') || '{}');
        checklistBoxes.forEach(chk => {
            if (saved[chk.id]) chk.checked = true;
        });
    } catch (e) {}

    checklistBoxes.forEach(chk => {
        chk.addEventListener('change', function(e) {
            e.stopPropagation();
            updateChecklistProgress();
        });
        chk.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    if (btnResetChecklist) {
        btnResetChecklist.addEventListener('click', function() {
            checklistBoxes.forEach(chk => { chk.checked = false; });
            try { localStorage.removeItem('fira_export_checklist'); } catch (e) {}
            updateChecklistProgress();
        });
    }

    // 7. FIRA AI MULTI-STAGE MARKET SYNTHESIS EXPERIENCE
    const aiProgressContainer = document.getElementById('ai-stage-progress-container');
    const aiStagePct = document.getElementById('ai-stage-pct');
    const aiStageStatusMsg = document.getElementById('ai-stage-status-msg');
    const analyzeButtons = document.querySelectorAll('#btn-analyze-coffee-prices, .btn-analyze-coffee-prices, #btn-request-ai-analysis');
    let isAnalyzingMarket = false;

    const stagesConfig = [
        { step: 1, label: 'Reading records', msg: 'Ingesting verified coffee minimum price records from official schedule...' },
        { step: 2, label: 'Normalizing prices', msg: 'Standardizing origins, grades, currency (USD/lb) and FOB Djibouti benchmarks...' },
        { step: 3, label: 'Comparing values', msg: 'Cross-referencing active floors against prior published reference datasets...' },
        { step: 4, label: 'Identifying trends', msg: 'Computing price ceilings, baseline floors, and regional grade spreads...' },
        { step: 5, label: 'Generating insights', msg: 'Formulating institutional analytical synthesis and actionable guidance...' },
    ];

    function setAiStage(stageIndex) {
        for (let i = 1; i <= 5; i++) {
            const stepEl = document.getElementById(`stage-step-${i}`);
            if (!stepEl) continue;
            if (i < stageIndex) {
                stepEl.className = 'stage-item completed';
                stepEl.querySelector('.stage-dot').innerHTML = '<i class="fa-solid fa-check" style="font-size: 10px;"></i>';
            } else if (i === stageIndex) {
                stepEl.className = 'stage-item current';
                stepEl.querySelector('.stage-dot').textContent = i;
            } else {
                stepEl.className = 'stage-item';
                stepEl.querySelector('.stage-dot').textContent = i;
            }
        }
        if (aiStagePct) aiStagePct.textContent = `Step ${stageIndex} of 5`;
        if (aiStageStatusMsg && stagesConfig[stageIndex - 1]) {
            aiStageStatusMsg.textContent = stagesConfig[stageIndex - 1].msg;
        }
    }

    async function triggerAiMarketSynthesis() {
        if (isAnalyzingMarket) return;
        isAnalyzingMarket = true;

        analyzeButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-75');
            const icon = btn.querySelector('.fa-sparkles');
            if (icon) icon.className = 'fa-solid fa-spinner fa-spin me-1';
        });

        if (aiProgressContainer) {
            aiProgressContainer.style.display = 'block';
            aiProgressContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        setAiStage(1);

        // Visual staged progression
        const t2 = setTimeout(() => setAiStage(2), 400);
        const t3 = setTimeout(() => setAiStage(3), 900);
        const t4 = setTimeout(() => setAiStage(4), 1400);
        const t5 = setTimeout(() => setAiStage(5), 1900);

        try {
            const res = await fetch(CONFIG.aiAnalysisUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CONFIG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ force: true })
            });

            const data = await res.json();

            // Clear visual progression timers
            clearTimeout(t2);
            clearTimeout(t3);
            clearTimeout(t4);
            clearTimeout(t5);
            setAiStage(5);

            if (data.success) {
                renderAiMarketSynthesis(data);
                setTimeout(() => {
                    if (aiProgressContainer) aiProgressContainer.style.display = 'none';
                    const dash = document.getElementById('fira-ai-intelligence-dashboard');
                    if (dash) dash.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 600);
            } else {
                alert(data.message || 'Failed to synthesize market data.');
                if (aiProgressContainer) aiProgressContainer.style.display = 'none';
            }
        } catch (err) {
            console.error('Synthesis error:', err);
            alert('A network error occurred while communicating with Fira AI. Please retry.');
            if (aiProgressContainer) aiProgressContainer.style.display = 'none';
        } finally {
            isAnalyzingMarket = false;
            analyzeButtons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
                const icon = btn.querySelector('.fa-spinner');
                if (icon) icon.className = 'fa-solid fa-sparkles me-1';
            });
        }
    }

    function renderAiMarketSynthesis(data) {
        if (!data) return;

        // 1. Overview Stat Cards
        const snap = data.snapshot || {};
        const elHighPrice = document.getElementById('overview-highest-price');
        const elHighSpec = document.getElementById('overview-highest-spec');
        const elLowPrice = document.getElementById('overview-lowest-price');
        const elLowSpec = document.getElementById('overview-lowest-spec');
        const elAvgPrice = document.getElementById('overview-average-price');
        const elMedSpread = document.getElementById('overview-median-spread');
        const elRecCount = document.getElementById('overview-records-count');

        if (elHighPrice && snap.highest_price) {
            elHighPrice.innerHTML = `$${parseFloat(snap.highest_price).toFixed(3)} <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>`;
        }
        if (elHighSpec && snap.highest_spec) {
            elHighSpec.textContent = snap.highest_spec;
            elHighSpec.title = snap.highest_spec;
        }
        if (elLowPrice && snap.lowest_price) {
            elLowPrice.innerHTML = `$${parseFloat(snap.lowest_price).toFixed(3)} <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>`;
        }
        if (elLowSpec && snap.lowest_spec) {
            elLowSpec.textContent = snap.lowest_spec;
            elLowSpec.title = snap.lowest_spec;
        }
        if (elAvgPrice && snap.average_price) {
            elAvgPrice.innerHTML = `$${parseFloat(snap.average_price).toFixed(3)} <small style="font-size: 12px; font-weight: 500; color: var(--muted);">/ lb</small>`;
        }
        if (elMedSpread && snap.price_spread !== undefined) {
            elMedSpread.textContent = `Spread: $${parseFloat(snap.price_spread).toFixed(3)}`;
        }
        if (elRecCount && snap.records_analyzed) {
            elRecCount.innerHTML = `${snap.records_analyzed} <small style="font-size: 12px; font-weight: 500; color: var(--muted);">Specs</small>`;
        }

        // 2. Pulse Bar Corridor & Takeaway
        const pulseCorridor = document.getElementById('pulse-corridor-val');
        if (pulseCorridor && snap.lowest_price && snap.highest_price) {
            pulseCorridor.innerHTML = `$${parseFloat(snap.lowest_price).toFixed(3)} &ndash; $${parseFloat(snap.highest_price).toFixed(3)} / lb`;
        }

        const insights = data.insights || {};
        const execSummaryEl = document.getElementById('ai-executive-summary-text');
        if (execSummaryEl && insights.executive_summary) {
            execSummaryEl.innerHTML = `<strong class="me-2 text-dark"><i class="fa-solid fa-bolt text-warning"></i> Executive Summary:</strong> ${insights.executive_summary}`;
        }

        // 3. 4 Structured Insight Cards
        const elLeaderPrice = document.getElementById('insight-leader-price');
        if (elLeaderPrice && snap.highest_price) {
            elLeaderPrice.innerHTML = `$${parseFloat(snap.highest_price).toFixed(3)} <small>/ lb</small>`;
        }
        const elFloorPrice = document.getElementById('insight-floor-price');
        if (elFloorPrice && snap.lowest_price) {
            elFloorPrice.innerHTML = `$${parseFloat(snap.lowest_price).toFixed(3)} <small>/ lb</small>`;
        }
        const elPatternPrice = document.getElementById('insight-pattern-price');
        if (elPatternPrice && snap.price_spread !== undefined) {
            elPatternPrice.innerHTML = `$${parseFloat(snap.price_spread).toFixed(3)} <small>/ lb spread</small>`;
        }

        const renderInsightCard = (prefix, item) => {
            if (!item) return;
            const t = document.getElementById(`insight-${prefix}-title`);
            const obs = document.getElementById(`insight-${prefix}-obs`);
            const ev = document.getElementById(`insight-${prefix}-evidence`);
            const imp = document.getElementById(`insight-${prefix}-implication`);
            const act = document.getElementById(`insight-${prefix}-action`);

            if (t && item.title) t.textContent = item.title;
            if (obs && item.observation) obs.textContent = item.observation;
            if (ev && item.evidence) ev.textContent = item.evidence;
            if (imp && item.commercial_implication) imp.textContent = item.commercial_implication;
            if (act && item.action) act.textContent = item.action;
        };

        renderInsightCard('leader', insights.price_leader);
        renderInsightCard('floor', insights.lowest_reference);
        renderInsightCard('movement', insights.largest_movement);
        renderInsightCard('pattern', insights.emerging_pattern);

        // 4. Market Signals Grid
        const oppContainer = document.getElementById('opportunity-signals-list');
        if (oppContainer && data.opportunity_signals) {
            oppContainer.innerHTML = data.opportunity_signals.map(opp => `
                <div class="signal-card opportunity">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 12px;">${opp.title || 'Market Opportunity'}</div>
                        <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">${opp.description || opp.message || ''}</div>
                    </div>
                </div>
            `).join('');
        }

        const riskContainer = document.getElementById('risk-signals-list');
        if (riskContainer && data.risk_signals) {
            riskContainer.innerHTML = data.risk_signals.map(risk => `
                <div class="signal-card risk">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 12px;">${risk.title || 'Regulatory Risk'}</div>
                        <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">${risk.description || risk.message || ''}</div>
                    </div>
                </div>
            `).join('');
        }

        const guidanceContainer = document.getElementById('buyer-seller-signals-list');
        if (guidanceContainer && data.buyer_seller_signals) {
            let sigArray = Array.isArray(data.buyer_seller_signals) ? data.buyer_seller_signals : [];
            if (!Array.isArray(data.buyer_seller_signals) && typeof data.buyer_seller_signals === 'object') {
                sigArray = [
                    { target: 'Seller Directives', guidance: data.buyer_seller_signals.seller_guidance || '' },
                    { target: 'Buyer Directives', guidance: data.buyer_seller_signals.buyer_guidance || '' }
                ].filter(s => s.guidance);
            }
            guidanceContainer.innerHTML = sigArray.map(sig => `
                <div class="signal-card guidance">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 12px;">${sig.target || 'Trade Directive'}</div>
                        <div class="text-secondary mt-0.5" style="font-size: 11.5px; line-height: 1.4;">${sig.signal || sig.guidance || ''}</div>
                    </div>
                </div>
            `).join('');
        }

        // 5. Recommended Actions
        const actionsContainer = document.getElementById('ai-recommended-actions-list');
        if (actionsContainer && insights.recommended_actions) {
            actionsContainer.innerHTML = insights.recommended_actions.map(act => `
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center gap-2 bg-white p-2 rounded border" style="font-size: 12px;">
                        <i class="fa-solid fa-circle-check text-success" style="font-size: 12px; flex-shrink: 0;"></i>
                        <span class="text-secondary text-truncate">${act}</span>
                    </div>
                </div>
            `).join('');
        }

        // 6. Regional Breakdown Table
        const regionalTbody = document.getElementById('regional-comparison-tbody');
        if (regionalTbody && data.regional_breakdown && data.regional_breakdown.length > 0) {
            const baseFloor = parseFloat(snap.lowest_price || 2.30);
            regionalTbody.innerHTML = data.regional_breakdown.map(region => {
                const avg = parseFloat(region.avg_price || 0);
                const premium = avg - baseFloor;
                const premClass = premium > 0 ? 'text-success' : 'text-muted';
                return `
                    <tr>
                        <td>
                            <div class="fw-bold" style="font-size: 13.5px; color: var(--text);">${region.origin || 'Ethiopian Origin'}</div>
                            <div class="text-muted" style="font-size: 11.5px;">Arabica Specialty &amp; Commercial Grades</div>
                        </td>
                        <td class="text-center"><span class="stat-badge-neutral">${region.total_specs || region.count || 1} Specs</span></td>
                        <td class="text-end font-monospace" style="font-size: 13px;">$${parseFloat(region.min_price || 0).toFixed(3)}</td>
                        <td class="text-end font-monospace" style="font-size: 13px;">$${parseFloat(region.max_price || 0).toFixed(3)}</td>
                        <td class="text-end font-monospace fw-bold" style="font-size: 13.5px; color: var(--brand);">$${avg.toFixed(3)}</td>
                        <td class="text-end font-monospace" style="font-size: 13px;">
                            <span class="${premClass} fw-semibold">+${Math.max(0, premium).toFixed(3)}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // 7. Synthesis timestamp
        const tsEl = document.getElementById('ai-synthesis-timestamp');
        if (tsEl) {
            const now = new Date();
            tsEl.innerHTML = `<i class="fa-regular fa-clock"></i> <span>Analyzed: ${now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })} ${now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>`;
        }
    }

    analyzeButtons.forEach(btn => {
        btn.addEventListener('click', triggerAiMarketSynthesis);
    });

    // 8. COFFEE MINIMUM PRICES LIVE FILTERING & SEARCH
    const searchInput = document.getElementById('prices-search-input');
    const originFilter = document.getElementById('prices-origin-filter');
    const gradeFilter = document.getElementById('prices-grade-filter');
    const processFilter = document.getElementById('prices-process-filter');
    const movementFilter = document.getElementById('prices-movement-filter');
    const btnResetFilters = document.getElementById('btn-reset-price-filters');
    const countBadge = document.getElementById('price-records-count');

    function applyPricesFilter() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const originVal = originFilter?.value || 'all';
        const gradeVal = gradeFilter?.value || 'all';
        const processVal = processFilter?.value || 'all';
        const movementVal = movementFilter?.value || 'all';

        const tableRows = document.querySelectorAll('#coffee-prices-tbody tr');
        const mobileCards = document.querySelectorAll('#coffee-prices-mobile-cards .mobile-spec-card');

        let visibleCount = 0;

        const checkMatch = (el) => {
            const o = (el.getAttribute('data-origin') || '').toLowerCase();
            const g = (el.getAttribute('data-grade') || '').toLowerCase();
            const p = (el.getAttribute('data-process') || '').toLowerCase();
            const m = (el.getAttribute('data-movement') || '').toLowerCase();
            const text = (el.textContent || '').toLowerCase();

            const matchQuery = !query || text.includes(query);
            const matchOrigin = (originVal === 'all') || o.includes(originVal.toLowerCase());
            const matchGrade = (gradeVal === 'all') || g.includes(gradeVal.toLowerCase());
            const matchProcess = (processVal === 'all') || p.includes(processVal.toLowerCase());
            const matchMovement = (movementVal === 'all') || m === movementVal.toLowerCase();

            return matchQuery && matchOrigin && matchGrade && matchProcess && matchMovement;
        };

        tableRows.forEach(row => {
            const match = checkMatch(row);
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        mobileCards.forEach(card => {
            card.style.display = checkMatch(card) ? '' : 'none';
        });

        if (countBadge) countBadge.textContent = visibleCount;
    }

    if (searchInput) searchInput.addEventListener('input', applyPricesFilter);
    if (originFilter) originFilter.addEventListener('change', applyPricesFilter);
    if (gradeFilter) gradeFilter.addEventListener('change', applyPricesFilter);
    if (processFilter) processFilter.addEventListener('change', applyPricesFilter);
    if (movementFilter) movementFilter.addEventListener('change', applyPricesFilter);

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (originFilter) originFilter.value = 'all';
            if (gradeFilter) gradeFilter.value = 'all';
            if (processFilter) processFilter.value = 'all';
            if (movementFilter) movementFilter.value = 'all';
            applyPricesFilter();
        });
    }

    // 9. FIRA AI ADVISOR CHAT
    const advisorInput = document.getElementById('advisor-query-input');
    const btnAskAdvisor = document.getElementById('btn-ask-advisor');
    const advisorReplyBox = document.getElementById('advisor-reply-box');
    const advisorReplyText = document.getElementById('advisor-reply-text');

    window.useAdvisorPrompt = function(promptText) {
        if (advisorInput) {
            advisorInput.value = promptText;
            askAdvisor();
        }
    };

    async function askAdvisor() {
        const query = advisorInput?.value?.trim();
        if (!query) return;

        if (btnAskAdvisor) {
            btnAskAdvisor.disabled = true;
            btnAskAdvisor.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }
        if (advisorReplyBox) advisorReplyBox.style.display = 'block';
        if (advisorReplyText) advisorReplyText.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Formulating advisor insight...</span>';

        try {
            const res = await fetch(CONFIG.askAdvisorUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ question: query }),
            });
            const data = await res.json();
            if (data.success && data.reply) {
                if (advisorReplyText) {
                    advisorReplyText.innerHTML = data.reply.replace(/\n\n/g, '<p class="mt-2 mb-2">').replace(/\n/g, '<br>');
                }
            }
        } catch (err) {
            if (advisorReplyText) advisorReplyText.innerHTML = '<span class="text-danger">Service temporarily unavailable. Please retry.</span>';
        } finally {
            if (btnAskAdvisor) {
                btnAskAdvisor.disabled = false;
                btnAskAdvisor.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Ask';
            }
        }
    }

    if (btnAskAdvisor) btnAskAdvisor.addEventListener('click', askAdvisor);
    if (advisorInput) {
        advisorInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') askAdvisor();
        });
    }

    window.copyAdvisorReply = function() {
        if (advisorReplyText) {
            navigator.clipboard.writeText(advisorReplyText.innerText).then(() => alert('Copied insight to clipboard!'));
        }
    };

    // Table Filter by origin/process (Indicator table)
    const tableOriginFilter = document.getElementById('table-origin-filter');
    const tableProcessFilter = document.getElementById('table-process-filter');

    function filterMarketTable() {
        const selOrigin = tableOriginFilter?.value || 'all';
        const selProcess = tableProcessFilter?.value || 'all';
        const rows = document.querySelectorAll('#market-origins-table tbody tr');

        rows.forEach(row => {
            const o = row.getAttribute('data-origin') || '';
            const p = row.getAttribute('data-process') || '';
            const originMatch = (selOrigin === 'all') || o.toLowerCase().includes(selOrigin.toLowerCase());
            const processMatch = (selProcess === 'all') || p.toLowerCase().includes(selProcess.toLowerCase());
            row.style.display = (originMatch && processMatch) ? '' : 'none';
        });
    }

    if (tableOriginFilter) tableOriginFilter.addEventListener('change', filterMarketTable);
    if (tableProcessFilter) tableProcessFilter.addEventListener('change', filterMarketTable);

    // Initial Execution
    document.addEventListener('DOMContentLoaded', function() {
        updateCalculator();
        updateQuotationPreview();
        updateChecklistProgress();
        if (currentMarketData && currentMarketData.history) {
            initTrendChart(currentMarketData.history);
        }
        startPolling();
    });

})();
</script>
@endpush
