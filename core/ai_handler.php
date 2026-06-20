<?php
require_once 'session.php';

// Only Admin/Staff should access the AI writer to prevent API quota abuse
if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'warden') {
    echo json_encode(['text' => 'Access Denied.']);
    exit;
}

header('Content-Type: application/json');

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$userPrompt = $input['prompt'] ?? '';

if (empty($userPrompt)) {
    echo json_encode(['text' => 'Prompt cannot be empty.']);
    exit;
}

// --- CONFIGURATION ---
$apiKey = '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'; // <--- Paste your key here

/**
 * Function to call OpenRouter API with a specific model
 */
function callAI($model, $prompt, $key) {
    $apiEndpoint = "https://openrouter.ai/api/v1/chat/completions";
    
    // Updated Persona & Rules based on your request
    $systemInstruction = "You are a professional AI content writer and an intelligent assistant for a Hostel Management System. " .
                         "You specialize in writing notices, generating emails, complaint messages, " .
                         "student-related content, and formal applications. " .
                         "Rules: " .
                         "1. Keep responses relevant to hostel or academic context. " .
                         "2. Use formal tone unless user asks otherwise. " .
                         "3. Keep answers clear and structured. " .
                         "4. Do not generate random or unrelated content. " .
                         "5. Be concise and keep responses brief (short and to the point). " .
                         "6. Respond in the language of the prompt but maintain professional quality.";

    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => $systemInstruction],
            ["role" => "user", "content" => "Please write content for: " . $prompt]
        ]
    ];

    $ch = curl_init($apiEndpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'HTTP-Referer: http://localhost/Universityhostel',
        'X-Title: University Hostel Management'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// List of models to try in order (Failover mechanism)
$modelsToTry = [
    "mistralai/mistral-7b-instruct:free",    // Option 1: Most reliable for current traffic
    "google/gemini-flash-1.5-8b:free",       // Option 2: Fast fallback
    "meta-llama/llama-3.1-8b-instruct:free", // Option 3: Fallback
    "openai/gpt-3.5-turbo"                   // Option 4: Your suggested model (if credits available)
];

$finalOutput = null;
$lastError = "Could not connect to any AI endpoint.";

foreach ($modelsToTry as $model) {
    $result = callAI($model, $userPrompt, $apiKey);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $finalOutput = $result['choices'][0]['message']['content'];
        break; // Success! Exit loop.
    } elseif (isset($result['error']['message'])) {
        $lastError = $result['error']['message'];
        // If the error is "No endpoints found", the loop will automatically try the next model
    }
}

if ($finalOutput) {
    echo json_encode(['text' => $finalOutput]);
} else {
    echo json_encode(['text' => 'AI System busy. Error: ' . $lastError]);
}
exit;