<?php
$dbFile = __DIR__ . '/database.json';
$database = [];
$error = null;
if (!file_exists($dbFile)) {
    $error = 'Saved database not found. Run the tracker first so database.json can be created.';
} else {
    $raw = file_get_contents($dbFile);
    $database = json_decode($raw, true);
    if (!is_array($database)) {
        $error = 'Saved database is invalid. Remove or repair database.json.';
        $database = [];
    }
}
$highlightId = isset($_GET['member']) ? trim($_GET['member']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Charter | Torn Faction Tracker</title>
    <style>
        :root {
            color-scheme: dark;
            background: #070707;
            color: #d4d4d4;
            font-family: Inter, system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: radial-gradient(circle at top,#111 0%,#070707 35%,#030303 100%); }
        header { padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-bottom: 1px solid rgba(255,255,255,.08); }
        header h1 { margin: 0; font-size: 1.4rem; letter-spacing: .04em; }
        header a { color: #8cf; text-decoration: none; font-size: .95rem; }
        main { width: min(1200px, calc(100% - 40px)); margin: 0 auto; padding: 24px 0 36px; }
        .banner { padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
        .banner.error { border-color: #bb2222; color: #ffb3b3; background: rgba(187,34,34,.12); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 14px; margin-bottom: 20px; }
        .stat-card { padding: 18px; border-radius: 18px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
        .stat-card strong { display: block; font-size: 1.8rem; line-height: 1; margin-bottom: 8px; }
        .member-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(320px,1fr)); gap: 18px; }
        .member-card { padding: 18px; border-radius: 18px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); transition: transform .2s ease, border-color .2s ease; }
        .member-card:hover { transform: translateY(-2px); border-color: rgba(140,200,255,.24); }
        .member-card.highlight { border-color: #77f; box-shadow: 0 0 0 1px rgba(119,187,255,.3); }
        .member-head { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; margin-bottom: 10px; }
        .member-head .name { font-size: 1.05rem; font-weight: 700; }
        .member-head .level { color: #8cf; font-weight: 600; }
        .member-meta { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px; margin-bottom: 14px; font-size: .94rem; color: #adb5c5; }
        .member-meta div { line-height: 1.5; }
        .chart-frame { position: relative; min-height: 96px; padding: 10px; border-radius: 16px; background: rgba(0,0,0,.24); }
        canvas.sparkline { width: 100%; height: 88px; display: block; }
        .chart-label { margin-top: 10px; display: flex; justify-content: space-between; gap: 8px; font-size: .82rem; color: #9ab; }
        .empty { padding: 28px 22px; border-radius: 16px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.02); color: #ccc; }
        .footer { margin-top: 28px; color: #7d8d9f; font-size: .88rem; }
    </style>
</head>
<body>
<header>
    <h1>Charter</h1>
    <a href="index.php">Back to Tracker</a>
</header>
<main>
    <?php if ($error): ?>
        <div class="banner error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (empty($database)): ?>
        <div class="empty">No saved member history found. Visit <a href="index.php" style="color:#8cf;">Tracker</a> and load a faction first.</div>
    <?php else: ?>
        <section class="stats">
            <div class="stat-card"><strong><?php echo count($database); ?></strong> members tracked</div>
            <div class="stat-card"><strong><?php echo array_sum(array_map(fn($item) => count($item['timestamps'] ?? []), $database)); ?></strong> total samples</div>
            <div class="stat-card"><strong><?php echo count(array_filter($database, fn($item) => strtolower($item['status'] ?? '') === 'online')); ?></strong> currently online</div>
        </section>
        <div class="member-grid">
            <?php foreach ($database as $member): ?>
                <?php
                    $memberId = $member['id'] ?? $member['name'] ?? 'unknown';
                    $safeId = htmlspecialchars($memberId, ENT_QUOTES, 'UTF-8');
                    $highlight = ($highlightId !== '' && $highlightId === (string)$memberId) ? ' highlight' : '';
                    $timestamps = is_array($member['timestamps'] ?? null) ? $member['timestamps'] : [];
                    $lastSample = end($timestamps);
                ?>
                <article id="member-<?php echo rawurlencode($memberId); ?>" class="member-card<?php echo $highlight; ?>">
                    <div class="member-head">
                        <div class="name"><?php echo htmlspecialchars($member['name'] ?? $memberId, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="level">Lvl <?php echo (int)($member['level'] ?? 0); ?></div>
                    </div>
                    <div class="member-meta">
                        <div><strong>Status</strong><br><?php echo htmlspecialchars($member['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><strong>Last seen</strong><br><?php echo htmlspecialchars($member['relative'] ?? 'unknown', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><strong>Samples</strong><br><?php echo count($timestamps); ?></div>
                        <div><strong>Latest sample</strong><br><?php echo htmlspecialchars($lastSample['relative'] ?? 'n/a', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="chart-frame">
                        <canvas class="sparkline" width="640" height="96" data-member-id="<?php echo $safeId; ?>"></canvas>
                        <div class="chart-label">
                            <span><?php echo htmlspecialchars($member['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><?php echo count($timestamps) ? date('M j H:i', (int)$timestamps[0]['time']) . ' → ' . date('M j H:i', (int)$timestamps[count($timestamps)-1]['time']) : 'no history'; ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="footer">Charts are generated from the saved <code>database.json</code> history.</div>
</main>
<script>
const members = <?php echo json_encode($database, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const statusLevels = {
    'online': 1,
    'away': 0.85,
    'working': 0.75,
    'busy': 0.65,
    'hospital': 0.25,
    'offline': 0,
    'in a faction war': 0.5,
    'training': 0.45,
    'shopping': 0.4
};
function normalizeStatus(raw) {
    if (!raw) return 0.3;
    const key = String(raw).trim().toLowerCase();
    return statusLevels[key] ?? 0.3;
}
function renderSparkline(canvas, timestamps) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    ctx.clearRect(0, 0, width, height);
    if (!Array.isArray(timestamps) || timestamps.length === 0) {
        ctx.fillStyle = '#889';
        ctx.font = '12px ui-sans-serif, system-ui, sans-serif';
        ctx.textBaseline = 'middle';
        ctx.fillText('No history available', 14, height / 2);
        return;
    }
    const points = timestamps
        .filter(item => typeof item.time === 'number' || /^\\d+$/.test(String(item.time)))
        .map(item => ({
            time: Number(item.time),
            value: normalizeStatus(item.status),
            status: item.status || '',
            relative: item.relative || ''
        }))
        .sort((a, b) => a.time - b.time);
    if (points.length === 0) {
        renderSparkline(canvas, []);
        return;
    }
    const minTime = points[0].time;
    const maxTime = points[points.length - 1].time;
    const span = Math.max(1, maxTime - minTime);
    const graphHeight = height - 24;
    const yOffset = 12;
    const plotted = points.map(point => ({
        x: 14 + ((width - 28) * (point.time - minTime) / span),
        y: yOffset + graphHeight * (1 - point.value),
        label: point.status || point.relative || 'unknown'
    }));
    ctx.save();
    ctx.strokeStyle = 'rgba(128, 220, 255, 0.92)';
    ctx.lineWidth = 2;
    ctx.beginPath();
    plotted.forEach((point, index) => {
        if (index === 0) ctx.moveTo(point.x, point.y);
        else ctx.lineTo(point.x, point.y);
    });
    ctx.stroke();
    ctx.fillStyle = 'rgba(68, 180, 255, 0.18)';
    ctx.beginPath();
    ctx.moveTo(plotted[0].x, height - 10);
    plotted.forEach(point => ctx.lineTo(point.x, point.y));
    ctx.lineTo(plotted[plotted.length - 1].x, height - 10);
    ctx.closePath();
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,0.14)';
    ctx.lineWidth = 1;
    for (let row = 0; row <= 3; row++) {
        const y = yOffset + (graphHeight / 3) * row;
        ctx.beginPath();
        ctx.moveTo(14, y);
        ctx.lineTo(width - 14, y);
        ctx.stroke();
    }
    ctx.fillStyle = '#cff';
    plotted.forEach(point => {
        ctx.beginPath();
        ctx.arc(point.x, point.y, 3.3, 0, Math.PI * 2);
        ctx.fill();
    });
    ctx.fillStyle = '#b1d8ff';
    ctx.font = '11px ui-sans-serif, system-ui, sans-serif';
    ctx.textBaseline = 'top';
    ctx.fillText(new Date(points[0].time * 1000).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }), 14, height - 18);
    ctx.textAlign = 'right';
    ctx.fillText(new Date(points[points.length - 1].time * 1000).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }), width - 14, height - 18);
    ctx.restore();
}
function renderCharts() {
    const canvases = document.querySelectorAll('canvas.sparkline');
    canvases.forEach(canvas => {
        const memberId = canvas.dataset.memberId;
        const member = members.find(item => String(item.id) === memberId || String(item.name) === memberId);
        if (!member) {
            renderSparkline(canvas, []);
            return;
        }
        renderSparkline(canvas, member.timestamps || []);
    });
}
window.addEventListener('load', renderCharts);
</script>
</body>
</html>
