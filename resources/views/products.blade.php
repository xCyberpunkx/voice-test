@extends('layouts.app')

@section('content')

<style>
    .page-title { font-size: 26px; font-weight: bold; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
    input, select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
    button { padding: 10px 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: all 0.2s; }
    button:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn-add { background: #2563eb; color: white; }
    .btn-edit { background: #f59e0b; color: white; margin-right: 5px; }
    .btn-delete { background: #ef4444; color: white; }
    .btn-mic { background: #7c3aed; color: white; font-size: 18px; }
    .btn-mic.listening { background: #dc2626; animation: pulse 1s infinite; }
    .btn-clear { background: #6b7280; color: white; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px; background: #f3f4f6; border-bottom: 2px solid #e5e7eb; }
    td { padding: 12px; border-top: 1px solid #eee; }
    tr:hover { background: #f9fafb; }
    .actions { display: flex; gap: 5px; }
    #voice-status { margin-top: 10px; padding: 10px; border-radius: 8px; background: #f3f4f6; font-size: 14px; min-height: 36px; transition: all 0.3s; }
    #voice-status.success { background: #d1fae5; color: #065f46; }
    #voice-status.error { background: #fee2e2; color: #991b1b; }
    #voice-status.thinking { background: #ede9fe; color: #5b21b6; }
    .voice-history { margin-top: 10px; max-height: 150px; overflow-y: auto; font-size: 12px; }
    .voice-history-item { padding: 5px; border-bottom: 1px solid #e5e7eb; }
    .search-box { width: 100%; margin-bottom: 15px; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
    .stat-card { padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center; }
    .stat-value { font-size: 24px; font-weight: bold; color: #2563eb; }
    .stat-label { font-size: 12px; color: #6b7280; margin-top: 5px; }
    
    /* Chatbot Styles */
    #chat-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 380px;
        height: 520px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.2);
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
    }
    #chat-header {
        background: #2563eb;
        color: white;
        padding: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #fafafa;
    }
    .chat-message {
        padding: 10px 14px;
        border-radius: 12px;
        max-width: 85%;
        word-wrap: break-word;
        font-size: 14px;
        line-height: 1.5;
    }
    .chat-message.user {
        background: #2563eb;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .chat-message.assistant {
        background: white;
        color: #1f2937;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .chat-message strong {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        opacity: 0.8;
    }
    #chat-input-area {
        padding: 15px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 10px;
        background: white;
    }
    #chat-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        outline: none;
        font-size: 14px;
    }
    #chat-input:focus {
        border-color: #2563eb;
    }
    #chat-send {
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chat-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(37,99,235,0.4);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    .chat-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(37,99,235,0.5);
    }
    .chat-typing {
        display: flex;
        gap: 4px;
        padding: 10px 14px;
    }
    .chat-typing span {
        width: 8px;
        height: 8px;
        background: #94a3b8;
        border-radius: 50%;
        animation: bounce 1.4s infinite;
    }
    .chat-typing span:nth-child(2) { animation-delay: 0.2s; }
    .chat-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-8px); }
    }
    @media (max-width: 768px) {
        #chat-container {
            width: 100%;
            height: 100vh;
            bottom: 0;
            right: 0;
            border-radius: 0;
        }
        .chat-toggle {
            bottom: 10px;
            right: 10px;
            width: 55px;
            height: 55px;
        }
    }
</style>

<div class="page-title">📦 Multilingual Voice Dashboard ⚡🌍</div>

<!-- Stats -->
<div class="stats" id="stats">
    <div class="stat-card">
        <div class="stat-value" id="total-products">0</div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="total-value">$0</div>
        <div class="stat-label">Total Inventory Value</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="avg-price">$0</div>
        <div class="stat-label">Average Price</div>
    </div>
</div>

<div class="card">
    <h3>🎤 Voice Command (English & French)</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
        English: "add iPhone price 500 quantity 10" · "rename iPhone to Xiaomi" · "delete Samsung"<br>
        French: "ajoutez iPhone prix 500 quantité 10" · "renommez Samsung en Galaxy" · "supprimer iPhone"
    </p>
    <button class="btn-mic" id="mic-btn" onclick="toggleVoice()">🎤 Click to Speak</button>
    <button class="btn-clear" onclick="clearHistory()" style="margin-left:10px;">Clear History</button>
    <div id="voice-status">Waiting for command...</div>
    <div class="voice-history" id="voice-history"></div>
</div>

<div class="card">
    <h3>Add Product Manually</h3>
    <div class="grid">
        <input id="name" placeholder="Product name">
        <input id="price" placeholder="Price" type="number" step="0.01">
        <input id="quantity" placeholder="Quantity" type="number">
    </div>
    <button class="btn-add" onclick="createProduct()">+ Add Product</button>
</div>

<div class="card">
    <h3>All Products</h3>
    <input type="text" class="search-box" id="search" placeholder="🔍 Search products..." oninput="filterProducts()">
    <table>
        <thead>
            <tr><th>Name</th><th>Price</th><th>Qty</th><th>Total</th><th>Actions</th></tr>
        </thead>
        <tbody id="table"></tbody>
    </table>
    <div id="empty-state" style="display:none; text-align:center; padding:20px; color:#6b7280;">
        No products yet. Add one using voice, chat, or the form above!
    </div>
</div>

<!-- Chatbot Toggle Button -->
<button class="chat-toggle" onclick="toggleChat()">💬</button>

<!-- Chatbot Container -->
<div id="chat-container">
    <div id="chat-header">
        <span>🤖 Inventory Assistant</span>
        <button onclick="toggleChat()" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <div id="chat-messages">
        <div class="chat-message assistant">
            <strong>🤖 Assistant</strong>
            <div>Hello! 👋 I'm your inventory assistant. You can ask me to:<br>
            • Add products: "add iPhone price 999 quantity 10"<br>
            • Delete products: "delete iPhone"<br>
            • Rename products: "rename iPhone to Xiaomi"<br>
            • Update prices: "change iPhone price to 899"<br>
            • Show inventory: "show all products"<br><br>
            How can I help you today?</div>
        </div>
    </div>
    <div id="chat-input-area">
        <input id="chat-input" placeholder="Type your message...">
        <button id="chat-send" onclick="sendChatMessage()">➤</button>
    </div>
</div>

<script>
const API = "/api/products";
const GROQ_KEY = "{{ env('GROQ_KEY') }}";
let allProducts = [];
let voiceHistory = [];
let chatMessages = [];
let isChatOpen = false;
let isChatProcessing = false;

// ---------- Helpers ----------
function setStatus(msg, type = '') {
    const el = document.getElementById('voice-status');
    el.textContent = msg;
    el.className = type;
}

function addToHistory(transcript, result) {
    voiceHistory.unshift({ transcript, result, time: new Date().toLocaleTimeString() });
    if (voiceHistory.length > 10) voiceHistory.pop();
    renderHistory();
}

function renderHistory() {
    const el = document.getElementById('voice-history');
    el.innerHTML = voiceHistory.map(h => 
        `<div class="voice-history-item">🕐 ${h.time} - "${h.transcript}" → ${h.result}</div>`
    ).join('');
}

function clearHistory() {
    voiceHistory = [];
    renderHistory();
}

// ---------- Enhanced fast parser (English + French) ----------
function fastParse(text) {
    const t = text.toLowerCase().trim();
    const hasWord = (words) => words.some(w => t.includes(w));
    const hasAnyExcluding = (words, excluding) => hasWord(words) && !hasWord(excluding);

    // 1. List command
    if (hasAnyExcluding(
        ['show', 'list', 'all', 'display', 'products', 'items', 'inventory', 'tous', 'affiche', 'montre', 'produits', 'articles', 'stock', 'liste', 'affichez'],
        ['delete', 'remove', 'add', 'create', 'update', 'change', 'set', 'rename', 'edit', 'ajoute', 'ajouter', 'ajoutez', 'supprimer', 'renommer', 'modifier']
    )) {
        return { action: "list", name: null, price: null, quantity: null };
    }

    // 2. Delete command
    let match = t.match(/\b(delete|remove|eliminate|erase|clear|destroy|supprimer|enlever|effacer|supprimez|enlevez)\s+(\w+)/);
    if (match) return { action: "delete", name: match[2], price: null, quantity: null };
    match = t.match(/(supprimer|enlever|effacer|supprimez|enlevez)\s+(?:le |la |l'|les )?(\w+)/);
    if (match) return { action: "delete", name: match[2], price: null, quantity: null };

    // 3. Create / Add command
    match = t.match(/\b(add|create|new|insert|put|ajouter|ajoutez|ajoute|créer|créez|nouveau|nouvel|nouvelle|insérer|insérez|mettre)\s+(\w+).*?\b(price|prix|cost|coût|montant|valeur)\s*(\d+(?:\.\d+)?).*?\b(quantity|qty|count|stock|units|quantité|qté|nombre|unités)\s*(\d+)/);
    if (match) {
        return { action: "create", name: match[2], price: parseFloat(match[4]), quantity: parseInt(match[6], 10) };
    }
    match = t.match(/(ajouter|ajoutez|ajoute|créer|créez|insérer|insérez|mettre|nouveau)\s+(\w+).*?\b(prix|coût|montant|valeur)\s*(\d+(?:\.\d+)?).*?\b(quantité|qté|nombre|unités)\s*(\d+)/);
    if (match) {
        return { action: "create", name: match[2], price: parseFloat(match[4]), quantity: parseInt(match[6], 10) };
    }

    // 4. Rename command
    match = t.match(/\b(rename|change\s+(the\s+)?name\s+(of|for)|edit\s+(the\s+)?name\s+(of|for)|update\s+(the\s+)?name\s+(of|for)|renommer|renommez|changer\s+(?:le\s+)?nom\s+(?:de|d'|du)\s?|modifier\s+(?:le\s+)?nom\s+(?:de|d'|du)\s?)\s+(\w+)\s+(?:to|into|as|->|with|en|à|vers|par)\s+(\w+)/);
    if (match) {
        const groups = match[0].match(/(\w+)\s+(?:to|into|as|->|with|en|à|vers|par)\s+(\w+)$/);
        if (groups) return { action: "rename", name: groups[1], newName: groups[2] };
        const parts = match[0].split(/\s+(?:to|into|as|->|with|en|à|vers|par)\s+/);
        if (parts.length === 2) {
            return { action: "rename", name: parts[0].split(/\s+/).pop(), newName: parts[1] };
        }
    }
    match = t.match(/(rename|renommer|renommez|changer\s+nom\s?|modifier\s+nom\s?)\s+(\w+)\s+(?:to|into|en|à|vers|par|->)\s+(\w+)/);
    if (match) return { action: "rename", name: match[2], newName: match[3] };

    // 5. Update price
    match = t.match(/\b(update|change|set|modify|adjust|modifier|modifiez|changer|changez|mettre\s+à\s+jour|mettre\s+le\s+prix)\s+(\w+).*?\b(price|prix|cost|coût|montant|valeur)\s+(?:to\s+|à\s+|de\s+)?(\d+(?:\.\d+)?)/);
    if (match) return { action: "update", name: match[2], price: parseFloat(match[4]), quantity: null };

    // 6. Update quantity
    match = t.match(/\b(update|change|set|modify|adjust|modifier|modifiez|changer|changez)\s+(\w+).*?\b(quantity|qty|count|stock|units|quantité|qté|nombre|unités)\s+(?:to\s+|à\s+)?(\d+)/);
    if (match) return { action: "update", name: match[2], price: null, quantity: parseInt(match[4], 10) };

    return null;
}

// ---------- Translate to English ----------
async function translateToEnglish(text, sourceLang) {
    if (!sourceLang || sourceLang === 'en' || sourceLang === 'english') return text;
    if (sourceLang === 'fr' || sourceLang === 'french') return text;
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
            body: JSON.stringify({
                model: 'llama3-8b-8192',
                messages: [
                    { role: 'system', content: `Translate the following ${sourceLang} text into English. Output ONLY the English translation, nothing else. Keep product names as-is.` },
                    { role: 'user', content: text }
                ],
                temperature: 0, max_tokens: 100
            })
        });
        const data = await response.json();
        return data.choices?.[0]?.message?.content?.trim() || text;
    } catch (e) { return text; }
}

// ---------- AI fallback parser ----------
async function aiParse(englishText) {
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
            body: JSON.stringify({
                model: 'llama3-8b-8192',
                messages: [{ role: 'system', content: `You are a product management assistant. Parse voice commands into JSON. Return ONLY raw JSON.

Format: { "action": "create|update|delete|list|rename", "name": "string or null", "newName": "string or null", "price": number or null, "quantity": number or null }

Rules:
- "rename": "name" = current product name, "newName" = new name
- "create": include name, price, quantity
- "update": include name and fields to change
- "delete": include only name
- "list": set everything to null
- Convert written numbers to digits: "five hundred" -> 500, "cinq cents" -> 500

Examples (English):
"add iPhone price 500 quantity 10" -> {"action":"create","name":"iPhone","price":500,"quantity":10}
"delete Samsung" -> {"action":"delete","name":"Samsung","price":null,"quantity":null}
"show all products" -> {"action":"list","name":null,"price":null,"quantity":null}
"rename iPhone to Xiaomi" -> {"action":"rename","name":"iPhone","newName":"Xiaomi","price":null,"quantity":null}
"change name of Samsung to Galaxy" -> {"action":"rename","name":"Samsung","newName":"Galaxy"}
"update Samsung price to 600" -> {"action":"update","name":"Samsung","price":600,"quantity":null}
"change Samsung quantity to 20" -> {"action":"update","name":"Samsung","price":null,"quantity":20}

Examples (French):
"ajoutez iPhone prix 500 quantité 10" -> {"action":"create","name":"iPhone","price":500,"quantity":10}
"supprimer Samsung" -> {"action":"delete","name":"Samsung"}
"afficher tous les produits" -> {"action":"list"}
"renommer Samsung en Galaxy" -> {"action":"rename","name":"Samsung","newName":"Galaxy"}
"changer le nom de iPhone en Xiaomi" -> {"action":"rename","name":"iPhone","newName":"Xiaomi"}
"modifier le prix de Samsung à 600 euros" -> {"action":"update","name":"Samsung","price":600,"quantity":null}
"mettre à jour la quantité de iPhone à 25" -> {"action":"update","name":"iPhone","price":null,"quantity":25}` },
                { role: 'user', content: englishText }],
                temperature: 0, max_tokens: 150
            })
        });
        const data = await response.json();
        if (!data.choices?.[0]) throw new Error('No AI response');
        let raw = data.choices[0].message.content.trim();
        raw = raw.replace(/^```[\s\S]*?\n/, '').replace(/\n```$/, '').replace(/^`+|`+$/g, '').trim();
        const jsonMatch = raw.match(/\{[\s\S]*\}/);
        if (jsonMatch) raw = jsonMatch[0];
        return JSON.parse(raw);
    } catch (err) { console.error('AI parse error:', err); return null; }
}

