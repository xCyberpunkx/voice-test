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

<div class="page-title">📦 Multilingual Product Dashboard</div>

<div class="card">
    <h3>🎤 Voice Command (works in any language!)</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
        English: "add iPhone price 500 quantity 10" · Spanish: "agregar iPhone precio 500 cantidad 10"<br>
        French: "ajouter iPhone prix 500 quantité 10" · Hindi: "iPhone जोड़ें कीमत 500 मात्रा 10"
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

if (!GROQ_KEY || GROQ_KEY === '') {
    alert('GROQ_KEY is not set. Please add it to your .env file.');
}

// Helper to set status message with styling
function setStatus(msg, type = '') {
    const el = document.getElementById('voice-status');
    el.textContent = msg;
    el.className = type;
}

// ---------- Voice Recording ----------
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
            setStatus('🤖 Transcribing audio...', 'thinking');

            try {
                const formData = new FormData();
                formData.append('file', audioBlob, 'audio.webm');
                formData.append('model', 'whisper-large-v3-turbo');
                // No 'language' parameter → Whisper auto-detects language

                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });

                const whisperData = await whisperRes.json();
                console.log('WHISPER RESPONSE:', JSON.stringify(whisperData));

                if (!whisperData.text) {
                    setStatus('❌ Could not transcribe audio. Please try again.', 'error');
                    return;
                }

                const transcript = whisperData.text;
                setStatus(`📝 Heard (auto-detected language): "${transcript}" — thinking...`, 'thinking');
                parseWithGroq(transcript);

            } catch (err) {
                setStatus(`❌ Transcription error: ${err.message}`, 'error');
            }
        };

        mediaRecorder.start();
        isListening = true;
        document.getElementById('mic-btn').classList.add('listening');
        document.getElementById('mic-btn').textContent = '🔴 Recording... click to stop';
        setStatus('🎤 Recording... click mic again to stop', 'thinking');

    } catch (err) {
        setStatus(`❌ Microphone access denied: ${err.message}`, 'error');
    }
}

