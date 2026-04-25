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
        No products yet. Add one using voice or the form above!
    </div>
</div>

<script>
const API = "/api/products";
const GROQ_KEY = "{{ env('GROQ_KEY') }}";
let allProducts = [];
let voiceHistory = [];

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

    // Helper to check if any word from a list exists in the text
    const hasWord = (words) => words.some(w => t.includes(w));
    const hasAnyExcluding = (words, excluding) => hasWord(words) && !hasWord(excluding);

    // 1. List command (English & French)
    if (hasAnyExcluding(
        ['show', 'list', 'all', 'display', 'products', 'items', 'inventory', 'tous', 'affiche', 'montre', 'produits', 'articles', 'stock', 'liste', 'affichez'],
        ['delete', 'remove', 'add', 'create', 'update', 'change', 'set', 'rename', 'edit', 'ajoute', 'ajouter', 'ajoutez', 'supprimer', 'renommer', 'modifier']
    )) {
        return { action: "list", name: null, price: null, quantity: null };
    }

    // 2. Delete command
    // English
    let match = t.match(/\b(delete|remove|eliminate|erase|clear|destroy|supprimer|enlever|effacer|supprimez|enlevez)\s+(\w+)/);
    if (match) {
        return { action: "delete", name: match[2], price: null, quantity: null };
    }
    // French "supprimer le produit iPhone" -> capture "iPhone"
    match = t.match(/(supprimer|enlever|effacer|supprimez|enlevez)\s+(?:le |la |l'|les )?(\w+)/);
    if (match) return { action: "delete", name: match[2], price: null, quantity: null };

    // 3. Create / Add command
    // English pattern
    match = t.match(/\b(add|create|new|insert|put|ajouter|ajoutez|ajoute|créer|créez|nouveau|nouvel|nouvelle|insérer|insérez|mettre)\s+(\w+).*?\b(price|prix|cost|coût|montant|valeur)\s*(\d+(?:\.\d+)?).*?\b(quantity|qty|count|stock|units|quantité|qté|nombre|unités)\s*(\d+)/);
    if (match) {
        return {
            action: "create",
            name: match[2],
            price: parseFloat(match[4]),
            quantity: parseInt(match[6], 10)
        };
    }
    // French flexible pattern: "ajoutez iPhone prix 500 quantité 10"
    match = t.match(/(ajouter|ajoutez|ajoute|créer|créez|insérer|insérez|mettre|nouveau)\s+(\w+).*?\b(prix|coût|montant|valeur)\s*(\d+(?:\.\d+)?).*?\b(quantité|qté|nombre|unités)\s*(\d+)/);
    if (match) {
        return {
            action: "create",
            name: match[2],
            price: parseFloat(match[4]),
            quantity: parseInt(match[6], 10)
        };
    }

    // 4. Rename command
    // English: "rename iPhone to Xiaomi", "edit the name of iPhone to Xiaomi", "change name of iPhone to Xiaomi"
    match = t.match(/\b(rename|change\s+(the\s+)?name\s+(of|for)|edit\s+(the\s+)?name\s+(of|for)|update\s+(the\s+)?name\s+(of|for)|renommer|renommez|changer\s+(?:le\s+)?nom\s+(?:de|d'|du)\s?|modifier\s+(?:le\s+)?nom\s+(?:de|d'|du)\s?)\s+(\w+)\s+(?:to|into|as|->|with|en|à|vers|par)\s+(\w+)/);
    if (match) {
        // The name and newName are the last two captures
        const groups = match[0].match(/(\w+)\s+(?:to|into|as|->|with|en|à|vers|par)\s+(\w+)$/);
        if (groups) {
            return { action: "rename", name: groups[1], newName: groups[2] };
        }
        // alternative extraction
        const parts = match[0].split(/\s+(?:to|into|as|->|with|en|à|vers|par)\s+/);
        if (parts.length === 2) {
            const oldName = parts[0].split(/\s+/).pop();
            const newName = parts[1];
            return { action: "rename", name: oldName, newName: newName };
        }
    }
    // simpler "renommer Samsung en Galaxy", "rename Samsung to Galaxy"
    match = t.match(/(rename|renommer|renommez|changer\s+nom\s?|modifier\s+nom\s?)\s+(\w+)\s+(?:to|into|en|à|vers|par|->)\s+(\w+)/);
    if (match) {
        return { action: "rename", name: match[2], newName: match[3] };
    }

    // 5. Update price command
    match = t.match(/\b(update|change|set|modify|adjust|modifier|modifiez|changer|changez|mettre\s+à\s+jour|mettre\s+le\s+prix)\s+(\w+).*?\b(price|prix|cost|coût|montant|valeur)\s+(?:to\s+|à\s+|de\s+)?(\d+(?:\.\d+)?)/);
    if (match) {
        return {
            action: "update",
            name: match[2],
            price: parseFloat(match[4]),
            quantity: null
        };
    }

    // 6. Update quantity command
    match = t.match(/\b(update|change|set|modify|adjust|modifier|modifiez|changer|changez)\s+(\w+).*?\b(quantity|qty|count|stock|units|quantité|qté|nombre|unités)\s+(?:to\s+|à\s+)?(\d+)/);
    if (match) {
        return {
            action: "update",
            name: match[2],
            price: null,
            quantity: parseInt(match[4], 10)
        };
    }

    return null; // No match, fallback to AI
}

// ---------- Translate to English (used only for non-English/French) ----------
async function translateToEnglish(text, sourceLang) {
    if (!sourceLang || sourceLang === 'en' || sourceLang === 'english') return text;
    // French is already handled by fastParse, no need to translate
    if (sourceLang === 'fr' || sourceLang === 'french') return text;
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_KEY}`
            },
            body: JSON.stringify({
                model: 'llama3-8b-8192',
                messages: [
                    { role: 'system', content: `Translate the following ${sourceLang} text into English. Output ONLY the English translation, nothing else. Keep product names as-is.` },
                    { role: 'user', content: text }
                ],
                temperature: 0,
                max_tokens: 100
            })
        });
        const data = await response.json();
        if (data.choices && data.choices[0]) {
            return data.choices[0].message.content.trim();
        }
        return text;
    } catch (e) {
        console.error('Translation error:', e);
        return text;
    }
}

// ---------- AI fallback parser (strong prompt with many examples) ----------
async function aiParse(englishText) {
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_KEY}`
            },
            body: JSON.stringify({
                model: 'llama3-8b-8192',
                messages: [
                    { role: 'system', content: `You are a product management assistant. Parse voice commands into a JSON action. Return ONLY raw JSON (no markdown, no explanation).

Format: { "action": "create|update|delete|list|rename", "name": "string or null", "newName": "string or null", "price": number or null, "quantity": number or null }

Rules:
- "rename": provide "name" (current product name) and "newName"
- "create": include name, price, quantity
- "update": include name and the fields to change (price, quantity, or both)
- "delete": include only name
- "list": set everything to null
- Convert written numbers to digits: e.g., "five hundred" -> 500, "cinq cents" -> 500
- Do not wrap JSON in backticks.

Examples (English):
"add iPhone price 500 quantity 10" -> {"action":"create","name":"iPhone","price":500,"quantity":10}
"delete Samsung" -> {"action":"delete","name":"Samsung","price":null,"quantity":null}
"show all products" -> {"action":"list","name":null,"price":null,"quantity":null}
"rename iPhone to Xiaomi" -> {"action":"rename","name":"iPhone","newName":"Xiaomi","price":null,"quantity":null}
"change name of Samsung to Galaxy" -> {"action":"rename","name":"Samsung","newName":"Galaxy"}
"edit the name of iPhone to Xiaomi" -> {"action":"rename","name":"iPhone","newName":"Xiaomi"}
"update Samsung price to 600" -> {"action":"update","name":"Samsung","price":600,"quantity":null}
"change Samsung quantity to 20" -> {"action":"update","name":"Samsung","price":null,"quantity":20}
"could you please add a new product called Pixel price 699 quantity 8" -> {"action":"create","name":"Pixel","price":699,"quantity":8}
"I want to see what products we have" -> {"action":"list"}
"put a MacBook into the system for 1299 and 5 in stock" -> {"action":"create","name":"MacBook","price":1299,"quantity":5}
"remove the iPad" -> {"action":"delete","name":"iPad"}
"modify Galaxy price 750" -> {"action":"update","name":"Galaxy","price":750,"quantity":null}

Examples (French):
"ajoutez iPhone prix 500 quantité 10" -> {"action":"create","name":"iPhone","price":500,"quantity":10}
"ajouter iPhone prix 500 quantité 10" -> {"action":"create","name":"iPhone","price":500,"quantity":10}
"créer un produit Samsung à 300 dollars" -> {"action":"create","name":"Samsung","price":300,"quantity":null}
"supprimer Samsung" -> {"action":"delete","name":"Samsung","price":null,"quantity":null}
"afficher tous les produits" -> {"action":"list"}
"montre les articles" -> {"action":"list"}
"renommer Samsung en Galaxy" -> {"action":"rename","name":"Samsung","newName":"Galaxy"}
"changer le nom de iPhone en Xiaomi" -> {"action":"rename","name":"iPhone","newName":"Xiaomi"}
"modifier le prix de Samsung à 600 euros" -> {"action":"update","name":"Samsung","price":600,"quantity":null}
"mettre à jour la quantité de iPhone à 25" -> {"action":"update","name":"iPhone","price":null,"quantity":25}
"supprimez le produit iPhone" -> {"action":"delete","name":"iPhone"}

If the command is ambiguous or doesn't match any action, return {"action":"list"} (show products as fallback).`
                    },
                    { role: 'user', content: englishText }
                ],
                temperature: 0,
                max_tokens: 150
            })
        });
        const data = await response.json();
        if (!data.choices?.[0]) throw new Error('No AI response');
        let raw = data.choices[0].message.content.trim();
        // Clean any markdown
        raw = raw.replace(/^```[\s\S]*?\n/, '').replace(/\n```$/, '').replace(/^`+|`+$/g, '').trim();
        const jsonMatch = raw.match(/\{[\s\S]*\}/);
        if (jsonMatch) raw = jsonMatch[0];
        return JSON.parse(raw);
    } catch (err) {
        console.error('AI parse error:', err);
        return null;
    }
}

