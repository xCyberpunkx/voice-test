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
        width: 400px;
        height: 550px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.2);
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
    }
    #chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        background: #667eea;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .chat-message.agent {
        background: white;
        color: #1f2937;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .chat-message strong {
        display: block;
        margin-bottom: 5px;
        font-size: 11px;
        opacity: 0.7;
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
        border-color: #667eea;
    }
    #chat-send {
        background: #667eea;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    .chat-toggle:hover {
        transform: scale(1.1);
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
        .grid { grid-template-columns: 1fr; }
        .stats { grid-template-columns: 1fr; }
        #chat-container {
            width: 100%;
            height: 100vh;
            bottom: 0;
            right: 0;
            border-radius: 0;
        }
        .chat-toggle { bottom: 10px; right: 10px; }
    }
</style>

<div class="page-title">🧠 AI Inventory Agent</div>

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
    <h3>🎤 Voice Command</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
        Speak naturally: "add iPhone price 999 quantity 10" · "rename iPhone to Xiaomi" · "show products"
    </p>
    <button class="btn-mic" id="mic-btn" onclick="toggleVoice()">🎤 Click to Speak</button>
    <button class="btn-clear" onclick="clearHistory()" style="margin-left:10px;">Clear History</button>
    <div id="voice-status">Waiting for command...</div>
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
    <h3>Inventory</h3>
    <input type="text" class="search-box" id="search" placeholder="🔍 Search products..." oninput="filterProducts()">
    <table>
        <thead>
            <tr><th>Name</th><th>Price</th><th>Qty</th><th>Total</th><th>Actions</th></tr>
        </thead>
        <tbody id="table"></tbody>
    </table>
    <div id="empty-state" style="display:none; text-align:center; padding:20px; color:#6b7280;">
        No products yet. Chat with the agent to add some!
    </div>
</div>

<!-- Chat Toggle -->
<button class="chat-toggle" onclick="toggleChat()">💬</button>

