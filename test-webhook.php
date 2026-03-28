<?php
require_once __DIR__ . '/config/config.php';
$db = Database::getInstance();

$waAccounts = $db->fetchAll("SELECT * FROM whatsapp_accounts LIMIT 1");
if (empty($waAccounts)) die("No accounts.");
$account = $waAccounts[0];

$flows = $db->fetchAll("SELECT * FROM chatbot_flows WHERE user_id = ?", [$account['user_id']]);

echo "USER: " . $account['user_id'] . "\n";
echo "PHONE: " . $account['phone_number_id'] . "\n";
echo "FLOWS: " . count($flows) . "\n";

// simulate
$payload = [
    'entry' => [
        [
            'changes' => [
                [
                    'value' => [
                        'metadata' => [
                            'phone_number_id' => $account['phone_number_id']
                        ],
                        'messages' => [
                            [
                                'from' => '919000000000',
                                'id' => 'wamid',
                                'type' => 'text',
                                'text' => ['body' => 'hi']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init('http://localhost/wapi/api/webhook.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) echo "CURL ERR: " . curl_error($ch) . "\n";
curl_close($ch);

echo "HTTP CODE: $http\n";
echo "RESPONDED: \n";
echo $resp;