// ---------- Voice recording ----------
let mediaRecorder, audioChunks = [], isListening = false;

async function toggleVoice() {
    if (isListening) { mediaRecorder.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.onstop = async () => {
            isListening = false;
            document.getElementById('mic-btn').classList.remove('listening');
            document.getElementById('mic-btn').textContent = '🎤 Click to Speak';
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            setStatus('🤖 Transcribing...', 'thinking');
            try {
                const formData = new FormData();
                formData.append('file', audioBlob, 'audio.webm');
                formData.append('model', 'whisper-large-v3-turbo');
                formData.append('response_format', 'verbose_json');
                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST', headers: { 'Authorization': `Bearer ${GROQ_KEY}` }, body: formData
                });
                const whisperData = await whisperRes.json();
                const transcript = whisperData.text;
                const lang = whisperData.language || 'en';
                const langName = whisperData.language_name || lang;
                if (!transcript) { setStatus('❌ Could not transcribe', 'error'); return; }
                setStatus(`📝 Heard (${langName}): "${transcript}"`, 'thinking');
                let textForParsing = transcript;
                if (lang !== 'en' && lang !== 'fr') textForParsing = await translateToEnglish(transcript, lang);
                let command = fastParse(textForParsing);
                if (command) {
                    setStatus(`⚡ Executing: ${JSON.stringify(command)}`, 'thinking');
                } else {
                    setStatus('🧠 Using AI...', 'thinking');
                    const englishForAI = (lang === 'en') ? transcript : (lang === 'fr') ? await translateToEnglish(transcript, 'fr') : textForParsing;
                    command = await aiParse(englishForAI);
                    if (!command) { setStatus('❌ Could not understand', 'error'); addToHistory(transcript, 'Failed'); return; }
                    setStatus(`🤖 AI parsed: ${JSON.stringify(command)}`, 'thinking');
                }
                const success = await executeAction(command);
                addToHistory(transcript, success ? `✓ ${command.action}: ${command.name || ''} ${command.newName ? '→ ' + command.newName : ''}` : '✗ Failed');
            } catch (err) { setStatus(`❌ Error: ${err.message}`, 'error'); }
        };
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording...';
        setStatus('🎤 Recording...', 'thinking');
    } catch (err) { setStatus(`❌ Mic access denied: ${err.message}`, 'error'); }
}

