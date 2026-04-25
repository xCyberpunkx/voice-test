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

<div class="stats">
    <div class="stat-card"><div class="stat-value" id="total-products">0</div><div class="stat-label">Total Products</div></div>
    <div class="stat-card"><div class="stat-value" id="total-value">$0</div><div class="stat-label">Total Value</div></div>
    <div class="stat-card"><div class="stat-value" id="avg-price">$0</div><div class="stat-label">Avg Price</div></div>
</div>

<div class="card">
    <h3>🎤 Voice Command</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">Try: "add iPhone price 999 quantity 10" · "show products" · "delete Samsung" · "find cheap products"</p>
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

// ====================== HELPER FUNCTIONS ======================
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
    document.getElementById('chat-messages').innerHTML = `
        <div class="chat-message agent">
            <strong>🤖 Agent</strong>
            <div>Conversation cleared. How can I help?</div>
        </div>`;
}

// ====================== CORE FUNCTIONS ======================
async function callGroq(messages, temperature = 0.3, maxTokens = 500) {
    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${GROQ_KEY}`
        },
        body: JSON.stringify({
            model: 'llama-8b-instant', // Fast, reliable model
            messages: messages,
            temperature: temperature,
            max_tokens: maxTokens
        })
    });
    
    if (!response.ok) {
        const errorText = await response.text();
        console.error('Groq API Error:', errorText);
        throw new Error(`API error: ${response.status}`);
    }
    
    const data = await response.json();
    return data.choices?.[0]?.message?.content?.trim() || '';
}

function extractJson(text) {
    // Try to find JSON in the text
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (jsonMatch) {
        try {
            return JSON.parse(jsonMatch[0]);
        } catch (e) {
            return null;
        }
    }
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
    
    const systemPrompt = `You are an AI inventory management assistant. You help users manage their product inventory.

CURRENT INVENTORY:
${JSON.stringify(context.products, null, 2)}
Total: ${context.count} products | Value: $${context.totalValue} | Avg Price: $${context.avgPrice}

YOUR CAPABILITIES:
- Add products (single or multiple)
- Delete products (single or multiple)
- Rename products
- Update prices and quantities
- List/search products
- Provide statistics

IMPORTANT RULES:
1. If the user wants to perform an action, respond with ONLY a JSON object (no other text) in this format:
   For adding: {"action":"add","products":[{"name":"Product Name","price":99.99,"quantity":10}]}
   For deleting: {"action":"delete","products":["Product Name"]}
   For renaming: {"action":"rename","oldName":"Old Name","newName":"New Name"}
   For updating: {"action":"update","products":[{"name":"Product Name","price":99.99,"quantity":10}]}
   For listing: {"action":"list"}
   For stats: {"action":"stats"}
   For searching: {"action":"search","query":"search term"}

2. If the user says just "add product" without details, DO NOT return JSON. Instead, ask them for the name, price, and quantity.

3. If the user provides complete information (name + price + quantity), immediately return the JSON action.

4. Extract product names, prices, and quantities from natural language. For example:
   "add iPhone price 999 quantity 10" -> name:iPhone, price:999, quantity:10
   "Samsung $799 x15" -> name:Samsung, price:799, quantity:15
   "Ajoutez iPhone prix 500 quantité 10" -> name:iPhone, price:500, quantity:10 (French)

5. Always use numbers (not strings) for price and quantity.

6. Be conversational and helpful when asking for clarification.

7. If they say things like "show products" or "list inventory", return {"action":"list"}

EXAMPLES:
User: "add iPhone price 999 quantity 10"
You: {"action":"add","products":[{"name":"iPhone","price":999,"quantity":10}]}

User: "delete iPhone and Samsung"
You: {"action":"delete","products":["iPhone","Samsung"]}

User: "show products"
You: {"action":"list"}

User: "add product"
You: I'd be happy to add a product! Could you tell me the name, price, and quantity? For example: "add iPhone price 999 quantity 10"

User: "what's in stock?"
You: {"action":"list"}

User: "rename iPhone to Xiaomi"
You: {"action":"rename","oldName":"iPhone","newName":"Xiaomi"}

User: "update iPhone price to 899"
You: {"action":"update","products":[{"name":"iPhone","price":899}]}`;

    const messages = [
        { role: 'system', content: systemPrompt },
        ...chatHistory.slice(-10),
        { role: 'user', content: userMessage }
    ];

    try {
        const response = await callGroq(messages, 0.1, 400);
        console.log('AI Response:', response);
        
        // Try to parse as JSON action
        const action = extractJson(response);
        
        if (action) {
            // Execute the action
            const result = await executeAction(action);
            return result;
        }
        
        // If no JSON, return the text response
        return response;
        
    } catch (error) {
        console.error('Agent Error:', error);
        return "I'm sorry, I encountered an error. Please try again.";
    }
}

async function executeAction(action) {
    try {
        switch (action.action) {
            case 'add':
                if (!action.products || !Array.isArray(action.products)) {
                    return "❌ Invalid add request. Please specify products to add.";
                }
                const addResults = [];
                for (const product of action.products) {
                    if (!product.name || product.price == null || product.quantity == null) {
                        addResults.push(`❌ Missing info for a product. Need name, price, and quantity.`);
                        continue;
                    }
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            name: String(product.name),
                            price: Number(product.price),
                            quantity: Number(product.quantity)
                        })
                    });
                    if (res.ok) {
                        addResults.push(`✅ Added "${product.name}" - $${product.price} (Qty: ${product.quantity})`);
                    } else {
                        const err = await res.text();
                        addResults.push(`❌ Failed to add "${product.name}": ${err}`);
                    }
                }
                await loadProducts();
                return addResults.join('\n');
                
            case 'delete':
                if (!action.products || !Array.isArray(action.products)) {
                    return "❌ Invalid delete request.";
                }
                await loadProducts();
                const deleteResults = [];
                for (const name of action.products) {
                    const match = allProducts.find(p => p.name.toLowerCase().includes(name.toLowerCase()));
                    if (match) {
                        await fetch(`${API}/${match.id}`, { method: 'DELETE' });
                        deleteResults.push(`✅ Deleted "${match.name}"`);
                    } else {
                        deleteResults.push(`❌ "${name}" not found`);
                    }
                }
                await loadProducts();
                return deleteResults.join('\n');
                
            case 'rename':
                if (!action.oldName || !action.newName) {
                    return "❌ Please specify both old and new names.";
                }
                await loadProducts();
                const renameMatch = allProducts.find(p => p.name.toLowerCase().includes(action.oldName.toLowerCase()));
                if (!renameMatch) return `❌ "${action.oldName}" not found`;
                await fetch(`${API}/${renameMatch.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: String(action.newName) })
                });
                await loadProducts();
                return `✅ Renamed "${renameMatch.name}" to "${action.newName}"`;
                
            case 'update':
                if (!action.products || !Array.isArray(action.products)) {
                    return "❌ Invalid update request.";
                }
                await loadProducts();
                const updateResults = [];
                for (const update of action.products) {
                    const match = allProducts.find(p => p.name.toLowerCase().includes(update.name.toLowerCase()));
                    if (!match) {
                        updateResults.push(`❌ "${update.name}" not found`);
                        continue;
                    }
                    const body = {};
                    if (update.price != null) body.price = Number(update.price);
                    if (update.quantity != null) body.quantity = Number(update.quantity);
                    if (Object.keys(body).length === 0) {
                        updateResults.push(`❌ No updates specified for "${update.name}"`);
                        continue;
                    }
                    const res = await fetch(`${API}/${match.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    });
                    if (res.ok) {
                        updateResults.push(`✅ Updated "${match.name}"`);
                    } else {
                        updateResults.push(`❌ Failed to update "${match.name}"`);
                    }
                }
                await loadProducts();
                return updateResults.join('\n');
                
            case 'list':
                await loadProducts();
                if (allProducts.length === 0) return "📭 Your inventory is empty.";
                return "📋 Current Inventory:\n" + allProducts.map(p => 
                    `• ${p.name}: $${parseFloat(p.price).toFixed(2)} (Qty: ${p.quantity})`
                ).join('\n');
                
            case 'stats':
                await loadProducts();
                const ctx = getInventoryContext();
                return `📊 Stats: ${ctx.count} products | Total Value: $${ctx.totalValue} | Avg Price: $${ctx.avgPrice}`;
                
            case 'search':
                if (!action.query) return "❌ What would you like to search for?";
                await loadProducts();
                const matches = allProducts.filter(p => p.name.toLowerCase().includes(action.query.toLowerCase()));
                if (matches.length === 0) return `🔍 No products matching "${action.query}"`;
                return `🔍 Found ${matches.length}:\n` + matches.map(p => 
                    `• ${p.name}: $${parseFloat(p.price).toFixed(2)} (Qty: ${p.quantity})`
                ).join('\n');
                
            default:
                return "❓ I'm not sure what action to take. Can you rephrase?";
        }
    } catch (error) {
        console.error('Execute Error:', error);
        return `❌ Error: ${error.message}`;
    }
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
    addChatMessage('user', message);
    chatHistory.push({ role: 'user', content: message });
    if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);
    
    isChatProcessing = true;
    showTyping();
    
    try {
        const reply = await processUserInput(message);
        removeTyping();
        addChatMessage('agent', reply);
        chatHistory.push({ role: 'assistant', content: reply });
    } catch (error) {
        removeTyping();
        addChatMessage('agent', '❌ Sorry, something went wrong.');
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
                const data = await whisperRes.json();
                const transcript = data.text;
                if (!transcript) { setStatus('❌ No speech detected', 'error'); return; }
                setStatus(`📝 "${transcript}" - processing...`, 'thinking');
                
                chatHistory.push({ role: 'user', content: transcript });
                const reply = await processUserInput(transcript);
                chatHistory.push({ role: 'assistant', content: reply });
                
                setStatus(`✅ ${reply}`, 'success');
                addToHistory(transcript, reply);
            } catch (e) {
                setStatus(`❌ ${e.message}`, 'error');
            }
        };
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording...';
        setStatus('🎤 Listening...', 'thinking');
    } catch (e) {
        setStatus('❌ Microphone access denied', 'error');
    }
}

// ====================== CRUD ======================
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Failed to load');
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
            const safeName = JSON.stringify(p.name);
            return `<tr>
                <td><strong>${p.name}</strong></td>
                <td>$${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.quantity}</td>
                <td>$${(p.price * p.quantity).toFixed(2)}</td>
                <td>
                    <button class="btn-edit" onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button>
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