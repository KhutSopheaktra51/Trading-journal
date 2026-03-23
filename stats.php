<?php
// stats.php — Deep-dive statistics
require_once 'db.php';
require_once 'header.php';

$db = getDB();

/* ── Overall ─────────────────────────────────────────── */
$ov = $db->query("
    SELECT
        COUNT(*)                                               AS total,
        COALESCE(SUM(pnl), 0)                                 AS total_pnl,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END)             AS wins,
        SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END)             AS losses,
        COALESCE(AVG(CASE WHEN pnl > 0 THEN pnl END), 0)     AS avg_win,
        COALESCE(AVG(CASE WHEN pnl < 0 THEN pnl END), 0)     AS avg_loss,
        COALESCE(MAX(pnl), 0)                                 AS best_pnl,
        COALESCE(MIN(pnl), 0)                                 AS worst_pnl,
        COALESCE(AVG(CASE WHEN rr IS NOT NULL THEN rr END),0) AS avg_rr,
        COALESCE(AVG(emotion), 0)                             AS avg_emotion,
        SUM(CASE WHEN direction='Long'  THEN 1 ELSE 0 END)   AS long_count,
        SUM(CASE WHEN direction='Short' THEN 1 ELSE 0 END)   AS short_count
    FROM trades
")->fetch();

$total   = (int)$ov['total'];
$wins    = (int)$ov['wins'];
$losses  = (int)$ov['losses'];
$winRate = $total > 0 ? round($wins / $total * 100, 1) : 0;

/* profit factor */
$grossWin  = $db->query("SELECT COALESCE(SUM(pnl),0) FROM trades WHERE pnl>0")->fetchColumn();
$grossLoss = abs($db->query("SELECT COALESCE(SUM(pnl),0) FROM trades WHERE pnl<0")->fetchColumn());
$pf = ($grossLoss > 0) ? round($grossWin / $grossLoss, 2) : null;

/* Expected value */
$ev = $total > 0 ? round(((float)$ov['avg_win'] * $wins + (float)$ov['avg_loss'] * $losses) / $total, 2) : null;

/* ── By strategy ─────────────────────────────────────── */
$byStrat = $db->query("
    SELECT
        CASE WHEN strategy='' OR strategy IS NULL THEN '(No Strategy)' ELSE strategy END AS strat,
        COUNT(*)                                          AS total,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END)          AS wins,
        COALESCE(SUM(pnl),0)                              AS total_pnl,
        COALESCE(AVG(pnl),0)                              AS avg_pnl,
        COALESCE(AVG(CASE WHEN pnl>0 THEN pnl END),0)    AS avg_win,
        COALESCE(AVG(CASE WHEN pnl<0 THEN pnl END),0)    AS avg_loss
    FROM trades
    GROUP BY strat
    ORDER BY total_pnl DESC
")->fetchAll();

/* ── Best & worst ────────────────────────────────────── */
$bestTrades  = $db->query("SELECT * FROM trades ORDER BY pnl DESC LIMIT 5")->fetchAll();
$worstTrades = $db->query("SELECT * FROM trades ORDER BY pnl ASC  LIMIT 5")->fetchAll();

/* ── By day of week ──────────────────────────────────── */
$byDow = $db->query("
    SELECT
        CAST(strftime('%w', date) AS INTEGER) AS dow,
        COUNT(*)                              AS total,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END) AS wins,
        COALESCE(SUM(pnl),0)                  AS total_pnl,
        COALESCE(AVG(pnl),0)                  AS avg_pnl
    FROM trades
    GROUP BY dow
    ORDER BY dow
")->fetchAll();
$dowNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

/* ── By month (last 12) ──────────────────────────────── */
$byMonth = $db->query("
    SELECT
        strftime('%Y-%m', date)               AS ym,
        strftime('%b %y', date)               AS label,
        COUNT(*)                              AS total,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END) AS wins,
        COALESCE(SUM(pnl),0)                  AS total_pnl
    FROM trades
    WHERE date >= date('now','-12 months')
    GROUP BY ym
    ORDER BY ym ASC
    LIMIT 12
")->fetchAll();

/* ── Emotion vs P&L ──────────────────────────────────── */
$byEmotion = $db->query("
    SELECT
        emotion,
        COUNT(*)                              AS total,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END) AS wins,
        COALESCE(AVG(pnl),0)                  AS avg_pnl,
        COALESCE(SUM(pnl),0)                  AS total_pnl
    FROM trades
    GROUP BY emotion
    ORDER BY emotion
