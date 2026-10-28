<?php
require_once dirname(__DIR__) . '/config/db.php';
session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(403); exit('Unauthorized'); }
date_default_timezone_set('Asia/Manila');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { exit('Database error'); }

function rows($conn, $sql) {
    try { $r = $conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; }
    catch (Throwable $e) { error_log($e->getMessage()); return []; }
}
function statusMap($conn, $table) {
    $m = [];
    foreach (rows($conn, "SELECT status, COUNT(*) c FROM $table GROUP BY status") as $r) {
        $m[strtolower(trim($r['status']))] = (int)$r['c'];
    }
    return $m;
}

$lost   = statusMap($conn, 'lost_items_reports');
$found  = statusMap($conn, 'found_items_reports');
$claims = statusMap($conn, 'claims_table');

$totalLost  = array_sum($lost);
$totalFound = array_sum($found);
$pending    = ($lost['pending'] ?? 0) + ($found['pending'] ?? 0);
$claimsDone = ($lost['claimed'] ?? 0) + ($found['claimed'] ?? 0);
$lostValid  = $totalLost - ($lost['rejected'] ?? 0);
$recovery   = $lostValid > 0 ? round((($lost['claimed'] ?? 0) / $lostValid) * 100, 1) . '%' : 'N/A';

// AI matching metrics
// Acceptance rate = Approved / (Approved + Rejected). Pending matches are excluded
// because the admin has not judged them yet.
$matchRate = 'N/A';
$matchSub  = 'No matches reviewed yet';
$genRate   = 'N/A';
$genSub    = 'No eligible lost items';