<!-- Chat Window -->
<div id="chat-container">
    <div id="chat-header">
        <span>🧠 AI Agent</span>
        <button onclick="toggleChat()" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <div id="chat-messages">
        <div class="chat-message agent">
            <strong>🤖 Agent</strong>
            <div>Hello! I'm your AI inventory agent. I can think, reason, and take actions autonomously. Tell me what you need!</div>
        </div>
    </div>
    <div id="chat-input-area">
        <input id="chat-input" placeholder="Chat with your AI agent...">
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
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: String(name), price: Number(price), quantity: Number(quantity) })
                });
                if (!res.ok) throw new Error(await res.text());
                await loadProducts();
                const product = await res.json();
                return `✅ Added "${product.name}" - $${product.price}, Qty: ${product.quantity}`;
            },
            deleteProduct: async (name) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(name).toLowerCase()));
                if (!match) return `❌ Product "${name}" not found. Try "show products" to see what's available.`;
                await fetch(`${API}/${match.id}`, { method: 'DELETE' });
                await loadProducts();
                return `✅ Deleted "${match.name}"`;
            },
            renameProduct: async (oldName, newName) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(oldName).toLowerCase()));
                if (!match) return `❌ Product "${oldName}" not found.`;
                await fetch(`${API}/${match.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: String(newName) })
                });
                await loadProducts();
                return `✅ Renamed "${match.name}" to "${newName}"`;
            },
            updateProduct: async (name, price, quantity) => {
                await loadProducts();
                const match = allProducts.find(p => p.name.toLowerCase().includes(String(name).toLowerCase()));
                if (!match) return `❌ Product "${name}" not found.`;
                const body = {};
                if (price !== null && price !== undefined) body.price = Number(price);
                if (quantity !== null && quantity !== undefined) body.quantity = Number(quantity);
                await fetch(`${API}/${match.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                await loadProducts();
                return `✅ Updated "${match.name}"`;
            },
            listProducts: async () => {
                await loadProducts();
                if (allProducts.length === 0) return "📭 Inventory is empty.";
                return "📋 Current Inventory:\n" + allProducts.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n');
            },
            getStats: async () => {
                await loadProducts();
                const total = allProducts.length;
                const value = allProducts.reduce((s, p) => s + (p.price * p.quantity), 0);
                const avg = total > 0 ? allProducts.reduce((s, p) => s + Number(p.price), 0) / total : 0;
                return `📊 ${total} products | Total Value: $${value.toFixed(2)} | Avg Price: $${avg.toFixed(2)}`;
            },
            searchProducts: async (query) => {
                await loadProducts();
                const matches = allProducts.filter(p => p.name.toLowerCase().includes(String(query).toLowerCase()));
                if (matches.length === 0) return `No products matching "${query}"`;
                return `🔍 Found ${matches.length}:\n` + matches.map(p => `• ${p.name}: $${p.price} (Qty: ${p.quantity})`).join('\n');
            }
        };
        
        this.systemPrompt = `You are an autonomous AI inventory management agent with reasoning abilities. You can:

1. **Think** before acting
2. **Remember** conversation context
3. **Decide** which tool to use
4. **Ask** when unsure
5. **Execute** actions independently
6. **Suggest** helpful actions

AVAILABLE TOOLS (call with JSON):
- addProduct(name, price, quantity)
- deleteProduct(name)
- renameProduct(oldName, newName)
- updateProduct(name, price, quantity)
- listProducts()
- getStats()
- searchProducts(query)

TOOL CALL FORMAT (always use this exact format):
{"tool":"toolName","args":{"param1":"value1","param2":"value2"}}

BEHAVIOR RULES:
1. ALWAYS reason step-by-step before acting
2. If a command is incomplete, ask for missing info conversationally
3. If you need to execute an action, output a JSON tool call
4. After a tool call, respond naturally to the user
5. Remember previous messages in the conversation
6. If the user says something ambiguous, clarify
7. Be proactive and helpful
8. Handle errors gracefully
9. You can chain multiple thoughts and tool calls
10. Be concise but thorough

IMPORTANT: When you want to take action, output EXACTLY:
{"tool":"toolName","args":{"arg1Name":"arg1Value","arg2Name":"arg2Value"}}
Then I will execute it and tell you the result.

Current date/time: ${new Date().toLocaleString()}`;
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
            { role: 'system', content: this.systemPrompt + `\n\nCURRENT INVENTORY STATE:\n${JSON.stringify(context)}` },
            ...this.memory
        ];
        
        try {
            const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
                body: JSON.stringify({
                    model: 'llama3-8b-8192',
                    messages: messages,
                    temperature: 0.3,
                    max_tokens: 500
                })
            });
            
            const data = await response.json();
            const content = data.choices?.[0]?.message?.content?.trim() || "I'm not sure how to respond. Could you rephrase?";
            
            // Store response in memory
            this.memory.push({ role: 'assistant', content: content });
            
            // Extract and execute tool calls
            const toolCallMatch = content.match(/\{"tool"\s*:\s*"(\w+)"\s*,\s*"args"\s*:\s*(\{[^}]+\})\s*\}/);
            
            if (toolCallMatch) {
                const toolName = toolCallMatch[1];
                const argsStr = toolCallMatch[2];
                
                try {
                    const args = JSON.parse(argsStr);
                    const result = await this.tools[toolName](...Object.values(args));
                    this.memory.push({ role: 'system', content: `Tool result: ${result}` });
                    
                    // Get final response after tool execution
                    const finalMessages = [
                        { role: 'system', content: this.systemPrompt + `\n\nCURRENT INVENTORY:\n${JSON.stringify(context)}` },
                        ...this.memory.slice(-10),
                        { role: 'user', content: `The tool executed. Result: ${result}. Now respond to the user naturally.` }
                    ];
                    
                    const finalResponse = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${GROQ_KEY}` },
                        body: JSON.stringify({
                            model: 'llama3-8b-8192',
                            messages: finalMessages,
                            temperature: 0.7,
                            max_tokens: 300
                        })
                    });
                    
                    const finalData = await finalResponse.json();
                    const finalContent = finalData.choices?.[0]?.message?.content?.trim() || result;
                    this.memory.push({ role: 'assistant', content: finalContent });
                    return finalContent;
                    
                } catch (e) {
                    const errorMsg = `Tool execution failed: ${e.message}. Please try a different approach.`;
                    this.memory.push({ role: 'assistant', content: errorMsg });
                    return errorMsg;
                }
            }
            
            return content;
            
        } catch (err) {
            const errorMsg = "I'm having trouble thinking right now. Can you repeat that?";
            this.memory.push({ role: 'assistant', content: errorMsg });
            return errorMsg;
        }
    }
    
    reset() {
        this.memory = [];
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
    agent.reset();
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
    input.focus();
    addChatMessage('user', message);
    isChatProcessing = true;
    showChatTyping();
    
    try {
        const response = await agent.process(message);
        removeChatTyping();
        addChatMessage('agent', response);
    } catch (err) {
        removeChatTyping();
        addChatMessage('agent', `❌ ${err.message}`);
    }
    
    isChatProcessing = false;
}

function addChatMessage(role, content) {
    const messagesDiv = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = `chat-message ${role}`;
    div.innerHTML = `<strong>${role === 'user' ? 'You' : '🤖 Agent'}</strong><div style="white-space:pre-wrap;">${content}</div>`;
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showChatTyping() {
    const messagesDiv = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = 'chat-message agent';
    div.id = 'typing-indicator';
    div.innerHTML = '<div class="chat-typing"><span></span><span></span><span></span></div>';
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeChatTyping() {
    document.getElementById('typing-indicator')?.remove();
}

document.getElementById('chat-input').addEventListener('keypress', (e) => {
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
            
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            setStatus('🤖 Transcribing...', 'thinking');
            
            try {
                const formData = new FormData();
                formData.append('file', audioBlob, 'audio.webm');
                formData.append('model', 'whisper-large-v3-turbo');
                
                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });
                
                const whisperData = await whisperRes.json();
                const transcript = whisperData.text;
                
                if (!transcript) {
                    setStatus('❌ Could not transcribe', 'error');
                    return;
                }
                
                setStatus(`📝 "${transcript}" - thinking...`, 'thinking');
                
                const response = await agent.process(transcript);
                setStatus(`✅ ${response}`, 'success');
                addToHistory(transcript, response);
                
            } catch (err) {
                setStatus(`❌ ${err.message}`, 'error');
            }
        };
        
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording...';
        setStatus('🎤 Listening...', 'thinking');
        
    } catch (err) {
        setStatus(`❌ Microphone access denied`, 'error');
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
    } catch (err) {
        document.getElementById("table").innerHTML = '<tr><td colspan="5">Error loading products</td></tr>';
    }
}

function renderTable(products) {
    const tbody = document.getElementById("table");
    const emptyState = document.getElementById("empty-state");
    
    if (products.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
        tbody.innerHTML = products.map(p => {
            const safeName = JSON.stringify(p.name);
            return `<tr>
                <td><strong>${p.name}</strong></td>
                <td>$${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.quantity}</td>
                <td>$${(p.price * p.quantity).toFixed(2)}</td>
                <td>
                    <div class="actions">
                        <button class="btn-edit" onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button>
                        <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }
}

