<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class AgentController extends Controller
{
    private $groqKey;
    private $model = 'llama-3.3-70b-versatile';

    public function __construct()
    {
        $this->groqKey = env('GROQ_KEY');
    }

    // ─── TOOLS DEFINITION ────────────────────────────────
    private function getTools()
    {
        return [
            // ── BASIC CRUD ──
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_product',
                    'description' => 'Add a single new product to the inventory. Use this when the user provides a product name, price, and quantity.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name'     => ['type' => 'string',  'description' => 'Product name'],
                            'price'    => ['type' => 'number',  'description' => 'Product price'],
                            'quantity' => ['type' => 'integer', 'description' => 'Product quantity'],
                        ],
                        'required' => ['name', 'price', 'quantity']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_products',
                    'description' => 'Get all products from inventory with their names, prices and quantities.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass()
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'delete_product',
                    'description' => 'Delete a single product by name.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Product name to delete']
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product',
                    'description' => 'Update the price or quantity of a product by name.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name'     => ['type' => 'string',  'description' => 'Product name to update'],
                            'price'    => ['type' => 'number',  'description' => 'New price (optional)'],
                            'quantity' => ['type' => 'integer', 'description' => 'New quantity (optional)'],
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_stats',
                    'description' => 'Get inventory statistics: total products, total quantity, total value, average price.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass()
                    ]
                ]
            ],

            // ── BULK ACTIONS ──
            [
                'type' => 'function',
                'function' => [
                    'name' => 'bulk_create_products',
                    'description' => 'Add multiple products to the inventory at once. Use when user wants to add several products in one message.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'products' => [
                                'type' => 'array',
                                'description' => 'List of products to create',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name'     => ['type' => 'string'],
                                        'price'    => ['type' => 'number'],
                                        'quantity' => ['type' => 'integer'],
                                    ],
                                    'required' => ['name', 'price', 'quantity']
                                ]
                            ]
                        ],
                        'required' => ['products']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'bulk_delete_products',
                    'description' => 'Delete multiple products by name at once.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'names' => [
                                'type' => 'array',
                                'description' => 'List of product names to delete',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['names']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'bulk_update_products',
                    'description' => 'Update multiple products at once. Useful for price changes across several products.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'updates' => [
                                'type' => 'array',
                                'description' => 'List of updates',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name'     => ['type' => 'string'],
                                        'price'    => ['type' => 'number'],
                                        'quantity' => ['type' => 'integer'],
                                    ],
                                    'required' => ['name']
                                ]
                            ]
                        ],
                        'required' => ['updates']
                    ]
                ]
            ],

            // ── SMART SEARCH ──
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_cheapest_products',
                    'description' => 'Find the cheapest products in inventory. Use when user asks for cheapest, lowest price, most affordable.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'How many to return (default 3)']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_most_expensive_products',
                    'description' => 'Find the most expensive products. Use when user asks for most expensive, highest price, premium items.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => ['type' => 'integer', 'description' => 'How many to return (default 3)']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_low_stock_products',
                    'description' => 'Find products with low stock. Use when user asks about low stock, running out, needs restocking.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'threshold' => ['type' => 'integer', 'description' => 'Quantity considered "low" (default 5)']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products_by_price_range',
                    'description' => 'Find products within a price range. Use when user asks "show me products under $X" or "between $X and $Y".',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'min_price' => ['type' => 'number', 'description' => 'Minimum price (optional)'],
                            'max_price' => ['type' => 'number', 'description' => 'Maximum price (optional)'],
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products_by_name',
                    'description' => 'Search products by partial name match. Use when user says "find", "search", "look for" a product.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Search keyword']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_inventory_report',
                    'description' => 'Generate a detailed inventory health report with insights, warnings about low stock, high value items, and recommendations.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass()
                    ]
                ]
            ],
        ];
    }

    // ─── TOOL EXECUTION ──────────────────────────────────
    private function executeTool(string $name, array $args): string
    {
        switch ($name) {

            // ── BASIC CRUD ──
            case 'create_product':
                $product = Product::create([
                    'name'     => $args['name'],
                    'price'    => $args['price'],
                    'quantity' => $args['quantity'],
                ]);
                return "✅ Created: {$product->name} at \${$product->price}, qty {$product->quantity}";

            case 'get_products':
                $products = Product::all();
                if ($products->isEmpty()) return "Inventory is empty.";
                return $products->map(fn($p) => "• {$p->name}: \${$p->price} (qty: {$p->quantity})")->join("\n");

            case 'delete_product':
                $product = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($args['name']) . '%'])->first();
                if (!$product) return "❌ Product '{$args['name']}' not found.";
                $n = $product->name;
                $product->delete();
                return "🗑️ Deleted: {$n}";

            case 'update_product':
                $product = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($args['name']) . '%'])->first();
                if (!$product) return "❌ Product '{$args['name']}' not found.";
                if (isset($args['price']))    $product->price    = $args['price'];
                if (isset($args['quantity'])) $product->quantity = $args['quantity'];
                $product->save();
                return "✏️ Updated {$product->name}: \${$product->price}, qty {$product->quantity}";

            case 'get_stats':
                $products = Product::all();
                $count    = $products->count();
                $total    = $products->sum(fn($p) => $p->price * $p->quantity);
                $totalQty = $products->sum('quantity');
                $avg      = $count ? round($products->avg('price'), 2) : 0;
                return "📊 Stats: {$count} products | {$totalQty} total units | \${$total} total value | \${$avg} avg price";

            // ── BULK ACTIONS ──
            case 'bulk_create_products':
                $created = [];
                foreach ($args['products'] as $p) {
                    Product::create([
                        'name'     => $p['name'],
                        'price'    => $p['price'],
                        'quantity' => $p['quantity'],
                    ]);
                    $created[] = $p['name'];
                }
                return "✅ Bulk created " . count($created) . " products: " . implode(', ', $created);

            case 'bulk_delete_products':
                $deleted = [];
                $notFound = [];
                foreach ($args['names'] as $name) {
                    $product = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])->first();
                    if ($product) {
                        $deleted[] = $product->name;
                        $product->delete();
                    } else {
                        $notFound[] = $name;
                    }
                }
                $result = "🗑️ Deleted: " . (count($deleted) ? implode(', ', $deleted) : 'none');
                if (count($notFound)) $result .= " | ❌ Not found: " . implode(', ', $notFound);
                return $result;

            case 'bulk_update_products':
                $updated = [];
                $notFound = [];
                foreach ($args['updates'] as $u) {
                    $product = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($u['name']) . '%'])->first();
                    if ($product) {
                        if (isset($u['price']))    $product->price    = $u['price'];
                        if (isset($u['quantity'])) $product->quantity = $u['quantity'];
                        $product->save();
                        $updated[] = $product->name;
                    } else {
                        $notFound[] = $u['name'];
                    }
                }
                $result = "✏️ Updated: " . (count($updated) ? implode(', ', $updated) : 'none');
                if (count($notFound)) $result .= " | ❌ Not found: " . implode(', ', $notFound);
                return $result;

            // ── SMART SEARCH ──
            case 'find_cheapest_products':
                $limit = $args['limit'] ?? 3;
                $products = Product::orderBy('price', 'asc')->limit($limit)->get();
                if ($products->isEmpty()) return "Inventory is empty.";
                return "💰 Cheapest {$limit}:\n" . $products->map(fn($p) => "• {$p->name}: \${$p->price} (qty: {$p->quantity})")->join("\n");

            case 'find_most_expensive_products':
                $limit = $args['limit'] ?? 3;
                $products = Product::orderBy('price', 'desc')->limit($limit)->get();
                if ($products->isEmpty()) return "Inventory is empty.";
                return "💎 Most expensive {$limit}:\n" . $products->map(fn($p) => "• {$p->name}: \${$p->price} (qty: {$p->quantity})")->join("\n");

            case 'find_low_stock_products':
                $threshold = $args['threshold'] ?? 5;
                $products = Product::where('quantity', '<=', $threshold)->orderBy('quantity', 'asc')->get();
                if ($products->isEmpty()) return "✅ No low stock products (threshold: {$threshold} units).";
                return "⚠️ Low stock (≤{$threshold} units):\n" . $products->map(fn($p) => "• {$p->name}: {$p->quantity} left @ \${$p->price}")->join("\n");

            case 'search_products_by_price_range':
                $query = Product::query();
                if (isset($args['min_price'])) $query->where('price', '>=', $args['min_price']);
                if (isset($args['max_price'])) $query->where('price', '<=', $args['max_price']);
                $products = $query->orderBy('price')->get();
                if ($products->isEmpty()) return "No products found in that price range.";
                $range = '';
                if (isset($args['min_price']) && isset($args['max_price']))
                    $range = "\${$args['min_price']} – \${$args['max_price']}";
                elseif (isset($args['max_price']))
                    $range = "under \${$args['max_price']}";
                else
                    $range = "above \${$args['min_price']}";
                return "🔍 Products {$range}:\n" . $products->map(fn($p) => "• {$p->name}: \${$p->price} (qty: {$p->quantity})")->join("\n");

            case 'search_products_by_name':
                $products = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($args['query']) . '%'])->get();
                if ($products->isEmpty()) return "No products found matching '{$args['query']}'.";
                return "🔍 Found " . $products->count() . " result(s):\n" . $products->map(fn($p) => "• {$p->name}: \${$p->price} (qty: {$p->quantity})")->join("\n");

            case 'get_inventory_report':
                $products = Product::all();
                if ($products->isEmpty()) return "Inventory is empty — nothing to report.";
                $count    = $products->count();
                $total    = $products->sum(fn($p) => $p->price * $p->quantity);
                $totalQty = $products->sum('quantity');
                $avg      = round($products->avg('price'), 2);
                $lowStock = $products->where('quantity', '<=', 5);
                $topValue = $products->sortByDesc(fn($p) => $p->price * $p->quantity)->first();
                $cheapest = $products->sortBy('price')->first();
                $priciest = $products->sortByDesc('price')->first();

                $report = "📋 INVENTORY REPORT\n";
                $report .= "────────────────────\n";
                $report .= "• {$count} products | {$totalQty} total units\n";
                $report .= "• Total value: \${$total} | Avg price: \${$avg}\n";
                $report .= "• Cheapest: {$cheapest->name} @ \${$cheapest->price}\n";
                $report .= "• Priciest: {$priciest->name} @ \${$priciest->price}\n";
                $report .= "• Most valuable stock: {$topValue->name} (\$" . ($topValue->price * $topValue->quantity) . " total)\n";
                if ($lowStock->count()) {
                    $report .= "⚠️ LOW STOCK ALERT (" . $lowStock->count() . " items): " . $lowStock->pluck('name')->join(', ');
                } else {
                    $report .= "✅ All products have healthy stock levels.";
                }
                return $report;

            default:
                return "Unknown tool: {$name}";
        }
    }

    // ─── WHISPER TRANSCRIPTION ───────────────────────────
    public function transcribe(Request $request)
    {
        $file = $request->file('audio');

        if (!$file) {
            return response()->json(['error' => 'No audio file received.'], 422);
        }

        // Send to Groq Whisper
        $response = Http::withToken($this->groqKey)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: 'voice.webm')
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model'           => 'whisper-large-v3',
                'response_format' => 'verbose_json', // returns language too
                'temperature'     => 0,
            ]);

        $data = $response->json();

        if (!$response->ok() || isset($data['error'])) {
            return response()->json([
                'error' => $data['error']['message'] ?? 'Whisper transcription failed.'
            ], 500);
        }

        return response()->json([
            'text'     => $data['text']     ?? '',
            'language' => $data['language'] ?? 'en',
        ]);
    }

    // ─── AGENT LOOP ──────────────────────────────────────
    public function chat(Request $request)
    {
        $userMessage = $request->input('message');
        $history     = $request->input('history', []);
        $language    = $request->input('language', 'en'); // 'en' or 'fr'

        $langInstruction = $language === 'fr'
            ? 'The user is speaking French. Always respond in French. Understand French product names, prices, and quantities correctly.'
            : 'The user is speaking English. Always respond in English.';

        $messages = [
            [
                'role'    => 'system',
                'content' => <<<PROMPT
You are a smart, helpful, and friendly inventory management assistant. {$langInstruction}

IMPORTANT BEHAVIOR RULES:
1. If the user's message is ambiguous or incomplete (e.g. "add a product" with no details), DO NOT guess. Instead, ask a specific clarifying question to get the missing information. Ask ONE question at a time.
2. Always use a tool to answer questions — never guess or make up data from memory.
3. After using a tool, explain the result clearly and naturally in plain language.
4. You have memory of this conversation — refer back to previously mentioned products or actions when relevant.
5. For bulk operations, use the bulk tools — they're more efficient.
6. Be concise but friendly. Use emojis sparingly to make responses feel warm.
7. If the user seems to be describing multiple products at once, use bulk_create_products.
8. For smart queries like "what's running low?" always use find_low_stock_products.
9. Understand informal language: "dirt cheap" = cheapest, "running dry" = low stock, "pricey stuff" = expensive products.
PROMPT
            ],
            ...$history,
            ['role' => 'user', 'content' => $userMessage]
        ];

        for ($i = 0; $i < 8; $i++) {
            $response = Http::withToken($this->groqKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'tools'       => $this->getTools(),
                    'tool_choice' => 'auto',
                    'max_tokens'  => 800,
                ]);

            $data = $response->json();

            if (!isset($data['choices'][0])) {
                return response()->json([
                    'reply'   => $language === 'fr'
                        ? "Je n'ai pas compris. Pouvez-vous reformuler?"
                        : "I didn't understand that. Could you rephrase?",
                    'history' => $history
                ]);
            }

            $choice  = $data['choices'][0];
            $message = $choice['message'];

            $messages[] = $message;

            if ($choice['finish_reason'] !== 'tool_calls') {
                return response()->json([
                    'reply'   => $message['content'],
                    'history' => array_slice($messages, 1) // strip system prompt from saved history
                ]);
            }

            foreach ($message['tool_calls'] as $toolCall) {
                $toolName   = $toolCall['function']['name'];
                $toolArgs   = json_decode($toolCall['function']['arguments'], true) ?? [];
                $toolResult = $this->executeTool($toolName, $toolArgs);

                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content'      => $toolResult,
                ];
            }
        }

        return response()->json([
            'reply'   => $language === 'fr'
                ? "Je n'ai pas pu terminer cette requête. Veuillez réessayer."
                : 'I could not complete that request. Please try again.',
            'history' => $history
        ]);
    }
}