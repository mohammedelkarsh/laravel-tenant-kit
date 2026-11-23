/**
 * Tenant Kit guided agent — sequential menus, flows, and free-text fallback.
 */
function bootApiOperatorWidget(root) {
    if (!root || root.dataset.booted === '1') {
        return;
    }
    root.dataset.booted = '1';

    const panel = root.querySelector('[data-api-operator-panel]');
    const fab = root.querySelector('[data-api-operator-fab]');
    const messagesEl = root.querySelector('[data-api-operator-messages]');
    const form = root.querySelector('[data-api-operator-form]');
    const input = root.querySelector('[data-api-operator-input]');
    const sendBtn = root.querySelector('[data-api-operator-send]');
    const statusEl = root.querySelector('[data-api-operator-status]');
    const quickActionsEl = root.querySelector('[data-api-operator-quick-actions]');

    const statusUrl = root.dataset.statusUrl;
    const chatUrl = root.dataset.chatUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const unavailable = root.dataset.unavailable ?? 'API Operator unavailable';
    const greeting = root.dataset.greeting ?? 'Hello';
    const centralDomain = root.dataset.centralDomain ?? 'localhost';
    const defaultPlaceholder = root.dataset.placeholder ?? 'Type your answer…';

    let i18n = {};
    try {
        i18n = JSON.parse(root.dataset.i18n || '{}');
    } catch {
        i18n = {};
    }

    const CHIP =
        'padding:0.35rem 0.65rem;font-size:12px;line-height:1.2;border:1px solid #6366f1;border-radius:9999px;background:#eef2ff;color:#312e81;cursor:pointer;white-space:nowrap;font-weight:600;';
    const CHIP_SECONDARY =
        'padding:0.35rem 0.65rem;font-size:12px;line-height:1.2;border:1px solid #d1d5db;border-radius:9999px;background:#fff;color:#4b5563;cursor:pointer;white-space:nowrap;';

    let sessionId = null;
    let healthy = false;
    let open = false;
    let currentMenu = 'main';
    let flow = null;
    let menuInitialized = false;
    let awaitingConfirmation = false;

    function initialMessages() {
        const bubble = messagesEl?.querySelector('[data-initial-agent-message] div');
        const text = bubble?.textContent?.trim() || greeting;
        return [{ role: 'agent', text, status: 'ok' }];
    }

    const messages = initialMessages();

    function t(path, fallback = '') {
        const parts = path.split('.');
        let node = i18n;
        for (const part of parts) {
            node = node?.[part];
        }
        return typeof node === 'string' && node.length ? node : fallback;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function slugify(name) {
        return name
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 63);
    }

    function isEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function pushAgent(text, status = 'ok') {
        messages.push({ role: 'agent', text, status });
        renderMessages();
    }

    function pushUser(text) {
        messages.push({ role: 'user', text, status: 'ok' });
        renderMessages();
    }

    function renderMessages() {
        if (!messagesEl) {
            return;
        }
        messagesEl.innerHTML = messages
            .map((msg) => {
                const isUser = msg.role === 'user';
                const align = isUser ? 'flex-end' : 'flex-start';
                let bg = '#fff';
                let color = '#1f2937';
                let border = '1px solid #e5e7eb';
                if (isUser) {
                    bg = '#4f46e5';
                    color = '#fff';
                    border = 'none';
                } else if (msg.status === 'error' || msg.status === 'deny') {
                    bg = '#fef2f2';
                    color = '#991b1b';
                    border = '1px solid #fecaca';
                } else if (msg.status === 'confirm') {
                    bg = '#fffbeb';
                    color = '#92400e';
                    border = '1px solid #fde68a';
                }
                return `<div style="display:flex;justify-content:${align};margin-bottom:0.75rem;">
                    <div style="max-width:90%;padding:0.5rem 0.75rem;border-radius:1rem;font-size:0.875rem;white-space:pre-wrap;background:${bg};color:${color};border:${border};">${escapeHtml(msg.text)}</div>
                </div>`;
            })
            .join('');
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function toDataAttrName(key) {
        return key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);
    }

    function renderChips(chips) {
        if (!quickActionsEl) {
            return;
        }
        quickActionsEl.innerHTML = chips
            .map((chip) => {
                const style = chip.secondary ? CHIP_SECONDARY : CHIP;
                const attrs = Object.entries(chip.data || {})
                    .map(([key, value]) => `data-${toDataAttrName(key)}="${escapeHtml(String(value))}"`)
                    .join(' ');
                return `<button type="button" style="${style}" ${attrs}>${escapeHtml(chip.label)}</button>`;
            })
            .join('');
    }

    function showMenu(menuId, announce = true) {
        currentMenu = menuId;
        flow = null;
        const menu = i18n.menus?.[menuId];
        if (!menu) {
            return;
        }
        if (announce && menu.prompt) {
            pushAgent(menu.prompt);
        }
        const chips = (menu.options || []).map((opt) => ({
            label: opt.label,
            data: { agentChip: 'menu', chipId: opt.id },
        }));
        renderChips(chips);
        if (input) {
            input.placeholder = defaultPlaceholder;
        }
    }

    function showHelp() {
        flow = null;
        currentMenu = 'main';
        pushAgent(i18n.help || t('help', 'Choose a topic below.'));
        showMenu('main', false);
    }

    function startFlow(flowId) {
        flow = { id: flowId, step: 0, data: {} };
        advanceFlow();
    }

    function workspaceChips() {
        const suggestions = i18n.workspace_suggestions || ['demo'];
        const chips = suggestions.map((ws) => ({
            label: ws,
            data: { agentChip: 'flow-value', value: ws },
        }));
        chips.push({
            label: t('chip_main_menu', '← Main menu'),
            secondary: true,
            data: { agentChip: 'main-menu' },
        });
        return chips;
    }

    function roleChips() {
        const roles = i18n.roles || { member: 'Member', admin: 'Admin' };
        const chips = Object.entries(roles).map(([key, label]) => ({
            label,
            data: { agentChip: 'flow-value', value: key },
        }));
        chips.push({
            label: t('chip_cancel', 'Cancel'),
            secondary: true,
            data: { agentChip: 'cancel-flow' },
        });
        return chips;
    }

    function confirmChips() {
        return [
            { label: t('chip_confirm', 'Confirm'), data: { agentChip: 'flow-confirm' } },
            { label: t('chip_cancel', 'Cancel'), secondary: true, data: { agentChip: 'cancel-flow' } },
        ];
    }

    function apiConfirmChips() {
        return [
            { label: t('chip_yes', 'Yes, proceed'), data: { agentChip: 'api-confirm', value: 'yes' } },
            { label: t('chip_no', 'No, cancel'), secondary: true, data: { agentChip: 'api-confirm', value: 'no' } },
        ];
    }

    function advanceFlow() {
        if (!flow) {
            showMenu('main', false);
            return;
        }

        const texts = i18n.flows?.[flow.id] || {};

        if (flow.id === 'create_workspace') {
            if (flow.step === 0) {
                pushAgent(texts.name_prompt || 'Workspace name?');
                renderChips([
                    { label: t('chip_cancel', 'Cancel'), secondary: true, data: { agentChip: 'cancel-flow' } },
                ]);
                return;
            }
            if (flow.step === 1) {
                const suggested = slugify(flow.data.name || '');
                pushAgent(texts.subdomain_prompt || 'Subdomain?');
                const chips = [];
                if (suggested) {
                    chips.push({
                        label: `${suggested}.${centralDomain}`,
                        data: { agentChip: 'flow-value', value: suggested },
                    });
                }
                chips.push({
                    label: t('chip_cancel', 'Cancel'),
                    secondary: true,
                    data: { agentChip: 'cancel-flow' },
                });
                renderChips(chips);
                return;
            }
            if (flow.step === 2) {
                const summary = (texts.confirm_prompt || 'Create ":name" at :subdomain.:domain?')
                    .replace(':name', flow.data.name)
                    .replace(':subdomain', flow.data.subdomain)
                    .replace(':domain', centralDomain);
                pushAgent(summary);
                renderChips(confirmChips());
            }
            return;
        }

        if (flow.id === 'usage' || flow.id === 'subscription') {
            if (flow.step === 0) {
                pushAgent(texts.workspace_prompt || 'Which workspace?');
                renderChips(workspaceChips());
            }
            return;
        }

        if (flow.id === 'invite_member') {
            if (flow.step === 0) {
                pushAgent(texts.email_prompt || 'Email?');
                renderChips([
                    { label: t('chip_cancel', 'Cancel'), secondary: true, data: { agentChip: 'cancel-flow' } },
                ]);
                return;
            }
            if (flow.step === 1) {
                pushAgent(texts.workspace_prompt || 'Workspace subdomain?');
                renderChips(workspaceChips());
                return;
            }
            if (flow.step === 2) {
                pushAgent(texts.role_prompt || 'Role?');
                renderChips(roleChips());
                return;
            }
            if (flow.step === 3) {
                const roleLabel = i18n.roles?.[flow.data.role] || flow.data.role;
                const summary = (texts.confirm_prompt || 'Invite :email to :workspace as :role?')
                    .replace(':email', flow.data.email)
                    .replace(':workspace', flow.data.workspace)
                    .replace(':role', roleLabel);
                pushAgent(summary);
                renderChips(confirmChips());
            }
        }
    }

    function cancelFlow() {
        flow = null;
        pushAgent(i18n.flow_cancelled || 'Cancelled. What else can I help with?');
        showMenu('main', false);
    }

    function completeFlow() {
        if (!flow) {
            return;
        }
        let command = '';
        if (flow.id === 'create_workspace') {
            command = `create workspace ${flow.data.name} subdomain ${flow.data.subdomain}`;
        } else if (flow.id === 'usage') {
            command = `get usage for ${flow.data.workspace}`;
        } else if (flow.id === 'subscription') {
            command = `get subscription for ${flow.data.workspace}`;
        } else if (flow.id === 'invite_member') {
            command = `invite ${flow.data.email} to ${flow.data.workspace} ${flow.data.role}`;
        }
        const doneFlow = flow.id;
        flow = null;
        sendToApi(command, { restoreMenu: true, silentUser: true });
        void doneFlow;
    }

    function handleFlowInput(text) {
        const trimmed = text.trim();
        if (!trimmed) {
            return;
        }
        pushUser(trimmed);
        const texts = i18n.flows?.[flow.id] || {};

        if (flow.id === 'create_workspace') {
            if (flow.step === 0) {
                flow.data.name = trimmed;
                flow.step = 1;
                advanceFlow();
                return;
            }
            if (flow.step === 1) {
                const sub = slugify(trimmed.replace(new RegExp(`\\.?${centralDomain.replace('.', '\\.')}$`, 'i'), ''));
                if (!sub) {
                    pushAgent(texts.invalid_subdomain || 'Invalid subdomain.', 'error');
                    return;
                }
                flow.data.subdomain = sub;
                flow.step = 2;
                advanceFlow();
            }
            return;
        }

        if (flow.id === 'invite_member' && flow.step === 0) {
            if (!isEmail(trimmed)) {
                pushAgent(texts.invalid_email || 'Invalid email.', 'error');
                return;
            }
            flow.data.email = trimmed.toLowerCase();
            flow.step = 1;
            advanceFlow();
        }
    }

    function handleChipClick(btn) {
        const kind = btn.dataset.agentChip;
        if (!kind) {
            return;
        }

        if (kind === 'main-menu') {
            pushUser(btn.textContent.trim());
            showMenu('main', true);
            return;
        }

        if (kind === 'cancel-flow') {
            pushUser(btn.textContent.trim());
            cancelFlow();
            return;
        }

        if (kind === 'flow-confirm') {
            if (!healthy) {
                pushAgent(unavailable, 'error');
                return;
            }
            pushUser(btn.textContent.trim());
            completeFlow();
            return;
        }

        if (kind === 'api-confirm') {
            if (!healthy) {
                pushAgent(unavailable, 'error');
                return;
            }
            const answer = btn.dataset.value || 'no';
            pushUser(btn.textContent.trim());
            awaitingConfirmation = false;
            sendToApi(answer, { restoreMenu: true, silentUser: true });
            return;
        }

        if (kind === 'flow-value') {
            const value = btn.dataset.value || '';
            pushUser(btn.textContent.trim());
            if (!flow) {
                return;
            }
            if (flow.id === 'usage' || flow.id === 'subscription') {
                flow.data.workspace = value;
                completeFlow();
                return;
            }
            if (flow.id === 'create_workspace' && flow.step === 1) {
                flow.data.subdomain = value;
                flow.step = 2;
                advanceFlow();
                return;
            }
            if (flow.id === 'invite_member') {
                if (flow.step === 1) {
                    flow.data.workspace = value;
                    flow.step = 2;
                    advanceFlow();
                    return;
                }
                if (flow.step === 2) {
                    flow.data.role = value;
                    flow.step = 3;
                    advanceFlow();
                }
            }
            return;
        }

        if (kind === 'menu') {
            const chipId = btn.dataset.chipId;
            const menu = i18n.menus?.[currentMenu];
            const option = menu?.options?.find((item) => item.id === chipId);
            if (!option) {
                return;
            }
            pushUser(option.label);

            if (option.help) {
                showHelp();
                return;
            }
            if (option.menu) {
                showMenu(option.menu, true);
                return;
            }
            if (option.flow) {
                startFlow(option.flow);
                return;
            }
            if (option.command) {
                if (!healthy) {
                    pushAgent(unavailable, 'error');
                    return;
                }
                sendToApi(option.command, { restoreMenu: true, silentUser: true });
            }
        }
    }

    function wireQuickActions() {
        if (!quickActionsEl || quickActionsEl.dataset.wired === '1') {
            return;
        }
        quickActionsEl.dataset.wired = '1';
        quickActionsEl.addEventListener('click', (event) => {
            const btn = event.target.closest('button[data-agent-chip]');
            if (!btn || !quickActionsEl.contains(btn)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            handleChipClick(btn);
        });
    }

    function normalizeFreeText(text) {
        const trimmed = text.trim();
        if (!trimmed) {
            return trimmed;
        }
        if (/^(yes|no|y|n)$/i.test(trimmed)) {
            return trimmed.toLowerCase();
        }
        const lower = trimmed.toLowerCase();
        if (/\bhelp\b/i.test(lower) || trimmed.includes('مساعدة')) {
            return '__help__';
        }
        return trimmed;
    }

    function setUiEnabled(enabled) {
        if (input) {
            input.disabled = !enabled;
        }
        if (sendBtn) {
            sendBtn.disabled = !enabled;
        }
        quickActionsEl?.querySelectorAll('button').forEach((btn) => {
            btn.disabled = false;
            btn.style.opacity = enabled ? '1' : '0.85';
            btn.style.pointerEvents = 'auto';
        });
    }

    async function sendToApi(text, options = {}) {
        const normalized = normalizeFreeText(text);
        if (!normalized || !healthy) {
            return;
        }

        if (normalized === '__help__') {
            showHelp();
            return;
        }

        if (!options.silentUser) {
            pushUser(text.trim());
        }

        setUiEnabled(false);
        try {
            const res = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message: normalized, session_id: sessionId }),
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message ?? unavailable);
            }
            sessionId = data.session_id;
            const agentStatus = data.status ?? 'ok';
            messages.push({
                role: 'agent',
                text: data.message,
                status: agentStatus,
            });
            renderMessages();

            if (agentStatus === 'confirm') {
                awaitingConfirmation = true;
                renderChips(apiConfirmChips());
                if (input) {
                    input.placeholder = i18n.confirm_placeholder || 'Type yes to confirm or no to cancel';
                }
                return;
            }

            awaitingConfirmation = false;
            if (agentStatus === 'deny') {
                // Policy/quota/ability rejection — keep menu available to try another action
                showMenu('main', false);
                return;
            }
            if (options.restoreMenu) {
                showMenu('main', agentStatus === 'ok');
            }
        } catch (error) {
            pushAgent(error.message ?? unavailable, 'error');
        } finally {
            setUiEnabled(healthy);
        }
    }

    function handleUserSubmit(text) {
        const trimmed = text.trim();
        if (!trimmed) {
            return;
        }
        if (awaitingConfirmation) {
            if (!healthy) {
                pushAgent(unavailable, 'error');
                return;
            }
            const normalized = normalizeFreeText(trimmed);
            if (!/^(yes|no|y|n)$/i.test(trimmed)) {
                pushAgent(i18n.confirm_hint || 'Please type yes to confirm or no to cancel.', 'confirm');
                renderChips(apiConfirmChips());
                return;
            }
            if (input) {
                input.value = '';
            }
            pushUser(trimmed);
            awaitingConfirmation = false;
            sendToApi(normalized, { restoreMenu: true, silentUser: true });
            return;
        }
        if (flow) {
            if (input) {
                input.value = '';
            }
            handleFlowInput(trimmed);
            return;
        }
        if (!healthy) {
            pushAgent(unavailable, 'error');
            return;
        }
        if (input) {
            input.value = '';
        }
        sendToApi(trimmed, { restoreMenu: false });
    }

    function setOpen(value) {
        open = value;
        if (panel) {
            panel.style.display = open ? 'flex' : 'none';
        }
        if (fab) {
            fab.setAttribute('aria-expanded', open ? 'true' : 'false');
            fab.innerHTML = open ? '&#9660;' : '&#128172;';
        }
        if (open) {
            renderMessages();
            if (!flow && quickActionsEl && !quickActionsEl.children.length) {
                showMenu('main', !menuInitialized);
                menuInitialized = true;
            }
            input?.focus();
        }
    }

    async function checkHealth() {
        try {
            const res = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            healthy = Boolean(data.enabled && data.healthy);
        } catch {
            healthy = false;
        }
        if (statusEl) {
            statusEl.textContent = healthy ? '● online' : '○ offline';
            statusEl.style.color = healthy ? '#a7f3d0' : '#fde68a';
        }
        setUiEnabled(healthy);
    }

    fab?.addEventListener('click', () => setOpen(!open));
    root.querySelector('[data-api-operator-close]')?.addEventListener('click', () => setOpen(false));
    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        handleUserSubmit(input?.value ?? '');
    });

    document.addEventListener('click', (e) => {
        if (!open || !panel || !fab) {
            return;
        }
        if (!root.contains(e.target)) {
            setOpen(false);
        }
    });

    wireQuickActions();
    renderMessages();
    setOpen(false);
    checkHealth();
}

function initApiOperatorWidgets() {
    document.querySelectorAll('[data-api-operator-widget]').forEach((root) => {
        if (root.dataset.booted === '1') {
            return;
        }
        bootApiOperatorWidget(root);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApiOperatorWidgets);
} else {
    initApiOperatorWidgets();
}

window.initApiOperatorWidgets = initApiOperatorWidgets;
