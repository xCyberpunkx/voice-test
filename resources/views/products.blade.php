@extends('layouts.app')

@section('content')

<style>
    .page-title { font-size: 26px; font-weight: bold; margin-bottom: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
    input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
    button { padding: 10px 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    .btn-add { background: #2563eb; color: white; }
    .btn-edit { background: #f59e0b; color: white; margin-right: 5px; }
    .btn-delete { background: #ef4444; color: white; }
    .btn-mic { background: #7c3aed; color: white; font-size: 18px; }
    .btn-mic.listening { background: #dc2626; animation: pulse 1s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px; background: #f3f4f6; }
    td { padding: 12px; border-top: 1px solid #eee; }
    tr:hover { background: #f9fafb; }
    .actions { display: flex; gap: 5px; }
    #voice-status { margin-top: 10px; padding: 10px; border-radius: 8px; background: #f3f4f6; font-size: 14px; min-height: 36px; }
    #voice-status.success { background: #d1fae5; color: #065f46; }
    #voice-status.error { background: #fee2e2; color: #991b1b; }
    #voice-status.thinking { background: #ede9fe; color: #5b21b6; }
</style>

<div class="page-title">📦 Multilingual Voice Dashboard ⚡🌍</div>

<div class="card">
    <h3>🎤 Voice Command (speak any language!)</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
        English: "add iPhone price 500 quantity 10"<br>
        French: "ajouter iPhone prix 500 quantité 10"<br>
       
    </p>
    <button class="btn-mic" id="mic-btn" onclick="toggleVoice()">🎤 Click to Speak</button>
    <div id="voice-status">Waiting for command...</div>
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
    <table>
        <thead>
            <tr><th>Name</th><th>Price</th><th>Qty</th><th>Actions</th></tr>
        </thead>
        <tbody id="table"></tbody>
    </table>
</div>

<script>
const API = "/api/products";
const GROQ_KEY = "{{ env('GROQ_KEY') }}";

// ---------- Helpers ----------
function setStatus(msg, type = '') {
    const el = document.getElementById('voice-status');
    el.textContent = msg;
    el.className = type;
}

// ---------- Fast local parser (English-only; expects already translated text) ----------
function fastParse(englishText) {
    const t = englishText.toLowerCase().trim();

    // 1. List command
    if (/\b(show|list|all|display|products|items|goods)\b/.test(t) && !/\b(delete|remove|add|create|update|change|set)\b/.test(t)) {
        return { action: "list", name: null, price: null, quantity: null };
    }

    // 2. Delete command
    const deleteMatch = t.match(/\b(delete|remove|eliminate|erase|clear)\s+(\w+)/);
    if (deleteMatch) {
        return { action: "delete", name: deleteMatch[2], price: null, quantity: null };
    }

    // 3. Create/Add command (flexible pattern)
    const addMatch = t.match(/\b(add|create|new|insert|put)\s+(\w+).*?\b(price|cost|amount)\s*(\d+(?:\.\d+)?).*?\b(quantity|qty|count|stock|units)\s*(\d+)/);
    if (addMatch) {
        return {
            action: "create",
            name: addMatch[2],
            price: parseFloat(addMatch[4]),
            quantity: parseInt(addMatch[6], 10)
        };
    }

    // 4. Update command
    const updateMatch = t.match(/\b(update|change|set|modify|adjust)\s+(\w+).*?\b(price|cost|amount)\s+(\d+(?:\.\d+)?)/);
    if (updateMatch) {
        return {
            action: "update",
            name: updateMatch[2],
            price: parseFloat(updateMatch[4]),
            quantity: null
        };
    }

    // No match
    return null;
}

// ---------- Translate to English (only if needed) ----------
async function translateToEnglish(text, sourceLang) {
    if (!sourceLang || sourceLang === 'en' || sourceLang === 'english') {
        return text; // already English
    }
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_KEY}`
            },
            body: JSON.stringify({
                model: 'llama3-8b-8192',          // fast & cheap
                messages: [
                    { role: 'system', content: `Translate the following ${sourceLang} text into English. Output ONLY the English translation, nothing else.` },
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
        return text; // fallback to original
    } catch (e) {
        console.error('Translation error:', e);
        return text; // fallback
    }
}

// ---------- AI fallback parser (for complex/non‑standard commands) ----------
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
                    { role: 'system', content: `You are a product management assistant. Translate the user's command into a JSON action. Return ONLY raw JSON, no markdown.
Format: { "action": "create|update|delete|list", "name": "string or null", "price": number or null, "quantity": number or null }
If price or quantity is missing, use null. If the action is list, set name, price, quantity to null.` },
                    { role: 'user', content: englishText }
                ],
                temperature: 0,
                max_tokens: 150
            })
        });
        const data = await response.json();
        if (!data.choices?.[0]) throw new Error('No AI response');
        let raw = data.choices[0].message.content.trim();
        raw = raw.replace(/^```[\s\S]*?\n/, '').replace(/\n```$/, '').replace(/^`+|`+$/g, '').trim();
        return JSON.parse(raw);
    } catch (err) {
        console.error('AI parse error:', err);
        return null;
    }
}

// ---------- Voice recording ----------
let mediaRecorder;
let audioChunks = [];
let isListening = false;

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
                // Request verbose JSON to get auto-detected language
                formData.append('response_format', 'verbose_json');

                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });

                const whisperData = await whisperRes.json();
                console.log('WHISPER FULL RESPONSE:', whisperData);

                const transcript = whisperData.text;
                const lang = whisperData.language;               // e.g., 'en', 'es', 'fr'
                const langName = whisperData.language_name || lang;

                if (!transcript) {
                    setStatus('❌ Could not transcribe', 'error');
                    return;
                }

                setStatus(`📝 Heard (${langName}): "${transcript}" — processing...`, 'thinking');

                // Translate if needed
                const englishText = await translateToEnglish(transcript, lang);

                // Try fast regex on English text
                let command = fastParse(englishText);
                if (command) {
                    console.log('FAST PARSER MATCH:', command);
                    setStatus(`⚡ Fast parsed: ${JSON.stringify(command)}`, 'thinking');
                } else {
                    // Fallback to AI
                    setStatus('🧠 Fast parser missed, using AI...', 'thinking');
                    command = await aiParse(englishText);
                    if (!command) {
                        setStatus('❌ AI failed to understand the command.', 'error');
                        return;
                    }
                }

                await executeAction(command);

            } catch (err) {
                setStatus(`❌ Error: ${err.message}`, 'error');
            }
        };

        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording... click to stop';
        setStatus('🎤 Recording...', 'thinking');

    } catch (err) {
        setStatus(`❌ Mic access denied: ${err.message}`, 'error');
    }
}

// ---------- Execute the parsed action (unchanged) ----------
async function executeAction(cmd) {
    try {
        if (cmd.action === 'list') {
            await loadProducts();
            setStatus('✅ Products loaded', 'success');
            return;
        }

        if (cmd.action === 'create') {
            if (!cmd.name || !cmd.name.trim()) {
                setStatus('❌ Missing product name.', 'error');
                return;
            }
            if (cmd.price == null || isNaN(cmd.price)) {
                setStatus('❌ Missing or invalid price.', 'error');
                return;
            }
            if (cmd.quantity == null || isNaN(cmd.quantity)) {
                setStatus('❌ Missing or invalid quantity.', 'error');
                return;
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
                const err = await res.json();
                setStatus(`❌ Create failed: ${JSON.stringify(err)}`, 'error');
                return;
            }
            setStatus(`✅ Created "${cmd.name}"`, 'success');
        }

        else if (cmd.action === 'delete') {
            if (!cmd.name) {
                setStatus('❌ Which product should I delete?', 'error');
                return;
            }
            const res = await fetch(API);
            const products = await res.json();
            let match = products.find(p => p.name.toLowerCase() === cmd.name.toLowerCase());
            if (!match) match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return;
            }
            await fetch(`${API}/${match.id}`, { method: 'DELETE' });
            setStatus(`✅ Deleted "${match.name}"`, 'success');
        }

        else if (cmd.action === 'update') {
            if (!cmd.name) {
                setStatus('❌ Which product should I update?', 'error');
                return;
            }
            const res = await fetch(API);
            const products = await res.json();
            let match = products.find(p => p.name.toLowerCase() === cmd.name.toLowerCase());
            if (!match) match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return;
            }

            const updateBody = {};
            if (cmd.name) updateBody.name = cmd.name.trim();
            if (cmd.price != null && !isNaN(cmd.price)) updateBody.price = Number(cmd.price);
            if (cmd.quantity != null && !isNaN(cmd.quantity)) updateBody.quantity = Number(cmd.quantity);

            await fetch(`${API}/${match.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updateBody)
            });
            setStatus(`✅ Updated "${match.name}"`, 'success');
        }

        else {
            setStatus(`❓ Unknown action: ${cmd.action}.`, 'error');
        }

        await loadProducts();
    } catch (err) {
        setStatus(`❌ Action error: ${err.message}`, 'error');
    }
}

// ---------- Manual CRUD ----------
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Load failed');
        const data = await res.json();
        const tbody = document.getElementById("table");
        tbody.innerHTML = data.map(p => {
            const safeName = JSON.stringify(p.name);
            return `
            <tr>
                <td>${p.name}</td>
                <td>$${p.price}</td>
                <td>${p.quantity}</td>
                <td>
                    <div class="actions">
                        <button class="btn-edit" onclick="editProduct(${p.id}, ${safeName}, ${p.price}, ${p.quantity})">Edit</button>
                        <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (err) {
        console.error(err);
        document.getElementById("table").innerHTML = '<tr><td colspan="4">Error loading products</td></tr>';
    }
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
    loadProducts();
}

async function deleteProduct(id) {
    if (!confirm('Delete this product?')) return;
    await fetch(`${API}/${id}`, { method: "DELETE" });
    loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Name:", nameVal);
    if (newName === null) return;
    const newPrice = prompt("Price:", priceVal);
    if (newPrice === null) return;
    const newQty = prompt("Quantity:", qtyVal);
    if (newQty === null) return;
    const priceNum = parseFloat(newPrice);
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(priceNum) || isNaN(qtyNum)) {
        alert('Invalid number');
        return;
    }
    await fetch(`${API}/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: newName.trim(), price: priceNum, quantity: qtyNum })
    });
    loadProducts();
}

// Initial load
loadProducts();
</script>

@endsection