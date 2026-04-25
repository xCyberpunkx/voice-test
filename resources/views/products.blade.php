@extends('layouts.app')

@section('content')

<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .page-title { font-size: 26px; font-weight: bold; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
    input, select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; width: 100%; }
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
    #voice-status { margin-top: 10px; padding: 10px; border-radius: 8px; background: #f3f4f6; font-size: 14px; min-height: 36px; transition: background 0.3s; }
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

    /* Chat */
    #chat-container {
        position: fixed; bottom: 20px; right: 20px; width: 420px; height: 550px;
        background: white; border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.2);
        display: none; flex-direction: column; z-index: 1000; overflow: hidden;
    }
    #chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;
    }
    #chat-messages { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 10px; background: #fafafa; }
    .chat-message {
        padding: 10px 14px; border-radius: 12px; max-width: 85%; word-wrap: break-word; font-size: 14px; line-height: 1.5;
    }
    .chat-message.user { background: #667eea; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .chat-message.agent { background: white; color: #1f2937; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .chat-message strong { display: block; margin-bottom: 5px; font-size: 11px; opacity: 0.7; }
    #chat-input-area { padding: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; background: white; }
    #chat-input { flex: 1; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 20px; outline: none; font-size: 14px; }
    #chat-input:focus { border-color: #667eea; }
    #chat-send { background: #667eea; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
    .chat-toggle {
        position: fixed; bottom: 20px; right: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 28px; cursor: pointer;
        box-shadow: 0 5px 20px rgba(102,126,234,0.4); z-index: 999; display: flex; align-items: center; justify-content: center;
    }
    .chat-typing { display: flex; gap: 4px; padding: 10px 14px; }
    .chat-typing span { width: 8px; height: 8px; background: #94a3b8; border-radius: 50%; animation: bounce 1.4s infinite; }
    .chat-typing span:nth-child(2) { animation-delay: 0.2s; }
    .chat-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-8px); } }
    @media (max-width: 768px) {
        .grid, .stats { grid-template-columns: 1fr; }
        #chat-container { width: 100%; height: 100vh; bottom: 0; right: 0; border-radius: 0; }
        .chat-toggle { bottom: 10px; right: 10px; }
    }
</style>

<div class="page-title">🧠 AI Inventory Agent</div>

<div class="stats" id="stats">
    <div class="stat-card"><div class="stat-value" id="total-products">0</div><div class="stat-label">Total Products</div></div>
    <div class="stat-card"><div class="stat-value" id="total-value">$0</div><div class="stat-label">Total Value</div></div>
    <div class="stat-card"><div class="stat-value" id="avg-price">$0</div><div class="stat-label">Avg Price</div></div>
</div>

<div class="card">
    <h3>🎤 Voice Command</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">Speak naturally: "add iPhone price 999 quantity 10", "show products", etc.</p>
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
    <div id="empty-state" style="display:none; text-align:center; padding:20px; color:#6b7280;">No products yet. Talk to the agent!</div>
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
            <div>Hello! I'm your AI inventory agent. You can ask me things like:
            • "Add iPhone $999 qty 10 and Samsung $799 qty 15"
            • "Delete all products under $100"
            • "What's my most expensive product?"
            I'm here to help!</div>
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