")->fetchAll();
$emoLabels = ['😰 Anxious','😟 Uncertain','😐 Neutral','😊 Confident','🤩 On Fire'];

$emotions = ['😰','😟','😐','😊','🤩'];
?>

<h1 class="page-title" style="margin-bottom:24px">Statistics</h1>

<?php if ($total === 0): ?>
<div class="empty">
  <span class="ei">📊</span>
  <h3>No data to analyze yet</h3>
  <p><a href="add.php" class="btn btn-primary btn-sm" style="display:inline-flex;margin-top:12px">+ Add your first trade</a></p>
</div>
<?php else: ?>

<!-- ── Summary Cards ──────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card" style="--stat-color:<?= $winRate>=50?'var(--green)':'var(--red)' ?>">
    <div class="stat-label">Win Rate</div>
    <div class="stat-value <?= $winRate>=50?'green':'red' ?>"><?= $winRate ?>%</div>
    <div class="stat-sub"><?= $wins ?> W · <?= $losses ?> L · <?= $total ?> total</div>
  </div>
  <div class="stat-card" style="--stat-color:var(--green)">
    <div class="stat-label">Avg Win</div>
    <div class="stat-value green">+$<?= number_format($ov['avg_win'],2) ?></div>
    <div class="stat-sub">per winning trade</div>
  </div>
  <div class="stat-card" style="--stat-color:var(--red)">
    <div class="stat-label">Avg Loss</div>
    <div class="stat-value red">−$<?= number_format(abs($ov['avg_loss']),2) ?></div>
    <div class="stat-sub">per losing trade</div>
  </div>
  <div class="stat-card" style="--stat-color:var(--yellow)">
    <div class="stat-label">Profit Factor</div>
    <div class="stat-value" style="color:var(--yellow)"><?= $pf !== null ? $pf : '—' ?></div>
    <div class="stat-sub">gross profit ÷ gross loss</div>
  </div>
</div>

<!-- ── Secondary metrics ─────────────────────────────── -->
<div class="three-col" style="margin-bottom:24px">
  <div class="card">
    <div class="card-title">Expected Value</div>
    <div style="font-size:26px;font-weight:800;color:<?= $ev>=0?'var(--green)':'var(--red)' ?>">
      <?= $ev !== null ? (($ev>=0?'+':'−').'$'.number_format(abs($ev),2)) : '—' ?>
    </div>
    <div class="muted small" style="margin-top:6px">per trade average</div>
  </div>
  <div class="card">
    <div class="card-title">Long vs Short</div>
    <div style="display:flex;gap:16px;align-items:center;margin-top:4px">
      <div>
        <div style="font-size:22px;font-weight:800;color:var(--accent)"><?= $ov['long_count'] ?></div>
        <div class="muted small">Long</div>
      </div>
      <div style="width:1px;height:40px;background:var(--border)"></div>
      <div>
        <div style="font-size:22px;font-weight:800;color:var(--red)"><?= $ov['short_count'] ?></div>
        <div class="muted small">Short</div>
      </div>
    </div>
    <?php if ($total > 0): ?>
    <div class="prog-wrap" style="margin-top:12px">
      <div class="prog">
        <div class="prog-fill" style="width:<?= round($ov['long_count']/$total*100) ?>%;background:var(--accent)"></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-title">Avg R:R</div>
    <div style="font-size:26px;font-weight:800;color:var(--purple)">
      <?= $ov['avg_rr'] > 0 ? round($ov['avg_rr'],2).'R' : '—' ?>
    </div>
    <div class="muted small" style="margin-top:6px">
      Avg emotion: <?= $ov['avg_emotion'] > 0 ? $emotions[round($ov['avg_emotion'])-1].' '.round($ov['avg_emotion'],1) : '—' ?>
    </div>
  </div>
</div>

