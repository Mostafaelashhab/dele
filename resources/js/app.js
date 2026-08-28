import './map';
import './uploads';

/**
 * Banha Delivery Network — front-end runtime.
 *
 * Livewire (and the Alpine it bundles) drives every interactive surface, so
 * this file only holds the few browser capabilities Livewire cannot express
 * from the server: geolocation, clipboard, and the service worker.
 */

/**
 * Rider position reporting.
 *
 * The interval comes from the server rather than being hard-coded, so the
 * platform can throttle every rider's phone at once — battery life is an
 * operational concern, not a front-end detail.
 */
class RiderLocationReporter {
    constructor({ endpoint, intervalSeconds = 15, csrfToken }) {
        this.endpoint = endpoint;
        this.intervalMs = intervalSeconds * 1000;
        this.csrfToken = csrfToken;
        this.watchId = null;
        this.timer = null;
        this.lastSentAt = 0;
        this.lastPosition = null;
    }

    start() {
        if (!('geolocation' in navigator) || this.watchId !== null) {
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.lastPosition = position;
                this.maybeSend();
            },
            (error) => console.warn('Geolocation unavailable', error.message),
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 },
        );

        // A stationary rider still needs to prove they are online, so a timer
        // backs up the movement-driven updates.
        this.timer = window.setInterval(() => this.maybeSend(true), this.intervalMs);
    }

    stop() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        if (this.timer !== null) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    }

    maybeSend(force = false) {
        if (!this.lastPosition) {
            return;
        }

        const now = Date.now();

        if (!force && now - this.lastSentAt < this.intervalMs) {
            return;
        }

        this.lastSentAt = now;
        this.send(this.lastPosition);
    }

    send(position) {
        const { latitude, longitude, accuracy, heading, speed } = position.coords;

        const body = JSON.stringify({
            latitude,
            longitude,
            accuracy: Number.isFinite(accuracy) ? Math.round(accuracy) : null,
            heading: Number.isFinite(heading) ? Math.round(heading) : null,
            speed: Number.isFinite(speed) ? Math.round(speed * 3.6) : null,
            recorded_at: new Date(position.timestamp).toISOString(),
        });

        fetch(this.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
            keepalive: true,
        }).catch(() => {
            // A dropped ping is not worth interrupting a rider for; the next
            // one carries the newer position anyway.
        });
    }
}

window.RiderLocationReporter = RiderLocationReporter;

/**
 * Copy-to-clipboard for tracking links and API keys, with a graceful fallback
 * for the non-secure origins some shared hosts still serve on.
 */
window.copyToClipboard = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);

            return true;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        return true;
    } catch {
        return false;
    }
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {
            // Offline support is an enhancement; the app works without it.
        });
    });
}