// ---------- Voice recording ----------
let mediaRecorder, audioChunks = [], isListening = false;

async function toggleVoice() {
    if (isListening) {
        mediaRecorder.stop();
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
                formData.append('response_format', 'verbose_json');

                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });
                
                const whisperData = await whisperRes.json();
                console.log('WHISPER RESPONSE:', whisperData);
                
                const transcript = whisperData.text;
                const lang = whisperData.language || 'en';
                const langName = whisperData.language_name || lang;
                
                if (!transcript) {
                    setStatus('❌ Could not transcribe audio. Please try again.', 'error');
                    return;
                }
                
                setStatus(`📝 Heard (${langName}): "${transcript}"`, 'thinking');
                
                // If not English or French, translate to English for the fast parser
                let textForParsing = transcript;
                if (lang !== 'en' && lang !== 'fr') {
                    textForParsing = await translateToEnglish(transcript, lang);
                }
                
                // Try fast parser first (handles English & French)
                let command = fastParse(textForParsing);
                
                if (command) {
                    console.log('FAST PARSER:', command);
                    setStatus(`⚡ Executing: ${JSON.stringify(command)}`, 'thinking');
                } else {
                    // Fallback to AI
                    setStatus('🧠 Fast parser missed, using AI...', 'thinking');
                    // For AI, we always use English text; if the text is already English, we're fine. If French, translate.
                    const englishForAI = (lang === 'en') ? transcript : (lang === 'fr') ? await translateToEnglish(transcript, 'fr') : textForParsing;
                    command = await aiParse(englishForAI);
                    if (!command) {
                        setStatus('❌ Could not understand the command. Please try again.', 'error');
                        addToHistory(transcript, 'Failed to understand');
                        return;
                    }
                    setStatus(`🤖 AI parsed: ${JSON.stringify(command)}`, 'thinking');
                }
                
                const success = await executeAction(command);
                if (success) {
                    addToHistory(transcript, `✓ ${command.action}: ${command.name || ''} ${command.newName ? '→ ' + command.newName : ''}`);
                } else {
                    addToHistory(transcript, '✗ Failed');
                }
                
            } catch (err) {
                setStatus(`❌ Error: ${err.message}`, 'error');
                addToHistory('', `Error: ${err.message}`);
            }
        };
        
        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording... click to stop';
        setStatus('🎤 Recording... speak clearly', 'thinking');
        
    } catch (err) {
        setStatus(`❌ Microphone access denied: ${err.message}`, 'error');
    }
}