<!-- ── Strategy & DoW ─────────────────────────────────── -->
<div class="two-col" style="margin-bottom:24px">

  <!-- Strategy table -->
  <div class="card">
    <div class="card-title">Performance by Strategy</div>
    <?php if (count($byStrat) > 0): ?>
    <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Strategy</th><th>Trades</th><th>Win %</th><th>Avg P&amp;L</th><th>Total P&amp;L</th>
      </tr></thead>
      <tbody>
      <?php foreach ($byStrat as $s):
        $wr = $s['total'] > 0 ? round($s['wins']/$s['total']*100, 0) : 0;
        $pos = (float)$s['total_pnl'] >= 0;
      ?>
      <tr>
        <td><strong><?= htmlspecialchars($s['strat']) ?></strong></td>
        <td class="muted"><?= $s['total'] ?></td>
        <td>
          <div class="prog-wrap">
            <div class="prog" style="width:80px">
              <div class="prog-fill" style="width:<?= $wr ?>%;background:<?= $wr>=50?'var(--green)':'var(--red)' ?>"></div>
            </div>
            <span style="font-size:12px;color:<?= $wr>=50?'var(--green)':'var(--red)' ?>;font-weight:600"><?= $wr ?>%</span>
          </div>
        </td>
        <td style="color:<?= (float)$s['avg_pnl']>=0?'var(--green)':'var(--red)' ?>;font-weight:600">
          <?= ((float)$s['avg_pnl']>=0?'+':'−') ?>$<?= number_format(abs($s['avg_pnl']),2) ?>
        </td>
        <td style="color:<?= $pos?'var(--green)':'var(--red)' ?>;font-weight:700">
          <?= ($pos?'+':'−') ?>$<?= number_format(abs($s['total_pnl']),2) ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty"><p>No strategy data</p></div>
    <?php endif; ?>
  </div>

  <!-- Day of week -->
  <div class="card">
    <div class="card-title">Performance by Day of Week</div>
    <div class="chart-box sm">
      <canvas id="dowChart"></canvas>
    </div>
    <div style="margin-top:14px">
    <?php
    // Fill in missing days
    $dowMap = [];
    foreach ($byDow as $d) $dowMap[$d['dow']] = $d;
    foreach (range(1,5) as $d): // Mon–Fri
      $row = $dowMap[$d] ?? ['total'=>0,'wins'=>0,'total_pnl'=>0,'avg_pnl'=>0];
      $wr2 = $row['total'] > 0 ? round($row['wins']/$row['total']*100) : 0;
      $pos2 = (float)$row['total_pnl'] >= 0;
    ?>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12px">
      <span style="width:32px;color:var(--text2);font-weight:600"><?= $dowNames[$d] ?></span>
      <div class="prog" style="flex:1">
        <div class="prog-fill" style="width:<?= $wr2 ?>%;background:<?= $wr2>=50?'var(--green)':'var(--red)' ?>"></div>
      </div>
      <span style="width:35px;text-align:right;color:<?= $wr2>=50?'var(--green)':'var(--red)' ?>;font-weight:600"><?= $wr2 ?>%</span>
      <span style="width:70px;text-align:right;color:<?= $pos2?'var(--green)':'var(--red)' ?>;font-weight:600">
        <?= ($pos2?'+':'−') ?>$<?= number_format(abs($row['total_pnl']),0) ?>
      </span>
      <span style="color:var(--text3);width:24px;text-align:right"><?= $row['total'] ?>t</span>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Monthly P&L chart ──────────────────────────────── -->
<?php if (count($byMonth) > 0): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-title">Monthly P&amp;L (Last 12 Months)</div>
  <div class="chart-box">
    <canvas id="monthChart"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- ── Best & Worst ───────────────────────────────────── -->
<div class="two-col" style="margin-bottom:24px">

  <div class="card">
    <div class="card-title">🏆 Best Trades</div>
    <?php if (count($bestTrades) > 0): ?>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Symbol</th><th>Dir</th><th>Strategy</th><th>P&amp;L</th><th>R:R</th></tr></thead>
      <tbody>
      <?php foreach ($bestTrades as $t): ?>
      <tr class="win">
        <td class="muted small"><?= htmlspecialchars($t['date']) ?></td>
        <td><strong><?= htmlspecialchars($t['symbol']) ?></strong></td>
        <td><span class="badge badge-<?= strtolower($t['direction']) ?>"><?= $t['direction'] ?></span></td>
        <td class="muted small"><?= $t['strategy'] ? htmlspecialchars($t['strategy']) : '—' ?></td>
        <td style="color:var(--green);font-weight:700">+$<?= number_format($t['pnl'],2) ?></td>
        <td class="muted small"><?= $t['rr'] !== null ? $t['rr'].'R' : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty"><p>No winning trades yet</p></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">💀 Worst Trades</div>
    <?php if (count($worstTrades) > 0): ?>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Symbol</th><th>Dir</th><th>Strategy</th><th>P&amp;L</th><th>R:R</th></tr></thead>
      <tbody>
      <?php foreach ($worstTrades as $t): ?>
      <tr class="loss">
        <td class="muted small"><?= htmlspecialchars($t['date']) ?></td>
        <td><strong><?= htmlspecialchars($t['symbol']) ?></strong></td>
        <td><span class="badge badge-<?= strtolower($t['direction']) ?>"><?= $t['direction'] ?></span></td>
        <td class="muted small"><?= $t['strategy'] ? htmlspecialchars($t['strategy']) : '—' ?></td>
        <td style="color:var(--red);font-weight:700">−$<?= number_format(abs($t['pnl']),2) ?></td>
        <td class="muted small"><?= $t['rr'] !== null ? $t['rr'].'R' : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="empty"><p>No losing trades (wow!)</p></div>
    <?php endif; ?>
  </div>