function filterProducts() {
    const query = document.getElementById('search').value.toLowerCase();
    if (!query) { renderTable(allProducts); return; }
    renderTable(allProducts.filter(p => p.name.toLowerCase().includes(query) || p.price.toString().includes(query)));
}

function updateStats() {
    document.getElementById('total-products').textContent = allProducts.length;
    const value = allProducts.reduce((s, p) => s + (p.price * p.quantity), 0);
    document.getElementById('total-value').textContent = '$' + value.toFixed(2);
    const avg = allProducts.length > 0 ? allProducts.reduce((s, p) => s + Number(p.price), 0) / allProducts.length : 0;
    document.getElementById('avg-price').textContent = '$' + avg.toFixed(2);
}

async function createProduct() {
    const name = document.getElementById("name").value.trim();
    const price = parseFloat(document.getElementById("price").value);
    const quantity = parseInt(document.getElementById("quantity").value, 10);
    if (!name || isNaN(price) || isNaN(quantity)) {
        alert('Fill all fields with valid numbers');
        return;
    }
    await fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify({ name, price, quantity })
    });
    document.getElementById("name").value = "";
    document.getElementById("price").value = "";
    document.getElementById("quantity").value = "";
    await loadProducts();
}

async function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    await fetch(`${API}/${id}`, { method: "DELETE" });
    await loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Name:", nameVal);
    if (!newName?.trim()) return;
    const newPrice = prompt("Price:", priceVal);
    if (newPrice === null) return;
    const newQty = prompt("Quantity:", qtyVal);
    if (newQty === null) return;
    const priceNum = parseFloat(newPrice);
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(priceNum) || isNaN(qtyNum)) { alert('Invalid number'); return; }
    await fetch(`${API}/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum })
    });
    await loadProducts();
}

// Start
loadProducts();
</script>

@endsection