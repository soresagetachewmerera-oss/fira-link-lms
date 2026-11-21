(function () {
    'use strict';

    function initPremiumHeader() {
        var header = document.querySelector('[data-site-header]');
        var desktopDropdown = document.querySelector('.fira-nav-dropdown');
        var desktopTrigger = document.querySelector('.fira-dropdown-trigger');
        var mobileDropdown = document.querySelector('.fira-mobile-dropdown');
        var mobileTrigger = mobileDropdown ? mobileDropdown.querySelector(':scope > button') : null;
        var mobilePanel = document.getElementById('fira-mobile-panel');
        var menuToggle = document.querySelector('.fira-menu-toggle');
        var closeButton = mobilePanel ? mobilePanel.querySelector('.offcanvas__close button') : null;
        var overlay = document.querySelector('.offcanvas__overlay');
        var lastFocused = null;

        function syncHeader() {
            if (header) header.classList.toggle('is-scrolled', window.scrollY > 8);
        }

        function setDesktopDropdown(open) {
            if (!desktopDropdown || !desktopTrigger) return;
            desktopDropdown.classList.toggle('is-open', open);
            desktopTrigger.setAttribute('aria-expanded', String(open));
        }

        function setMobileDropdown(open) {
            if (!mobileDropdown || !mobileTrigger) return;
            mobileDropdown.classList.toggle('is-open', open);
            mobileTrigger.setAttribute('aria-expanded', String(open));
        }

        function panelIsOpen() {
            return mobilePanel && mobilePanel.classList.contains('info-open');
        }

        function openMobilePanel() {
            if (!mobilePanel) return;
            mobilePanel.classList.add('info-open');
            if (overlay) overlay.classList.add('overlay-open');
            syncMobileAccessibility(true);
            window.setTimeout(focusFirstPanelControl, 50);
        }

        function closeMobilePanel() {
            if (!mobilePanel) return;
            mobilePanel.classList.remove('info-open');
            if (overlay) overlay.classList.remove('overlay-open');
            syncMobileAccessibility(false);
            if (lastFocused) lastFocused.focus();
        }

        function syncMobileAccessibility(open) {
            if (!mobilePanel || !menuToggle) return;
            mobilePanel.setAttribute('aria-hidden', String(!open));
            menuToggle.setAttribute('aria-expanded', String(open));
            menuToggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
            document.body.classList.toggle('fira-menu-open', open);
        }

        function focusFirstPanelControl() {
            if (!mobilePanel) return;
            var first = mobilePanel.querySelector('button, a[href]');
            if (first) first.focus();
        }

        if (desktopTrigger) {
            desktopTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                setDesktopDropdown(!desktopDropdown.classList.contains('is-open'));
            });
        }

        if (mobileTrigger) {
            mobileTrigger.addEventListener('click', function () {
                setMobileDropdown(!mobileDropdown.classList.contains('is-open'));
            });
        }

        if (menuToggle) {
                menuToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    lastFocused = menuToggle;
                    if (panelIsOpen()) {
                        closeMobilePanel();
                    } else {
                        openMobilePanel();
                    }
                });
            }

            [closeButton, overlay].forEach(function (control) {
                if (!control) return;
                    control.addEventListener('click', function (event) {
                        event.preventDefault();
                        closeMobilePanel();
                    });
                });

                document.addEventListener('click', function (event) {
                    if (desktopDropdown && !desktopDropdown.contains(event.target)) setDesktopDropdown(false);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') return;
                    setDesktopDropdown(false);
                    if (panelIsOpen()) closeMobilePanel();
                });

                if (mobilePanel) {
                    mobilePanel.addEventListener('keydown', function (event) {
                        if (event.key !== 'Tab' || !panelIsOpen()) return;
                        var controls = Array.prototype.slice.call(mobilePanel.querySelectorAll('a[href], button:not([disabled])'));
                        if (!controls.length) return;
                        var first = controls[0];
                        var last = controls[controls.length - 1];
                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last.focus();
                        } else if (!event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first.focus();
                        }
                    });
                }

                syncHeader();
                syncMobileAccessibility(false);
                window.addEventListener('scroll', syncHeader, { passive: true });
            }

    if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPremiumHeader);
            } else {
                initPremiumHeader();
            }
        } ());
