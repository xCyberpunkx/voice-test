@extends('layouts.app')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; }

    .page-title { font-size: 26px; font-weight: 700; margin-bottom: 20px; color: #111827; }
    .card { background: white; padding: 20px; border-radius: 14px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; }
    .card h3 { font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 14px; }

    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
    input[type=text], input[type=number] {
        padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
        outline: none; width: 100%; font-size: 14px; font-family: inherit;
        transition: border-color 0.2s;
    }
    input:focus { border-color: #6366f1; }

    button { padding: 10px 16px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; font-family: inherit; }
    button:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-add    { background: #6366f1; color: white; }
    .btn-edit   { background: #f59e0b; color: white; }
    .btn-delete { background: #ef4444; color: white; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 11px 14px; background: #f9fafb; border-bottom: 2px solid #e5e7eb; font-size: 13px; color: #6b7280; font-weight: 600; }
    td { padding: 12px 14px; border-top: 1px solid #f3f4f6; font-size: 14px; }
    tr:hover td { background: #fafafa; }
    .actions { display: flex; gap: 6px; }
    .search-box { width: 100%; margin-bottom: 16px; }

    /* Stats */
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    .stat-card { padding: 18px; background: white; border-radius: 14px; text-align: center; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; }
    .stat-value { font-size: 26px; font-weight: 700; color: #6366f1; }
    .stat-label { font-size: 12px; color: #9ca3af; margin-top: 4px; font-weight: 500; }

    #empty-state { display: none; text-align: center; padding: 30px; color: #9ca3af; font-size: 14px; }

    /* ── CHAT TOGGLE ── */
    .chat-toggle {
        position: fixed; bottom: 24px; right: 24px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white; border: none; border-radius: 50%;
        width: 62px; height: 62px; font-size: 26px;
        cursor: pointer; box-shadow: 0 6px 24px rgba(99,102,241,0.45);
        z-index: 999; display: flex; align-items: center; justify-content: center;
        transition: transform 0.2s;
    }
    .chat-toggle:hover { transform: scale(1.08); }

    /* ── CHAT CONTAINER ── */
    #chat-container {
        position: fixed; bottom: 100px; right: 24px;
        width: 420px; height: 580px;
        background: white; border-radius: 20px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.16);
        display: none; flex-direction: column; z-index: 1000; overflow: hidden;
        border: 1px solid rgba(99,102,241,0.12);
    }
    #chat-container.open { display: flex; animation: slideUp 0.25s ease; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    #chat-header {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white; padding: 16px 20px;
        font-weight: 700; font-size: 15px;
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0;
    }
    #chat-header .header-left { display: flex; align-items: center; gap: 10px; }
    #chat-header .lang-badge {
        background: rgba(255,255,255,0.2); border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
    }
    #chat-header button { background: none; border: none; color: white; font-size: 20px; cursor: pointer; line-height: 1; padding: 0; }

    /* Quick action chips */
    #quick-actions {
        display: flex; gap: 6px; padding: 10px 14px 0;
        overflow-x: auto; flex-shrink: 0;
        background: white;
        scrollbar-width: none;
    }
    #quick-actions::-webkit-scrollbar { display: none; }
    .quick-chip {
        white-space: nowrap; background: #f3f4f6; color: #374151;
        border: none; border-radius: 20px; padding: 6px 12px;
        font-size: 12px; font-weight: 500; cursor: pointer;
        transition: all 0.15s; flex-shrink: 0;
    }
    .quick-chip:hover { background: #e0e7ff; color: #6366f1; transform: none; }

    #chat-messages {
        flex: 1; overflow-y: auto; padding: 14px 16px;
        display: flex; flex-direction: column; gap: 10px;
        background: #f9fafb;
        scroll-behavior: smooth;
    }

    .msg {
        padding: 10px 14px; border-radius: 14px; max-width: 88%;
        font-size: 14px; line-height: 1.55; word-wrap: break-word;
        animation: msgIn 0.18s ease;
    }
    @keyframes msgIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .msg.user     { background: #6366f1; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .msg.agent    { background: white; color: #1f2937; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); white-space: pre-wrap; }
    .msg.thinking { background: #e5e7eb; color: #9ca3af; align-self: flex-start; font-style: italic; }
    .msg.error    { background: #fee2e2; color: #991b1b; align-self: flex-start; border-bottom-left-radius: 4px; }

    /* Typing dots */
    .typing-dots span {
        display: inline-block; width: 7px; height: 7px;
        background: #9ca3af; border-radius: 50%; margin: 0 2px;
        animation: dot 1.2s infinite;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes dot { 0%,80%,100% { transform: scale(0.7); opacity: 0.5; } 40% { transform: scale(1); opacity: 1; } }

    /* Input area */
    #chat-input-area {
        padding: 12px 14px; border-top: 1px solid #f0f0f0;
        display: flex; gap: 8px; align-items: center; background: white; flex-shrink: 0;
    }
    #chat-input {
        flex: 1; padding: 10px 14px; border: 1.5px solid #e5e7eb;
        border-radius: 24px; outline: none; font-size: 14px; font-family: inherit;
        transition: border-color 0.2s;
    }
    #chat-input:focus { border-color: #6366f1; }

    /* Voice button */
    #voice-btn {
        width: 40px; height: 40px; border-radius: 50%; border: none;
        background: #f3f4f6; color: #6b7280; font-size: 18px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all 0.2s; padding: 0;
    }
    #voice-btn:hover { background: #e0e7ff; color: #6366f1; transform: none; }
    #voice-btn.recording {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        animation: pulse 1.4s ease-in-out infinite;
    }
    #voice-btn.processing {
        background: #fef3c7; color: #d97706;
        animation: spin 1s linear infinite;
    }
    @keyframes pulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); transform: scale(1); }
        50%      { box-shadow: 0 0 0 10px rgba(239,68,68,0); transform: scale(1.06); }
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    #chat-send {
        background: #6366f1; color: white; border: none; border-radius: 50%;
        width: 40px; height: 40px; font-size: 17px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 0;
    }
    #chat-send:hover { transform: none; opacity: 0.9; }

    /* Voice overlay bar */
    #voice-indicator {
        display: none; padding: 8px 16px; background: #1f2937;
        flex-direction: column; gap: 4px; flex-shrink: 0;
    }
    #voice-indicator.active { display: flex; }
    #voice-status-row { display: flex; align-items: center; justify-content: space-between; }
    #voice-status-text { font-size: 12px; color: #e5e7eb; font-weight: 500; }
    #voice-lang-text   { font-size: 11px; color: #9ca3af; }
    #voice-interim {
        font-size: 13px; color: #a78bfa; font-style: italic;
        min-height: 18px; white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Waveform bars */
    #voice-wave { display: flex; align-items: center; gap: 3px; height: 20px; }
    #voice-wave span {
        display: inline-block; width: 3px; background: #6366f1;
        border-radius: 3px; animation: wave 1s ease-in-out infinite;
    }
    #voice-wave span:nth-child(1){ height:4px;  animation-delay:0s; }
    #voice-wave span:nth-child(2){ height:10px; animation-delay:0.1s; }
    #voice-wave span:nth-child(3){ height:16px; animation-delay:0.2s; }
    #voice-wave span:nth-child(4){ height:10px; animation-delay:0.3s; }
    #voice-wave span:nth-child(5){ height:6px;  animation-delay:0.4s; }
    #voice-wave span:nth-child(6){ height:14px; animation-delay:0.15s; }
    #voice-wave span:nth-child(7){ height:8px;  animation-delay:0.25s; }
    @keyframes wave {
        0%,100% { transform: scaleY(0.5); opacity: 0.6; }
        50%      { transform: scaleY(1.4); opacity: 1; }
    }

    @media (max-width: 768px) {
        .grid, .stats { grid-template-columns: 1fr; }
        #chat-container { width: calc(100vw - 32px); right: 16px; }
    }