// ---------- Chatbot Functions ----------
function toggleChat() {
    isChatOpen = !isChatOpen;
    document.getElementById('chat-container').style.display = isChatOpen ? 'flex' : 'none';
    if (isChatOpen) document.getElementById('chat-toggle').style.display = 'none';
    else document.getElementById('chat-toggle')?.style?.removeProperty?.('display');
}

async function sendChatMessage() {
    if (isChatProcessing) return;
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    addChatMessage('user', message);
    
    // Try to parse as command first
    const command = fastParse(message) || await aiParse(message);
    if (command && command.action !== 'list') {
        const success = await executeAction(command);
        if (success) {
            addChatMessage('assistant', `✅ Executed: ${command.action === 'create' ? `Added "${command.name}" ($${command.price}, Qty: ${command.quantity})` : command.action === 'rename' ? `Renamed "${command.name}" to "${command.newName}"` : command.action === 'delete' ? `Deleted "${command.name}"` : `Updated "${command.name}"`}`);
        } else {
            addChatMessage('assistant', '❌ I understood the command but execution failed. Check the error above.');
        }
    } else {
        // General conversation with context
        await chatReply(message);
    }
}

async function chatReply(message) {
    isChatProcessing = true;
    showChatTyping();
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
            body: JSON.stringify({
                model: 'llama3-8b-8192',
                messages: [
                    { role: 'system', content: `You are a helpful inventory assistant. Current inventory: ${JSON.stringify(allProducts.map(p => ({ name: p.name, price: p.price, quantity: p.quantity })))}. Total products: ${allProducts.length}, Total value: $${allProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0).toFixed(2)}. Be concise and helpful. You can execute commands when users ask.` },
                    ...chatMessages.slice(-6).map(m => ({ role: m.role === 'user' ? 'user' : 'assistant', content: m.content.split('<strong>')[1] ? m.content.split('</strong>')[1].trim() : m.content })),
                    { role: 'user', content: message }
                ],
                temperature: 0.7, max_tokens: 300
            })
        });
        const data = await response.json();
        removeChatTyping();
        const reply = data.choices?.[0]?.message?.content || 'Sorry, try again.';
        addChatMessage('assistant', reply);
    } catch (err) {
        removeChatTyping();
        addChatMessage('assistant', '❌ Error connecting to AI.');
    }
    isChatProcessing = false;
}

