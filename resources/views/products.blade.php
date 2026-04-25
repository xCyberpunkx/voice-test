@extends('layouts.app')

@section('content')

<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .page-title { font-size: 26px; font-weight: bold; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
    input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; width: 100%; }
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
    #voice-status { margin-top: 10px; padding: 10px; border-radius: 8px; background: #f3f4f6; font-size: 14px; min-height: 36px; }
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
    #chat-container { position: fixed; bottom: 20px; right: 20px; width: 420px; height: 550px; background: white; border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.2); display: none; flex-direction: column; z-index: 1000; overflow: hidden; }
    #chat-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
    #chat-messages { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 10px; background: #fafafa; }
    .chat-message { padding: 10px 14px; border-radius: 12px; max-width: 85%; word-wrap: break-word; font-size: 14px; line-height: 1.5; }
    .chat-message.user { background: #667eea; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .chat-message.agent { background: white; color: #1f2937; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .chat-message strong { display: block; margin-bottom: 5px; font-size: 11px; opacity: 0.7; }
    #chat-input-area { padding: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; background: white; }
    #chat-input { flex: 1; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 20px; outline: none; font-size: 14px; }
    #chat-input:focus { border-color: #667eea; }
    #chat-send { background: #667eea; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
    .chat-toggle { position: fixed; bottom: 20px; right: 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 28px; cursor: pointer; box-shadow: 0 5px 20px rgba(102,126,234,0.4); z-index: 999; display: flex; align-items: center; justify-content: center; }
    .chat-typing { display: flex; gap: 4px; padding: 10px 14px; }
    .chat-typing span { width: 8px; height: 8px; background: #94a3b8; border-radius: 50%; animation: bounce 1.4s infinite; }
    .chat-typing span:nth-child(2) { animation-delay: 0.2s; }
    .chat-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-8px); } }
    @media (max-width: 768px) { .grid, .stats { grid-template-columns: 1fr; } #chat-container { width: 100%; height: 100vh; bottom: 0; right: 0; border-radius: 0; } .chat-toggle { bottom: 10px; right: 10px; } }
</style>

<div class="page-title">🧠 AI Inventory Agent</div>

<div class="stats" id="stats">
    <div class="stat-card"><div class="stat-value" id="total-products">0</div><div class="stat-label">Total Products</div></div>
    <div class="stat-card"><div class="stat-value" id="total-value">$0</div><div class="stat-label">Total Value</div></div>
    <div class="stat-card"><div class="stat-value" id="avg-price">$0</div><div class="stat-label">Avg Price</div></div>
</div>

<div class="card">
    <h3>🎤 Voice Command</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">Try: "add iPhone price 999 quantity 10" · "show products" · "delete Samsung"</p>
    <button class="btn-mic" id="mic-btn" onclick="toggleVoice()">🎤 Click to Speak</button>
    <button class="btn-clear" onclick="clearHistory()" style="margin-left:10px;">Clear</button>
    <div id="voice-status">Ready for commands...</div>
    <div class="voice-history" id="voice-history"></div>
</div>

<div class="card">
    <h3>Quick Add</h3>
    <div class="grid">
        <input id="name" placeholder="Product name">
        <input id="price" placeholder="Price" type="number" step="0.01">
        <input id="quantity" placeholder="Quantity" type="number">
    </div>
    <button class="btn-add" onclick="createProduct()">+ Add Product</button>
</div>

<div class="card">
    <h3>All Products</h3>
    <input type="text" class="search-box" id="search" placeholder="🔍 Search..." oninput="filterProducts()">
    <table>
        <thead><tr><th>Name</th><th>Price</th><th>Qty</th><th>Total</th><th>Actions</th></tr></thead>
        <tbody id="table"></tbody>
    </table>
    <div id="empty-state" style="display:none; text-align:center; padding:20px; color:#6b7280;">No products yet. Chat with the agent!</div>
</div>

<button class="chat-toggle" onclick="toggleChat()">💬</button>

<div id="chat-container">
    <div id="chat-header">
        <span>🧠 AI Agent</span>
        <button onclick="toggleChat()" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <div id="chat-messages">
        <div class="chat-message agent">
            <strong>🤖 Agent</strong>
            <div>Hello! I'm your AI inventory agent. How can I help?</div>
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
let chatHistory = [];

if (!GROQ_KEY || GROQ_KEY.includes('{{')) {
    console.error('GROQ_KEY not set in .env');
}

// ====================== HELPERS ======================
function setStatus(msg, type = '') {
    const el = document.getElementById('voice-status');
    el.textContent = msg;
    el.className = type;
}
function addToHistory(transcript, result) {
    voiceHistory.unshift({ transcript, result, time: new Date().toLocaleTimeString() });
    if (voiceHistory.length > 10) voiceHistory.pop();
    document.getElementById('voice-history').innerHTML = voiceHistory.map(h => 
        `<div class="voice-history-item">🕐 ${h.time} - "${h.transcript}" → ${h.result}</div>`
    ).join('');
}
function clearHistory() {
    voiceHistory = [];
    document.getElementById('voice-history').innerHTML = '';
    chatHistory = [];
    document.getElementById('chat-messages').innerHTML = `<div class="chat-message agent"><strong>🤖 Agent</strong><div>Conversation cleared. How can I help?</div></div>`;
}

// ====================== GROQ CALL ======================
async function callGroq(messages) {
    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
        body: JSON.stringify({
            model: 'llama-3.1-8b-instant',   // <<< CORRECT MODEL
            messages: messages,
            temperature: 0.1,
            max_tokens: 500
        })
    });
    if (!response.ok) {
        const err = await response.text();
        throw new Error(`API Error ${response.status}: ${err}`);
    }
    const data = await response.json();
    return data.choices?.[0]?.message?.content?.trim() || '';
}

