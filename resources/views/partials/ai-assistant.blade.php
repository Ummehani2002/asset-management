@auth
<div id="ai-assistant-root" class="ai-assistant no-print"
     data-status-url="{{ route('ai.status') }}"
     data-chat-url="{{ route('ai.chat') }}"
     data-csrf="{{ csrf_token() }}">
    <button type="button" class="ai-assistant-toggle" id="ai-assistant-toggle" aria-label="Open AI Assistant" title="AI Assistant">
        <i class="bi bi-robot"></i>
    </button>

    <div class="ai-assistant-panel" id="ai-assistant-panel" hidden>
        <div class="ai-assistant-header">
            <div>
                <strong>AI Assistant</strong>
                <div class="ai-assistant-sub">Help + inventory questions</div>
            </div>
            <button type="button" class="ai-assistant-close" id="ai-assistant-close" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="ai-assistant-messages" id="ai-assistant-messages"></div>
        <form class="ai-assistant-form" id="ai-assistant-form">
            <input type="text" id="ai-assistant-input" maxlength="2000" placeholder="Ask how to use the system or about asset counts..." autocomplete="off">
            <button type="submit" id="ai-assistant-send" class="btn btn-sm btn-primary">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
        <div class="ai-assistant-footer" id="ai-assistant-footer"></div>
    </div>
</div>

<style>
.ai-assistant {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1050;
    font-family: 'Inter', 'Roboto', sans-serif;
}
.ai-assistant-toggle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: var(--primary, #1F2A44);
    color: #fff;
    box-shadow: 0 8px 24px rgba(31, 42, 68, 0.35);
    font-size: 22px;
    cursor: pointer;
}
.ai-assistant-toggle:hover {
    background: var(--hover, #2C3E66);
}
.ai-assistant-panel {
    position: absolute;
    right: 0;
    bottom: 70px;
    width: min(380px, calc(100vw - 32px));
    height: min(520px, calc(100vh - 120px));
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(0,0,0,0.18);
    border: 1px solid var(--border-light, #E5E7EB);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ai-assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: var(--primary, #1F2A44);
    color: #fff;
}
.ai-assistant-sub {
    font-size: 12px;
    opacity: 0.85;
    margin-top: 2px;
}
.ai-assistant-close {
    border: none;
    background: transparent;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    line-height: 1;
}
.ai-assistant-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    background: #F7F8FA;
}
.ai-msg {
    max-width: 92%;
    margin-bottom: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.45;
    white-space: pre-wrap;
    word-break: break-word;
}
.ai-msg-user {
    margin-left: auto;
    background: var(--primary, #1F2A44);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ai-msg-assistant {
    margin-right: auto;
    background: #fff;
    border: 1px solid #E5E7EB;
    color: #1F2A44;
    border-bottom-left-radius: 4px;
}
.ai-msg-system {
    margin: 0 auto 10px;
    background: #fff8e8;
    border: 1px solid #f0d9a8;
    color: #6b5420;
    font-size: 12px;
    text-align: center;
}
.ai-assistant-form {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid #E5E7EB;
    background: #fff;
}
.ai-assistant-form input {
    flex: 1;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}
.ai-assistant-form input:focus {
    outline: none;
    border-color: var(--secondary, #C6A87D);
    box-shadow: 0 0 0 2px rgba(198, 168, 125, 0.25);
}
.ai-assistant-footer {
    padding: 0 12px 10px;
    font-size: 11px;
    color: #6c757d;
}
.ai-assistant-panel[hidden] {
    display: none !important;
}
</style>

<script>
(function () {
    var root = document.getElementById('ai-assistant-root');
    if (!root) return;

    var toggle = document.getElementById('ai-assistant-toggle');
    var panel = document.getElementById('ai-assistant-panel');
    var closeBtn = document.getElementById('ai-assistant-close');
    var form = document.getElementById('ai-assistant-form');
    var input = document.getElementById('ai-assistant-input');
    var sendBtn = document.getElementById('ai-assistant-send');
    var messagesEl = document.getElementById('ai-assistant-messages');
    var footer = document.getElementById('ai-assistant-footer');
    var history = [];
    var enabled = false;
    var busy = false;

    function addMessage(role, content) {
        var div = document.createElement('div');
        div.className = 'ai-msg ai-msg-' + role;
        div.textContent = content;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setBusy(state) {
        busy = state;
        sendBtn.disabled = state || !enabled;
        input.disabled = state || !enabled;
    }

    function openPanel() {
        panel.hidden = false;
        if (enabled) input.focus();
    }

    function closePanel() {
        panel.hidden = true;
    }

    toggle.addEventListener('click', function () {
        if (panel.hidden) openPanel();
        else closePanel();
    });
    closeBtn.addEventListener('click', closePanel);

    fetch(root.dataset.statusUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (data) {
        enabled = !!data.enabled;
        if (!enabled) {
            addMessage('system', data.message || 'AI Assistant is not configured.');
            footer.textContent = 'Configure OPENAI_API_KEY to enable.';
            setBusy(false);
            sendBtn.disabled = true;
            input.disabled = true;
        } else {
            addMessage('assistant', 'Hi! Ask me how to use Asset Management, or ask inventory questions like “How many available laptops?”');
            footer.textContent = 'Read-only answers. Admins get more detailed lookups.';
            setBusy(false);
        }
    }).catch(function () {
        enabled = false;
        addMessage('system', 'Could not reach AI Assistant status endpoint.');
        sendBtn.disabled = true;
        input.disabled = true;
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!enabled || busy) return;
        var text = (input.value || '').trim();
        if (!text) return;

        addMessage('user', text);
        history.push({ role: 'user', content: text });
        input.value = '';
        setBusy(true);
        addMessage('assistant', 'Thinking…');
        var thinking = messagesEl.lastChild;

        fetch(root.dataset.chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': root.dataset.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: text,
                history: history.slice(0, -1)
            })
        }).then(function (r) {
            return r.json().then(function (data) {
                return { ok: r.ok, data: data };
            });
        }).then(function (result) {
            var reply = (result.data && result.data.reply) ? result.data.reply : 'No response received.';
            if (thinking && thinking.parentNode) thinking.remove();
            addMessage('assistant', reply);
            history.push({ role: 'assistant', content: reply });
            if (history.length > 12) history = history.slice(-12);
        }).catch(function () {
            if (thinking && thinking.parentNode) thinking.remove();
            addMessage('assistant', 'Network error. Please try again.');
        }).finally(function () {
            setBusy(false);
        });
    });
})();
</script>
@endauth