// ====================== AI AGENT ======================
class InventoryAgent {
    constructor() {
        this.memory = [];
        this.tools = {
            addProduct: async (name, price, quantity) => {
                const res = await fetch(API, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: String(name), price: Number(price), quantity: Number(quantity) })
                });
                if (!res.ok) throw new Error(await res.text());
                await loadProducts();
                return `✅ Added "${name}" - $${price}, Qty: ${quantity}`;
            },
            addMultipleProducts: async (products) => {
                const results = [];
                for (const p of products) {
                    try {
                        const res = await fetch(API, {
                            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ name: String(p.name), price: Number(p.price), quantity: Number(p.quantity) })
                        });
                        if (res.ok) results.push(`✅ ${p.name}: $${p.price} (Qty: ${p.quantity})`);
                        else results.push(`❌ ${p.name}: Failed`);
                    } catch (e) { results.push(`❌ ${p.name}: ${e.message}`); }
                }
                await loadProducts();
                return results.join('\n');
            },
            deleteProduct: async (name) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(name).toLowerCase()));
                if (!match) return `❌ "${name}" not found`;
                await fetch(`${API}/${match.id}`, { method: 'DELETE' });
                await loadProducts();
                return `✅ Deleted "${match.name}"`;
            },
            deleteMultipleProducts: async (names) => {
                await loadProducts();
                const results = [];
                for (const name of names) {
                    const match = allProducts.find(p => p.name.toLowerCase().includes(String(name).toLowerCase()));
                    if (match) {
                        await fetch(`${API}/${match.id}`, { method: 'DELETE' });
                        results.push(`✅ Deleted "${match.name}"`);
                    } else results.push(`❌ "${name}" not found`);
                }
                await loadProducts();
                return results.join('\n');
            },
            renameProduct: async (oldName, newName) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(oldName).toLowerCase()));
                if (!match) return `❌ "${oldName}" not found`;
                await fetch(`${API}/${match.id}`, {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: String(newName) })
                });
                await loadProducts();
                return `✅ Renamed "${oldName}" to "${newName}"`;
            },
            updateProduct: async (name, price, quantity) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(name).toLowerCase()));
                if (!match) return `❌ "${name}" not found`;
                const body = {};
                if (price !== null && price !== undefined && price !== 'null') body.price = Number(price);
                if (quantity !== null && quantity !== undefined && quantity !== 'null') body.quantity = Number(quantity);
                if (!Object.keys(body).length) return `❌ Nothing to update for "${name}"`;
                await fetch(`${API}/${match.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                await loadProducts();
                return `✅ Updated "${match.name}"`;
            },
            updateMultipleProducts: async (updates) => {
                await loadProducts();
                const results = [];
                for (const u of updates) {
                    const match = allProducts.find(p => p.name.toLowerCase().includes(String(u.name).toLowerCase()));
                    if (match) {
                        const body = {};
                        if (u.price && u.price !== 'null') body.price = Number(u.price);
                        if (u.quantity && u.quantity !== 'null') body.quantity = Number(u.quantity);
                        if (Object.keys(body).length) {
                            await fetch(`${API}/${match.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                            results.push(`✅ Updated "${match.name}"`);
                        } else results.push(`❌ No valid updates for "${u.name}"`);
                    } else results.push(`❌ "${u.name}" not found`);
                }
                await loadProducts();
                return results.join('\n');
            },
            listProducts: async () => {
                await loadProducts();
                if (!allProducts.length) return "Inventory is empty.";
                return allProducts.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n');
            },
            getStats: async () => {
                await loadProducts();
                const total = allProducts.length;
                const value = allProducts.reduce((s, p) => s + (p.price * p.quantity), 0);
                const avg = total > 0 ? allProducts.reduce((s, p) => s + Number(p.price), 0) / total : 0;
                return `${total} products | Total Value: $${value.toFixed(2)} | Avg Price: $${avg.toFixed(2)}`;
            },
            searchProducts: async (query) => {
                await loadProducts();
                const matches = allProducts.filter(p => p.name.toLowerCase().includes(String(query).toLowerCase()));
                if (!matches.length) return `No matches for "${query}"`;
                return matches.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n');
            }
        };

        this.systemPrompt = `You are an autonomous AI inventory agent. You can think, reason, and use tools.

AVAILABLE TOOLS (use JSON format):
- addProduct(name, price, quantity)
- addMultipleProducts([{name, price, quantity}, ...])
- deleteProduct(name)
- deleteMultipleProducts([name1, name2, ...])
- renameProduct(oldName, newName)
- updateProduct(name, price, quantity)
- updateMultipleProducts([{name, price, quantity}, ...])
- listProducts()
- getStats()
- searchProducts(query)

TOOL CALL FORMAT: {"tool":"toolName","args":{"param1":"value1","param2":"value2"}}
For batch: {"tool":"addMultipleProducts","args":{"products":[{"name":"iPhone","price":999,"quantity":10},{"name":"Samsung","price":799,"quantity":15}]}}

RULES:
1. Think step-by-step before replying.
2. When you need to act, output a valid JSON tool call (or multiple, one per line).
3. Ask for clarification if something is missing.
4. Be helpful, concise, and proactive.`;
    }

    async process(userInput) {
        this.memory.push({ role: 'user', content: userInput });
        if (this.memory.length > 30) this.memory = this.memory.slice(-30);

        const context = {
            products: allProducts.map(p => ({ name: p.name, price: p.price, qty: p.quantity })),
            count: allProducts.length,
            value: allProducts.reduce((s, p) => s + (p.price * p.quantity), 0).toFixed(2)
        };

        const messages = [
            { role: 'system', content: this.systemPrompt + '\n\nCURRENT INVENTORY:\n' + JSON.stringify(context) },
            ...this.memory
        ];

        try {
            const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
                body: JSON.stringify({
                    model: 'llama3-8b-8192',
                    messages,
                    temperature: 0.3,
                    max_tokens: 800
                })
            });

            const data = await response.json();
            let content = data.choices?.[0]?.message?.content?.trim() || "I didn’t catch that. Can you rephrase?";

            this.memory.push({ role: 'assistant', content: content });

            // Extract tool calls
            const toolCalls = [];
            const regex = /\{"tool"\s*:\s*"(\w+)"\s*,\s*"args"\s*:\s*(\{[^}]+\})\s*\}/g;
            let m;
            while ((m = regex.exec(content)) !== null) {
                toolCalls.push({ tool: m[1], argsStr: m[2] });
            }

            if (toolCalls.length > 0) {
                const results = [];
                for (const call of toolCalls) {
                    try {
                        const args = JSON.parse(call.argsStr);
                        const result = await this.tools[call.tool](...Object.values(args));
                        results.push(result);
                    } catch (e) {
                        results.push(`❌ Error: ${e.message}`);
                    }
                }
                const combined = results.join('\n');
                this.memory.push({ role: 'system', content: `Tool results:\n${combined}` });

                // Final natural reply
                const finalMessages = [
                    ...messages.slice(-10),
                    { role: 'user', content: `Results from tools:\n${combined}\nNow respond naturally.` }
                ];
                try {
                    const finalRes = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
                        body: JSON.stringify({ model: 'llama3-8b-8192', messages: finalMessages, temperature: 0.7, max_tokens: 300 })
                    });
                    content = finalRes.json().then(d => d.choices?.[0]?.message?.content?.trim()) || combined;
                } catch {}
                return combined;
            }

            return content;

        } catch (e) {
            const err = "⚠️ I’m having trouble connecting. Please try again.";
            this.memory.push({ role: 'assistant', content: err });
            return err;
        }
    }
}