function addChatMessage(role, content) {
    chatMessages.push({ role, content });
    renderChat();
}

function showChatTyping() {
    const messagesDiv = document.getElementById('chat-messages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message assistant';
    typingDiv.id = 'chat-typing';
    typingDiv.innerHTML = '<div class="chat-typing"><span></span><span></span><span></span></div>';
    messagesDiv.appendChild(typingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeChatTyping() {
    const typingDiv = document.getElementById('chat-typing');
    if (typingDiv) typingDiv.remove();
}

function renderChat() {
    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML = chatMessages.map(msg => `
        <div class="chat-message ${msg.role}">
            <strong>${msg.role === 'user' ? 'You' : '🤖 Assistant'}</strong>
            <div>${msg.content}</div>
        </div>
    `).join('');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

document.getElementById('chat-input').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendChatMessage();
});

// ---------- Execute the parsed action ----------
async function executeAction(cmd) {
    try {
        if (cmd.action === 'list') { await loadProducts(); setStatus('✅ Products loaded', 'success'); return true; }
        if (cmd.action === 'create') {
            if (!cmd.name || !cmd.name.trim()) { setStatus('❌ Missing product name.', 'error'); return false; }
            if (cmd.price == null || isNaN(cmd.price) || cmd.price < 0) { setStatus('❌ Missing or invalid price.', 'error'); return false; }
            if (cmd.quantity == null || isNaN(cmd.quantity) || cmd.quantity < 0) { setStatus('❌ Missing or invalid quantity.', 'error'); return false; }
            const res = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name: cmd.name.trim(), price: Number(cmd.price), quantity: Number(cmd.quantity) }) });
            if (!res.ok) { setStatus(`❌ Create failed: ${await res.text()}`, 'error'); return false; }
            setStatus(`✅ Created "${cmd.name}"`, 'success'); await loadProducts(); return true;
        }
        if (cmd.action === 'delete') {
            if (!cmd.name) { setStatus('❌ Which product?', 'error'); return false; }
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) { setStatus(`❌ "${cmd.name}" not found`, 'error'); return false; }
            await fetch(`${API}/${match.id}`, { method: 'DELETE' });
            setStatus(`✅ Deleted "${match.name}"`, 'success'); await loadProducts(); return true;
        }
        if (cmd.action === 'rename') {
            if (!cmd.name || !cmd.newName) { setStatus('❌ Need old and new names', 'error'); return false; }
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) { setStatus(`❌ "${cmd.name}" not found`, 'error'); return false; }
            const res = await fetch(`${API}/${match.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ name: cmd.newName.trim() }) });
            if (!res.ok) { setStatus(`❌ Rename failed: ${await res.text()}`, 'error'); return false; }
            setStatus(`✅ Renamed to "${cmd.newName}"`, 'success'); await loadProducts(); return true;
        }
        if (cmd.action === 'update') {
            if (!cmd.name) { setStatus('❌ Which product?', 'error'); return false; }
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) { setStatus(`❌ "${cmd.name}" not found`, 'error'); return false; }
            const updateBody = {};
            if (cmd.name) updateBody.name = cmd.name.trim();
            if (cmd.price != null && !isNaN(cmd.price)) updateBody.price = Number(cmd.price);
            if (cmd.quantity != null && !isNaN(cmd.quantity)) updateBody.quantity = Number(cmd.quantity);
            if (Object.keys(updateBody).length === 0) { setStatus('❌ Nothing to update', 'error'); return false; }
            const res = await fetch(`${API}/${match.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(updateBody) });
            if (!res.ok) { setStatus(`❌ Update failed: ${await res.text()}`, 'error'); return false; }
            setStatus(`✅ Updated "${match.name}"`, 'success'); await loadProducts(); return true;
        }
        setStatus(`❓ Unknown action: ${cmd.action}`, 'error'); return false;
    } catch (err) { setStatus(`❌ Error: ${err.message}`, 'error'); return false; }
}

