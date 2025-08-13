<?php

$api_key = 'xai-KrwtGa2OYj7QC2sXNXSDFqjLKsk3Nus319ipGCsFhy45InvL2TpxboJzaymTc1aJgXKYFDYAWjeUaeaK';
$url = 'https://api.x.ai/v1/chat/completions';

$data = [
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are a test assistant.'
        ],
        [
            'role' => 'user',
            'content' => 'Testing. Just say hi and hello world and nothing else.'
        ]
    ],
    'model' => 'grok-4-latest',
    'stream' => false,
    'temperature' => 0
];

$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    $result = json_decode($response, true);
    print_r($result);
}

curl_close($ch);