const agent = new InventoryAgent();

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
    agent.memory = [];
}

// ====================== CHAT ======================
let isChatProcessing = false;

function toggleChat() {
    const container = document.getElementById('chat-container');
    container.style.display = container.style.display === 'flex' ? 'none' : 'flex';
}

async function sendChatMessage() {
    if (isChatProcessing) return;
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    appendMessage('user', message);
    isChatProcessing = true;
    showTyping();

    try {
        const reply = await agent.process(message);
        removeTyping();
        appendMessage('agent', reply);
    } catch (e) {
        removeTyping();
        appendMessage('agent', '❌ ' + e.message);
    }
    isChatProcessing = false;
}

function appendMessage(role, text) {
    const div = document.createElement('div');
    div.className = `chat-message ${role}`;
    div.innerHTML = `<strong>${role === 'user' ? 'You' : '🤖 Agent'}</strong><div style="white-space:pre-wrap;">${text}</div>`;
    const area = document.getElementById('chat-messages');
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}

function showTyping() {
    const div = document.createElement('div');
    div.className = 'chat-message agent';
    div.id = 'typing';
    div.innerHTML = '<div class="chat-typing"><span></span><span></span><span></span></div>';
    const area = document.getElementById('chat-messages');
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}

function removeTyping() {
    document.getElementById('typing')?.remove();
}

document.getElementById('chat-input').addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
});

// ====================== VOICE ======================
let mediaRecorder, audioChunks = [], isListening = false;

async function toggleVoice() {
    if (isListening) {
        mediaRecorder?.stop();
        return;
    }
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
                const formData = new FormData();
                formData.append('file', blob, 'audio.webm');
                formData.append('model', 'whisper-large-v3-turbo');
                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });
                const { text } = await whisperRes.json();
                if (!text) { setStatus('❌ No speech detected', 'error'); return; }
                setStatus(`📝 Heard: "${text}" - Agent is thinking...`, 'thinking');
                const reply = await agent.process(text);
                setStatus(`✅ ${reply}`, 'success');
                addToHistory(text, reply);
            } catch (e) { setStatus(`❌ ${e.message}`, 'error'); }
        };
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording...';
        setStatus('🎤 Listening...', 'thinking');
    } catch (e) { setStatus('❌ Microphone access denied', 'error'); }
}

// ====================== CRUD ======================
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Failed');
        allProducts = await res.json();
        renderTable(allProducts);
        updateStats();
    } catch (e) {
        document.getElementById("table").innerHTML = '<tr><td colspan="5">Error loading products</td></tr>';
    }
}

function renderTable(products) {
    const tbody = document.getElementById("table");
    const empty = document.getElementById("empty-state");
    if (!products.length) {
        tbody.innerHTML = '';
        empty.style.display = 'block';
    } else {
        empty.style.display = 'none';
        tbody.innerHTML = products.map(p => {
            const nameEsc = JSON.stringify(p.name);
            return `<tr>
                <td><strong>${p.name}</strong></td>
                <td>$${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.quantity}</td>
                <td>$${(p.price * p.quantity).toFixed(2)}</td>
                <td>
                    <button class="btn-edit" onclick="editProduct(${p.id}, ${nameEsc}, ${p.price}, ${p.quantity})">Edit</button>
                    <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                </td>
            </tr>`;
        }).join('');
    }
}

function filterProducts() {
    const q = document.getElementById('search').value.toLowerCase();
    renderTable(q ? allProducts.filter(p => p.name.toLowerCase().includes(q) || p.price.toString().includes(q)) : allProducts);
}

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
    await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name, price, quantity })
    });
    document.getElementById("name").value = '';
    document.getElementById("price").value = '';
    document.getElementById("quantity").value = '';
    await loadProducts();
}

async function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    await fetch(`${API}/${id}`, { method: 'DELETE' });
    await loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt('Product name:', nameVal);
    if (newName === null || !newName.trim()) return;
    const newPrice = prompt('Price:', priceVal);
    if (newPrice === null) return;
    const newQty = prompt('Quantity:', qtyVal);
    if (newQty === null) return;
    const priceNum = parseFloat(newPrice);
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(priceNum) || isNaN(qtyNum)) return alert('Invalid number');
    await fetch(`${API}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum })
    });
    await loadProducts();
}

// Initial load
loadProducts();
</script>

@endsection