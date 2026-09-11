(function () {
    const storageKey = 'cleanflow-ios-install-hint-dismissed';
    const installDismissalStorageKey = 'cleanflow-install-banner-dismissed-until';
    const installDismissalCooldownMs = 30 * 24 * 60 * 60 * 1000;
    const isSecureOrigin = window.isSecureContext
        || window.location.hostname === 'localhost'
        || window.location.hostname === '127.0.0.1';

    let deferredPrompt = null;
    let installBanner = null;
    let iosBanner = null;

    function canUseLocalStorage() {
        try {
            const testKey = '__cleanflow_pwa__';
            localStorage.setItem(testKey, testKey);
            localStorage.removeItem(testKey);

            return true;
        } catch (error) {
            return false;
        }
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function isHomePage() {
        return window.location.pathname === '/' || window.location.pathname === '';
    }

    function hasInstallDismissalCooldown() {
        if (!canUseLocalStorage()) {
            return false;
        }

        const dismissedUntil = Number(localStorage.getItem(installDismissalStorageKey));

        if (Number.isFinite(dismissedUntil) && dismissedUntil > Date.now()) {
            return true;
        }

        localStorage.removeItem(installDismissalStorageKey);

        return false;
    }

    function rememberInstallDismissal() {
        if (canUseLocalStorage()) {
            localStorage.setItem(
                installDismissalStorageKey,
                String(Date.now() + installDismissalCooldownMs)
            );
        }
    }

    function createActionButton(label, onClick) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.style.cssText = 'border:0;border-radius:14px;padding:12px 16px;font-weight:700;font-size:14px;cursor:pointer;';
        button.addEventListener('click', onClick);

        return button;
    }

    function buildBanner(title, message, actionLabel, actionHandler, onDismiss) {
        const banner = document.createElement('aside');
        banner.style.cssText = [
            'position:fixed',
            'right:16px',
            'bottom:16px',
            'z-index:9999',
            'width:min(100vw - 32px, 360px)',
            'padding:18px',
            'border-radius:22px',
            'background:#1E40AF',
            'color:#ffffff',
            'box-shadow:0 22px 45px rgba(15, 23, 42, 0.24)',
            'font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
        ].join(';');

        const heading = document.createElement('div');
        heading.textContent = title;
        heading.style.cssText = 'font-size:18px;font-weight:800;line-height:1.2;margin-bottom:8px;';

        const copy = document.createElement('p');
        copy.textContent = message;
        copy.style.cssText = 'margin:0 0 16px;font-size:14px;line-height:1.6;color:rgba(255,255,255,0.92);';

        const actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;';

        const actionButton = createActionButton(actionLabel, actionHandler);
        actionButton.style.background = '#ffffff';
        actionButton.style.color = '#1E3A8A';

        const dismissButton = createActionButton('Not now', onDismiss);
        dismissButton.style.background = 'rgba(255,255,255,0.18)';
        dismissButton.style.color = '#ffffff';

        actions.append(actionButton, dismissButton);
        banner.append(heading, copy, actions);

        return banner;
    }

    function hideBanner(banner) {
        if (banner && banner.parentNode) {
            banner.parentNode.removeChild(banner);
        }
    }

    function showInstallBanner() {
        if (!deferredPrompt || !isHomePage() || isStandalone() || installBanner || hasInstallDismissalCooldown()) {
            return;
        }

        installBanner = buildBanner(
            'Install CleanFlow',
            'Add Home Cleaning Service to your phone or desktop for faster access and an app-like experience.',
            'Install app',
            async function () {
                const promptEvent = deferredPrompt;

                if (!promptEvent) {
                    return;
                }

                let outcome = 'dismissed';

                try {
                    promptEvent.prompt();
                    outcome = (await promptEvent.userChoice)?.outcome ?? 'dismissed';
                } catch (error) {
                    console.warn('Install prompt failed:', error);
                }

                deferredPrompt = null;

                if (outcome !== 'accepted') {
                    rememberInstallDismissal();
                }

                hideBanner(installBanner);
                installBanner = null;
            },
            function () {
                rememberInstallDismissal();
                hideBanner(installBanner);
                installBanner = null;
            }
        );

        document.body.appendChild(installBanner);
    }

    function showIosBanner() {
        const isiOS = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
        const storageAllowed = canUseLocalStorage();

        if (!isiOS || !isHomePage() || isStandalone() || iosBanner || (storageAllowed && localStorage.getItem(storageKey) === '1')) {
            return;
        }

        iosBanner = buildBanner(
            'Install on iPhone',
            'Open the Share menu in Safari, then choose "Add to Home Screen" to install this web app.',
            'Got it',
            function () {
                if (storageAllowed) {
                    localStorage.setItem(storageKey, '1');
                }

                hideBanner(iosBanner);
                iosBanner = null;
            },
            function () {
                if (storageAllowed) {
                    localStorage.setItem(storageKey, '1');
                }

                hideBanner(iosBanner);
                iosBanner = null;
            }
        );

        document.body.appendChild(iosBanner);
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        showInstallBanner();
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        hideBanner(installBanner);
        installBanner = null;
        hideBanner(iosBanner);
        iosBanner = null;
    });

    window.addEventListener('load', function () {
        if ('serviceWorker' in navigator && isSecureOrigin) {
            navigator.serviceWorker.register('/sw.js').catch(function (error) {
                console.warn('Service worker registration failed:', error);
            });
        }

        showIosBanner();
    });
})();