// ---------- Execute the parsed action (robust error display) ----------
async function executeAction(cmd) {
    try {
        if (cmd.action === 'list') {
            await loadProducts();
            setStatus('✅ Products loaded successfully', 'success');
            return true;
        }

        if (cmd.action === 'create') {
            if (!cmd.name || !cmd.name.trim()) {
                setStatus('❌ Missing product name.', 'error');
                return false;
            }
            if (cmd.price == null || isNaN(cmd.price) || cmd.price < 0) {
                setStatus('❌ Missing or invalid price.', 'error');
                return false;
            }
            if (cmd.quantity == null || isNaN(cmd.quantity) || cmd.quantity < 0) {
                setStatus('❌ Missing or invalid quantity.', 'error');
                return false;
            }
            
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: cmd.name.trim(),
                    price: Number(cmd.price),
                    quantity: Number(cmd.quantity)
                })
            });
            
            if (!res.ok) {
                const err = await res.text();
                setStatus(`❌ Create failed: ${err}`, 'error');
                return false;
            }
            setStatus(`✅ Created "${cmd.name}" - $${cmd.price}, Qty: ${cmd.quantity}`, 'success');
            await loadProducts();
            return true;
        }

        if (cmd.action === 'delete') {
            if (!cmd.name) {
                setStatus('❌ Which product should I delete?', 'error');
                return false;
            }
            
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return false;
            }
            
            await fetch(`${API}/${match.id}`, { method: 'DELETE' });
            setStatus(`✅ Deleted "${match.name}"`, 'success');
            await loadProducts();
            return true;
        }

        if (cmd.action === 'rename') {
            if (!cmd.name || !cmd.newName) {
                setStatus('❌ I need both the old and new names. Example: "rename iPhone to Xiaomi"', 'error');
                return false;
            }
            
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return false;
            }
            
            const res = await fetch(`${API}/${match.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: cmd.newName.trim() })
            });
            
            if (!res.ok) {
                const errorBody = await res.text();
                console.error('Rename error:', errorBody);
                setStatus(`❌ Rename failed: ${errorBody}`, 'error');
                return false;
            }
            
            setStatus(`✅ Renamed "${cmd.name}" to "${cmd.newName}"`, 'success');
            await loadProducts();
            return true;
        }

        if (cmd.action === 'update') {
            if (!cmd.name) {
                setStatus('❌ Which product should I update?', 'error');
                return false;
            }
            
            await loadProducts();
            const match = findProduct(cmd.name);
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return false;
            }
            
            const updateBody = {};
            if (cmd.name) updateBody.name = cmd.name.trim();
            if (cmd.price != null && !isNaN(cmd.price)) updateBody.price = Number(cmd.price);
            if (cmd.quantity != null && !isNaN(cmd.quantity)) updateBody.quantity = Number(cmd.quantity);
            
            if (Object.keys(updateBody).length === 0) {
                setStatus('❌ Nothing to update. Please specify price or quantity.', 'error');
                return false;
            }
            
            const res = await fetch(`${API}/${match.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updateBody)
            });
            
            if (!res.ok) {
                const errorBody = await res.text();
                setStatus(`❌ Update failed: ${errorBody}`, 'error');
                return false;
            }
            setStatus(`✅ Updated "${match.name}" successfully`, 'success');
            await loadProducts();
            return true;
        }

        setStatus(`❓ Unknown action: ${cmd.action}. Say "add", "delete", "rename", or "show products".`, 'error');
        return false;
        
    } catch (err) {
        setStatus(`❌ Error: ${err.message}`, 'error');
        return false;
    }
}

