const STORAGE_KEY = 'nadia_chat';
const TTL_MS = 24 * 60 * 60 * 1000;

function readBoot() {
    const node = document.getElementById('nadia-chat-boot');

    if (!node) {
        return { greeting: '' };
    }

    try {
        const data = JSON.parse(node.textContent || '{}');

        return {
            greeting: typeof data.greeting === 'string' ? data.greeting : '',
        };
    } catch {
        return { greeting: '' };
    }
}

function loadState() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return emptyState();
        }

        const data = JSON.parse(raw);

        if (!data || typeof data.at !== 'number' || Date.now() - data.at > TTL_MS) {
            localStorage.removeItem(STORAGE_KEY);

            return emptyState();
        }

        return {
            session: typeof data.session === 'string' ? data.session : null,
            limited: Boolean(data.limited),
            messages: Array.isArray(data.messages) ? data.messages.filter(isMessage) : [],
        };
    } catch {
        return emptyState();
    }
}

function emptyState() {
    return { session: null, limited: false, messages: [] };
}

function isMessage(item) {
    return item && (item.role === 'user' || item.role === 'assistant') && typeof item.content === 'string';
}

function saveState(state) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        at: Date.now(),
        session: state.session,
        limited: state.limited,
        messages: state.messages,
    }));
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') || '' : '';
}

function el(tag, className) {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    return node;
}

function chatIcon() {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '26');
    svg.setAttribute('height', '26');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('fill', 'currentColor');
    path.setAttribute('d', 'M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z');
    svg.appendChild(path);

    return svg;
}

function mount() {
    const boot = readBoot();
    const state = loadState();
    const root = el('div', 'nadia-chat-root');
    const launcher = el('button', 'nadia-chat-launcher');
    launcher.type = 'button';
    launcher.setAttribute('aria-label', 'Open chat');
    launcher.setAttribute('aria-expanded', 'false');
    launcher.appendChild(chatIcon());

    const panel = el('section', 'nadia-chat-panel');
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-labelledby', 'nadia-chat-title');

    const header = el('div', 'nadia-chat-header');
    const title = el('h2', 'nadia-chat-title');
    title.id = 'nadia-chat-title';
    title.textContent = "Nadia's assistant";
    const close = el('button', 'nadia-chat-close');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close chat');
    close.textContent = '×';
    header.append(title, close);

    const log = el('div', 'nadia-chat-log');
    log.setAttribute('aria-live', 'polite');

    const form = el('form', 'nadia-chat-form');
    const field = el('div', 'nadia-chat-field');
    const input = el('textarea', 'nadia-chat-input');
    input.rows = 1;
    input.maxLength = 1000;
    input.setAttribute('aria-label', 'Message');
    input.placeholder = 'Message';
    const count = el('div', 'nadia-chat-count');
    count.hidden = true;
    field.append(input, count);
    const send = el('button', 'nadia-chat-send');
    send.type = 'submit';
    send.textContent = 'Send';
    form.append(field, send);
    panel.append(header, log, form);
    root.append(launcher, panel);
    document.body.appendChild(root);

    function autosize() {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 88)}px`;
        const length = input.value.length;
        count.hidden = length <= 800;
        count.textContent = `${length}/1000`;
    }

    function setLimited(limited) {
        state.limited = limited;
        input.disabled = limited;
        send.disabled = limited || input.value.trim() === '';
        saveState(state);
    }

    function appendBubble(role, content, lead) {
        const row = el('div', `nadia-chat-row is-${role}`);
        const bubble = el('div', 'nadia-chat-bubble');
        bubble.textContent = content;
        row.appendChild(bubble);
        log.appendChild(row);

        if (lead) {
            const thanks = el('p', 'nadia-chat-thanks');
            thanks.textContent = 'Thanks — Nadia will reach out personally.';
            log.appendChild(thanks);
        }

        log.scrollTop = log.scrollHeight;
    }

    function render() {
        log.replaceChildren();
        if (boot.greeting) {
            appendBubble('assistant', boot.greeting, false);
        }
        state.messages.forEach((message) => {
            appendBubble(message.role, message.content, Boolean(message.lead));
        });
        setLimited(state.limited);
    }

    function showTyping() {
        const typing = el('div', 'nadia-chat-typing');
        typing.setAttribute('aria-label', 'Typing');
        typing.append(el('span'), el('span'), el('span'));
        log.appendChild(typing);
        log.scrollTop = log.scrollHeight;

        return typing;
    }

    function openPanel() {
        if (!panel.hidden) {
            return;
        }

        panel.hidden = false;
        root.classList.add('is-open');
        launcher.setAttribute('aria-expanded', 'true');

        if (!sessionStorage.getItem('nadia_chat_open')) {
            sessionStorage.setItem('nadia_chat_open', '1');
            window.dispatchEvent(new CustomEvent('nadia:chat_open'));
        }

        input.focus();
    }

    function closePanel() {
        panel.hidden = true;
        root.classList.remove('is-open');
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    }

    async function submit(event) {
        event.preventDefault();
        const text = input.value.trim();

        if (!text || state.limited || send.disabled) {
            return;
        }

        state.messages.push({ role: 'user', content: text, lead: false });
        saveState(state);
        input.value = '';
        autosize();
        render();
        send.disabled = true;
        const typing = showTyping();

        try {
            const response = await fetch('/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    session: state.session,
                    message: text,
                }),
            });
            const data = await response.json().catch(() => ({}));
            typing.remove();

            if (response.status === 429 || response.status === 503 || response.status === 422) {
                const serverText = data.error || data.message || (data.errors && data.errors.message && data.errors.message[0]) || '';
                const fallback = response.status === 503
                    ? 'Chat is unavailable right now. You can leave your details in the form below.'
                    : 'Too many attempts. Please try again in a minute.';
                const shown = serverText && serverText !== 'unavailable' ? serverText : fallback;
                if (data.code === 'session_limit') {
                    state.messages.pop();
                    state.limited = true;
                }
                state.messages.push({ role: 'assistant', content: shown, lead: false });
                saveState(state);
                render();

                return;
            }

            if (!response.ok) {
                state.messages.push({
                    role: 'assistant',
                    content: "Sorry, I'm having trouble answering right now. You can leave your details in the form below and Nadia will get back to you.",
                    lead: false,
                });
                saveState(state);
                render();

                return;
            }

            state.session = typeof data.session === 'string' ? data.session : state.session;
            state.messages.push({
                role: 'assistant',
                content: typeof data.reply === 'string' ? data.reply : '',
                lead: Boolean(data.lead_captured),
            });
            saveState(state);
            render();

            if (data.lead_captured) {
                window.dispatchEvent(new CustomEvent('nadia:chat_lead'));
            }
        } catch {
            typing.remove();
            state.messages.push({
                role: 'assistant',
                content: "Sorry, I'm having trouble answering right now. You can leave your details in the form below and Nadia will get back to you.",
                lead: false,
            });
            saveState(state);
            render();
        }
    }

    launcher.addEventListener('click', openPanel);
    close.addEventListener('click', closePanel);
    form.addEventListener('submit', submit);
    input.addEventListener('input', () => {
        autosize();
        send.disabled = state.limited || input.value.trim() === '';
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
        }
    });

    render();
    send.disabled = true;
}

if (document.getElementById('nadia-chat-boot')) {
    mount();
}
