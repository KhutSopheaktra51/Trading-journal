<?php
// index.php — Dashboard
require_once 'db.php';
require_once 'header.php';

$db = getDB();

/* ── Summary stats ──────────────────────────────────── */
$summary = $db->query("
    SELECT
        COUNT(*)                                              AS total,
        COALESCE(SUM(pnl), 0)                                AS total_pnl,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END)            AS wins,
        SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END)            AS losses,
        SUM(CASE WHEN pnl = 0 THEN 1 ELSE 0 END)            AS breakeven,
        COALESCE(AVG(CASE WHEN rr IS NOT NULL THEN rr END),0) AS avg_rr,
        COALESCE(AVG(CASE WHEN pnl > 0 THEN pnl END), 0)    AS avg_win,
        COALESCE(AVG(CASE WHEN pnl < 0 THEN pnl END), 0)    AS avg_loss
    FROM trades
")->fetch();

$total    = (int)$summary['total'];
$wins     = (int)$summary['wins'];
$losses   = (int)$summary['losses'];
$totalPnl = (float)$summary['total_pnl'];
$avgRR    = $summary['avg_rr'] > 0 ? round((float)$summary['avg_rr'], 2) : null;
$winRate  = $total > 0 ? round($wins / $total * 100, 1) : 0;

/* ── Recent trades ──────────────────────────────────── */
$recent = $db->query("
    SELECT * FROM trades ORDER BY date DESC, id DESC LIMIT 10
")->fetchAll();

/* ── Equity curve data ───────────────────────────────── */
$all = $db->query("
    SELECT date, pnl FROM trades ORDER BY date ASC, id ASC
")->fetchAll();

$cumPnl = 0;
$eqLabels = [];
$eqValues = [];
foreach ($all as $row) {
    $cumPnl += (float)$row['pnl'];
    $eqLabels[] = $row['date'];
    $eqValues[] = round($cumPnl, 2);
}

/* ── Monthly win rate for mini-bar ──────────────────── */
$monthly = $db->query("
    SELECT
        strftime('%b', date) AS mon,
        COUNT(*) AS total,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END) AS wins,
        COALESCE(SUM(pnl),0) AS pnl_sum
    FROM trades
    WHERE date >= date('now','-6 months')
    GROUP BY strftime('%Y-%m', date)
    ORDER BY date ASC
    LIMIT 6
")->fetchAll();

$emotions_map = ['😰','😟','😐','😊','🤩'];
?>

<!-- Page header -->
<div class="page-header">
  <h1 class="page-title">
    Dashboard
    <span class="badge-count"><?= $total ?> trade<?= $total !== 1 ? 's' : '' ?></span>
  </h1>
  <?php if ($total > 0): ?>
  <a href="trades.php" class="btn btn-ghost btn-sm">View all trades →</a>
  <?php endif; ?>
</div>

<!-- ── Stat Cards ────────────────────────────────────── -->
<div class="stats-grid">

  <div class="stat-card" style="--stat-color:var(--accent)">
    <div class="stat-label">Total Trades</div>
    <div class="stat-value accent"><?= $total ?></div>
    <div class="stat-sub"><?= $wins ?> W · <?= $losses ?> L<?= $summary['breakeven'] > 0 ? ' · '.$summary['breakeven'].' B/E' : '' ?></div>
  </div>

  <div class="stat-card" style="--stat-color:<?= $winRate >= 50 ? 'var(--green)' : 'var(--red)' ?>">
    <div class="stat-label">Win Rate</div>
    <div class="stat-value <?= $winRate >= 50 ? 'green' : 'red' ?>"><?= $winRate ?>%</div>
    <div class="stat-sub"><?= $wins ?> of <?= $total ?> trades won</div>
  </div>

  <div class="stat-card" style="--stat-color:<?= $totalPnl >= 0 ? 'var(--green)' : 'var(--red)' ?>">
    <div class="stat-label">Total P&amp;L</div>
    <div class="stat-value <?= $totalPnl >= 0 ? 'green' : 'red' ?>">
      <?= ($totalPnl >= 0 ? '+' : '−') ?>$<?= number_format(abs($totalPnl), 2) ?>
    </div>
    <div class="stat-sub">Avg win $<?= number_format(abs($summary['avg_win']),2) ?> · Avg loss $<?= number_format(abs($summary['avg_loss']),2) ?></div>
  </div>

  <div class="stat-card" style="--stat-color:var(--purple)">
    <div class="stat-label">Avg R:R</div>
    <div class="stat-value purple"><?= $avgRR !== null ? $avgRR.'R' : '—' ?></div>
    <div class="stat-sub">Risk-reward ratio</div>
  </div>

</div>

<!-- ── Equity Curve + Recent Trades ─────────────────── -->
<div class="two-col" style="margin-bottom:22px">

  <!-- Equity curve -->
  <div class="card">
    <div class="card-title">Equity Curve</div>
    <?php if (count($eqValues) > 0): ?>
    <div class="chart-box">
      <canvas id="eqChart"></canvas>
    </div>
    <?php else: ?>
    <div class="empty">
      <span class="ei">📉</span>
      <h3>No trade data yet</h3>
      <p><a href="add.php">Record your first trade</a> to see the equity curve.</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent trades -->
  <div class="card">
    <div class="card-title">Recent Trades</div>
    <?php if (count($recent) > 0): ?>
    <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Date</th><th>Symbol</th><th>Dir</th><th>Strategy</th><th>P&amp;L</th><th>Feel</th>
      </tr></thead>
      <tbody>
      <?php foreach ($recent as $t):
        $win = (float)$t['pnl'] >= 0;
      ?>
      <tr class="<?= $win ? 'win' : 'loss' ?>">
        <td class="muted small"><?= htmlspecialchars($t['date']) ?></td>
        <td><strong><?= htmlspecialchars($t['symbol']) ?></strong></td>
        <td><span class="badge badge-<?= strtolower(htmlspecialchars($t['direction'])) ?>"><?= htmlspecialchars($t['direction']) ?></span></td>
        <td class="muted small"><?= $t['strategy'] ? htmlspecialchars($t['strategy']) : '—' ?></td>
        <td style="font-weight:700;color:<?= $win ? 'var(--green)' : 'var(--red)' ?>">
          <?= ($t['pnl'] >= 0 ? '+' : '−') ?>$<?= number_format(abs($t['pnl']), 2) ?>
        </td>
        <td><?= isset($emotions_map[$t['emotion']-1]) ? $emotions_map[$t['emotion']-1] : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div style="text-align:right;margin-top:12px">
      <a href="trades.php" class="btn btn-ghost btn-sm">All trades →</a>
    </div>
    <?php else: ?>
    <div class="empty">
      <span class="ei">📋</span>
      <h3>No trades yet</h3>
      <p><a href="add.php">Add your first trade</a> to get started.</p>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── Monthly Performance ───────────────────────────── -->
<?php if (count($monthly) > 0): ?>
<div class="card" style="margin-bottom:22px">
  <div class="card-title">Monthly Performance (Last 6 Months)</div>
  <div style="display:grid;grid-template-columns:repeat(<?= count($monthly) ?>,1fr);gap:12px">
    <?php foreach ($monthly as $m):
      $wr = $m['total'] > 0 ? round($m['wins']/$m['total']*100) : 0;
      $pos = (float)$m['pnl_sum'] >= 0;
    ?>
    <div style="text-align:center;padding:14px 10px;background:var(--bg3);border-radius:var(--r);border:1px solid var(--border)">
      <div style="font-size:11px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">
        <?= htmlspecialchars($m['mon']) ?>
      </div>
      <div style="font-size:18px;font-weight:800;color:<?= $pos ? 'var(--green)' : 'var(--red)' ?>;margin-bottom:4px">
        <?= ($pos ? '+' : '−') ?>$<?= number_format(abs($m['pnl_sum']),0) ?>
      </div>
      <div style="font-size:11px;color:var(--text3)"><?= $wr ?>% win · <?= $m['total'] ?> trades</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
<?php if (count($eqValues) > 0): ?>
(function () {
    const labels = <?= json_encode($eqLabels) ?>;
    const values = <?= json_encode($eqValues) ?>;
    const final  = values[values.length - 1];
    const color  = final >= 0 ? '#00cc99' : '#ff3f5c';

    const canvas = document.getElementById('eqChart');
    const ctx    = canvas.getContext('2d');
    const grad   = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, final >= 0 ? 'rgba(0,204,153,.28)' : 'rgba(255,63,92,.28)');
    grad.addColorStop(1, 'rgba(0,0,0,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: color,
                backgroundColor: grad,
                borderWidth: 2.5,
                fill: true,
                tension: .38,
                pointRadius: labels.length > 50 ? 0 : 3,
                pointHoverRadius: 6,
                pointBackgroundColor: color,
                pointBorderColor: 'var(--bg2)',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0b1628',
                    borderColor: '#1a2e4a',
                    borderWidth: 1,
                    padding: 10,
                    titleColor: '#6e91b8',
                    bodyColor: '#dce8f8',
                    callbacks: {
                        label: c => '  Equity: ' + (c.parsed.y >= 0 ? '+' : '−') + '$' + Math.abs(c.parsed.y).toFixed(2)
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(26,46,74,.6)' },
                    ticks: { color: '#354f6e', maxTicksLimit: 8, font: { size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(26,46,74,.6)' },
                    ticks: {
                        color: '#354f6e', font: { size: 11 },
                        callback: v => (v >= 0 ? '' : '−') + '$' + Math.abs(v).toFixed(0)
                    }
                }
            }
        }
    });
})();
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