function findProduct(name) {
    let match = allProducts.find(p => p.name.toLowerCase() === name.toLowerCase());
    if (!match) match = allProducts.find(p => p.name.toLowerCase().includes(name.toLowerCase()));
    return match;
}

// ---------- CRUD Operations ----------
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Failed to load');
        allProducts = await res.json();
        renderTable(allProducts);
        updateStats();
    } catch (err) { document.getElementById("table").innerHTML = '<tr><td colspan="4">Error loading products</td></tr>'; }
}

function renderTable(products) {
    const tbody = document.getElementById("table");
    const emptyState = document.getElementById("empty-state");
    if (products.length === 0) { tbody.innerHTML = ''; emptyState.style.display = 'block'; }
    else {
        emptyState.style.display = 'none';
        tbody.innerHTML = products.map(p => {
            const safeName = JSON.stringify(p.name);
            const total = (p.price * p.quantity).toFixed(2);
            return `<tr>
                <td><strong>${p.name}</strong></td>
                <td>$${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.quantity}</td>
                <td>$${total}</td>
                <td><div class="actions">
                    <button class="btn-edit" onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button>
                    <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                </div></td>
            </tr>`;
        }).join('');
    }
}

function filterProducts() {
    const query = document.getElementById('search').value.toLowerCase();
    renderTable(allProducts.filter(p => p.name.toLowerCase().includes(query) || p.price.toString().includes(query)));
}

