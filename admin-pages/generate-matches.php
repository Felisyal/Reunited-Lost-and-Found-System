<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "reunited_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("DB Error");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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
$lostItems = mysqli_query($conn,"
    SELECT id, embedding, categoryLostItem, locationLostItem
    FROM lost_items WHERE embedding IS NOT NULL AND status = 'Approved'
");
$foundItems = mysqli_query($conn,"
    SELECT id, embedding, categoryFoundItem, locationFoundItem
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
        $overallScore = ($descScore * 0.6) + ($catScore * 0.25) + ($locScore * 0.15);
        echo "lost#{$lost['id']} vs found#{$found['id']} = overall " . round($overallScore,2) . "% | ";
        echo "description={$descScore}% category={$catScore}% location={$locScore}%<br>";
        if($overallScore >= 60){
            $lostId = (int)$lost['id'];
            $foundId = (int)$found['id'];
            $roundedOverall = round($overallScore, 2);
            $roundedDesc = round($descScore, 2);
            $check = mysqli_query($conn, "
                SELECT match_id FROM ai_matches
                WHERE lost_item_id = $lostId AND found_item_id = $foundId
            ");
            if(mysqli_num_rows($check) == 0){
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO ai_matches
                    (lost_item_id, found_item_id, similarity_score, description_score, category_score, location_score, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')
                ");
                mysqli_stmt_bind_param(
                    $stmt, "iidddd",
                    $lostId, $foundId, $roundedOverall, $roundedDesc, $catScore, $locScore
                );
                $stmt_result = mysqli_stmt_execute($stmt);
                echo $stmt_result 
                    ? "&nbsp;&nbsp;→ INSERTED successfully!<br>" 
                    : "&nbsp;&nbsp;→ INSERT FAILED: " . mysqli_stmt_error($stmt) . "<br>";
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE ai_matches
                    SET similarity_score = ?, description_score = ?, category_score = ?, location_score = ?
                    WHERE lost_item_id = ? AND found_item_id = ?
                    AND status = 'Pending'
                ");
                mysqli_stmt_bind_param(
                    $stmt, "ddddii",
                    $roundedOverall, $roundedDesc, $catScore, $locScore, $lostId, $foundId
                );
                $stmt_result = mysqli_stmt_execute($stmt);
                echo $stmt_result 
                    ? "&nbsp;&nbsp;→ UPDATED existing match!<br>" 
                    : "&nbsp;&nbsp;→ UPDATE FAILED: " . mysqli_stmt_error($stmt) . "<br>";
                mysqli_stmt_close($stmt);
            }
        } else {
            echo "&nbsp;&nbsp;→ BELOW threshold<br>";
        }
    }
}
echo "<br>AI Matching Completed!";
?>