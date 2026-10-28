<?php

require_once dirname(__DIR__) . '/config/db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) die("DB Error");

require_once dirname(__DIR__) . '/config/audit.php';

function cosineSimilarity($a, $b){
    $dot = 0; $normA = 0; $normB = 0;
    $len = min(count($a), count($b));

    for($i=0; $i<$len; $i++){
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }

    if($normA == 0 || $normB == 0) return 0;

    return ($dot / (sqrt($normA) * sqrt($normB))) * 100;
}

function categoryScore($catLost, $catFound){
    $catLost = strtolower(trim($catLost));
    $catFound = strtolower(trim($catFound));
    return ($catLost === $catFound) ? 100 : 0;
}

function locationScore($locLost, $locFound){
    $locLost = strtolower(trim($locLost));
    $locFound = strtolower(trim($locFound));

    if($locLost === $locFound) return 100;

    similar_text($locLost, $locFound, $percent);
    return round($percent, 2);
}

function nameScore($nameLost, $nameFound){
    $nameLost = strtolower(trim($nameLost));
    $nameFound = strtolower(trim($nameFound));

    if($nameLost === $nameFound) return 100;

    // Word matching: "wallet" vs "black leather wallet" counts as a strong match
    $wordsLost  = array_unique(preg_split('/[^a-z0-9]+/', $nameLost, -1, PREG_SPLIT_NO_EMPTY));
    $wordsFound = array_unique(preg_split('/[^a-z0-9]+/', $nameFound, -1, PREG_SPLIT_NO_EMPTY));

    $wordPercent = 0;
    if($wordsLost && $wordsFound){
        $common = count(array_intersect($wordsLost, $wordsFound));
        $wordPercent = ($common / min(count($wordsLost), count($wordsFound))) * 90;
    }

    // Letter matching still catches typos like "walet" vs "wallet"
    similar_text($nameLost, $nameFound, $letterPercent);

    return round(max($wordPercent, $letterPercent), 2);
}

$inserted = 0;
$updated  = 0;
$outdated = 0;

$lostItems = mysqli_query($conn,"
    SELECT id, item_name, embedding, categoryLostItem, locationLostItem
    FROM lost_items WHERE embedding IS NOT NULL AND status = 'Approved'
");

$foundItems = mysqli_query($conn,"
    SELECT id, itemName_found, embedding, categoryFoundItem, locationFoundItem
    FROM found_items WHERE embedding IS NOT NULL AND status = 'Approved'
");

while($lost = mysqli_fetch_assoc($lostItems)){

    mysqli_data_seek($foundItems, 0);

    while($found = mysqli_fetch_assoc($foundItems)){

        $v1 = json_decode($lost['embedding'], true);
        $v2 = json_decode($found['embedding'], true);

        if(!$v1 || !$v2){
            echo "SKIPPED - invalid embedding<br>";
            continue;
        }

        $descScore = cosineSimilarity($v1, $v2);
        $catScore  = categoryScore($lost['categoryLostItem'], $found['categoryFoundItem']);
        $locScore  = locationScore($lost['locationLostItem'], $found['locationFoundItem']);
        $nameScr   = nameScore($lost['item_name'], $found['itemName_found']);

        // description 45% + item name 20% + category 20% + location 15%
        $overallScore = ($descScore * 0.45) + ($nameScr * 0.20) + ($catScore * 0.20) + ($locScore * 0.15);

        echo "lost#{$lost['id']} vs found#{$found['id']} = overall " . round($overallScore,2) . "% | ";
        echo "description={$descScore}% name={$nameScr}% category={$catScore}% location={$locScore}%<br>";

        if($overallScore >= 70){

            $lostId = (int)$lost['id'];
            $foundId = (int)$found['id'];
            $roundedOverall = round($overallScore, 2);
            $roundedDesc = round($descScore, 2);
            $roundedName = round($nameScr, 2);

            $check = mysqli_query($conn, "
                SELECT match_id FROM ai_matches
                WHERE lost_item_id = $lostId AND found_item_id = $foundId
            ");

            if(mysqli_num_rows($check) == 0){

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO ai_matches
                    (lost_item_id, found_item_id, similarity_score, description_score, name_score, category_score, location_score, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");

                mysqli_stmt_bind_param(
                    $stmt, "iiddddd",
                    $lostId, $foundId, $roundedOverall, $roundedDesc, $roundedName, $catScore, $locScore
                );

                $stmt_result = mysqli_stmt_execute($stmt);

                if ($stmt_result) $inserted++;

                echo $stmt_result
                    ? "&nbsp;&nbsp;→ INSERTED successfully!<br>"
                    : "&nbsp;&nbsp;→ INSERT FAILED: " . mysqli_stmt_error($stmt) . "<br>";

                mysqli_stmt_close($stmt);

            } else {

                $stmt = mysqli_prepare($conn, "
                    UPDATE ai_matches
                    SET similarity_score = ?, description_score = ?, name_score = ?, category_score = ?, location_score = ?,
                        status = 'Pending'
                    WHERE lost_item_id = ? AND found_item_id = ?
                    AND status IN ('Pending', 'Outdated')
                ");

                mysqli_stmt_bind_param(
                    $stmt, "dddddii",
                    $roundedOverall, $roundedDesc, $roundedName, $catScore, $locScore, $lostId, $foundId
                );

                $stmt_result = mysqli_stmt_execute($stmt);

                if ($stmt_result) $updated++;

                echo $stmt_result
                    ? "&nbsp;&nbsp;→ UPDATED existing match!<br>"
                    : "&nbsp;&nbsp;→ UPDATE FAILED: " . mysqli_stmt_error($stmt) . "<br>";

                mysqli_stmt_close($stmt);
            }

        } else {

            // Score dropped below 80: mark any old pending match as Outdated
            $lostId = (int)$lost['id'];
            $foundId = (int)$found['id'];

            $stmt = mysqli_prepare($conn, "
                UPDATE ai_matches SET status = 'Outdated'
                WHERE lost_item_id = ? AND found_item_id = ?
                AND status = 'Pending'
            ");

            mysqli_stmt_bind_param($stmt, "ii", $lostId, $foundId);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $outdated++;
                echo "&nbsp;&nbsp;→ BELOW threshold, old match marked Outdated<br>";
            } else {
                echo "&nbsp;&nbsp;→ BELOW threshold<br>";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

logAudit($conn, 'AI_MATCHING_RUN', 'match', null, "new=$inserted, updated=$updated, outdated=$outdated");

echo "<br>AI Matching Completed!";

?>