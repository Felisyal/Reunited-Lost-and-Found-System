<?php
require_once __DIR__ . '/config/api_config.php';

function generateEmbedding($description) {
    $apiKey = OPENAI_API_KEY;

    $data = [
        "model" => "text-embedding-3-small",
        "input" => $description
    ];

    $ch = curl_init("https://api.openai.com/v1/embeddings");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($data),

        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Embedding curl error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        error_log("Embedding API HTTP error: " . $httpCode);
        error_log(print_r($result, true));
        return null;
    }

    if (isset($result["data"][0]["embedding"])) {
        return $result["data"][0]["embedding"];
    }

    error_log("Embedding API error: " . print_r($result, true));

    return null;
}

function cosineSimilarity($vecA, $vecB) {
    $dot = 0; $normA = 0; $normB = 0;
    foreach ($vecA as $i => $val) {
        $dot += $val * $vecB[$i];
        $normA += $val * $val;
        $normB += $vecB[$i] * $vecB[$i];
    }
    if ($normA == 0 || $normB == 0) return 0;
    return $dot / (sqrt($normA) * sqrt($normB));
}