<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fira Link Design System v2.0 — Styleguide & Component Lab</title>
    
    <!-- Typography: Inter & Noto Sans Ethiopic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Ethiopic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ==========================================================================
           FIRA LINK DESIGN SYSTEM v2.0 TOKENS (CSS VARIABLES)
           ========================================================================== */
        :root {
            /* Brand Core */
            --fira-brand-primary:   #6D1A95; /* Regal Purple */
            --fira-brand-deep:      #3B0764; /* Velvet Deep Indigo / Hover */
            --fira-brand-tint:      #F3E8FF; /* Soft Lavender Tint */
            --fira-brand-subtle:    #FAF5FF; /* Subtle Tint */

            /* Neutral Surface & Backgrounds */
            --fira-page-bg:         #F9F7FA; /* Warm Alabaster Page Background */
            --fira-surface:         #FFFFFF; /* Pure White Surface */
            --fira-surface-alt:     #F3F0F5; /* Secondary Surface / Fill */
            --fira-border-hairline: #E9E4F0; /* 1px Architectural Hairline */
            --fira-border-strong:   #D4C9DF; /* Border Active */

            /* Typography Colors */
            --fira-text-primary:    #1E1B4B; /* Obsidian Navy */
            --fira-text-secondary:  #4B4869; /* Secondary Body */
            --fira-text-muted:      #6B7280; /* Tertiary / Meta */
            --fira-text-inverse:    #FFFFFF; /* White on Brand */

            /* Status Tokens (Word + Colour) */
            /* RULE: Pending is PURPLE on Lavender (#6D1A95 on #F3E8FF), never amber/green */
            --fira-pending:         #6D1A95;
            --fira-pending-bg:      #F3E8FF;
            --fira-pending-border:  #E9D5FF;

            --fira-success:         #15803D; /* Forest Green */
            --fira-success-bg:      #DCFCE7;
            --fira-success-border:  #BBF7D0;

            --fira-danger:          #B91C1C; /* Crimson */
            --fira-danger-bg:       #FEE2E2;
            --fira-danger-border:   #FECACA;

            --fira-info:            #1E1B4B;
            --fira-info-bg:         #EDE9FE;
            --fira-info-border:     #DDD6FE;

            /* Spacing (8px Base Scale) */
            --fira-space-0-5:       4px;
            --fira-space-1:         8px;
            --fira-space-1-5:       12px;
            --fira-space-2:         16px;
            --fira-space-3:         24px;
            --fira-space-4:         32px;
            --fira-space-6:         48px;
            --fira-space-8:         64px;
            --fira-space-12:        96px;

            /* Radii */
            --fira-radius-input:    12px;
            --fira-radius-card:     16px;
            --fira-radius-large:    24px;
            --fira-radius-pill:     999px;

            /* Shadows (Purple-tinted, never pitch black) */
            --fira-shadow-1: 0 1px 3px rgba(59, 7, 100, 0.05), 0 1px 2px rgba(59, 7, 100, 0.03);
            --fira-shadow-2: 0 4px 16px -2px rgba(59, 7, 100, 0.08), 0 2px 6px -1px rgba(59, 7, 100, 0.04);
            --fira-shadow-3: 0 16px 36px -4px rgba(59, 7, 100, 0.12), 0 6px 16px -2px rgba(59, 7, 100, 0.06);

            /* Typography Families */
            --fira-font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --fira-font-amharic: 'Noto Sans Ethiopic', 'Inter', sans-serif;

            /* Motion */
            --fira-transition-fast: 150ms ease-out;
            --fira-transition-normal: 250ms cubic-bezier(0.16, 1, 0.3, 1);
            --fira-transition-slow: 400ms cubic-bezier(0.16, 1, 0.3, 1);

            /* Container */
            --fira-container-max: 1200px;
        }

        /* Base Resets */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: var(--fira-font-sans);
            background-color: var(--fira-page-bg);
            color: var(--fira-text-primary);
            line-height: 1.6;
        }

        /* ONE Unified Container Rule */
        .fira-container {
            width: 100%;
            max-width: var(--fira-container-max);
            margin-left: auto;
            margin-right: auto;
            padding-left: 24px;
            padding-right: 24px;
        }

        @media (max-width: 768px) {
            .fira-container {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        /* Section Rhythms */
        .styleguide-section {
            padding-top: var(--fira-space-8);
            padding-bottom: var(--fira-space-8);
            border-bottom: 1px solid var(--fira-border-hairline);
        }

        .styleguide-section:nth-child(even) {
            background-color: var(--fira-surface);
        }

        /* Section Headers */
        .fira-section-header {
            margin-bottom: var(--fira-space-4);
        }
        .fira-eyebrow {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--fira-brand-primary);
            margin-bottom: var(--fira-space-1);
        }
        .fira-section-title {
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--fira-text-primary);
            line-height: 1.25;
            margin-bottom: var(--fira-space-1);
        }
        .fira-section-intro {
            font-size: 1rem;
            color: var(--fira-text-secondary);
            max-width: 70ch;
        }

        /* Grid utilities */
        .fira-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .fira-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .fira-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
        @media (max-width: 1024px) {
            .fira-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .fira-grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .fira-grid-4, .fira-grid-3, .fira-grid-2 { grid-template-columns: 1fr; }
        }

        /* Card Surface */
        .fira-card {
            background-color: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-card);
            padding: var(--fira-space-3);
            box-shadow: var(--fira-shadow-1);
            transition: transform var(--fira-transition-normal), box-shadow var(--fira-transition-normal);
        }
        .fira-card:hover {
            box-shadow: var(--fira-shadow-2);
        }

        /* ==========================================================================
           BUTTONS & ACTIONS
           ========================================================================== */
        .fira-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: var(--fira-font-sans);
            font-size: 0.875rem; /* Strict floor for UI controls: 14px */
            font-weight: 600;
            line-height: 1;
            border-radius: var(--fira-radius-input);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--fira-transition-fast);
            border: 1px solid transparent;
            white-space: nowrap;
        }

        /* Heights */
        .fira-btn-md { height: 44px; padding: 0 20px; }
        .fira-btn-lg { height: 52px; padding: 0 28px; font-size: 1rem; }

        /* Variants */
        .fira-btn-primary {
            background-color: var(--fira-brand-primary);
            color: var(--fira-text-inverse);
            border-color: var(--fira-brand-primary);
        }
        .fira-btn-primary:hover, .fira-btn-primary.is-hover {
            background-color: var(--fira-brand-deep);
            border-color: var(--fira-brand-deep);
        }
        .fira-btn-primary:focus-visible, .fira-btn-primary.is-focus {
            outline: 2px solid var(--fira-brand-primary);
            outline-offset: 3px;
        }
        .fira-btn-primary:active, .fira-btn-primary.is-active {
            transform: scale(0.98);
        }

        .fira-btn-secondary {
            background-color: transparent;
            color: var(--fira-text-primary);
            border-color: var(--fira-border-hairline);
        }
        .fira-btn-secondary:hover, .fira-btn-secondary.is-hover {
            background-color: var(--fira-brand-subtle);
            border-color: var(--fira-brand-primary);
            color: var(--fira-brand-primary);
        }

        .fira-btn-text {
            background-color: transparent;
            color: var(--fira-brand-primary);
            border-color: transparent;
            padding: 0 8px;
            height: auto;
        }
        .fira-btn-text:hover {
            text-decoration: underline;
            color: var(--fira-brand-deep);
        }

        .fira-btn-destructive {
            background-color: var(--fira-danger-bg);
            color: var(--fira-danger);
            border-color: var(--fira-danger-border);
        }
        .fira-btn-destructive:hover {
            background-color: var(--fira-danger);
            color: var(--fira-surface);
            border-color: var(--fira-danger);
        }

        .fira-btn:disabled, .fira-btn.is-disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }

        .fira-btn.is-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .fira-btn.is-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid #FFFFFF;
            border-right-color: transparent;
            border-radius: 50%;
            animation: fira-spin 0.75s linear infinite;
        }
        .fira-btn-secondary.is-loading::after {
            border-color: var(--fira-brand-primary);
            border-right-color: transparent;
        }

        @keyframes fira-spin {
            to { transform: rotate(360deg); }
        }

        /* ==========================================================================
           FORM CONTROLS
           ========================================================================== */
        .fira-form-group {
            margin-bottom: var(--fira-space-2);
        }
        .fira-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--fira-text-primary);
            margin-bottom: 6px;
        }
        .fira-hint {
            display: block;
            font-size: 0.8125rem;
            color: var(--fira-text-muted);
            margin-top: 4px;
        }
        .fira-error-msg {
            display: block;
            font-size: 0.8125rem;
            color: var(--fira-danger);
            font-weight: 500;
            margin-top: 4px;
        }

        .fira-input, .fira-select, .fira-textarea {
            width: 100%;
            height: 44px;
            padding: 0 14px;
            font-family: var(--fira-font-sans);
            font-size: 0.875rem;
            color: var(--fira-text-primary);
            background-color: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-input);
            outline: none;
            transition: border-color var(--fira-transition-fast), box-shadow var(--fira-transition-fast);
        }
        .fira-textarea {
            height: 96px;
            padding: 10px 14px;
            resize: vertical;
        }
        .fira-input:focus, .fira-select:focus, .fira-textarea:focus, .fira-input.is-focus {
            border-color: var(--fira-brand-primary);
            box-shadow: 0 0 0 3px rgba(109, 26, 149, 0.12);
        }
        .fira-input.is-error {
            border-color: var(--fira-danger);
            background-color: #FFFDFD;
        }
        .fira-input:disabled {
            background-color: var(--fira-surface-alt);
            color: var(--fira-text-muted);
            cursor: not-allowed;
        }

        /* Checkbox & Switch */
        .fira-checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--fira-text-primary);
            user-select: none;
        }
        .fira-checkbox {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 1.5px solid var(--fira-border-strong);
            border-radius: 6px;
            background: var(--fira-surface);
            display: inline-grid;
            place-content: center;
            cursor: pointer;
            transition: all var(--fira-transition-fast);
        }
        .fira-checkbox:checked {
            background: var(--fira-brand-primary);
            border-color: var(--fira-brand-primary);
        }
        .fira-checkbox:checked::before {
            content: "✓";
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 700;
        }

        /* Switch */
        .fira-switch-row {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            user-select: none;
        }
        .fira-switch-toggle {
            position: relative;
            width: 44px;
            height: 24px;
            background: var(--fira-border-hairline);
            border-radius: var(--fira-radius-pill);
            transition: background var(--fira-transition-fast);
        }
        .fira-switch-toggle::after {
            content: "";
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #FFFFFF;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            transition: transform var(--fira-transition-fast);
        }
        .fira-switch.is-active .fira-switch-toggle {
            background: var(--fira-brand-primary);
        }
        .fira-switch.is-active .fira-switch-toggle::after {
            transform: translateX(20px);
        }

        /* ==========================================================================
           NAVIGATION, TABS (UNDERLINE ONLY) & CHIPS
           ========================================================================== */
        .fira-tabs-underline {
            display: flex;
            align-items: center;
            gap: 28px;
            border-bottom: 1px solid var(--fira-border-hairline);
            margin-bottom: var(--fira-space-3);
        }
        .fira-tab-item {
            position: relative;
            padding: 12px 2px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--fira-text-muted);
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            transition: color var(--fira-transition-fast);
        }
        .fira-tab-item:hover {
            color: var(--fira-text-primary);
        }
        .fira-tab-item.is-active {
            color: var(--fira-brand-primary);
            font-weight: 600;
        }
        .fira-tab-item.is-active::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--fira-brand-primary);
            border-radius: 2px 2px 0 0;
        }

        /* Segmented control */
        .fira-segmented-control {
            display: inline-flex;
            background-color: var(--fira-surface-alt);
            padding: 4px;
            border-radius: var(--fira-radius-input);
            border: 1px solid var(--fira-border-hairline);
        }
        .fira-segment-btn {
            border: none;
            background: transparent;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--fira-text-secondary);
            cursor: pointer;
            transition: all var(--fira-transition-fast);
        }
        .fira-segment-btn.is-active {
            background-color: var(--fira-surface);
            color: var(--fira-brand-primary);
            font-weight: 600;
            box-shadow: var(--fira-shadow-1);
        }

        /* Chips */
        .fira-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 12px;
            border-radius: var(--fira-radius-pill);
            border: 1px solid var(--fira-border-hairline);
            background-color: var(--fira-surface);
            font-size: 0.8125rem;
            color: var(--fira-text-secondary);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--fira-transition-fast);
        }
        .fira-chip:hover {
            border-color: var(--fira-border-strong);
            color: var(--fira-text-primary);
        }
        .fira-chip.is-active {
            background-color: var(--fira-brand-tint);
            border-color: var(--fira-brand-primary);
            color: var(--fira-brand-primary);
            font-weight: 600;
        }

        /* ==========================================================================
           BADGES & STATUS PILLS (WORD + COLOUR)
           ========================================================================== */
        .fira-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            height: 24px;
            padding: 0 10px;
            border-radius: var(--fira-radius-pill);
            font-size: 0.75rem; /* 12px floor for micro badges */
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            letter-spacing: 0.02em;
        }
        .fira-badge-pending {
            background-color: var(--fira-pending-bg);
            color: var(--fira-pending);
            border: 1px solid var(--fira-pending-border);
        }
        .fira-badge-success {
            background-color: var(--fira-success-bg);
            color: var(--fira-success);
            border: 1px solid var(--fira-success-border);
        }
        .fira-badge-danger {
            background-color: var(--fira-danger-bg);
            color: var(--fira-danger);
            border: 1px solid var(--fira-danger-border);
        }
        .fira-badge-info {
            background-color: var(--fira-info-bg);
            color: var(--fira-info);
            border: 1px solid var(--fira-info-border);
        }

        /* ==========================================================================
           CARDS (COFFEE LOT, COURSE, METRIC)
           ========================================================================== */
        .fira-lot-card {
            background: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-card);
            overflow: hidden;
            box-shadow: var(--fira-shadow-1);
            display: flex;
            flex-direction: column;
            transition: transform var(--fira-transition-normal), box-shadow var(--fira-transition-normal);
        }
        .fira-lot-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--fira-shadow-2);
        }
        .fira-lot-thumb {
            position: relative;
            height: 180px;
            background-size: cover;
            background-position: center;
        }
        .fira-lot-thumb-badge {
            position: absolute;
            top: 12px;
            left: 12px;
        }
        .fira-lot-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .fira-lot-origin {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--fira-brand-primary);
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .fira-lot-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--fira-text-primary);
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .fira-lot-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            background: var(--fira-page-bg);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 16px;
        }
        .fira-lot-meta-item {
            font-size: 0.75rem;
            color: var(--fira-text-muted);
        }
        .fira-lot-meta-val {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--fira-text-primary);
            font-variant-numeric: tabular-nums;
        }
        .fira-lot-pricing {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid var(--fira-border-hairline);
        }
        .fira-lot-price-val {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--fira-brand-deep);
            font-variant-numeric: tabular-nums;
        }
        .fira-lot-price-sub {
            font-size: 0.75rem;
            color: var(--fira-text-muted);
        }

        /* Course Card */
        .fira-course-card {
            background: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-card);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--fira-shadow-1);
        }
        .fira-course-thumb {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .fira-course-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .fira-course-instructor {
            font-size: 0.8125rem;
            color: var(--fira-text-secondary);
            margin-bottom: 6px;
        }
        .fira-course-instructor strong {
            color: var(--fira-text-primary);
        }

        /* Metric Stat Card */
        .fira-stat-card {
            background: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-card);
            padding: 24px;
            box-shadow: var(--fira-shadow-1);
        }
        .fira-stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--fira-brand-deep);
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
            margin-bottom: 4px;
        }
        .fira-stat-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fira-text-secondary);
        }
        .fira-stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--fira-success);
            margin-top: 8px;
        }

        /* ==========================================================================
           DATA TABLE (TABULAR FIGURES, STICKY HEADER)
           ========================================================================== */
        .fira-table-wrap {
            width: 100%;
            overflow-x: auto;
            background: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-card);
            box-shadow: var(--fira-shadow-1);
        }
        .fira-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .fira-table th {
            background-color: var(--fira-page-bg);
            color: var(--fira-text-secondary);
            font-weight: 600;
            padding: 14px 18px;
            border-bottom: 2px solid var(--fira-border-hairline);
            white-space: nowrap;
        }
        .fira-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--fira-border-hairline);
            color: var(--fira-text-primary);
            vertical-align: middle;
        }
        .fira-table tr:hover td {
            background-color: var(--fira-brand-subtle);
        }
        .fira-table .num-col {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ==========================================================================
           ALERTS, TOASTS, EMPTY & SKELETON STATES
           ========================================================================== */
        .fira-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-radius: var(--fira-radius-input);
            font-size: 0.875rem;
            margin-bottom: var(--fira-space-2);
        }
        .fira-alert-pending {
            background-color: var(--fira-pending-bg);
            border: 1px solid var(--fira-pending-border);
            color: var(--fira-brand-deep);
        }
        .fira-alert-success {
            background-color: var(--fira-success-bg);
            border: 1px solid var(--fira-success-border);
            color: #14532D;
        }
        .fira-alert-danger {
            background-color: var(--fira-danger-bg);
            border: 1px solid var(--fira-danger-border);
            color: #7F1D1D;
        }

        /* Skeleton loader */
        .fira-skeleton {
            background: linear-gradient(90deg, #F3F0F5 25%, #E9E4F0 50%, #F3F0F5 75%);
            background-size: 200% 100%;
            animation: fira-shimmer 1.5s infinite;
            border-radius: 6px;
        }
        @keyframes fira-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Stale data pill */
        .fira-stale-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            color: var(--fira-text-muted);
            background: var(--fira-surface-alt);
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* Sparkline */
        .fira-sparkline {
            width: 80px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
        }

        /* ==========================================================================
           BEFORE / AFTER PROOF OF CONCEPT SECTION
           ========================================================================== */
        .fira-poc-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: var(--fira-space-3);
        }
        @media (max-width: 1024px) {
            .fira-poc-container { grid-template-columns: 1fr; }
        }
        .fira-poc-box {
            background: var(--fira-surface);
            border: 1px solid var(--fira-border-hairline);
            border-radius: var(--fira-radius-large);
            overflow: hidden;
            box-shadow: var(--fira-shadow-2);
        }
        .fira-poc-header {
            padding: 12px 20px;
            background: var(--fira-surface-alt);
            border-bottom: 1px solid var(--fira-border-hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .fira-poc-title {
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .fira-poc-body {
            padding: 20px;
        }

        /* Mock Header Aligned */
        .poc-new-header {
            background: var(--fira-surface);
            border-bottom: 1px solid var(--fira-border-hairline);
            padding: 14px 0;
            margin-bottom: 20px;
        }
        .poc-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .poc-brand-logo {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--fira-brand-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .poc-nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }
        .poc-nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fira-text-secondary);
            text-decoration: none;
            position: relative;
            padding-bottom: 4px;
        }
        .poc-nav-link.is-active {
            color: var(--fira-brand-primary);
            font-weight: 600;
        }
        .poc-nav-link.is-active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--fira-brand-primary);
            border-radius: 2px;
        }

        /* Mock Hero New */
        .poc-hero-new {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 28px;
            align-items: center;
        }
        @media (max-width: 640px) {
            .poc-hero-new { grid-template-columns: 1fr; }
        }
        .poc-hero-img-wrap {
            height: 260px;
            border-radius: var(--fira-radius-card);
            overflow: hidden;
            background-size: cover;
            background-position: center;
            box-shadow: var(--fira-shadow-2);
            position: relative;
        }
        .poc-hero-img-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(30, 27, 75, 0.85);
            backdrop-filter: blur(8px);
            color: #FFFFFF;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Swatches */
        .fira-swatch {
            border-radius: var(--fira-radius-input);
            overflow: hidden;
            border: 1px solid var(--fira-border-hairline);
            box-shadow: var(--fira-shadow-1);
        }
        .fira-swatch-color {
            height: 70px;
            display: flex;
            align-items: flex-end;
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: monospace;
        }
        .fira-swatch-info {
            padding: 10px 12px;
            background: var(--fira-surface);
        }
        .fira-swatch-name {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--fira-text-primary);
        }
        .fira-swatch-var {
            font-size: 0.6875rem;
            color: var(--fira-text-muted);
            font-family: monospace;
        }
    </style>
