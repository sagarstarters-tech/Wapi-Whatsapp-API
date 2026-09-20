<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';
    require_once __DIR__ . '/../../classes/AIKnowledgeBase.php';
    require_once __DIR__ . '/../../helpers/functions.php';

    $db = Database::getInstance();
    $botId = 1;
    $bot = $db->fetch("SELECT * FROM ai_bots WHERE id = ?", [$botId]);
    if (!$bot) {
        throw new Exception("Bot $botId not found");
    }
    $userId = (int) $bot['user_id'];

    // Ensure KB exists
    $kb = $db->fetch("SELECT * FROM ai_knowledge_bases WHERE bot_id = ? LIMIT 1", [$botId]);
    if (!$kb) {
        $kbId = $db->insert('ai_knowledge_bases', [
            'bot_id' => $botId,
            'user_id' => $userId,
            'name' => 'Sagar Starters Knowledge Base',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $kbId = (int) $kb['id'];
    }

    $results = ['kb_id' => $kbId];

    // 1. Q&A Pairs to insert
    $qaPairs = [
        [
            'q' => '1 HP submersible pump motor starter buy link price model single phase 220v kharidna link',
            'a' => "1 HP Single Phase (220V) Submersible Motor ke liye Sagar Starters ke pass ye best models hain:
1. 1 HP Oil Field Automatic Submersible Pump Starter
   - Price: ₹1,600.00
   - Buy Link: https://www.sagarstarters.com/product/1-1-hp-oil-field-automatic-submersible-pump-starter
   - Features: 100% Genuine Copper Winding, Overload & Dry Run Auto Protection, 1-Year Replacement Warranty.

2. 1 HP Water Field Automatic Submersible Pump Starter
   - Price: ₹1,800.00
   - Buy Link: https://www.sagarstarters.com/product/1773369116-1-hp-water-field-automatic-submersible-pump-starte
   - Features: High efficiency, auto cut-off protection, heavy-duty contactor.

3. 1 HP Automatic Digital Submersible Pump Starter
   - Price: ₹2,250.00
   - Buy Link: https://www.sagarstarters.com/product/2-1-hp-automatic-digital-submersible-pump-starter
   - Features: Digital Voltmeter & Ammeter display, High & Low voltage protection, auto restart timer.

Sabhi starters par 1 saal ki replacement warranty milti hai aur pure Bharat me fast doorstep delivery uplabdh hai."
        ],
        [
            'q' => '2 HP submersible pump motor starter buy link price 1.5 HP kharidna link single phase',
            'a' => "2 HP aur 1.5 HP Single Phase Submersible Motor ke liye models:
1. 2 HP Automatic Submersible Pump Starter
   - Price: ₹3,050.00
   - Buy Link: https://www.sagarstarters.com/product/1773369466-2-hp-automatic-submersible-pump-starter

2. 2 HP Automatic Digital Submersible Pump Starter
   - Price: ₹3,200.00
   - Buy Link: https://www.sagarstarters.com/product/1785304026-2-hp-automatic-digital-submersible-pump-starter
   - Features: Digital screen, dry run & overload auto-cut.

3. 1.5 HP Automatic Submersible Pump Starter
   - Price: ₹2,350.00
   - Buy Link: https://www.sagarstarters.com/product/1772713796-1-5-hp-automatic-submersible-pump-starter"
        ],
        [
            'q' => '5 HP single phase submersible pump starter buy link price kharidna link',
            'a' => "5 HP Single Phase Submersible Motor ke liye:
5 HP Single Phase Submersible Pump Starter
- Price: ₹7,600.00
- Buy Link: https://www.sagarstarters.com/product/1787120077-5-hp-single-please-submersible-pump-starter-
- Features: Heavy duty contactor panel, high current protection, copper winding, 1 year warranty."
        ],
        [
            'q' => '3 Phase motor starter 3 HP 5 HP 7.5 HP 30 HP Star Delta buy link price kharidna 415v',
            'a' => "3 Phase Motor Starters (415V):
1. 3 HP 3 Phase Semi Automatic Motor Starter - ₹6,470.00
   - Buy Link: https://www.sagarstarters.com/product/1773370897-3-hp-3-phase-semi-automatic-motor-starter

2. 3 HP 3 Phase Automatic Motor Starter - ₹8,300.00
   - Buy Link: https://www.sagarstarters.com/product/1773370426-3-hp-3-phase-automatic-motor-starter

3. Up to 7.5 HP 3 Phase Semi Automatic Motor Starter - ₹6,570.00
   - Buy Link: https://www.sagarstarters.com/product/1773371765-up-to-7-5-hp-3-phase-semi-automatic-motor-starter

4. Up to 7.5 HP 3 Phase Automatic Motor Starter - ₹8,500.00
   - Buy Link: https://www.sagarstarters.com/product/1773371205-up-to-7-5-hp-3-phase-automatic-motor-starter

5. 30 HP Automatic Star Delta Motor Starter - ₹27,500.00
   - Buy Link: https://www.sagarstarters.com/product/1777614874-30-hp-automatic-star-delta-motor-starter

6. 3 Phase Motor SZ5 Automatic Switch - ₹491.00
   - Buy Link: https://www.sagarstarters.com/product/1787297700-3-phase-motor-sz5-automatic-switch"
        ],
        [
            'q' => 'product kharidne ka link do website link shop store online order kaise kare buy link purchase checkout',
            'a' => "Aap Sagar Starters ke sabhi products hamari official website se directly khareed sakte hain:
- Official Website: https://www.sagarstarters.com
- All Products Shop Catalog: https://www.sagarstarters.com/shop.php
- Single Phase Starters: https://www.sagarstarters.com/shop.php?category=11
- 3 Phase Starters: https://www.sagarstarters.com/shop.php?category=7
- Voltage Stabilizers: https://www.sagarstarters.com/shop.php?category=8
- Accessories & Parts: https://www.sagarstarters.com/shop.php?category=4

Order karne ke liye link par click karke 'Buy Now' ya 'Add to Cart' karein. Payment UPI, Debit Card, Credit Card, Net Banking ya Cash on Delivery (COD) dwara kar sakte hain."
        ],
        [
            'q' => 'Voltage Stabilizer 5 KVA 8 KVA price buy link kharidna',
            'a' => "Automatic Voltage Stabilizers (90V - 440V Wide Range):
1. 5 KVA Automatic Voltage Stabilizer - ₹14,500.00
   - Buy Link: https://www.sagarstarters.com/product/1773375208-5-kva-automatic-voltage-stabilizer
2. 8 KVA Automatic Voltage Stabilizer - ₹14,999.00
   - Buy Link: https://www.sagarstarters.com/product/1776498640-8-kva-automatic-voltage-stabilizer
Ghar aur shop ke appliances ko voltage fluctuations se bachane ke liye automatic high/low cutoff ke sath aate hain."
        ],
        [
            'q' => 'Submersible cable wire float switch meter parts circuit breaker switch buy link',
            'a' => "Electrical Accessories & Pump Parts:
- 2.5 sq mm 3 Core Submersible Cable - ₹2,700.00 | Link: https://www.sagarstarters.com/product/1787052372-2-5-sq-mm-3-core-submersible-cable
- Float Switch for Water Tank (2 Meter Wire) - ₹500.00 | Link: https://www.sagarstarters.com/product/1773379075-float-switch-for-water-tank-with-2-meter-wire
- Single Phase Motor Protection Device - ₹1,300.00 | Link: https://www.sagarstarters.com/product/1773379418-single-phase-motor-protection-device
- Digital Volt / Amps Meter (72x72mm) - ₹1,100.00 | Link: https://www.sagarstarters.com/product/1773378839-digital-volt-amps-meter
- 72mm Analog Volt / Amp Meter (Combo) - ₹550.00 | Link: https://www.sagarstarters.com/product/1773377429-72mm-analog-volt-amp-meter
- Safety Wire for Submersible Pump (30m) - ₹450.00 | Link: https://www.sagarstarters.com/product/1787051392-safety-wire-for-submersible-pump
- India Mark Hand Pump Cylinder - ₹2,200.00 | Link: https://www.sagarstarters.com/product/1787053509-india-mark-hand-pump-cylinder
- 20A 250VAC Circuit Breaker Switch TEKNIC - ₹240.00 | Link: https://www.sagarstarters.com/product/1786092777-20a-250vac-circuit-breakers-switch-teknic
- Single Pole 10A Vastav Anand Circuit Breaker - ₹70.00 | Link: https://www.sagarstarters.com/product/1786092233-single-pole-16a-teknic-circuit-breaker
- Vastav Anand GF-01 Push Button Switch Set - ₹150.00 | Link: https://www.sagarstarters.com/product/1786088002-vastav-anand-gf-01-push-button-switch-set
- BNO Push Button - ₹45.00 | Link: https://www.sagarstarters.com/product/1773377963-bno-push-button-pack-of-20-pcs-
- 30A 5 Way Bakelite Connector Strip - ₹80.00 | Link: https://www.sagarstarters.com/product/1773376871-30a-5-way-bakelite-connector-strip-open-type
- BCH Type Contactor Coil - ₹400.00 | Link: https://www.sagarstarters.com/product/1773376431-2-pole-bch-type-contactor-coil-pack-of-2-
- Red Colour Neon Light Bulb Indicator Lamp - ₹40.00 | Link: https://www.sagarstarters.com/product/1773378387-red-colour-neon-light-bulb-panel-indicator-lamp-pa"
        ],
        [
            'q' => '1 HP Apollo submersible motor pump set combo buy link price',
            'a' => "Submersible Motors & Pump Sets:
- 1 HP Apollo Submersible Motor - ₹8,500.00 | Link: https://www.sagarstarters.com/product/1774363370-1-hp-apollo-submersible-motor-
- 1 HP Submersible Pump Set Combo (Oil Field Single Phase 4 Inch) - ₹14,600.00 | Link: https://www.sagarstarters.com/product/1773372972-1-hp-oil-field-single-phase-4-inch-submersible-pum"
        ],
        [
            'q' => 'warranty guarantee delivery payment cod replacement return policy',
            'a' => "Sagar Starters Policies & Services:
- Warranty: Sabhi motor starters par 1-Year Replacement Warranty milti hai. 100% Genuine Copper Winding hoti hai.
- Delivery: Pan-India Fast & Insured Doorstep Delivery milti hai.
- Payment Options: UPI (PhonePe, Google Pay, Paytm), Debit & Credit Cards, Net Banking aur Cash on Delivery (COD) uplabdh hai.
- Website: https://www.sagarstarters.com"
        ]
    ];

    // Clear old QA and chunks for clean state if needed or append
    $insertedQA = 0;
    foreach ($qaPairs as $pair) {
        $existing = $db->fetch("SELECT id FROM ai_kb_qa_pairs WHERE kb_id = ? AND question = ? LIMIT 1", [$kbId, $pair['q']]);
        if (!$existing) {
            $qaId = $db->insert('ai_kb_qa_pairs', [
                'kb_id' => $kbId,
                'user_id' => $userId,
                'question' => $pair['q'],
                'answer' => $pair['a'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $chunkText = "Question: {$pair['q']}\nAnswer: {$pair['a']}";
            $db->insert('ai_kb_chunks', [
                'kb_id' => $kbId,
                'source_type' => 'qa',
                'source_id' => $qaId,
                'content' => $chunkText,
                'word_count' => str_word_count($chunkText),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $insertedQA++;
        }
    }
    $results['inserted_qa'] = $insertedQA;

    // 2. Comprehensive Catalog Document Chunk
    $catalogDoc = "SAGAR STARTERS - COMPLETE OFFICIAL PRODUCT CATALOG & PURCHASE LINKS\n";
    $catalogDoc .= "Official Website: https://www.sagarstarters.com\n";
    $catalogDoc .= "Shop All Products: https://www.sagarstarters.com/shop.php\n\n";

    $catalogDoc .= "=== SINGLE PHASE SUBMERSIBLE PUMP STARTERS (220V) ===\n";
    $catalogDoc .= "1. 1 HP Oil Field Automatic Submersible Pump Starter\n   Price: ₹1,600.00\n   URL: https://www.sagarstarters.com/product/1-1-hp-oil-field-automatic-submersible-pump-starter\n   Specs: Heavy-duty 1 HP oil field automatic starter, overload & dry run protection, 100% copper.\n\n";
    $catalogDoc .= "2. 1 HP Water Field Automatic Submersible Pump Starter\n   Price: ₹1,800.00\n   URL: https://www.sagarstarters.com/product/1773369116-1-hp-water-field-automatic-submersible-pump-starte\n   Specs: Water field automatic starter for borewell pumps, auto shutoff on fault.\n\n";
    $catalogDoc .= "3. 1 HP Automatic Digital Submersible Pump Starter\n   Price: ₹2,250.00\n   URL: https://www.sagarstarters.com/product/2-1-hp-automatic-digital-submersible-pump-starter\n   Specs: Digital display volt/amp meter, auto-cut protection, dry run and overload safety.\n\n";
    $catalogDoc .= "4. 1.5 HP Automatic Submersible Pump Starter\n   Price: ₹2,350.00\n   URL: https://www.sagarstarters.com/product/1772713796-1-5-hp-automatic-submersible-pump-starter\n   Specs: Water field 1.5 HP automatic starter for submersible pumps.\n\n";
    $catalogDoc .= "5. 2 HP Automatic Submersible Pump Starter\n   Price: ₹3,050.00\n   URL: https://www.sagarstarters.com/product/1773369466-2-hp-automatic-submersible-pump-starter\n   Specs: 2 HP automatic motor starter with overload relay.\n\n";
    $catalogDoc .= "6. 2 HP Automatic Digital Submersible Pump Starter\n   Price: ₹3,200.00\n   URL: https://www.sagarstarters.com/product/1785304026-2-hp-automatic-digital-submersible-pump-starter\n   Specs: 2 HP digital motor starter with auto cutoff and digital display screen.\n\n";
    $catalogDoc .= "7. 5 HP Single Phase Submersible Pump Starter\n   Price: ₹7,600.00\n   URL: https://www.sagarstarters.com/product/1787120077-5-hp-single-please-submersible-pump-starter-\n   Specs: 5 HP heavy duty single phase starter for agricultural and domestic high power pumps.\n\n";

    $catalogDoc .= "=== THREE PHASE MOTOR STARTERS (415V) ===\n";
    $catalogDoc .= "1. 3 HP 3 Phase Semi Automatic Motor Starter\n   Price: ₹6,470.00\n   URL: https://www.sagarstarters.com/product/1773370897-3-hp-3-phase-semi-automatic-motor-starter\n\n";
    $catalogDoc .= "2. 3 HP 3 Phase Automatic Motor Starter\n   Price: ₹8,300.00\n   URL: https://www.sagarstarters.com/product/1773370426-3-hp-3-phase-automatic-motor-starter\n\n";
    $catalogDoc .= "3. Up to 7.5 HP 3 Phase Semi Automatic Motor Starter\n   Price: ₹6,570.00\n   URL: https://www.sagarstarters.com/product/1773371765-up-to-7-5-hp-3-phase-semi-automatic-motor-starter\n\n";
    $catalogDoc .= "4. Up to 7.5 HP 3 Phase Automatic Motor Starter\n   Price: ₹8,500.00\n   URL: https://www.sagarstarters.com/product/1773371205-up-to-7-5-hp-3-phase-automatic-motor-starter\n\n";
    $catalogDoc .= "5. 30 HP Automatic Star Delta Motor Starter\n   Price: ₹27,500.00\n   URL: https://www.sagarstarters.com/product/1777614874-30-hp-automatic-star-delta-motor-starter\n\n";
    $catalogDoc .= "6. 3 Phase Motor SZ5 Automatic Switch\n   Price: ₹491.00\n   URL: https://www.sagarstarters.com/product/1787297700-3-phase-motor-sz5-automatic-switch\n\n";

    $catalogDoc .= "=== AUTOMATIC VOLTAGE STABILIZERS ===\n";
    $catalogDoc .= "1. 5 KVA Automatic Voltage Stabilizer (90V-440V)\n   Price: ₹14,500.00\n   URL: https://www.sagarstarters.com/product/1773375208-5-kva-automatic-voltage-stabilizer\n\n";
    $catalogDoc .= "2. 8 KVA Automatic Voltage Stabilizer (90V-440V)\n   Price: ₹14,999.00\n   URL: https://www.sagarstarters.com/product/1776498640-8-kva-automatic-voltage-stabilizer\n\n";

    $catalogDoc .= "=== ELECTRICAL ACCESSORIES & PARTS ===\n";
    $catalogDoc .= "- 2.5 sq mm 3 Core Submersible Cable: ₹2,700.00 | URL: https://www.sagarstarters.com/product/1787052372-2-5-sq-mm-3-core-submersible-cable\n";
    $catalogDoc .= "- Float Switch for Water Tank (2 Meter): ₹500.00 | URL: https://www.sagarstarters.com/product/1773379075-float-switch-for-water-tank-with-2-meter-wire\n";
    $catalogDoc .= "- Single Phase Motor Protection Device: ₹1,300.00 | URL: https://www.sagarstarters.com/product/1773379418-single-phase-motor-protection-device\n";
    $catalogDoc .= "- Digital Volt / Amps Meter: ₹1,100.00 | URL: https://www.sagarstarters.com/product/1773378839-digital-volt-amps-meter\n";
    $catalogDoc .= "- 72mm Analog Volt / Amp Meter (Combo): ₹550.00 | URL: https://www.sagarstarters.com/product/1773377429-72mm-analog-volt-amp-meter\n";
    $catalogDoc .= "- Safety Wire for Submersible Pump: ₹450.00 | URL: https://www.sagarstarters.com/product/1787051392-safety-wire-for-submersible-pump\n";
    $catalogDoc .= "- India Mark Hand Pump Cylinder: ₹2,200.00 | URL: https://www.sagarstarters.com/product/1787053509-india-mark-hand-pump-cylinder\n";
    $catalogDoc .= "- 20A 250VAC Circuit Breaker Switch TEKNIC: ₹240.00 | URL: https://www.sagarstarters.com/product/1786092777-20a-250vac-circuit-breakers-switch-teknic\n";
    $catalogDoc .= "- Single Pole 10A Vastav Anand Circuit Breaker: ₹70.00 | URL: https://www.sagarstarters.com/product/1786092233-single-pole-16a-teknic-circuit-breaker\n";
    $catalogDoc .= "- Vastav Anand GF-01 Push Button Switch Set: ₹150.00 | URL: https://www.sagarstarters.com/product/1786088002-vastav-anand-gf-01-push-button-switch-set\n";
    $catalogDoc .= "- BNO Push Button: ₹45.00 | URL: https://www.sagarstarters.com/product/1773377963-bno-push-button-pack-of-20-pcs-\n";
    $catalogDoc .= "- 30A 5 Way Bakelite Connector Strip: ₹80.00 | URL: https://www.sagarstarters.com/product/1773376871-30a-5-way-bakelite-connector-strip-open-type\n";
    $catalogDoc .= "- BCH Type Contactor Coil: ₹400.00 | URL: https://www.sagarstarters.com/product/1773376431-2-pole-bch-type-contactor-coil-pack-of-2-\n";
    $catalogDoc .= "- Red Colour Neon Light Bulb Indicator: ₹40.00 | URL: https://www.sagarstarters.com/product/1773378387-red-colour-neon-light-bulb-panel-indicator-lamp-pa\n";
    $catalogDoc .= "- 1 HP Apollo Submersible Motor: ₹8,500.00 | URL: https://www.sagarstarters.com/product/1774363370-1-hp-apollo-submersible-motor-\n";
    $catalogDoc .= "- 1 HP Submersible Pump Set (Combo): ₹14,600.00 | URL: https://www.sagarstarters.com/product/1773372972-1-hp-oil-field-single-phase-4-inch-submersible-pum\n";

    // Insert as document manual chunk
    $docId = $db->insert('ai_kb_documents', [
        'kb_id' => $kbId,
        'user_id' => $userId,
        'file_name' => 'Sagar Starters Full Product Catalog & Buy Links',
        'file_path' => 'manual_catalog',
        'file_type' => 'txt',
        'file_size' => strlen($catalogDoc),
        'status' => 'completed',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $db->insert('ai_kb_chunks', [
        'kb_id' => $kbId,
        'source_type' => 'document',
        'source_id' => $docId,
        'content' => $catalogDoc,
        'word_count' => str_word_count($catalogDoc),
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 3. Update Bot System Prompt
    $newSystemPrompt = "You are the official AI Sales & Support Assistant for Sagar Starters (https://www.sagarstarters.com).

Your primary role is to assist customers with motor starters, submersible pump starters, voltage stabilizers, and electrical accessories, recommend products, answer technical questions, and share DIRECT PRODUCT PURCHASE LINKS.

CRITICAL RULES:
1. When a customer inquires about a product, wants to buy, or asks for a product link, ALWAYS provide the exact product URL (e.g. https://www.sagarstarters.com/product/...) or shop link (https://www.sagarstarters.com/shop.php) from your Knowledge Base so the customer can directly click and buy online.
2. NEVER invent fake links like 'google.com' or write placeholder text like '(यहाँ लिंक डालें)'. Only use real Sagar Starters links provided in the Knowledge Base.
3. Automatically match the customer's language (Hindi, Hinglish, English, etc.) and reply in the same language.
4. Explain product features clearly: 100% genuine copper winding, 1-year replacement warranty, overload and dry run protection, fast pan-India delivery, and payment options (UPI, Card, Net Banking, COD).
5. Keep WhatsApp replies well-formatted, polite, concise, and helpful with bullet points and bold text where appropriate.";

    $db->update('ai_bots', [
        'system_prompt' => $newSystemPrompt,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$botId]);

    $results['system_prompt_updated'] = true;
    $results['total_chunks'] = $db->fetchColumn("SELECT COUNT(*) FROM ai_kb_chunks WHERE kb_id = ?", [$kbId]);

    echo json_encode(['status' => 'success', 'data' => $results], JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo json_encode(['status' => 'error', 'message' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
