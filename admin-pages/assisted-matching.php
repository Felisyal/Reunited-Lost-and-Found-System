<?php
session_start();

$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = mysqli_connect($host, $dbUser, $dbPass, $dbName);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$listSql = "
SELECT
    am.match_id,
    am.similarity_score,
    am.status AS match_status,
    l.item_name,
    l.locationLostItem,
    f.itemName_found,
    f.locationFoundItem,
    l.id AS lost_id,
    f.id AS found_id
FROM ai_matches am
INNER JOIN lost_items l ON am.lost_item_id = l.id
INNER JOIN found_items f ON am.found_item_id = f.id
WHERE am.status = 'Pending'
ORDER BY am.similarity_score DESC
";
$listResult = mysqli_query($conn, $listSql);
$selectedMatchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;

$detailSql = "
SELECT
    am.match_id,
    am.similarity_score,
    am.description_score,
    am.category_score,
    am.location_score,
    am.status AS match_status,
    am.created_at,

    l.id AS lost_id,
    l.user_id,
    l.item_name,
    l.descriptionLostItem,
    l.categoryLostItem,
    l.locationLostItem,
    l.last_seen_date,
    l.lostImage,

    f.id AS found_id,
    f.staff_employee_id,
    f.itemName_found,
    f.descriptionFoundItem,
    f.categoryFoundItem,
    f.locationFoundItem,
    f.dateFoundItem,
    f.foundImage

FROM ai_matches am
INNER JOIN lost_items l ON am.lost_item_id = l.id
INNER JOIN found_items f ON am.found_item_id = f.id
WHERE am.status = 'Pending'
";

if ($selectedMatchId) {
    $detailSql .= " AND am.match_id = " . $selectedMatchId;
} else {
    $detailSql .= " ORDER BY am.similarity_score DESC LIMIT 1";
}

$detailResult = mysqli_query($conn, $detailSql);
$selectedMatch = mysqli_fetch_assoc($detailResult);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
if ($isAjax) {
    include 'review-matches.php';
    exit;
}
?>

<div class="header-assist">
    <div class="column-one">
        <span class="dashboard_assist">AI Assisted Matching</span>
        <span class="sub_assist">
            The system compares report embeddings and ranks possible matches.You review <br>
            the evidence and make the final call — nothing is published or released automatically.
        </span>
    </div>
    <button type="button" id="generateMatchesBtn">
        Generate AI Matches
    </button>
</div>

<div class="assisted_table">
    <div class="assisted-body">
        <div class="queue-section">
            <div class="assisted-description">
                <span class="match-queue">Match Queue</span>
                <span class="sub-match">
                    Sorted by confidence — status shows where each match is in the
                    review lifecycle.
                </span>
            </div>
            <div class="queue-list">
                <?php if (mysqli_num_rows($listResult) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($listResult)) {
                        $activeClass = ($selectedMatch && $row['match_id'] == $selectedMatch['match_id']) ? 'active' : '';
                    ?>
                    <div class="queue-modal <?= $activeClass ?>" data-match-id="<?= $row['match_id'] ?>" style="cursor:pointer;">
                        <div class="queue-row-percent">
                            <span class="header-queue">
                                <?= htmlspecialchars($row['item_name']) ?>
                            </span>
                            <div class="progress-circle">
                                <div class="progress-inner">
                                    <?= round($row['similarity_score']) ?>%
                                </div>
                            </div>
                        </div>
                        <div class="queue-column">
                            <div class="queue-row">
                                <img src="image/play.png">
                                <span class="sub-match">
                                    <?= htmlspecialchars($row['itemName_found']) ?> -
                                </span>
                                <span class="sub-match">
                                    <?= htmlspecialchars($row['locationFoundItem']) ?>
                                </span>
                            </div>
                            <div class="queue-row">
                                <span class="sub-match">LOST#<?= $row['lost_id'] ?> -</span>
                                <span class="sub-match">FOUND#<?= $row['found_id'] ?></span>
                            </div>
                            <div class="status-pill">• <?= $row['match_status'] ?></div>
                        </div>
                    </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="queue-modal">
                        <div class="queue-column">
                            <span class="sub-match">No pending AI matches.</span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="match-section">
                <?php include 'review-matches.php'; ?>
        </div>
    </div>
</div>