</style>

<div class="page-title">📦 Products Dashboard</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-value" id="total-products">0</div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="total-value">$0</div>
        <div class="stat-label">Total Value</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="avg-price">$0</div>
        <div class="stat-label">Avg Price</div>
    </div>
</div>

<!-- Add Product Form -->
<div class="card">
    <h3>➕ Add Product</h3>
    <div class="grid">
        <input id="name"     placeholder="Product name">
        <input id="price"    placeholder="Price"    type="number" step="0.01">
        <input id="quantity" placeholder="Quantity" type="number">
    </div>
    <button class="btn-add" onclick="createProduct()">+ Add Product</button>
</div>

<!-- Products Table -->
<div class="card">
    <h3>📋 All Products</h3>
    <input type="text" class="search-box" id="search" placeholder="🔍 Search products..." oninput="filterProducts()">
    <table>
        <thead>
            <tr><th>Name</th><th>Price</th><th>Qty</th><th>Total Value</th><th>Actions</th></tr>
        </thead>
        <tbody id="table"></tbody>
    </table>
    <div id="empty-state">No products yet. Try asking the AI agent! 🤖</div>
</div>

<!-- Chat Toggle Button -->
<button class="chat-toggle" onclick="toggleChat()" title="Open AI Agent">🤖</button>

