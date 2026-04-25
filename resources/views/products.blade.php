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

<div class="page-title">📦 Products Dashboard</div>

<div class="card">
    <h3>🎤 Voice Command</h3>
    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
        Try: "add iPhone price 500 quantity 10" · "delete iPhone" · "show products"
    </p>
    <button class="btn-mic" id="mic-btn" onclick="toggleVoice()">🎤 Click to Speak</button>
    <div id="voice-status">Waiting for command...</div>
</div>

<div class="card">
    <h3>Add Product</h3>
    <div class="grid">
        <input id="name" placeholder="Product name">
        <input id="price" placeholder="Price">
        <input id="quantity" placeholder="Quantity">
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

function setStatus(msg, type = '') {
    const el = document.getElementById('voice-status');
    el.textContent = msg;
    el.className = type;
}

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

                const whisperRes = await fetch('https://api.groq.com/openai/v1/audio/transcriptions', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${GROQ_KEY}` },
                    body: formData
                });

                const whisperData = await whisperRes.json();
                console.log('WHISPER RESPONSE:', JSON.stringify(whisperData));

                if (!whisperData.text) {
                    setStatus('❌ Could not transcribe audio', 'error');
                    return;
                }

                const transcript = whisperData.text;
                setStatus(`📝 Heard: "${transcript}" — thinking...`, 'thinking');
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
        setStatus(`❌ Mic access denied: ${err.message}`, 'error');
    }
}

async function parseWithGroq(transcript) {
    try {
        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_KEY}`
            },
            body: JSON.stringify({
                model: 'llama-3.1-8b-instant',
                messages: [{
                    role: 'system',
                    content: `You are a product management assistant. Extract product info from voice commands.
Return ONLY a raw JSON object, no markdown, no backticks, no explanation.
Format: { "action": "create|update|delete|list", "name": "string or null", "price": number or null, "quantity": number or null }

Rules:
- "name" must be the product name only, never null for create commands
- "price" and "quantity" must be numbers only, never strings or words
- If you cannot find a number for price or quantity, use null

Examples:
"add iPhone price 500 quantity 10" → { "action": "create", "name": "iPhone", "price": 500, "quantity": 10 }
"create a product called Samsung for 300 dollars 5 units" → { "action": "create", "name": "Samsung", "price": 300, "quantity": 5 }
"delete iPhone" → { "action": "delete", "name": "iPhone", "price": null, "quantity": null }
"show all products" → { "action": "list", "name": null, "price": null, "quantity": null }`
                }, {
                    role: 'user',
                    content: transcript
                }]
            })
        });

        const data = await response.json();
        console.log('GROQ RESPONSE:', JSON.stringify(data));

        if (!data.choices || !data.choices[0]) {
            setStatus(`❌ Groq error: ${data.error?.message || 'Unknown error'}`, 'error');
            return;
        }

        const raw = data.choices[0].message.content.trim();

        let parsed;
        try {
            parsed = JSON.parse(raw);
        } catch {
            const cleaned = raw.replace(/```json|```/g, '').trim();
            parsed = JSON.parse(cleaned);
        }

        console.log('PARSED:', JSON.stringify(parsed));
        setStatus(`🤖 AI understood: ${JSON.stringify(parsed)}`, 'thinking');
        await executeAction(parsed);

    } catch (err) {
        setStatus(`❌ AI error: ${err.message}`, 'error');
        console.error(err);
    }
}

async function executeAction(cmd) {
    try {
        if (cmd.action === 'list') {
            await loadProducts();
            setStatus('✅ Products loaded', 'success');
            return;
        }

        if (cmd.action === 'create') {
            if (!cmd.name) {
                setStatus('❌ Could not understand product name. Try: "add iPhone price 500 quantity 10"', 'error');
                return;
            }
            if (cmd.price === null || cmd.price === undefined) {
                setStatus('❌ Could not understand price. Try: "add iPhone price 500 quantity 10"', 'error');
                return;
            }
            if (cmd.quantity === null || cmd.quantity === undefined) {
                setStatus('❌ Could not understand quantity. Try: "add iPhone price 500 quantity 10"', 'error');
                return;
            }

            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: cmd.name, price: cmd.price, quantity: cmd.quantity })
            });

            const result = await res.json();
            console.log('CREATE RESULT:', result);

            if (!res.ok) {
                setStatus(`❌ Create failed: ${JSON.stringify(result)}`, 'error');
                return;
            }

            setStatus(`✅ Created "${cmd.name}"`, 'success');
        }

        if (cmd.action === 'delete') {
            if (!cmd.name) {
                setStatus('❌ Could not understand product name to delete', 'error');
                return;
            }
            const res = await fetch(API);
            const products = await res.json();
            const match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return;
            }
            await fetch(`${API}/${match.id}`, { method: 'DELETE' });
            setStatus(`✅ Deleted "${match.name}"`, 'success');
        }

        if (cmd.action === 'update') {
            if (!cmd.name) {
                setStatus('❌ Could not understand product name to update', 'error');
                return;
            }
            const res = await fetch(API);
            const products = await res.json();
            const match = products.find(p => p.name.toLowerCase().includes(cmd.name.toLowerCase()));
            if (!match) {
                setStatus(`❌ Product "${cmd.name}" not found`, 'error');
                return;
            }
            await fetch(`${API}/${match.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: cmd.name ?? match.name,
                    price: cmd.price ?? match.price,
                    quantity: cmd.quantity ?? match.quantity
                })
            });
            setStatus(`✅ Updated "${match.name}"`, 'success');
        }

        await loadProducts();

    } catch (err) {
        setStatus(`❌ Action failed: ${err.message}`, 'error');
    }
}

async function loadProducts() {
    const res = await fetch(API);
    const data = await res.json();
    document.getElementById("table").innerHTML = data.map(p => `
        <tr>
            <td>${p.name}</td>
            <td>$${p.price}</td>
            <td>${p.quantity}</td>
            <td>
                <div class="actions">
                    <button class="btn-edit" onclick="editProduct(${p.id}, '${p.name}', ${p.price}, ${p.quantity})">Edit</button>
                    <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function createProduct() {
    const name = document.getElementById("name").value;
    const price = document.getElementById("price").value;
    const quantity = document.getElementById("quantity").value;

    if (!name || !price || !quantity) {
        alert('Please fill in all fields');
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
    await fetch(`${API}/${id}`, { method: "DELETE" });
    loadProducts();
}

async function editProduct(id, nameVal, priceVal, qtyVal) {
    const newName = prompt("Name:", nameVal);
    const newPrice = prompt("Price:", priceVal);
    const newQty = prompt("Quantity:", qtyVal);
    await fetch(`${API}/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: newName, price: newPrice, quantity: newQty })
    });
    loadProducts();
}

loadProducts();
</script>

@endsection