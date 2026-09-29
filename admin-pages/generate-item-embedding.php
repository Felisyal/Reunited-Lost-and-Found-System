<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../embeddings.php';

$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed'
    ]);
    exit;
}

$id   = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;

if (!$id || !$type) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing parameters'
    ]);
    exit;
}

if ($type === 'lost') {

    $stmt = $conn->prepare("
        SELECT descriptionLostItem
        FROM lost_items
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Lost item not found'
        ]);
        exit;
    }

    $description = trim($result['descriptionLostItem'] ?? '');

    if ($description === '') {
        echo json_encode([
            'success' => true,
            'message' => 'No description to embed'
        ]);
        exit;
    }

    $embedding = generateEmbedding($description);

    if (!$embedding) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate embedding'
        ]);
        exit;
    }

    $embeddingJson = json_encode($embedding);

    $update = $conn->prepare("
        UPDATE lost_items
        SET embedding = ?
        WHERE id = ?
    ");

    $update->bind_param("si", $embeddingJson, $id);

    if (!$update->execute()) {
        echo json_encode([
            'success' => false,
            'error' => $update->error
        ]);
        exit;
    }

}


elseif ($type === 'found') {

    $stmt = $conn->prepare("
        SELECT descriptionFoundItem
        FROM found_items
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Found item not found'
        ]);
        exit;
    }

    $description = trim($result['descriptionFoundItem'] ?? '');

    if ($description === '') {
        echo json_encode([
            'success' => true,
            'message' => 'No description to embed'
        ]);
        exit;
    }

    $embedding = generateEmbedding($description);

    if (!$embedding) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate embedding'
        ]);
        exit;
    }

    $embeddingJson = json_encode($embedding);

    $update = $conn->prepare("
        UPDATE found_items
        SET embedding = ?
        WHERE id = ?
    ");

    $update->bind_param("si", $embeddingJson, $id);

    if (!$update->execute()) {
        echo json_encode([
            'success' => false,
            'error' => $update->error
        ]);
        exit;
    }

}

else {

    echo json_encode([
        'success' => false,
        'error' => 'Invalid type'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Embedding generated successfully'
]);

$conn->close();
?>