<!-- Chat Window -->
<div id="chat-container">
    <div id="chat-header">
        <div class="header-left">
            <span>🧠 Inventory Agent</span>
            <span class="lang-badge" id="lang-badge">EN</span>
        </div>
        <button onclick="toggleChat()" title="Close">✕</button>
    </div>

    <!-- Quick Action Chips -->
    <div id="quick-actions">
        <button class="quick-chip" onclick="quickSend('Show all products')">📋 All products</button>
        <button class="quick-chip" onclick="quickSend('What are my inventory stats?')">📊 Stats</button>
        <button class="quick-chip" onclick="quickSend('What products have low stock?')">⚠️ Low stock</button>
        <button class="quick-chip" onclick="quickSend('Find the cheapest products')">💰 Cheapest</button>
        <button class="quick-chip" onclick="quickSend('Generate an inventory report')">📋 Report</button>
        <button class="quick-chip" onclick="quickSend('Find the most expensive products')">💎 Priciest</button>
    </div>

    <div id="chat-messages">
        <div class="msg agent">Hi! I'm your inventory agent 👋

I speak <strong>English</strong> and <strong>French</strong> — just talk naturally!

Try saying:
• "Add iPhone for $999, quantity 5"
• "Ajoute un produit appelé clavier à 25€ quantité 10"
• "What's running low?"
• "Add 3 products: Apple $1.5 qty 20, Banana $0.8 qty 15, Mango $2 qty 8"</div>
    </div>

    <!-- Voice overlay -->
    <div id="voice-indicator">
        <div id="voice-status-row">
            <span id="voice-status-text">🎤 Listening…</span>
            <div id="voice-wave">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </div>
            <span id="voice-lang-text">Detecting language…</span>
        </div>
        <div id="voice-interim"></div>
    </div>

    <div id="chat-input-area">
        <button id="voice-btn" onclick="toggleVoice()" title="Hold to speak">🎤</button>
        <input id="chat-input" placeholder="Ask me anything..." />
        <button id="chat-send" onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
const API   = "/api/products";
const AGENT = "/api/agent/chat";

let allProducts  = [];
let chatHistory  = [];
let isSending    = false;
let detectedLang = 'en';
// Voice vars declared in voice engine section below

// ─── LANGUAGE DETECTION ──────────────────────────────────────────────
const FR_WORDS = [
    'ajoute','ajouter','supprime','supprimer','montre','affiche','produit',
    'prix','quantité','liste','tous','toutes','inventaire','statistiques',
    'rapport','cherche','trouve','mets','change','modifie','donne','moi',
    'quel','quelle','combien','quels','quelles','montrer','voir','avoir',
    'etre','être','faire','quel','est','sont','le','la','les','un','une',
    'des','du','et','ou','pas','pour','avec','dans','sur','que','qui'
];

function detectLanguage(text) {
    const lower = text.toLowerCase().split(/\s+/);
    const frScore = lower.filter(w => FR_WORDS.includes(w)).length;
    return frScore >= 2 ? 'fr' : 'en';
}

function updateLangBadge(lang) {
    detectedLang = lang;
    const badge = document.getElementById('lang-badge');
    badge.textContent = lang.toUpperCase();
    badge.style.background = lang === 'fr'
        ? 'rgba(250,204,21,0.35)'
        : 'rgba(255,255,255,0.2)';
}

// ─── VOICE ENGINE (MediaRecorder → Groq Whisper) ──────────────────────
/*
 * Why NOT Web Speech API:
 *   The browser's SpeechRecognition sends audio to Google's servers.
 *   In Algeria (and other regions) those servers are blocked → "network" error.
 *
 * Solution:
 *   1. MediaRecorder captures raw audio in the browser (no Google).
 *   2. On stop, we send the audio blob to our own Laravel endpoint.
 *   3. Laravel forwards it to Groq's Whisper API (same key you already have).
 *   4. Whisper auto-detects EN/FR (and even Algerian Arabic mixed speech).
 *   5. Transcript comes back → language detected → sent to agent.
 */

let mediaRecorder   = null;
let audioChunks     = [];
let isRecording     = false;
let recordingStream = null;
let silenceTimer    = null;
let elapsedTimer    = null;
let elapsedSeconds  = 0;
const MAX_SECONDS   = 30; // auto-stop after 30s

