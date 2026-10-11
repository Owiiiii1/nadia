function readCookie(name) {
    const prefix = `${name}=`;
    const parts = document.cookie ? document.cookie.split('; ') : [];

    for (const part of parts) {
        if (part.startsWith(prefix)) {
            return decodeURIComponent(part.slice(prefix.length));
        }
    }

    return null;
}

function writeCookie(name, value, maxAge) {
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax; Secure`;
}

function uuidv4() {
    if (window.crypto?.randomUUID) {
        return crypto.randomUUID();
    }

    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function visitorId() {
    const existing = readCookie('nadia_vid');

    if (existing) {
        return existing;
    }

    const created = uuidv4();
    writeCookie('nadia_vid', created, 60 * 60 * 24 * 365);

    return created;
}

function sessionId() {
    const existing = readCookie('nadia_sid');
    const id = existing || uuidv4();
    writeCookie('nadia_sid', id, 1800);

    return id;
}

function externalReferrer() {
    if (!document.referrer) {
        return null;
    }

    try {
        const url = new URL(document.referrer);

        return url.host === window.location.host ? null : document.referrer;
    } catch {
        return null;
    }
}

function utm() {
    const params = new URLSearchParams(window.location.search);

    return {
        utm_source: params.get('utm_source') || null,
        utm_medium: params.get('utm_medium') || null,
        utm_campaign: params.get('utm_campaign') || null,
        utm_term: params.get('utm_term') || null,
        utm_content: params.get('utm_content') || null,
    };
}

function send(payload) {
    const body = JSON.stringify(payload);

    if (navigator.sendBeacon) {
        const blob = new Blob([body], { type: 'text/plain' });

        if (navigator.sendBeacon('/t', blob)) {
            return;
        }
    }

    fetch('/t', {
        method: 'POST',
        body,
        keepalive: true,
        headers: { 'Content-Type': 'text/plain' },
    }).catch(() => {});
}

function track(extra) {
    send({
        visitor_id: visitorId(),
        session_id: sessionId(),
        path: window.location.pathname,
        referrer: externalReferrer(),
        ...utm(),
        ...extra,
    });
}

if (navigator.webdriver !== true) {
    track({ type: 'pageview' });

    window.addEventListener('nadia:lead', (event) => {
        track({
            type: 'event',
            event: 'form_submit',
            meta: { goal: event.detail?.goal || null },
        });
        trackConversion('form');
    });

    window.addEventListener('nadia:chat_open', () => {
        track({ type: 'event', event: 'chat_open' });
    });

    window.addEventListener('nadia:chat_lead', () => {
        track({ type: 'event', event: 'chat_lead' });
        trackConversion('chat');
    });
}

function trackConversion(method) {
    try {
        if (typeof window.fbq === 'function') {
            window.fbq('track', 'Lead', { content_name: method });
        }
    } catch {
        // A missing or broken pixel must not break the page.
    }

    try {
        if (typeof window.gtag === 'function') {
            window.gtag('event', 'generate_lead', { method });
        }
    } catch {
        // A missing or broken tag must not break the page.
    }
}