if (rows($conn, "SHOW TABLES LIKE 'ai_matches'")) {
    $m        = statusMap($conn, 'ai_matches');
    $approved = $m['approved'] ?? 0;
    $rejected = $m['rejected'] ?? 0;
    $reviewed = $approved + $rejected;

    if ($reviewed > 0) {
        $matchRate = round(($approved / $reviewed) * 100, 1) . '%';
        $matchSub  = "$approved of $reviewed reviewed";
    }

    // Generation coverage = lost items with at least one candidate / lost items that were matchable
    $g = rows($conn, "SELECT
        (SELECT COUNT(DISTINCT lost_item_id) FROM ai_matches WHERE status <> 'Outdated') AS with_matches,
        (SELECT COUNT(*) FROM lost_items WHERE embedding IS NOT NULL) AS eligible");
    $withMatches = (int)($g[0]['with_matches'] ?? 0);
    $eligible    = (int)($g[0]['eligible'] ?? 0);
    if ($eligible > 0) {
        $genRate = round(($withMatches / $eligible) * 100, 1) . '%';
        $genSub  = "$withMatches of $eligible lost items";
    }
}

// Monthly trend (huling 6 buwan)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[date('Y-m', strtotime("first day of -$i months"))] = ['lost' => 0, 'found' => 0];
}
$start = array_key_first($months) . '-01';
foreach (rows($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM lost_items_reports WHERE created_at >= '$start' GROUP BY m") as $r) {
    if (isset($months[$r['m']])) $months[$r['m']]['lost'] = (int)$r['c'];
}
foreach (rows($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM found_items_reports WHERE created_at >= '$start' GROUP BY m") as $r) {
    if (isset($months[$r['m']])) $months[$r['m']]['found'] = (int)$r['c'];
}

// Top categories at locations (lost + found, hindi kasama ang Rejected)
$cats = rows($conn, "SELECT category, COUNT(*) c FROM (
    SELECT categoryLostItem AS category FROM lost_items_reports WHERE status <> 'Rejected'
    UNION ALL
    SELECT categoryFoundItem FROM found_items_reports WHERE status <> 'Rejected'
) t WHERE category IS NOT NULL AND category <> '' GROUP BY category ORDER BY c DESC LIMIT 5");

$locs = rows($conn, "SELECT location, COUNT(*) c FROM (
    SELECT locationLostItem AS location FROM lost_items_reports WHERE status <> 'Rejected'
    UNION ALL
    SELECT locationFoundItem FROM found_items_reports WHERE status <> 'Rejected'
) t WHERE location IS NOT NULL AND location <> '' GROUP BY location ORDER BY c DESC LIMIT 5");

// Totals (basehan ng percentage sa Top Categories / Top Locations)
$catTotal = (int)(rows($conn, "SELECT COUNT(*) c FROM (
    SELECT categoryLostItem AS category FROM lost_items_reports WHERE status <> 'Rejected'
    UNION ALL
    SELECT categoryFoundItem FROM found_items_reports WHERE status <> 'Rejected'
) t WHERE category IS NOT NULL AND category <> ''")[0]['c'] ?? 0);

$locTotal = (int)(rows($conn, "SELECT COUNT(*) c FROM (
    SELECT locationLostItem AS location FROM lost_items_reports WHERE status <> 'Rejected'
    UNION ALL
    SELECT locationFoundItem FROM found_items_reports WHERE status <> 'Rejected'
) t WHERE location IS NOT NULL AND location <> ''")[0]['c'] ?? 0);

// Status distribution (lost + found)
$allStatus = [];
foreach ([$lost, $found] as $map) foreach ($map as $k => $v) $allStatus[$k] = ($allStatus[$k] ?? 0) + $v;

$chartData = [
    'months'       => array_map(fn($k) => date('M Y', strtotime($k . '-01')), array_keys($months)),
    'lost'         => array_column($months, 'lost'),
    'found'        => array_column($months, 'found'),
    'catLabels'    => array_column($cats, 'category'),
    'catData'      => array_map('intval', array_column($cats, 'c')),
    'catTotal'     => $catTotal,
    'locLabels'    => array_column($locs, 'location'),
    'locData'      => array_map('intval', array_column($locs, 'c')),
    'locTotal'     => $locTotal,
    'statusLabels' => array_map('ucwords', array_keys($allStatus)),
    'statusData'   => array_values($allStatus),
];

// Maliliit na inline icons para sa chart titles
$icons = [
    'trend' => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>',
    'pie'   => '<svg viewBox="0 0 24 24"><path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15z"/></svg>',
    'tag'   => '<svg viewBox="0 0 24 24"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.2"/></svg>',
    'pin'   => '<svg viewBox="0 0 24 24"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
];
?>
<style>
.an-wrap{--an-maroon:#7a1f1f;--an-text:#1f2430;--an-muted:#6b7280;--an-line:#e8eaf0;
    font-family:'Inter','Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;color:var(--an-text)}
.an-wrap *{box-sizing:border-box}

/* KPI cards: 4 sa unang row, 3 sa ikalawa */
.an-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:18px 0 20px}
.an-card{background:#fff;border-radius:10px;padding:16px 18px;min-height:96px;
    box-shadow:0 1px 4px rgba(16,24,40,.07);border-left:4px solid var(--an-maroon);
    display:flex;flex-direction:column;justify-content:center;gap:2px}
.an-card .an-num{font-size:28px;line-height:1.15;font-weight:700;color:var(--an-maroon);letter-spacing:-.01em}
.an-card .an-lbl{font-size:13px;font-weight:500;color:var(--an-muted)}
.an-card .an-sub{font-size:11.5px;color:#9aa0ab}

/* Charts: 2 columns, mas malapad ang Monthly Reports sa itaas */
.an-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.an-box{background:#fff;border-radius:12px;padding:16px 20px 18px;box-shadow:0 1px 4px rgba(16,24,40,.07);min-width:0}
.an-box.an-trend{grid-column:span 7}
.an-box.an-status{grid-column:span 5}
.an-box.an-cat,.an-box.an-loc{grid-column:span 6}
.an-box h4{display:flex;align-items:center;gap:8px;margin:0 0 12px;font-size:14px;font-weight:700;color:var(--an-text)}
.an-box h4 svg{width:18px;height:18px;flex:none;fill:none;stroke:var(--an-maroon);stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
.an-chart{position:relative;height:250px}
.an-chart.an-short{height:220px}

/* Donut + legend table */
.an-donut-row{display:flex;align-items:center;justify-content:center;gap:28px;height:250px}
.an-donut{position:relative;flex:0 0 180px;width:180px;height:180px}
.an-donut canvas{display:block}
.an-donut-total{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.an-donut-total span{font-size:12px;color:var(--an-muted)}
.an-donut-total strong{font-size:26px;line-height:1.1;color:var(--an-text)}
.an-legend{flex:0 1 270px;min-width:0;display:flex;flex-direction:column}
/* Fixed na lapad ng bawat column para pantay ang pagkaka-align sa lahat ng row */
.an-legend-row{display:grid;grid-template-columns:12px minmax(0,1fr) 32px 52px;align-items:center;column-gap:10px;
    padding:9px 0;font-size:13px;border-bottom:1px solid var(--an-line)}
.an-legend-row:last-child{border-bottom:0}
.an-legend-row i{width:10px;height:10px;border-radius:50%;display:block}
.an-legend-row span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.an-legend-row b{font-weight:600;text-align:right}
.an-legend-row em{font-style:normal;color:var(--an-muted);text-align:right}

@media (max-width:1200px){
    .an-cards{grid-template-columns:repeat(3,minmax(0,1fr))}
    .an-box.an-trend,.an-box.an-status{grid-column:span 12}
}
@media (max-width:820px){
    .an-cards{grid-template-columns:repeat(2,minmax(0,1fr))}
    .an-box.an-cat,.an-box.an-loc{grid-column:span 12}
    .an-donut-row{flex-direction:column;height:auto}
    .an-legend{width:100%}
}
</style>

<div class="an-wrap">
    <div class="header-claim">
        <span class="dashboard_claim">Analytics</span>
        <span class="sub_claim">Lost and found activity overview</span>
    </div>

    <div class="an-cards">
        <div class="an-card"><div class="an-num"><?= $totalLost ?></div><div class="an-lbl">Total Lost Reports</div></div>
        <div class="an-card"><div class="an-num"><?= $totalFound ?></div><div class="an-lbl">Total Found Reports</div></div>
        <div class="an-card"><div class="an-num"><?= $pending ?></div><div class="an-lbl">Pending Review</div></div>
        <div class="an-card"><div class="an-num"><?= $claimsDone ?></div><div class="an-lbl">Claims Completed</div></div>
        <div class="an-card"><div class="an-num"><?= $recovery ?></div><div class="an-lbl">Recovery Rate</div></div>
        <div class="an-card"><div class="an-num"><?= $matchRate ?></div><div class="an-lbl">AI Match Acceptance Rate</div><div class="an-sub"><?= $matchSub ?></div></div>
        <div class="an-card"><div class="an-num"><?= $genRate ?></div><div class="an-lbl">AI Match Coverage</div><div class="an-sub"><?= $genSub ?></div></div>
    </div>

    <div class="an-grid">
        <div class="an-box an-trend">
            <h4><?= $icons['trend'] ?>Monthly Reports (Last 6 Months)</h4>
            <div class="an-chart"><canvas id="an2Trend"></canvas></div>
        </div>

        <div class="an-box an-status">
            <h4><?= $icons['pie'] ?>Report Status Distribution</h4>
            <div class="an-donut-row">
                <div class="an-donut">
                    <canvas id="an2Status"></canvas>
                    <div class="an-donut-total"><span>Total</span><strong id="an2StatusTotal">0</strong></div>
                </div>
                <div class="an-legend" id="an2StatusLegend"></div>
            </div>
        </div>

        <div class="an-box an-cat">
            <h4><?= $icons['tag'] ?>Top Categories</h4>
            <div class="an-chart an-short"><canvas id="an2Category"></canvas></div>
        </div>

        <div class="an-box an-loc">
            <h4><?= $icons['pin'] ?>Top Locations</h4>
            <div class="an-chart an-short"><canvas id="an2Location"></canvas></div>
        </div>
    </div>
</div>

<div id="an2Data" hidden data-chart="<?= htmlspecialchars(json_encode($chartData), ENT_QUOTES) ?>"></div>

<!-- Self-contained chart code (hindi na kailangan ng external JS; gumagana kahit AJAX-loaded) -->
<script type="text/plain" id="an2Src">
/* Analytics charts — kailangan ng Chart.js (v3 o v4) na naka-load na.
   Palitan nito ang dating chart code. Tawagin ang initAnalytics() pagkatapos
   ma-load ang analytics.php sa page (awtomatiko rin itong tumatakbo kung
   nandoon na ang #an2Data sa DOM). */
(function () {
    var MAROON = '#7a1f1f';
    var GOLD   = '#d9a441';
    var TEXT   = '#6b7280';
    var GRID   = '#eceef3';

    // Fixed na kulay kada status para pareho palagi sa donut at legend
    var STATUS_COLORS = {
        'pending':        '#7a1f1f',
        'approved':       '#d9a441',
        'rejected':       '#2e7d32',
        'ready for claim':'#1976d2',
        'claimed':        '#9e9e9e'
    };
    var FALLBACK = ['#6d4c9f', '#00897b', '#ef6c00', '#5d4037', '#455a64'];

    function destroyIfExists(canvas) {
        var existing = Chart.getChart(canvas);
        if (existing) existing.destroy();
    }

    function pct(v, total) {
        return total > 0 ? (v / total * 100).toFixed(1) + '%' : '0%';
    }

    function lineChart(d) {
        var canvas = document.getElementById('an2Trend');
        if (!canvas) return;
        destroyIfExists(canvas);
        var ctx = canvas.getContext('2d');

        function gradient(rgb) {
            var g = ctx.createLinearGradient(0, 0, 0, canvas.parentNode.clientHeight || 250);
            g.addColorStop(0, 'rgba(' + rgb + ',.16)');
            g.addColorStop(1, 'rgba(' + rgb + ',0)');
            return g;
        }

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: d.months,
                datasets: [
                    { label: 'Lost',  data: d.lost,  borderColor: MAROON, backgroundColor: gradient('122,31,31'),
                      fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: MAROON },
                    { label: 'Found', data: d.found, borderColor: GOLD,   backgroundColor: gradient('217,164,65'),
                      fill: true, tension: .4, borderWidth: 2, pointRadius: 3, pointBackgroundColor: GOLD }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'rect', boxWidth: 10, boxHeight: 10,
                              color: '#444', font: { size: 12 } } },
                    tooltip: { padding: 10 }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: TEXT, font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: GRID }, border: { display: false },
                         ticks: { color: TEXT, precision: 0, font: { size: 11 } } }
                }
            }
        });
    }

    function donutChart(d) {
        var canvas = document.getElementById('an2Status');
        if (!canvas) return;
        destroyIfExists(canvas);

        var total = d.statusData.reduce(function (a, b) { return a + b; }, 0);
        var colors = d.statusLabels.map(function (l, i) {
            return STATUS_COLORS[l.toLowerCase()] || FALLBACK[i % FALLBACK.length];
        });

        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: d.statusLabels, datasets: [{ data: d.statusData, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) {
                        return ' ' + c.label + ': ' + c.parsed + ' (' + pct(c.parsed, total) + ')';
                    } } }
                }
            }
        });

        var totalEl = document.getElementById('an2StatusTotal');
        if (totalEl) totalEl.textContent = total;

        var legend = document.getElementById('an2StatusLegend');
        if (legend) {
            legend.innerHTML = d.statusLabels.map(function (l, i) {
                return '<div class="an-legend-row"><i style="background:' + colors[i] + '"></i>' +
                       '<span>' + l + '</span><b>' + d.statusData[i] + '</b><em>' + pct(d.statusData[i], total) + '</em></div>';
            }).join('');
        }
    }

    // Plugin: naglalagay ng "32 (36.8%)" sa dulo ng bawat bar
    var barValueLabels = {
        id: 'anBarLabels',
        afterDatasetsDraw: function (chart, args, opts) {
            var ctx = chart.ctx, meta = chart.getDatasetMeta(0), data = chart.data.datasets[0].data;
            ctx.save();
            ctx.font = '600 11px Inter, "Segoe UI", sans-serif';
            ctx.fillStyle = '#333';
            ctx.textBaseline = 'middle';
            meta.data.forEach(function (bar, i) {
                ctx.fillText(data[i] + ' (' + pct(data[i], opts.total) + ')', bar.x + 6, bar.y);
            });
            ctx.restore();
        }
    };

    function barChart(id, labels, data, total) {
        var canvas = document.getElementById(id);
        if (!canvas) return;
        destroyIfExists(canvas);
        var max = Math.max.apply(null, data.concat([1]));

        new Chart(canvas, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: MAROON, borderRadius: 2, barThickness: 14 }] },
            plugins: [barValueLabels],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { right: 8 } },
                plugins: {
                    legend: { display: false },
                    anBarLabels: { total: total },
                    tooltip: { callbacks: { label: function (c) {
                        return ' ' + c.parsed.x + ' (' + pct(c.parsed.x, total) + ')';
                    } } }
                },
                scales: {
                    x: { beginAtZero: true, suggestedMax: Math.ceil(max * 1.25), grid: { color: GRID }, border: { display: false },
                         ticks: { color: TEXT, precision: 0, font: { size: 11 } } },
                    y: { grid: { display: false }, border: { display: false }, ticks: { color: '#444', font: { size: 11 } } }
                }
            }
        });
    }

    function initAnalytics() {
        var el = document.getElementById('an2Data');
        if (!el || typeof Chart === 'undefined') return;
        var d = JSON.parse(el.getAttribute('data-chart'));

        lineChart(d);
        donutChart(d);
        barChart('an2Category', d.catLabels, d.catData, d.catTotal);
        barChart('an2Location', d.locLabels, d.locData, d.locTotal);
    }

    window.initAnalytics = initAnalytics;
    if (document.readyState !== 'loading') initAnalytics();
    else document.addEventListener('DOMContentLoaded', initAnalytics);
})();

</script>
<img alt="" width="0" height="0" style="display:none" src="data:,"
     onerror="(function(i){i.remove();var run=function(){var e=document.createElement('script');e.text=document.getElementById('an2Src').textContent;document.head.appendChild(e)};if(window.Chart){run()}else{var c=document.createElement('script');c.src='https://cdn.jsdelivr.net/npm/chart.js@4';c.onload=run;document.head.appendChild(c)}})(this)">