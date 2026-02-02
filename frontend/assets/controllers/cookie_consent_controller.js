import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['banner'];
    static values = {
        gaId: String
    };

    connect() {
        const consent = this.getConsent();

        if (consent === null) {
            this.showBanner();
        } else if (consent === 'granted') {
            this.loadGoogleAnalytics();
        }
    }

    accept() {
        this.setConsent('granted');
        this.hideBanner();
        this.loadGoogleAnalytics();
    }

    decline() {
        this.setConsent('denied');
        this.hideBanner();
    }

    openSettings() {
        this.showBanner();
    }

    showBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.remove('d-none');
            this.bannerTarget.setAttribute('aria-hidden', 'false');
        }
    }

    hideBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.add('d-none');
            this.bannerTarget.setAttribute('aria-hidden', 'true');
        }
    }

    getConsent() {
        // Try localStorage first
        const localData = localStorage.getItem('cookie_consent_data');
        if (localData) {
            try {
                const parsed = JSON.parse(localData);
                return parsed.consent;
            } catch (e) {
                // Invalid JSON, continue to cookie fallback
            }
        }

        // Fallback to cookie
        const cookieMatch = document.cookie.match(/(?:^|;\s*)cookie_consent=([^;]*)/);
        if (cookieMatch) {
            return cookieMatch[1];
        }

        return null;
    }

    setConsent(value) {
        // Store in cookie (365 days)
        const expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = `cookie_consent=${value}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;

        // Store in localStorage with metadata
        const data = {
            consent: value,
            timestamp: new Date().toISOString(),
            version: '1.0'
        };
        localStorage.setItem('cookie_consent_data', JSON.stringify(data));
    }

    loadGoogleAnalytics() {
        if (!this.gaIdValue || window.gtag) {
            return;
        }

        // Load gtag.js script
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${this.gaIdValue}`;
        document.head.appendChild(script);

        // Initialize gtag
        window.dataLayer = window.dataLayer || [];
        window.gtag = function() {
            window.dataLayer.push(arguments);
        };
        window.gtag('js', new Date());
        window.gtag('config', this.gaIdValue);
    }
}
