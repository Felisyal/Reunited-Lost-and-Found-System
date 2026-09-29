<?php
require_once 'config/api_config.php';
require_once 'faqs.php';
/** @var array $faqs */

$conn = new mysqli("localhost", "root", "", "reunited_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($data['message'] ?? '');

$lower = strtolower($userMessage);

foreach($faqs as $faq){

    foreach($faq['keywords'] as $keyword){

        if(strpos($lower,$keyword)!==false){

            echo json_encode([
                "reply"=>$faq['answer'],
                "items"=>[]
            ]);

            exit;
        }
    }
}

$stopWords = ['i', 'my', 'a', 'an', 'the', 'have', 'has', 'is', 'was', 'are',
              'where', 'can', 'you', 'help', 'me', 'please', 'find', 'looking',
              'for', 'seen', 'left', 'missing', 'it', 'think', 'maybe', 'about',
              'somewhere', 'around', 'ago', 'yesterday', 'today', 'forgot', 'of',
              'do', 'did', 'any', 'some', 'that', 'this', 'with', 'and', 'or',
              'in', 'at', 'on', 'to', 'hi', 'hello', 'hey', 'po', 'ako', 'ko',
              'ang', 'ng', 'sa', 'na', 'yung', 'ung', 'may', 'kung', 'sino',
              'lost', 'found', 'important', 'things', 'problem', 'need', 'want',
              'know', 'think', 'from', 'what', 'when', 'how', 'who', 'why'];

$words = preg_split('/\s+/', strtolower($userMessage));
$keywords = array_values(array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords)));

$matchedItems = [];

if (!empty($keywords)) {
    $clauses = array_map(fn($kw) => 'LOWER(itemName_found) LIKE ?', $keywords);
    $foundConditions = implode(' OR ', $clauses);

    $clauses2 = array_map(fn($kw) => 'LOWER(item_name) LIKE ?', $keywords);
    $lostConditions = implode(' OR ', $clauses2);

    $params = [];
    $types  = '';
    foreach ($keywords as $kw) {
        $params[] = '%' . $kw . '%';
        $types   .= 's';
    }
    $stmt = $conn->prepare("
        SELECT itemName_found AS item_name,
               descriptionFoundItem AS description,
               categoryFoundItem AS category,
               locationFoundItem AS location,
               dateFoundItem AS item_date,
               status,
               foundImage AS image,
               'found' AS item_type,
               expiration_date
        FROM found_items
        WHERE $foundConditions
        LIMIT 10
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $foundResult = $stmt->get_result();
    while ($row = $foundResult->fetch_assoc()) {
        $matchedItems[] = [
            'name'            => $row['item_name'],
            'description'     => $row['description'],
            'category'        => $row['category'],
            'location'        => $row['location'],
            'date'            => $row['item_date'],
            'status'          => $row['status'],
            'image'           => 'Reunited/staff-pages/uploads/' . $row['image'],
            'type'            => 'found',
            'expiration_date' => $row['expiration_date']
        ];
    }
    $stmt2 = $conn->prepare("
        SELECT item_name,
               descriptionLostItem AS description,
               categoryLostItem AS category,
               locationLostItem AS location,
               last_seen_date AS item_date,
               status,
               lostImage AS image,
               'lost' AS item_type
        FROM lost_items
        WHERE $lostConditions
        LIMIT 10
    ");
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $lostResult = $stmt2->get_result();
    while ($row = $lostResult->fetch_assoc()) {
        $matchedItems[] = [
            'name'        => $row['item_name'],
            'description' => $row['description'],
            'category'    => $row['category'],
            'location'    => $row['location'],
            'date'        => $row['item_date'],
            'status'      => $row['status'],
            'image'       => 'Reunited/student-pages/uploads/' . $row['image'],
            'type'        => 'lost'
        ];
    }
}

$itemContext = '';
if (!empty($matchedItems)) {
    $itemContext = "Here are items from the database that match the user's message:\n";
    foreach ($matchedItems as $item) {
        $type = isset($item['type']) ? ucfirst($item['type']) : 'Item';
        $itemContext .= "- [{$type}] {$item['name']} ({$item['category']}), location: {$item['location']}, status: {$item['status']}\n";
    }
} else {
    $itemContext = "No matching items found in the database.";
}

$instructions = <<<PROMPT
You are the Reunited Assistant for a Polytechnic University of the Philippines-Parañaque lost-and-found system.

RULES:
- If matching items are provided, IMMEDIATELY share them. Do NOT ask for more details.
- Only ask for more info if NO matching items were found.
- Never ask for color, brand, or description if you already have a match.
- Only answer lost and found related questions.
- Politely decline unrelated questions.
- Be short, friendly, and empathetic.

DATABASE RESULTS:
$itemContext
PROMPT;

$payload = [
    'model' => 'gpt-4.1-mini',
    'instructions' => $instructions,
    'input' => $userMessage,
    'max_output_tokens' => 200
];

$ch = curl_init('https://api.openai.com/v1/responses');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY
]);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        'reply' => 'Sorry, there was an error connecting to the AI service.',
        'items' => $matchedItems
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$decoded = json_decode($response, true);

$reply = 'Sorry, I could not respond.'; // default fallback

if (isset($decoded['output']) && is_array($decoded['output'])) {
    foreach ($decoded['output'] as $outputItem) {
        if (($outputItem['type'] ?? '') === 'message' && isset($outputItem['content'])) {
            foreach ($outputItem['content'] as $contentItem) {
                if (($contentItem['type'] ?? '') === 'output_text') {
                    $reply = $contentItem['text'];
                    break 2;
                }
            }
        }
    }
}

echo json_encode([
    'reply' => $reply,
    'items' => $matchedItems
]);