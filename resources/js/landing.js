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

function externalReferrer() {
    if (!document.referrer) {
        return '';
    }

    try {
        const url = new URL(document.referrer);

        return url.host === window.location.host ? '' : document.referrer;
    } catch {
        return '';
    }
}

function attribution() {
    const params = new URLSearchParams(window.location.search);
    const keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    const hasUtm = keys.some((key) => params.get(key));
    let stored = null;

    try {
        stored = JSON.parse(readCookie('nadia_ft') || 'null');
    } catch {
        stored = null;
    }

    if (!stored || typeof stored !== 'object' || hasUtm) {
        stored = {
            utm_source: params.get('utm_source') || '',
            utm_medium: params.get('utm_medium') || '',
            utm_campaign: params.get('utm_campaign') || '',
            utm_term: params.get('utm_term') || '',
            utm_content: params.get('utm_content') || '',
            referrer: externalReferrer(),
            landing_path: `${window.location.pathname}${window.location.search}`,
        };
        writeCookie('nadia_ft', JSON.stringify(stored), 60 * 60 * 24 * 30);
    }

    return stored;
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

function setValue(id, value) {
    const field = document.getElementById(id);

    if (field) {
        field.value = value || '';
    }
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach((node) => node.remove());
    form.querySelector('[data-form-error]')?.remove();
}

function showBanner(form, message) {
    const banner = document.createElement('p');
    banner.className = 'lead-banner';
    banner.dataset.formError = '1';
    banner.textContent = message;
    form.insertBefore(banner, form.firstChild);
}

function showThanks(form, name, goal) {
    const box = document.createElement('div');
    box.className = 'cta-box';
    box.id = 'lead-thanks';
    const paragraph = document.createElement('p');
    paragraph.className = 'lead-success';
    paragraph.textContent = `Thank you, ${name}. I'll be in touch shortly.`;
    box.append(paragraph);
    form.replaceWith(box);
    window.dispatchEvent(new CustomEvent('nadia:lead', { detail: { goal } }));
}

const form = document.getElementById('lead-form');

if (form) {
    const touch = attribution();
    setValue('visitor_id', visitorId());
    setValue('utm_source', touch.utm_source);
    setValue('utm_medium', touch.utm_medium);
    setValue('utm_campaign', touch.utm_campaign);
    setValue('utm_term', touch.utm_term);
    setValue('utm_content', touch.utm_content);
    setValue('referrer', touch.referrer);
    setValue('landing_path', touch.landing_path);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const button = form.querySelector('button[type="submit"]');
        const previousLabel = button ? button.textContent : '';

        if (button) {
            button.disabled = true;
            button.textContent = 'Sending…';
        }

        const payload = Object.fromEntries(new FormData(form).entries());
        delete payload._token;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/leads', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(payload),
            });

            if (response.status === 200 || response.status === 201) {
                showThanks(form, payload.name || '', payload.goal || '');
                return;
            }

            if (response.status === 422) {
                const body = await response.json().catch(() => ({}));
                Object.entries(body.errors || {}).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);

                    if (!input) {
                        return;
                    }

                    const note = document.createElement('span');
                    note.className = 'field-error';
                    note.textContent = Array.isArray(messages) ? messages[0] : String(messages);
                    input.insertAdjacentElement('afterend', note);
                });
            } else if (response.status === 429) {
                showBanner(form, 'Too many attempts, please try again in a minute.');
            } else {
                showBanner(form, 'Something went wrong. Please try again.');
            }
        } catch {
            showBanner(form, 'Something went wrong. Please try again.');
        }

        if (button) {
            button.disabled = false;
            button.textContent = previousLabel;
        }
    });
}