function findProduct(name) {
    let match = allProducts.find(p => p.name.toLowerCase() === name.toLowerCase());
    if (!match) {
        match = allProducts.find(p => p.name.toLowerCase().includes(name.toLowerCase()));
    }
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
    } catch (err) {
        console.error('Load error:', err);
        document.getElementById("table").innerHTML = '<tr><td colspan="4">Error loading products</td></tr>';
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
            const total = (p.price * p.quantity).toFixed(2);
            return `
            <tr>
                <td><strong>${p.name}</strong></td>
                <td>$${parseFloat(p.price).toFixed(2)}</td>
                <td>${p.quantity}</td>
                <td>$${total}</td>
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
    const filtered = allProducts.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.price.toString().includes(query)
    );
    renderTable(filtered);
}

function updateStats() {
    document.getElementById('total-products').textContent = allProducts.length;
    
    const totalValue = allProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);
    document.getElementById('total-value').textContent = '$' + totalValue.toFixed(2);
    
    const avgPrice = allProducts.length > 0 
        ? allProducts.reduce((sum, p) => sum + parseFloat(p.price), 0) / allProducts.length 
        : 0;
    document.getElementById('avg-price').textContent = '$' + avgPrice.toFixed(2);
}

async function createProduct() {
    const name = document.getElementById("name").value.trim();
    const price = parseFloat(document.getElementById("price").value);
    const quantity = parseInt(document.getElementById("quantity").value, 10);
    
    if (!name || isNaN(price) || isNaN(quantity)) {
        alert('Please fill in all fields with valid numbers');
        return;
    }
    
    const res = await fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify({ name, price, quantity })
    });
    
    if (res.ok) {
        document.getElementById("name").value = "";
        document.getElementById("price").value = "";
        document.getElementById("quantity").value = "";
        await loadProducts();
    }
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    await fetch(`${API}/${id}`, { method: "DELETE" });
    await loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Product name:", nameVal);
    if (newName === null || newName.trim() === '') return;
    
    const newPrice = prompt("Price:", priceVal);
    if (newPrice === null) return;
    const priceNum = parseFloat(newPrice);
    if (isNaN(priceNum) || priceNum < 0) {
        alert('Please enter a valid price');
        return;
    }
    
    const newQty = prompt("Quantity:", qtyVal);
    if (newQty === null) return;
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(qtyNum) || qtyNum < 0) {
        alert('Please enter a valid quantity');
        return;
    }
    
    await fetch(`${API}/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum })
    });
    await loadProducts();
}

// Initial load
loadProducts();
</script>

@endsection