function extractJson(text) {
    const m = text.match(/\{[\s\S]*\}/);
    if (m) { try { return JSON.parse(m[0]); } catch(e){} }
    return null;
}

function getInventoryContext() {
    return {
        products: allProducts.map(p => ({ name: p.name, price: parseFloat(p.price), quantity: parseInt(p.quantity) })),
        count: allProducts.length,
        totalValue: allProducts.reduce((s, p) => s + (p.price * p.quantity), 0).toFixed(2),
        avgPrice: allProducts.length > 0 ? (allProducts.reduce((s, p) => s + parseFloat(p.price), 0) / allProducts.length).toFixed(2) : '0'
    };
}

// ====================== AI AGENT ======================
async function processUserInput(userMessage) {
    const context = getInventoryContext();
    const system = `You are an AI inventory assistant. Current inventory: ${JSON.stringify(context.products)}. 
Total: ${context.count} products, Value: $${context.totalValue}, Avg Price: $${context.avgPrice}.

If user wants an action, respond with ONLY JSON:
- Add: {"action":"add","products":[{"name":"...","price":99,"quantity":5}]}
- Delete: {"action":"delete","products":["name"]}
- Rename: {"action":"rename","oldName":"old","newName":"new"}
- Update: {"action":"update","products":[{"name":"...","price":99}]}
- List: {"action":"list"}
- Stats: {"action":"stats"}
- Search: {"action":"search","query":"term"}

If information is missing, ask politely. Extract details from natural language.`;

    const messages = [
        { role: 'system', content: system },
        ...chatHistory.slice(-10),
        { role: 'user', content: userMessage }
    ];
    const reply = await callGroq(messages);
    console.log('AI:', reply);
    const action = extractJson(reply);
    if (action) return await executeAction(action);
    return reply;
}

