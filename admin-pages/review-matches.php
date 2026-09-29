<?php
/** @var array|null $selectedMatch */
if($selectedMatch) ?>
<?php if($selectedMatch){ ?>
    <div class="match-status" id="matchStatusPanel">
        <div class="header-status">
            <span class="header-font-status">
                Review Match
            </span>
            <div class="queue-row">
                <span class="sub-match">
                    Reported <?= date("M d, Y", strtotime($selectedMatch['created_at'])) ?> -
                </span>
                <span class="sub-match">
                    <?= htmlspecialchars($selectedMatch['locationLostItem']) ?>
                </span>
            </div>
        </div>
        <hr>
        <div class="status-row">
            <div class="lost-report-status">
                <div class="column-inside">
                    <span class="sub-match">
                        LOST REPORT
                    </span>
                    <span class="report-status-font">
                        <?= htmlspecialchars($selectedMatch['item_name']) ?>
                    </span>
                </div>
                <div class="column-inside">
                    <div class="row-only">
                        <span class="sub-row-only">
                            Reported By
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['user_id']) ?>
                        </span>
                    </div>
                    <div class="row-only">
                        <span class="sub-row-only">
                            Location:
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['locationLostItem']) ?>
                        </span>
                    </div>
                    <div class="row-only">
                        <span class="sub-row-only">
                            Date:
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['last_seen_date']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="cost-similarity">
                <span class="top-line"></span>
                <span class="cost-font">
                    <?= round($selectedMatch['similarity_score']) ?>%
                </span>
                <span class="sub-match">
                    Similarity
                </span>
                <span class="top-line"></span>
            </div>
            <div class="found-report-status">
                <div class="column-inside">
                    <span class="sub-match">
                        FOUND REPORT
                    </span>
                    <span class="report-status-font">
                        <?= htmlspecialchars($selectedMatch['itemName_found']) ?>
                    </span>
                </div>
                <div class="column-inside">
                    <div class="row-only">
                        <span class="sub-row-only">
                            Reported By
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['staff_employee_id']) ?>
                        </span>
                    </div>
                    <div class="row-only">
                        <span class="sub-row-only">
                            Location:
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['locationFoundItem']) ?>
                        </span>
                    </div>
                    <div class="row-only">
                        <span class="sub-row-only">
                            Date:
                        </span>
                        <span class="sub-row-maroon">
                            <?= htmlspecialchars($selectedMatch['dateFoundItem']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="analysis-card">
            <h3>AI Similarity Analysis</h3>
            <div class="analysis-row">
                <span class="label">Overall Match</span>
                <div class="bar"><div class="fill" style="width:<?= round($selectedMatch['similarity_score']) ?>%"></div></div>
                <span class="value"><?= round($selectedMatch['similarity_score']) ?>%</span>
            </div>
            <div class="analysis-row">
                <span class="label">Description</span>
                <div class="bar"><div class="fill" style="width:<?= round($selectedMatch['description_score']) ?>%"></div></div>
                <span class="value"><?= round($selectedMatch['description_score']) ?>%</span>
            </div>
            <div class="analysis-row">
                <span class="label">Category</span>
                <div class="bar"><div class="fill" style="width:<?= round($selectedMatch['category_score']) ?>%"></div></div>
                <span class="value"><?= round($selectedMatch['category_score']) ?>%</span>
            </div>
            <div class="analysis-row">
                <span class="label">Location proximity</span>
                <div class="bar"><div class="fill" style="width:<?= round($selectedMatch['location_score']) ?>%"></div></div>
                <span class="value"><?= round($selectedMatch['location_score']) ?>%</span>
            </div>
        </div>
        <div class="ai-recommendation">
            <div class="recommend-line"></div>
            <div class="recommend-content">
                <h4>AI Recommendation</h4>
                <p>
                    This is a high-confidence semantic match. Descriptions,
                    category, and contextual details are strongly aligned.
                    Admin verification is still required before approval.
                </p>
            </div>
        </div>
        <div class="action-modal-status">
            <button class="rejected-modal" data-id="<?= $selectedMatch['match_id'] ?>">
                ✕ Reject Match
            </button>
            <button class="approval-modal" data-id="<?= $selectedMatch['match_id'] ?>">
                ✓ Approve Match
            </button>
        </div>
    </div>
<?php } else { ?>
    <div class="match-status" id="matchStatusPanel">
        <p>No Matches</p>
    </div>
<?php } ?>