function setVoiceUI(state) {
    const btn       = document.getElementById('voice-btn');
    const indicator = document.getElementById('voice-indicator');
    const statusTxt = document.getElementById('voice-status-text');
    const langTxt   = document.getElementById('voice-lang-text');
    const interim   = document.getElementById('voice-interim');

    if (state === 'recording') {
        btn.classList.add('recording');
        btn.classList.remove('processing');
        btn.textContent = '⏹';
        indicator.classList.add('active');
        statusTxt.textContent = '🎤 Recording… click ⏹ to send';
        langTxt.textContent   = '🌐 EN / FR — Whisper detects automatically';
        interim.textContent   = '';
    } else if (state === 'processing') {
        btn.classList.remove('recording');
        btn.classList.add('processing');
        btn.textContent = '⏳';
        indicator.classList.add('active');
        statusTxt.textContent = '⚙️ Transcribing with Whisper…';
        langTxt.textContent   = 'Please wait';
        interim.textContent   = '';
    } else {
        btn.classList.remove('recording', 'processing');
        btn.textContent = '🎤';
        indicator.classList.remove('active');
        interim.textContent = '';
    }
}

function updateElapsed() {
    elapsedSeconds++;
    const s = String(elapsedSeconds).padStart(2, '0');
    const statusTxt = document.getElementById('voice-status-text');
    if (statusTxt) statusTxt.textContent = `🎤 Recording… ${s}s  (click ⏹ to send)`;
    if (elapsedSeconds >= MAX_SECONDS) stopAndTranscribe();
}

async function toggleVoice() {
    if (isRecording) {
        stopAndTranscribe();
        return;
    }

    // Request mic access
    try {
        recordingStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        addMessage('⚠️ Microphone access denied. Please allow mic access in your browser settings.', 'error');
        return;
    }

    // Pick best supported format (webm is fine for Whisper)
    const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
        ? 'audio/webm;codecs=opus'
        : MediaRecorder.isTypeSupported('audio/webm')
            ? 'audio/webm'
            : 'audio/ogg';

    audioChunks = [];
    mediaRecorder = new MediaRecorder(recordingStream, { mimeType });

    mediaRecorder.ondataavailable = (e) => {
        if (e.data && e.data.size > 0) audioChunks.push(e.data);
    };

    mediaRecorder.onstop = async () => {
        // Clean up stream
        recordingStream.getTracks().forEach(t => t.stop());
        recordingStream = null;

        if (audioChunks.length === 0) {
            setVoiceUI('idle');
            addMessage('⚠️ No audio recorded. Please try again.', 'error');
            return;
        }

        setVoiceUI('processing');

        const blob = new Blob(audioChunks, { type: mimeType });
        await transcribeWithWhisper(blob, mimeType);
    };

    mediaRecorder.start(250); // collect chunks every 250ms
    isRecording    = true;
    elapsedSeconds = 0;
    setVoiceUI('recording');

    elapsedTimer = setInterval(updateElapsed, 1000);
}

function stopAndTranscribe() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') return;
    clearInterval(elapsedTimer);
    isRecording = false;
    mediaRecorder.stop(); // triggers onstop → transcribeWithWhisper
}

async function transcribeWithWhisper(blob, mimeType) {
    try {
        const ext      = mimeType.includes('ogg') ? 'ogg' : 'webm';
        const formData = new FormData();
        formData.append('audio', blob, `voice.${ext}`);

        const res = await fetch('/api/agent/transcribe', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });

        const data = await res.json();

        if (!res.ok || data.error) {
            setVoiceUI('idle');
            addMessage(`⚠️ Transcription failed: ${data.error || 'unknown error'}`, 'error');
            return;
        }

        const transcript = (data.text || '').trim();
        if (!transcript) {
            setVoiceUI('idle');
            addMessage('⚠️ Could not hear anything. Please speak clearly and try again.', 'error');
            return;
        }

        // Whisper also returns detected language
        const detectedLangCode = data.language || 'en';
        const lang = detectedLangCode.startsWith('fr') ? 'fr' : 'en';
        updateLangBadge(lang);

        // Show transcript in interim before sending
        document.getElementById('voice-interim').textContent = `"${transcript}"`;
        document.getElementById('voice-indicator').classList.add('active');
        document.getElementById('voice-status-text').textContent = '✅ Heard — sending…';
        document.getElementById('voice-lang-text').textContent =
            lang === 'fr' ? '🇫🇷 French detected' : '🇬🇧 English detected';

        await new Promise(r => setTimeout(r, 700)); // brief moment to show transcript

        setVoiceUI('idle');
        document.getElementById('chat-input').value = transcript;
        sendMessage();

    } catch (e) {
        setVoiceUI('idle');
        addMessage('⚠️ Voice transcription error. Please try again or type your message.', 'error');
    }
}