async function executeAction(action) {
    try {
        if (action.action === 'add') {
            const results = [];
            for (const p of action.products) {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: String(p.name), price: Number(p.price), quantity: Number(p.quantity) })
                });
                results.push(res.ok ? `✅ Added "${p.name}"` : `❌ Failed "${p.name}": ${await res.text()}`);
            }
            await loadProducts();
            return results.join('\n');
        }
        if (action.action === 'delete') {
            await loadProducts();
            const results = [];
            for (const name of action.products) {
                const m = allProducts.find(p => p.name.toLowerCase().includes(name.toLowerCase()));
                if (m) { await fetch(`${API}/${m.id}`, { method: 'DELETE' }); results.push(`✅ Deleted "${m.name}"`); }
                else results.push(`❌ "${name}" not found`);
            }
            await loadProducts();
            return results.join('\n');
        }
        if (action.action === 'rename') {
            await loadProducts();
            const m = allProducts.find(p => p.name.toLowerCase().includes(action.oldName.toLowerCase()));
            if (!m) return `❌ "${action.oldName}" not found`;
            await fetch(`${API}/${m.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: String(action.newName) }) });
            await loadProducts();
            return `✅ Renamed "${m.name}" to "${action.newName}"`;
        }
        if (action.action === 'update') {
            await loadProducts();
            const results = [];
            for (const u of action.products) {
                const m = allProducts.find(p => p.name.toLowerCase().includes(u.name.toLowerCase()));
                if (!m) { results.push(`❌ "${u.name}" not found`); continue; }
                const body = {};
                if (u.price != null) body.price = Number(u.price);
                if (u.quantity != null) body.quantity = Number(u.quantity);
                await fetch(`${API}/${m.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                results.push(`✅ Updated "${m.name}"`);
            }
            await loadProducts();
            return results.join('\n');
        }
        if (action.action === 'list') { await loadProducts(); return allProducts.length ? '📋 ' + allProducts.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n') : '📭 Empty'; }
        if (action.action === 'stats') { const c = getInventoryContext(); return `📊 ${c.count} products, Value: $${c.totalValue}, Avg Price: $${c.avgPrice}`; }
        if (action.action === 'search') { await loadProducts(); const ms = allProducts.filter(p => p.name.toLowerCase().includes(action.query.toLowerCase())); return ms.length ? '🔍 ' + ms.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n') : `No match for "${action.query}"`; }
        return "❓ Unknown action";
    } catch(e) { return `❌ ${e.message}`; }
}

// ====================== CHAT ======================
let isChatProcessing = false;
function toggleChat() { const c = document.getElementById('chat-container'); c.style.display = c.style.display === 'flex' ? 'none' : 'flex'; }
async function sendChatMessage() {
    if (isChatProcessing) return;
    const input = document.getElementById('chat-input');
    const msg = input.value.trim(); if (!msg) return;
    input.value = '';
    addChatMessage('user', msg);
    chatHistory.push({ role: 'user', content: msg });
    if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);
    isChatProcessing = true;
    showTyping();
    try {
        const reply = await processUserInput(msg);
        removeTyping();
        addChatMessage('agent', reply);
        chatHistory.push({ role: 'assistant', content: reply });
    } catch(e) {
        removeTyping();
        addChatMessage('agent', `❌ ${e.message}`);
    }
    isChatProcessing = false;
}
function addChatMessage(role, text) {
    const div = document.createElement('div');
    div.className = `chat-message ${role}`;
    div.innerHTML = `<strong>${role === 'user' ? 'You' : '🤖 Agent'}</strong><div style="white-space:pre-wrap;">${text}</div>`;
    const area = document.getElementById('chat-messages');
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}
function showTyping() {
    const div = document.createElement('div'); div.className = 'chat-message agent'; div.id = 'typing';
    div.innerHTML = '<div class="chat-typing"><span></span><span></span><span></span></div>';
    document.getElementById('chat-messages').appendChild(div);
}
function removeTyping() { document.getElementById('typing')?.remove(); }
document.getElementById('chat-input').addEventListener('keypress', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); } });