</head>
<body>

    <!-- Header Navigation for Styleguide -->
    <header style="background: var(--fira-surface); border-bottom: 1px solid var(--fira-border-hairline); position: sticky; top: 0; z-index: 100;">
        <div class="fira-container" style="display: flex; align-items: center; justify-content: space-between; height: 68px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.25rem; font-weight: 800; color: var(--fira-brand-primary); letter-spacing: -0.02em;">FIRA LINK</span>
                <span style="height: 18px; width: 1px; background: var(--fira-border-hairline);"></span>
                <span style="font-size: 0.875rem; font-weight: 600; color: var(--fira-text-secondary);">Design System v2.0 (Component Lab)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <span class="fira-badge fira-badge-pending">Phase 1: Design System Ready</span>
                <a href="{{ route('home') }}" class="fira-btn fira-btn-secondary fira-btn-md">← Back to Site</a>
            </div>
        </div>
    </header>

    <!-- SECTION: BEFORE / AFTER PROOF OF CONCEPT -->
    <section class="styleguide-section" style="background: var(--fira-surface);">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Proof of Concept</span>
                <h2 class="fira-section-title">Header & Hero Redesign: Side-by-Side Verification</h2>
                <p class="fira-section-intro">
                    Direct comparison addressing all Phase 0 blocker defects: conflicting container widths (1440px vs 1200px), dual active indicators (pill + underline), two competing primary buttons, and AI cutout art.
                </p>
            </div>

            <div class="fira-poc-container">
                <!-- BEFORE -->
                <div class="fira-poc-box" style="border-top: 4px solid var(--fira-danger);">
                    <div class="fira-poc-header">
                        <span class="fira-poc-title" style="color: var(--fira-danger);">✕ Before (Phase 0 Audited State)</span>
                        <span style="font-size: 0.75rem; color: var(--fira-danger); font-weight: 600;">Multiple Discrepancies</span>
                    </div>
                    <div class="fira-poc-body" style="background: #FFF8F8;">
                        <div style="padding: 10px; background: #FFFFFF; border: 1px dashed #FECACA; border-radius: 8px; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; margin-bottom: 8px;">
                                <strong>Header: 1440px Container</strong>
                                <span style="color: #B91C1C;">(Width mismatch with content)</span>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <span style="background: #F3E8FF; padding: 4px 10px; border-radius: 999px; text-decoration: underline; color: #6D1A95; font-size: 0.75rem; font-weight: 600;">
                                    Home (Pill + Underline dual active!)
                                </span>
                                <span style="font-size: 0.75rem; color: #6B7280;">Courses</span>
                                <span style="font-size: 0.75rem; color: #6B7280;">Marketplace</span>
                                <div style="margin-left: auto; display: flex; gap: 6px;">
                                    <button style="background: #6D1A95; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem;">Join Free (Primary 1)</button>
                                    <button style="background: #6D1A95; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem;">Login (Primary 2)</button>
                                </div>
                            </div>
                        </div>

                        <div style="padding: 16px; background: #FFFFFF; border: 1px dashed #FECACA; border-radius: 8px;">
                            <div style="font-size: 0.6875rem; color: #B91C1C; margin-bottom: 6px;">
                                Hero Container: 1200px (Misaligned edges) • Headline: Academy Only
                            </div>
                            <h4 style="font-size: 1.125rem; color: #1E1B4B; margin-bottom: 6px;">Learn To Import & Export Coffee Through Ethiopia</h4>
                            <p style="font-size: 0.75rem; color: #6B7280; margin-bottom: 12px;">
                                Narrow educational focus ignores B2B trade marketplace, cupping lab, and export contracts.
                            </p>
                            <div style="display: flex; align-items: center; gap: 10px; background: #F9F7FA; padding: 8px; border-radius: 6px;">
                                <div style="width: 60px; height: 50px; background: #DDD; border-radius: 4px; display: grid; place-content: center; font-size: 0.625rem; color: #777;">
                                    AI Cutout
                                </div>
                                <span style="font-size: 0.6875rem; color: #B91C1C;">Cutout artifact with jagged white halo (fira_hero_cutout.png)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AFTER -->
                <div class="fira-poc-box" style="border-top: 4px solid var(--fira-success);">
                    <div class="fira-poc-header">
                        <span class="fira-poc-title" style="color: var(--fira-success);">✓ After (Design System v2.0 Proof of Concept)</span>
                        <span style="font-size: 0.75rem; color: var(--fira-success); font-weight: 600;">Unified System</span>
                    </div>
                    <div class="fira-poc-body" style="background: #FAFFFA;">
                        <!-- Header Mockup -->
                        <div class="poc-new-header" style="background: #FFFFFF; border: 1px solid var(--fira-border-hairline); border-radius: 12px; padding: 12px 18px; margin-bottom: 14px;">
                            <div class="poc-header-row">
                                <div class="poc-brand-logo">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                                    FIRA LINK
                                </div>
                                <ul class="poc-nav-links">
                                    <li><a href="#" class="poc-nav-link is-active">Trade Platform</a></li>
                                    <li><a href="#" class="poc-nav-link">B2B Lots</a></li>
                                    <li><a href="#" class="poc-nav-link">Market Terminal</a></li>
                                    <li><a href="#" class="poc-nav-link">Academy</a></li>
                                </ul>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <button class="fira-btn fira-btn-secondary fira-btn-md" style="height: 38px; padding: 0 14px;">Sign In</button>
                                    <button class="fira-btn fira-btn-primary fira-btn-md" style="height: 38px; padding: 0 16px;">Request Quote</button>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Mockup -->
                        <div style="background: #FFFFFF; border: 1px solid var(--fira-border-hairline); border-radius: 12px; padding: 18px;">
                            <div class="poc-hero-new">
                                <div>
                                    <span class="fira-badge fira-badge-pending" style="margin-bottom: 8px;">Direct Trade Infrastructure</span>
                                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--fira-text-primary); line-height: 1.25; margin-bottom: 6px;">
                                        Ethiopia's Premier Digital Coffee Trade & Export Gateway
                                    </h3>
                                    <p style="font-size: 0.8125rem; color: var(--fira-text-secondary); margin-bottom: 14px;">
                                        Source certified specialty Grade 1 & 2 micro-lots directly from Yirgacheffe, Sidama, and Guji washing stations with transparent cupping scores and escrow-backed contracts.
                                    </p>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <button class="fira-btn fira-btn-primary fira-btn-md" style="height: 40px;">Explore Verified Lots</button>
                                        <button class="fira-btn fira-btn-secondary fira-btn-md" style="height: 40px;">Market Prices</button>
                                    </div>
                                </div>
                                <div class="poc-hero-img-wrap" style="background-image: url('{{ asset('storage/hero/fira_hero_drying_beds.jpg') }}'); height: 180px;">
                                    <div class="poc-hero-img-badge">
                                        Authentic Ethiopian Origin • Raised Beds
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 1: COLOR TOKENS -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Tokens 01</span>
                <h2 class="fira-section-title">Color Palette & Semantic Tokens</h2>
                <p class="fira-section-intro">Strictly approved palette. Pending status is strictly purple on lavender. Pitch-black shadows are eliminated.</p>
            </div>

            <!-- Brand Swatches -->
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 12px; color: var(--fira-text-primary);">Brand Core</h3>
            <div class="fira-grid-4" style="margin-bottom: 24px;">
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #6D1A95; color: #FFFFFF;">#6D1A95</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Brand Primary</div>
                        <div class="fira-swatch-var">--fira-brand-primary</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #3B0764; color: #FFFFFF;">#3B0764</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Brand Deep (Hover)</div>
                        <div class="fira-swatch-var">--fira-brand-deep</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #F3E8FF; color: #3B0764;">#F3E8FF</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Brand Tint</div>
                        <div class="fira-swatch-var">--fira-brand-tint</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #FAF5FF; color: #6D1A95;">#FAF5FF</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Brand Subtle</div>
                        <div class="fira-swatch-var">--fira-brand-subtle</div>
                    </div>
                </div>
            </div>

            <!-- Surface & Text -->
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 12px; color: var(--fira-text-primary);">Surface & Typography</h3>
            <div class="fira-grid-4" style="margin-bottom: 24px;">
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #F9F7FA; color: #1E1B4B;">#F9F7FA</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Page Background</div>
                        <div class="fira-swatch-var">--fira-page-bg</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #FFFFFF; color: #1E1B4B; border-bottom: 1px solid #E9E4F0;">#FFFFFF</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Card / Modal Surface</div>
                        <div class="fira-swatch-var">--fira-surface</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #1E1B4B; color: #FFFFFF;">#1E1B4B</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Text Primary</div>
                        <div class="fira-swatch-var">--fira-text-primary</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #E9E4F0; color: #1E1B4B;">#E9E4F0</div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Hairline Border</div>
                        <div class="fira-swatch-var">--fira-border-hairline</div>
                    </div>
                </div>
            </div>

            <!-- Status Colors -->
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 12px; color: var(--fira-text-primary);">Semantic Status</h3>
            <div class="fira-grid-3">
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #F3E8FF; color: #6D1A95; border-bottom: 1px solid #E9D5FF;">
                        #6D1A95 on #F3E8FF
                    </div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Pending (Purple on Lavender)</div>
                        <div class="fira-swatch-var">--fira-pending</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #DCFCE7; color: #15803D; border-bottom: 1px solid #BBF7D0;">
                        #15803D on #DCFCE7
                    </div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Success (Verified / Active)</div>
                        <div class="fira-swatch-var">--fira-success</div>
                    </div>
                </div>
                <div class="fira-swatch">
                    <div class="fira-swatch-color" style="background: #FEE2E2; color: #B91C1C; border-bottom: 1px solid #FECACA;">
                        #B91C1C on #FEE2E2
                    </div>
                    <div class="fira-swatch-info">
                        <div class="fira-swatch-name">Danger / Error</div>
                        <div class="fira-swatch-var">--fira-danger</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: TYPOGRAPHY SCALE -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Tokens 02</span>
                <h2 class="fira-section-title">Typography Hierarchy (English & Amharic)</h2>
                <p class="fira-section-intro">Strict type scale with 14px floor for UI controls, 12px absolute floor for micro badges. Sentence case throughout.</p>
            </div>

            <div class="fira-card" style="padding: 32px;">
                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Display (56px – 64px) • Weight 700 • Letter-spacing: -0.035em</div>
                    <div style="font-size: clamp(2.5rem, 4vw, 3.5rem); font-weight: 700; line-height: 1.1; letter-spacing: -0.035em; color: var(--fira-brand-deep);">
                        Ethiopian Specialty Coffee Trade
                    </div>
                </div>

                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Heading 1 (40px – 44px) • Weight 700 • Letter-spacing: -0.025em</div>
                    <div style="font-size: 2.5rem; font-weight: 700; line-height: 1.15; letter-spacing: -0.025em; color: var(--fira-text-primary);">
                        Direct Sourcing from Washing Stations
                    </div>
                </div>

                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Heading 2 (30px – 32px) • Weight 600 • Letter-spacing: -0.02em</div>
                    <div style="font-size: 1.875rem; font-weight: 600; line-height: 1.25; letter-spacing: -0.02em; color: var(--fira-text-primary);">
                        Specialty Lot Verification & Lab Cupping
                    </div>
                </div>

                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Heading 3 (22px – 24px) • Weight 600 • Letter-spacing: -0.015em</div>
                    <div style="font-size: 1.375rem; font-weight: 600; line-height: 1.3; color: var(--fira-text-primary);">
                        Export Documentation & Phytosanitary Certificates
                    </div>
                </div>

                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Body Large (17px) & Standard (16px) • Line-height: 1.6</div>
                    <p style="font-size: 1.0625rem; color: var(--fira-text-secondary); max-width: 70ch; margin-bottom: 8px;">
                        Fira Link provides guaranteed chain-of-custody verification for every coffee container shipped through Djibouti. All lots include harvest elevation, processing method, moisture readings, and water activity analysis.
                    </p>
                    <p style="font-size: 1rem; color: var(--fira-text-secondary); max-width: 70ch;">
                        Our direct origin contracts connect specialty importers in Europe, Asia, and the Americas directly with certified Ethiopian cooperatives.
                    </p>
                </div>

                <!-- Amharic Specimen -->
                <div style="border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase;">Amharic Specimen (Noto Sans Ethiopic) • Line-height: 1.7</div>
                    <div style="font-family: var(--fira-font-amharic); font-size: 1.375rem; font-weight: 600; color: var(--fira-brand-primary); margin-bottom: 6px;">
                        የኢትዮጵያ ምርጥ ቡና መዳረሻ እና የንግድ መድረክ
                    </div>
                    <p style="font-family: var(--fira-font-amharic); font-size: 1rem; color: var(--fira-text-secondary); line-height: 1.7; max-width: 70ch;">
                        ፊራ ሊንክ የኢትዮጵያን የቡና አምራቾች፣ የህብረት ሥራ ማህበራትንና ላኪዎችን ከዓለም አቀፍ ገዢዎች ጋር በቀጥታ የሚያገናኝ ዘመናዊ የንግድ መድረክ ነው።
                    </p>
                </div>

                <!-- Tabular Figures -->
                <div>
                    <div style="font-size: 0.75rem; color: var(--fira-text-muted); text-transform: uppercase; margin-bottom: 8px;">Tabular Numbers (font-variant-numeric: tabular-nums)</div>
                    <div style="font-variant-numeric: tabular-nums; font-size: 1.5rem; font-weight: 700; color: var(--fira-brand-deep); display: flex; gap: 32px;">
                        <span>$4.85 / lb</span>
                        <span>$10,692 / MT</span>
                        <span>88.50 Q-Score</span>
                        <span>320 Bags (19.2 MT)</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: BUTTONS & INTERACTIVE STATES -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 01</span>
                <h2 class="fira-section-title">Buttons & Interactive States</h2>
                <p class="fira-section-intro">
                    The One-Primary-Per-View Rule: only ONE solid purple button is permitted per card/section. Heights: 52px (Hero/Main CTA) and 44px (Standard UI).
                </p>
            </div>

            <div class="fira-card">
                <!-- State Matrix -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center; border-bottom: 1px solid var(--fira-border-hairline); padding-bottom: 16px; margin-bottom: 16px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--fira-text-muted);">
                    <div>Variant</div>
                    <div>Default</div>
                    <div>Hover</div>
                    <div>Focus-Visible</div>
                    <div>Disabled</div>
                </div>

                <!-- Primary 44px -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 0.8125rem; font-weight: 600;">Primary (44px)</div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-md">Request sample</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-md is-hover">Request sample</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-md is-focus">Request sample</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-md" disabled>Request sample</button></div>
                </div>

                <!-- Primary 52px (Hero) -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 0.8125rem; font-weight: 600;">Primary Hero (52px)</div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-lg">Explore marketplace</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-lg is-hover">Explore marketplace</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-lg is-focus">Explore marketplace</button></div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-lg" disabled>Explore marketplace</button></div>
                </div>

                <!-- Secondary Outline -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 0.8125rem; font-weight: 600;">Secondary Outline</div>
                    <div><button class="fira-btn fira-btn-secondary fira-btn-md">View cupping sheet</button></div>
                    <div><button class="fira-btn fira-btn-secondary fira-btn-md is-hover">View cupping sheet</button></div>
                    <div><button class="fira-btn fira-btn-secondary fira-btn-md is-focus">View cupping sheet</button></div>
                    <div><button class="fira-btn fira-btn-secondary fira-btn-md" disabled>View cupping sheet</button></div>
                </div>

                <!-- Loading State -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 0.8125rem; font-weight: 600;">Loading State</div>
                    <div><button class="fira-btn fira-btn-primary fira-btn-md is-loading">Submit contract</button></div>
                    <div><button class="fira-btn fira-btn-secondary fira-btn-md is-loading">Fetching prices</button></div>
                    <div></div>
                    <div></div>
                </div>

                <!-- Destructive & Text Link -->
                <div style="display: grid; grid-template-columns: 140px 1fr 1fr 1fr 1fr; gap: 16px; align-items: center;">
                    <div style="font-size: 0.8125rem; font-weight: 600;">Special Actions</div>
                    <div><button class="fira-btn fira-btn-destructive fira-btn-md">Revoke license</button></div>
                    <div><a href="#" class="fira-btn-text">Download export contract (PDF)</a></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: FORM CONTROLS -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 02</span>
                <h2 class="fira-section-title">Form Controls & Validation States</h2>
                <p class="fira-section-intro">Strict 44px control heights, 12px radii, clean purple focus ring, and unambiguous error guidance.</p>
            </div>

            <div class="fira-grid-3">
                <!-- Standard Input -->
                <div class="fira-card">
                    <div class="fira-form-group">
                        <label class="fira-label">Buyer organization name</label>
                        <input type="text" class="fira-input" placeholder="e.g. Nordic Roasting Guild" value="Nordic Roasting Guild">
                        <span class="fira-hint">As registered on commercial import licenses</span>
                    </div>

                    <div class="fira-form-group">
                        <label class="fira-label">Select coffee origin region</label>
                        <select class="fira-select">
                            <option>Yirgacheffe (Gedeo Zone)</option>
                            <option>Sidama (Bensa / Chire)</option>
                            <option>Guji (Uraga / Hambela)</option>
                            <option>Harar (Heritage Natural)</option>
                        </select>
                    </div>
                </div>

                <!-- Error & Disabled States -->
                <div class="fira-card">
                    <div class="fira-form-group">
                        <label class="fira-label">Target FOB price (USD/lb)</label>
                        <input type="text" class="fira-input is-error" value="$2.10">
                        <span class="fira-error-msg">Price is below the ECX statutory minimum for Grade 1 Natural ($3.80/lb)</span>
                    </div>

                    <div class="fira-form-group">
                        <label class="fira-label">TIN / VAT certificate number</label>
                        <input type="text" class="fira-input" value="ET-00984128-L" disabled>
                        <span class="fira-hint">Locked after verified business onboarding</span>
                    </div>
                </div>

                <!-- Checkbox & Switch -->
                <div class="fira-card">
                    <label class="fira-label" style="margin-bottom: 12px;">Trade Preferences</label>
                    <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
                        <label class="fira-checkbox-row">
                            <input type="checkbox" class="fira-checkbox" checked>
                            <span>Only display lots with SCA Q-Score ≥ 87.0</span>
                        </label>
                        <label class="fira-checkbox-row">
                            <input type="checkbox" class="fira-checkbox" checked>
                            <span>Require verified dry mill warehouse receipt</span>
                        </label>
                        <label class="fira-checkbox-row">
                            <input type="checkbox" class="fira-checkbox">
                            <span>Request air freight sample prior to FCL booking</span>
                        </label>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div class="fira-switch-row fira-switch is-active">
                            <div class="fira-switch-toggle"></div>
                            <span style="font-size: 0.875rem; font-weight: 500;">Real-time ECX market price alerts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: NAVIGATION, TABS & CHIPS -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 03</span>
                <h2 class="fira-section-title">Navigation, Underline Tabs & Filter Chips</h2>
                <p class="fira-section-intro">
                    Active tabs use clean text with a 2px purple underline indicator. Filled pill buttons are prohibited as navigation tabs.
                </p>
            </div>

            <div class="fira-card" style="margin-bottom: 24px;">
                <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--fira-text-muted); margin-bottom: 12px;">Underline Style Tabs (Rule: Active is Underline, NOT a filled pill)</div>
                <div class="fira-tabs-underline">
                    <button class="fira-tab-item is-active">Spot contracts available</button>
                    <button class="fira-tab-item">Forward harvest bookings</button>
                    <button class="fira-tab-item">Verified washing stations</button>
                    <button class="fira-tab-item">Export shipping schedules</button>
                </div>
                <p style="font-size: 0.875rem; color: var(--fira-text-secondary);">
                    Viewing 42 verified spot coffee lots currently stored in Addis Ababa and Hawassa bonded warehouses awaiting container stuffing.
                </p>
            </div>

            <div class="fira-grid-2">
                <!-- Segmented Control -->
                <div class="fira-card">
                    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--fira-text-muted); margin-bottom: 12px;">Segmented Control</div>
                    <div class="fira-segmented-control">
                        <button class="fira-segment-btn is-active">USD / Pound ($/lb)</button>
                        <button class="fira-segment-btn">USD / Metric Tonne ($/MT)</button>
                        <button class="fira-segment-btn">ETB / Feresula</button>
                    </div>
                </div>

                <!-- Filter Chips -->
                <div class="fira-card">
                    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--fira-text-muted); margin-bottom: 12px;">Origin Filter Chips</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button class="fira-chip is-active">All Origins</button>
                        <button class="fira-chip">Yirgacheffe (18)</button>
                        <button class="fira-chip">Sidama (14)</button>
                        <button class="fira-chip">Guji (9)</button>
                        <button class="fira-chip">Limu (4)</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: BADGES & STATUS PILLS -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 04</span>
                <h2 class="fira-section-title">Badges & Semantic Status Pills</h2>
                <p class="fira-section-intro">Always a combination of Word + Colour. Pending is strictly purple on lavender.</p>
            </div>

            <div class="fira-card">
                <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                    <span class="fira-badge fira-badge-pending">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Pending admin review
                    </span>

                    <span class="fira-badge fira-badge-success">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Verified exporter (Tier 3)
                    </span>

                    <span class="fira-badge fira-badge-info">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Escrow secured
                    </span>

                    <span class="fira-badge fira-badge-danger">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        Contract disputed
                    </span>

                    <span class="fira-stale-tag">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        ECX price cached 14m ago
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 7: CARDS & LOT ARCHITECTURE -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 05</span>
                <h2 class="fira-section-title">Specialty Lot & Course Cards</h2>
                <p class="fira-section-intro">High data density with genuine Ethiopian origin imagery, tabular figures, and a single primary action.</p>
            </div>

            <div class="fira-grid-3">
                <!-- Coffee Lot Card 1 -->
                <div class="fira-lot-card">
                    <div class="fira-lot-thumb" style="background-image: url('{{ asset('storage/hero/fira_hero_drying_beds.jpg') }}');">
                        <span class="fira-badge fira-badge-pending fira-lot-thumb-badge" style="background: rgba(243, 232, 255, 0.95);">Grade 1 Natural</span>
                    </div>
                    <div class="fira-lot-body">
                        <span class="fira-lot-origin">Yirgacheffe • Chelchele Station</span>
                        <h4 class="fira-lot-title">Lot #YR-26-884 Chelchele Anaerobic</h4>
                        <div class="fira-lot-meta-grid">
                            <div class="fira-lot-meta-item">
                                <div>SCA Cupping</div>
                                <div class="fira-lot-meta-val">88.75 Q</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Elevation</div>
                                <div class="fira-lot-meta-val">2,150 MASL</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Available Volume</div>
                                <div class="fira-lot-meta-val">120 Bags (7.2 MT)</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Moisture / Water Act.</div>
                                <div class="fira-lot-meta-val">10.8% / 0.54 aW</div>
                            </div>
                        </div>
                        <div class="fira-lot-pricing">
                            <div>
                                <div class="fira-lot-price-val">$5.20 / lb</div>
                                <div class="fira-lot-price-sub">FOB Djibouti • Port Delivery</div>
                            </div>
                            <button class="fira-btn fira-btn-primary fira-btn-md">Request sample</button>
                        </div>
                    </div>
                </div>

                <!-- Coffee Lot Card 2 -->
                <div class="fira-lot-card">
                    <div class="fira-lot-thumb" style="background-image: url('{{ asset('storage/hero/fira_hero_cupping_lab.jpg') }}');">
                        <span class="fira-badge fira-badge-success fira-lot-thumb-badge" style="background: rgba(220, 252, 231, 0.95);">Grade 2 Washed</span>
                    </div>
                    <div class="fira-lot-body">
                        <span class="fira-lot-origin">Sidama • Bensa Station</span>
                        <h4 class="fira-lot-title">Lot #SD-26-312 Bensa Washed 74158</h4>
                        <div class="fira-lot-meta-grid">
                            <div class="fira-lot-meta-item">
                                <div>SCA Cupping</div>
                                <div class="fira-lot-meta-val">87.50 Q</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Elevation</div>
                                <div class="fira-lot-meta-val">1,980 MASL</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Available Volume</div>
                                <div class="fira-lot-meta-val">320 Bags (1 FCL)</div>
                            </div>
                            <div class="fira-lot-meta-item">
                                <div>Moisture / Water Act.</div>
                                <div class="fira-lot-meta-val">11.1% / 0.56 aW</div>
                            </div>
                        </div>
                        <div class="fira-lot-pricing">
                            <div>
                                <div class="fira-lot-price-val">$4.65 / lb</div>
                                <div class="fira-lot-price-sub">FOB Djibouti • Port Delivery</div>
                            </div>
                            <button class="fira-btn fira-btn-primary fira-btn-md">Request sample</button>
                        </div>
                    </div>
                </div>

                <!-- Course Card (Academy) -->
                <div class="fira-course-card">
                    <div class="fira-course-thumb" style="background-image: url('{{ asset('storage/courses/images/course-1-ethiopian-coffee-sourcing.jpg') }}');">
                        <span class="fira-badge fira-badge-info" style="position: absolute; top: 12px; left: 12px;">Trade Course</span>
                    </div>
                    <div class="fira-course-body">
                        <div class="fira-course-instructor">Instructor: <strong>Firaol Kebede</strong></div>
                        <h4 style="font-size: 1.0625rem; font-weight: 700; color: var(--fira-text-primary); margin-bottom: 8px;">
                            Ethiopian Coffee Sourcing & Export Logistics
                        </h4>
                        <p style="font-size: 0.8125rem; color: var(--fira-text-secondary); margin-bottom: 16px;">
                            Comprehensive mastery of ECX auctions, direct farm contracts, ICO documentation, and Djibouti port customs.
                        </p>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--fira-border-hairline);">
                            <span style="font-size: 0.875rem; font-weight: 700; color: var(--fira-success);">Free Enrollment</span>
                            <button class="fira-btn fira-btn-secondary fira-btn-md">View curriculum</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 8: DATA TABLE -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 06</span>
                <h2 class="fira-section-title">Commodity Market Table (Tabular Numbers)</h2>
                <p class="fira-section-intro">Sticky headers, right-aligned monetary rates and quantities with font-variant-numeric: tabular-nums.</p>
            </div>

            <div class="fira-table-wrap">
                <table class="fira-table">
                    <thead>
                        <tr>
                            <th>Origin / Station</th>
                            <th>Grade & Process</th>
                            <th class="num-col">SCA Q-Score</th>
                            <th class="num-col">Available (MT)</th>
                            <th class="num-col">FOB Djibouti ($/lb)</th>
                            <th class="num-col">FOB Rate ($/MT)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Yirgacheffe Chelchele</strong><br><span style="font-size: 0.75rem; color: var(--fira-text-muted);">Gedeo Zone • 2,100m</span></td>
                            <td>Grade 1 Anaerobic Natural</td>
                            <td class="num-col">89.25</td>
                            <td class="num-col">19.20</td>
                            <td class="num-col" style="font-weight: 700; color: var(--fira-brand-deep);">$5.40</td>
                            <td class="num-col">$11,905</td>
                            <td><span class="fira-badge fira-badge-success">Spot Ready</span></td>
                            <td><button class="fira-btn fira-btn-secondary fira-btn-md" style="height: 34px; padding: 0 12px; font-size: 0.75rem;">Inquire</button></td>
                        </tr>
                        <tr>
                            <td><strong>Sidama Bensa Kokose</strong><br><span style="font-size: 0.75rem; color: var(--fira-text-muted);">Sidama Region • 2,050m</span></td>
                            <td>Grade 1 Washed 74158</td>
                            <td class="num-col">88.50</td>
                            <td class="num-col">38.40</td>
                            <td class="num-col" style="font-weight: 700; color: var(--fira-brand-deep);">$4.95</td>
                            <td class="num-col">$10,912</td>
                            <td><span class="fira-badge fira-badge-success">Spot Ready</span></td>
                            <td><button class="fira-btn fira-btn-secondary fira-btn-md" style="height: 34px; padding: 0 12px; font-size: 0.75rem;">Inquire</button></td>
                        </tr>
                        <tr>
                            <td><strong>Guji Hambela Wamena</strong><br><span style="font-size: 0.75rem; color: var(--fira-text-muted);">East Guji • 2,200m</span></td>
                            <td>Grade 1 Natural Heirloom</td>
                            <td class="num-col">88.00</td>
                            <td class="num-col">57.60</td>
                            <td class="num-col" style="font-weight: 700; color: var(--fira-brand-deep);">$4.80</td>
                            <td class="num-col">$10,582</td>
                            <td><span class="fira-badge fira-badge-pending">Forward Contract</span></td>
                            <td><button class="fira-btn fira-btn-secondary fira-btn-md" style="height: 34px; padding: 0 12px; font-size: 0.75rem;">Inquire</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- SECTION 9: OVERLAYS, ALERTS & SKELETONS -->
    <section class="styleguide-section">
        <div class="fira-container">
            <div class="fira-section-header">
                <span class="fira-eyebrow">Components 07</span>
                <h2 class="fira-section-title">Alert Banners & Skeletons</h2>
                <p class="fira-section-intro">Graceful loading and clear status feedback banners.</p>
            </div>

            <div class="fira-grid-2">
                <div>
                    <div class="fira-alert fira-alert-pending">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div>
                            <strong>Seller KYC verification pending review:</strong> Your commercial export license has been submitted and is currently being audited by the compliance desk.
                        </div>
                    </div>

                    <div class="fira-alert fira-alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <div>
                            <strong>Escrow funds deposited:</strong> 100% of purchase collateral ($102,400 USD) has been verified. Dry milling release authorized.
                        </div>
                    </div>
                </div>

                <!-- Skeleton preview -->
                <div class="fira-card">
                    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--fira-text-muted); margin-bottom: 12px;">Skeleton Loader State</div>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <div class="fira-skeleton" style="width: 64px; height: 64px; border-radius: 12px;"></div>
                        <div style="flex-grow: 1;">
                            <div class="fira-skeleton" style="height: 18px; width: 60%; margin-bottom: 8px;"></div>
                            <div class="fira-skeleton" style="height: 14px; width: 85%; margin-bottom: 6px;"></div>
                            <div class="fira-skeleton" style="height: 14px; width: 40%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer for Styleguide -->
    <footer style="background: var(--fira-surface); border-top: 1px solid var(--fira-border-hairline); padding: 48px 0; margin-top: 48px;">
        <div class="fira-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
            <div>
                <div style="font-size: 1.125rem; font-weight: 800; color: var(--fira-brand-primary);">FIRA LINK PLATFORM</div>
                <p style="font-size: 0.8125rem; color: var(--fira-text-muted); margin-top: 4px;">
                    Unified Design System v2.0 • Phase 1 Component & Architectural Lab
                </p>
            </div>
            <div style="font-size: 0.8125rem; color: var(--fira-text-muted);">
                Single Container Rule (1200px) • 8px Spacing Grid • Inter + Noto Sans Ethiopic
            </div>
        </div>
    </footer>

</body>
</html>