// ─── CHAT ─────────────────────────────────────────────────────────────
function toggleChat() {
    const container = document.getElementById('chat-container');
    container.classList.toggle('open');
    if (container.classList.contains('open')) {
        document.getElementById('chat-input').focus();
    }
}

function addMessage(text, role) {
    const div = document.createElement('div');
    div.className = `msg ${role}`;

    if (role === 'thinking') {
        div.innerHTML = '<span class="typing-dots"><span></span><span></span><span></span></span>';
    } else {
        div.textContent = text;
    }

    const area = document.getElementById('chat-messages');
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
    return div;
}

function quickSend(text) {
    document.getElementById('chat-input').value = text;
    sendMessage();
}

async function sendMessage() {
    if (isSending) return;
    const input = document.getElementById('chat-input');
    const msg   = input.value.trim();
    if (!msg) return;

    input.value = '';

    // Detect language from message
    const lang = detectLanguage(msg);
    updateLangBadge(lang);

    addMessage(msg, 'user');
    const thinking = addMessage('', 'thinking');
    isSending = true;

    try {
        const res = await fetch(AGENT, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ message: msg, history: chatHistory, language: lang })
        });

        const data = await res.json();
        thinking.remove();

        if (!data.reply) {
            addMessage(lang === 'fr'
                ? "Je n'ai pas compris. Pouvez-vous reformuler?"
                : "I didn't understand that. Could you rephrase?", 'error');
        } else {
            addMessage(data.reply, 'agent');
            chatHistory = data.history;
        }

        await loadProducts();

    } catch (e) {
        thinking.remove();
        addMessage('Something went wrong. Please try again.', 'error');
    }

    isSending = false;
}

document.getElementById('chat-input').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMessage();
});

// ─── PRODUCTS ─────────────────────────────────────────────────────────
async function loadProducts() {
    try {
        const res = await fetch(API);
        allProducts = await res.json();
        renderTable(allProducts);
        updateStats();
    } catch (e) {
        document.getElementById('table').innerHTML = '<tr><td colspan="5" style="color:#ef4444">Error loading products</td></tr>';
    }
}

function renderTable(products) {
    const empty = document.getElementById('empty-state');
    const tbody = document.getElementById('table');
    if (!products.length) { tbody.innerHTML = ''; empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    tbody.innerHTML = products.map(p => {
        const safeName = JSON.stringify(p.name);
        const total    = (parseFloat(p.price) * parseInt(p.quantity)).toFixed(2);
        return `<tr>
            <td><strong>${p.name}</strong></td>
            <td>$${parseFloat(p.price).toFixed(2)}</td>
            <td>${p.quantity}</td>
            <td>$${total}</td>
            <td><div class="actions">
                <button class="btn-edit"   onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button>
                <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
            </div></td>
        </tr>`;
    }).join('');
}

function filterProducts() {
    const q = document.getElementById('search').value.toLowerCase();
    renderTable(q ? allProducts.filter(p =>
        p.name.toLowerCase().includes(q) || p.price.toString().includes(q)
    ) : allProducts);
}

function updateStats() {
    document.getElementById('total-products').textContent = allProducts.length;
    const val = allProducts.reduce((s, p) => s + parseFloat(p.price) * parseInt(p.quantity), 0);
    document.getElementById('total-value').textContent  = '$' + val.toFixed(2);
    const avg = allProducts.length ? allProducts.reduce((s, p) => s + parseFloat(p.price), 0) / allProducts.length : 0;
    document.getElementById('avg-price').textContent = '$' + avg.toFixed(2);
}

async function createProduct() {
    const name     = document.getElementById('name').value.trim();
    const price    = parseFloat(document.getElementById('price').value);
    const quantity = parseInt(document.getElementById('quantity').value, 10);
    if (!name || isNaN(price) || isNaN(quantity)) { alert('Fill all fields correctly.'); return; }
    await fetch(API, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ name, price, quantity })
    });
    document.getElementById('name').value = '';
    document.getElementById('price').value = '';
    document.getElementById('quantity').value = '';
    await loadProducts();
}

async function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    await fetch(`${API}/${id}`, { method: 'DELETE' });
    await loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName  = prompt('Name:', nameVal);     if (!newName?.trim()) return;
    const newPrice = prompt('Price:', priceVal);   if (newPrice === null) return;
    const newQty   = prompt('Quantity:', qtyVal);  if (newQty === null) return;
    const priceNum = parseFloat(newPrice), qtyNum = parseInt(newQty);
    if (isNaN(priceNum) || isNaN(qtyNum)) { alert('Invalid number.'); return; }
    await fetch(`${API}/${id}`, {
        method:  'PUT',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum })
    });
    await loadProducts();
}

loadProducts();
</script>

@endsection