// ---------- Natural Language Parsing (multilingual) ----------
async function parseWithGroq(transcript) {
    try {
        // Updated prompt with multilingual examples
        const systemPrompt = 
`You are a multilingual product management assistant. You understand and process voice commands in ANY language.
You extract product information and return ONLY a raw JSON object, no markdown, no backticks, no explanation.

The JSON format must be:
{ "action": "create|update|delete|list", "name": "product name or null", "price": number or null, "quantity": number or null }

Rules:
- "name" must be the product name only, never null for create commands.
- "price" and "quantity" must be numbers only (not strings like "five"). Use null if not provided.
- For "update" action, only include fields that should be changed.
- For "delete" action, only name is needed.
- For "list" action, everything else should be null.

Examples in multiple languages:

English:
"add iPhone price 500 quantity 10" → { "action": "create", "name": "iPhone", "price": 500, "quantity": 10 }
"delete Samsung" → { "action": "delete", "name": "Samsung", "price": null, "quantity": null }
"show all products" → { "action": "list", "name": null, "price": null, "quantity": null }
"change Pixel price to 600" → { "action": "update", "name": "Pixel", "price": 600, "quantity": null }

French:
"ajouter iPhone prix 500 quantité 10" → { "action": "create", "name": "iPhone", "price": 500, "quantity": 10 }
"supprimer Samsung" → { "action": "delete", "name": "Samsung" }


Now process the following command (already transcribed from speech):
"${transcript}"
`;

        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_KEY}`
            },
            body: JSON.stringify({
                model: 'openai/gpt-oss-120b',  // this model supports multiple languages
                messages: [
                    { role: 'system', content: systemPrompt },
                    { role: 'user', content: transcript }
                ]
            })
        });

        const data = await response.json();
        console.log('GROQ RESPONSE:', JSON.stringify(data));

        if (!data.choices || !data.choices[0]) {
            setStatus(`❌ AI understanding failed: ${data.error?.message || 'Unknown error'}`, 'error');
            return;
        }

        let raw = data.choices[0].message.content.trim();

        // Robust JSON extraction – removes any surrounding markdown code fences
        raw = raw.replace(/^```[\s\S]*?\n/, '').replace(/\n```$/, '').trim();

        // Sometimes the model encloses JSON in backticks without a language marker
        raw = raw.replace(/^`+|`+$/g, '').trim();

        let parsed;
        try {
            parsed = JSON.parse(raw);
        } catch {
            // If still fails, try to extract the first {...} block
            const match = raw.match(/\{[\s\S]*\}/);
            if (match) {
                try { parsed = JSON.parse(match[0]); } catch(e2) {
                    setStatus('❌ Failed to parse AI response as JSON', 'error');
                    return;
                }
            } else {
                setStatus('❌ AI response was not valid JSON', 'error');
                return;
            }
        }

        console.log('PARSED COMMAND:', JSON.stringify(parsed));
        setStatus(`✅ Understood: ${parsed.action} "${parsed.name || ''}"`, 'thinking');
        await executeAction(parsed);

    } catch (err) {
        setStatus(`❌ AI error: ${err.message}`, 'error');
        console.error(err);
    }
}

// ---------- Execute the parsed action ----------
async function executeAction(cmd) {
    try {
        if (cmd.action === 'list') {
            await loadProducts();
            setStatus('✅ Product list refreshed', 'success');
            return;
        }

        if (cmd.action === 'create') {
            if (!cmd.name || cmd.name.trim() === '') {
                setStatus('❌ I missed the product name. Please try again, e.g. "add iPhone price 500 quantity 10"', 'error');
                return;
            }
            if (cmd.price == null || isNaN(cmd.price)) {
                setStatus('❌ I need a valid price. Please say something like "price 500"', 'error');
                return;
            }
            if (cmd.quantity == null || isNaN(cmd.quantity)) {
                setStatus('❌ I need a quantity. Please say "quantity 10"', 'error');
                return;
            }

            // Ensure numbers are sent as numbers
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: cmd.name.trim(),
                    price: Number(cmd.price),
                    quantity: Number(cmd.quantity)
                })
            });

            const result = await res.json();
            if (!res.ok) {
                setStatus(`❌ Create failed: ${JSON.stringify(result)}`, 'error');
                return;
            }
            setStatus(`✅ Created "${cmd.name}"`, 'success');
        }

        else if (cmd.action === 'delete') {
            if (!cmd.name) {
                setStatus('❌ Which product should I delete? Try "delete Apple"', 'error');
                return;
            }
            const res = await fetch(API);
            const products = await res.json();
            // Case-insensitive exact match first, then partial if needed
            let match = products.find(p => p.name.toLowerCase() === cmd.name.toLowerCase());
            if (!match) {
                match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            }
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
            if (!match) {
                match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            }
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
            setStatus(`❓ Unknown action: ${cmd.action}. Try "add", "delete", or "show products".`, 'error');
        }

        await loadProducts();

    } catch (err) {
        setStatus(`❌ Action failed: ${err.message}`, 'error');
    }
}

// ---------- Manual CRUD ----------
async function loadProducts() {
    try {
        const res = await fetch(API);
        if (!res.ok) throw new Error('Failed to load products');
        const data = await res.json();
        const tbody = document.getElementById("table");
        tbody.innerHTML = data.map(p => {
            // Escape strings for inline event handlers safely using JSON.stringify
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
        alert('Please fill in all fields with valid numbers');
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
    if (!confirm('Are you sure you want to delete this product?')) return;
    await fetch(`${API}/${id}`, { method: "DELETE" });
    loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Name:", nameVal);
    if (newName === null) return; // cancelled
    const newPrice = prompt("Price:", priceVal);
    if (newPrice === null) return;
    const newQty = prompt("Quantity:", qtyVal);
    if (newQty === null) return;

    const priceNum = parseFloat(newPrice);
    const qtyNum = parseInt(newQty, 10);
    if (isNaN(priceNum) || isNaN(qtyNum)) {
        alert('Invalid number entered');
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