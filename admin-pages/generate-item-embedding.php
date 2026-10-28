<?php
require_once dirname(__DIR__) . '/config/db.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../embeddings.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed'
    ]);
    exit;
}

// Pagsamahin ang item name at description para kasama ang pangalan sa embedding
function buildEmbeddingText($itemName, $description) {
    $parts = array_filter([trim($itemName ?? ''), trim($description ?? '')], 'strlen');
    return implode(' - ', $parts);
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
        SELECT item_name, descriptionLostItem
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

    // Item name + description, e.g. "Wallet - black leather with PUP ID"
    $text = buildEmbeddingText($result['item_name'], $result['descriptionLostItem']);

    if ($text === '') {
        echo json_encode([
            'success' => true,
            'message' => 'No item name or description to embed'
        ]);
        exit;
    }

    $embedding = generateEmbedding($text);

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
        SELECT itemName_found, descriptionFoundItem
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

    $text = buildEmbeddingText($result['itemName_found'], $result['descriptionFoundItem']);

    if ($text === '') {
        echo json_encode([
            'success' => true,
            'message' => 'No item name or description to embed'
        ]);
        exit;
    }

    $embedding = generateEmbedding($text);

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