</div>

<!-- ── Emotion analysis ───────────────────────────────── -->
<?php if (count($byEmotion) > 0): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-title">Emotion vs Performance</div>
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px">
  <?php foreach ($byEmotion as $e):
    $wr3 = $e['total'] > 0 ? round($e['wins']/$e['total']*100) : 0;
    $pos3 = (float)$e['avg_pnl'] >= 0;
    $ei = max(0, min(4, (int)$e['emotion']-1));
  ?>
  <div style="text-align:center;padding:14px 8px;background:var(--bg3);border-radius:var(--r);border:1px solid var(--border)">
    <div style="font-size:28px;margin-bottom:6px"><?= $emotions[$ei] ?></div>
    <div style="font-size:11px;color:var(--text3);margin-bottom:8px"><?= $emoLabels[$ei] ?></div>
    <div style="font-size:18px;font-weight:800;color:<?= $pos3?'var(--green)':'var(--red)' ?>">
      <?= ($pos3?'+':'−') ?>$<?= number_format(abs($e['avg_pnl']),0) ?>
    </div>
    <div style="font-size:11px;color:var(--text3);margin-top:4px"><?= $wr3 ?>% win · <?= $e['total'] ?>t</div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; // end $total > 0 ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
<?php if ($total > 0): ?>

/* Shared Chart defaults */
Chart.defaults.color = '#354f6e';
Chart.defaults.borderColor = 'rgba(26,46,74,.7)';

const tooltip = {
    backgroundColor: '#0b1628',
    borderColor: '#1a2e4a',
    borderWidth: 1,
    padding: 10,
    titleColor: '#6e91b8',
    bodyColor: '#dce8f8',
};

/* ── Day of Week chart ───────────────────────────────── */
(function () {
    const dowRaw = <?= json_encode(array_values($byDow)) ?>;
    const dowMap = {};
    dowRaw.forEach(r => dowMap[r.dow] = r);

    const labels = <?= json_encode($dowNames) ?>;
    const pnls   = labels.map((_, i) => dowMap[i] ? parseFloat(dowMap[i].total_pnl) : 0);
    const colors = pnls.map(v => v >= 0 ? 'rgba(0,204,153,.75)' : 'rgba(255,63,92,.75)');

    new Chart(document.getElementById('dowChart').getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ data: pnls, backgroundColor: colors, borderRadius: 5, borderSkipped: false }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { ...tooltip,
                callbacks: { label: c => (c.parsed.y >= 0 ? '+' : '−') + '$' + Math.abs(c.parsed.y).toFixed(2) }
            }},
            scales: {
                x: { ticks: { font: { size: 11 } } },
                y: { ticks: { font: { size: 11 }, callback: v => '$' + v } }
            }
        }
    });
})();

<?php if (count($byMonth) > 0): ?>
/* ── Monthly chart ───────────────────────────────────── */
(function () {
    const raw    = <?= json_encode($byMonth) ?>;
    const labels = raw.map(r => r.label);
    const pnls   = raw.map(r => parseFloat(r.total_pnl));
    const colors = pnls.map(v => v >= 0 ? 'rgba(0,204,153,.75)' : 'rgba(255,63,92,.75)');

    new Chart(document.getElementById('monthChart').getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ data: pnls, backgroundColor: colors, borderRadius: 5, borderSkipped: false }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { ...tooltip,
                callbacks: { label: c => (c.parsed.y >= 0 ? '+' : '−') + '$' + Math.abs(c.parsed.y).toFixed(2) }
            }},
            scales: {
                x: { ticks: { font: { size: 11 } } },
                y: { ticks: { font: { size: 11 }, callback: v => (v >= 0 ? '' : '−') + '$' + Math.abs(v) } }
            }
        }
    });
})();
<?php endif; ?>

<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