function updateStats() {
    document.getElementById('total-products').textContent = allProducts.length;
    const totalValue = allProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);
    document.getElementById('total-value').textContent = '$' + totalValue.toFixed(2);
    const avgPrice = allProducts.length > 0 ? allProducts.reduce((sum, p) => sum + parseFloat(p.price), 0) / allProducts.length : 0;
    document.getElementById('avg-price').textContent = '$' + avgPrice.toFixed(2);
}

async function createProduct() {
    const name = document.getElementById("name").value.trim();
    const price = parseFloat(document.getElementById("price").value);
    const quantity = parseInt(document.getElementById("quantity").value, 10);
    if (!name || isNaN(price) || isNaN(quantity)) { alert('Please fill in all fields with valid numbers'); return; }
    await fetch(API, { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify({ name, price, quantity }) });
    document.getElementById("name").value = "";
    document.getElementById("price").value = "";
    document.getElementById("quantity").value = "";
    await loadProducts();
}

async function deleteProduct(id) {
    if (!confirm('Are you sure?')) return;
    await fetch(`${API}/${id}`, { method: "DELETE" });
    await loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Name:", nameVal);
    if (newName === null || newName.trim() === '') return;
    const newPrice = prompt("Price:", priceVal);
    if (newPrice === null) return;
    const newQty = prompt("Quantity:", qtyVal);
    if (newQty === null) return;
    const priceNum = parseFloat(newPrice);
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(priceNum) || isNaN(qtyNum)) { alert('Invalid number'); return; }
    await fetch(`${API}/${id}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum }) });
    await loadProducts();
}

// Initial load
loadProducts();
</script>

@endsection