// ====================== VOICE ======================
let mediaRecorder, audioChunks = [], isListening = false;
async function toggleVoice() {
    if (isListening) { mediaRecorder?.stop(); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.onstop = async () => {
            isListening = false;
            document.getElementById('mic-btn').classList.remove('listening');
            document.getElementById('mic-btn').textContent = '🎤 Click to Speak';
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            setStatus('🤖 Transcribing...', 'thinking');
            try {
                const fd = new FormData(); fd.append('file', blob, 'audio.webm'); fd.append('model', 'whisper-large-v3-turbo');
                const wRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', { method: 'POST', headers: { 'Authorization': `Bearer ${GROQ_KEY}` }, body: fd });
                const data = await wRes.json();
                const transcript = data.text;
                if (!transcript) { setStatus('❌ No speech', 'error'); return; }
                setStatus(`📝 "${transcript}"`, 'thinking');
                chatHistory.push({ role: 'user', content: transcript });
                const reply = await processUserInput(transcript);
                chatHistory.push({ role: 'assistant', content: reply });
                setStatus('✅ Done', 'success');
                addToHistory(transcript, reply);
            } catch(e) { setStatus(`❌ ${e.message}`, 'error'); }
        };
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording...';
        setStatus('🎤 Listening...', 'thinking');
    } catch(e) { setStatus('❌ Mic access denied', 'error'); }
}

// ====================== CRUD ======================
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Failed');
        allProducts = await res.json();
        renderTable(allProducts);
        updateStats();
    } catch(e) { document.getElementById("table").innerHTML = '<tr><td colspan="5">Error loading</td></tr>'; }
}
function renderTable(products) {
    const empty = document.getElementById("empty-state");
    const tbody = document.getElementById("table");
    if (!products.length) { tbody.innerHTML = ''; empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    tbody.innerHTML = products.map(p => {
        const safeName = JSON.stringify(p.name);
        return `<tr><td><strong>${p.name}</strong></td><td>$${parseFloat(p.price).toFixed(2)}</td><td>${p.quantity}</td><td>$${(p.price * p.quantity).toFixed(2)}</td><td><button class="btn-edit" onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button><button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button></td></tr>`;
    }).join('');
}
function filterProducts() { const q = document.getElementById('search').value.toLowerCase(); renderTable(q ? allProducts.filter(p => p.name.toLowerCase().includes(q) || p.price.toString().includes(q)) : allProducts); }
function updateStats() {
    document.getElementById('total-products').textContent = allProducts.length;
    const val = allProducts.reduce((s, p) => s + (p.price * p.quantity), 0);
    document.getElementById('total-value').textContent = '$' + val.toFixed(2);
    const avg = allProducts.length ? allProducts.reduce((s, p) => s + Number(p.price), 0) / allProducts.length : 0;
    document.getElementById('avg-price').textContent = '$' + avg.toFixed(2);
}
async function createProduct() {
    const name = document.getElementById("name").value.trim();
    const price = parseFloat(document.getElementById("price").value);
    const quantity = parseInt(document.getElementById("quantity").value, 10);
    if (!name || isNaN(price) || isNaN(quantity)) return alert('Fill all fields correctly.');
    await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, price, quantity }) });
    document.getElementById("name").value = '';
    document.getElementById("price").value = '';
    document.getElementById("quantity").value = '';
    await loadProducts();
}
async function deleteProduct(id) { if (!confirm('Delete?')) return; await fetch(`${API}/${id}`, { method: 'DELETE' }); await loadProducts(); }
async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt('Name:', nameVal); if (!newName?.trim()) return;
    const newPrice = prompt('Price:', priceVal); if (newPrice === null) return;
    const newQty = prompt('Quantity:', qtyVal); if (newQty === null) return;
    const priceNum = parseFloat(newPrice), qtyNum = parseInt(newQty);
    if (isNaN(priceNum) || isNaN(qtyNum)) return alert('Invalid number');
    await fetch(`${API}/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum }) });
    await loadProducts();
}
loadProducts();
</script>
@endsection