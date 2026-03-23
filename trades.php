<?php
// trades.php — Paginated & filtered trade list
require_once 'db.php';
require_once 'header.php';

$db = getDB();

/* ── Handle delete ──────────────────────────────────── */
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM trades WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: trades.php?msg=deleted');
    exit;
}

/* ── Flash messages ──────────────────────────────────── */
$flashMap = [
    'deleted' => 'Trade deleted successfully.',
    'added'   => 'Trade added successfully.',
    'updated' => 'Trade updated successfully.',
];
$flash = $flashMap[$_GET['msg'] ?? ''] ?? '';

/* ── Build filters ───────────────────────────────────── */
$fFrom     = trim($_GET['from']      ?? '');
$fTo       = trim($_GET['to']        ?? '');
$fSymbol   = trim($_GET['symbol']    ?? '');
$fDir      = trim($_GET['direction'] ?? '');
$fStrategy = trim($_GET['strategy']  ?? '');
$fResult   = trim($_GET['result']    ?? '');

$where  = [];
$params = [];

if ($fFrom)    { $where[] = 'date >= ?';        $params[] = $fFrom; }
if ($fTo)      { $where[] = 'date <= ?';        $params[] = $fTo; }
if ($fSymbol)  { $where[] = 'symbol LIKE ?';    $params[] = '%' . $fSymbol . '%'; }
if ($fDir && in_array($fDir, ['Long','Short'])) {
                 $where[] = 'direction = ?';    $params[] = $fDir; }
if ($fStrategy){ $where[] = 'strategy LIKE ?'; $params[] = '%' . $fStrategy . '%'; }
if ($fResult === 'win')  { $where[] = 'pnl > 0'; }
if ($fResult === 'loss') { $where[] = 'pnl < 0'; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ── Pagination ──────────────────────────────────────── */
$perPage  = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM trades $whereSQL");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

/* ── Fetch trades ────────────────────────────────────── */
$stmt = $db->prepare("
    SELECT * FROM trades $whereSQL
    ORDER BY date DESC, id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$trades = $stmt->fetchAll();

/* ── Filtered summary ────────────────────────────────── */
$sumStmt = $db->prepare("
    SELECT
        COALESCE(SUM(pnl),0) AS total_pnl,
        SUM(CASE WHEN pnl>0 THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN pnl<0 THEN 1 ELSE 0 END) AS losses
    FROM trades $whereSQL
");
$sumStmt->execute($params);
$fSum = $sumStmt->fetch();

/* ── URL builder (preserves filters) ────────────────── */
function pageUrl(int $p): string {
    $q = $_GET;
    unset($q['page'], $q['msg']);
    $q['page'] = $p;
    return 'trades.php?' . http_build_query($q);
}
function filterUrl(array $extra = []): string {
    $q = array_merge($_GET, $extra);
    unset($q['page'], $q['msg']);
    return 'trades.php?' . http_build_query($q);
}

$emotions = ['😰','😟','😐','😊','🤩'];
?>

<?php if ($flash): ?>
<div class="alert alert-ok">✓ <?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1 class="page-title">
    Trades
    <span class="badge-count"><?= $totalRows ?> result<?= $totalRows !== 1 ? 's' : '' ?></span>
  </h1>
  <a href="add.php" class="btn btn-primary">+ Add Trade</a>
</div>

<!-- ── Filters ────────────────────────────────────────── -->
<form method="GET" action="trades.php" class="filters">
  <div class="form-group">
    <label>FROM</label>
    <input type="date" name="from" value="<?= htmlspecialchars($fFrom) ?>">
  </div>
  <div class="form-group">
    <label>TO</label>
    <input type="date" name="to" value="<?= htmlspecialchars($fTo) ?>">
  </div>
  <div class="form-group">
    <label>SYMBOL</label>
    <input type="text" name="symbol" placeholder="BTC, AAPL…" value="<?= htmlspecialchars($fSymbol) ?>">
  </div>
  <div class="form-group">
    <label>DIRECTION</label>
    <select name="direction">
      <option value="">All</option>
      <option value="Long"  <?= $fDir==='Long'  ? 'selected':'' ?>>Long</option>
      <option value="Short" <?= $fDir==='Short' ? 'selected':'' ?>>Short</option>
    </select>
  </div>
  <div class="form-group">
    <label>STRATEGY</label>
    <input type="text" name="strategy" placeholder="Breakout…" value="<?= htmlspecialchars($fStrategy) ?>">
  </div>
  <div class="form-group">
    <label>RESULT</label>
    <select name="result">
      <option value="">All</option>
      <option value="win"  <?= $fResult==='win'  ? 'selected':'' ?>>Wins only</option>
      <option value="loss" <?= $fResult==='loss' ? 'selected':'' ?>>Losses only</option>
    </select>
  </div>
  <div style="display:flex;gap:8px;align-items:flex-end">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="trades.php" class="btn btn-ghost">Reset</a>
  </div>
</form>

<!-- ── Filtered summary strip ────────────────────────── -->
<?php if ($totalRows > 0): ?>
<div style="display:flex;gap:20px;align-items:center;margin-bottom:14px;padding:12px 16px;
  background:var(--bg2);border:1px solid var(--border);border-radius:var(--r);flex-wrap:wrap">
  <span style="font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.07em">Filter Summary</span>
  <span style="font-size:13px;color:var(--green)">✓ <?= (int)$fSum['wins'] ?> wins</span>
  <span style="font-size:13px;color:var(--red)">✗ <?= (int)$fSum['losses'] ?> losses</span>
  <span style="font-size:13px;font-weight:700;color:<?= $fSum['total_pnl'] >= 0 ? 'var(--green)' : 'var(--red)' ?>">
    P&amp;L: <?= ($fSum['total_pnl'] >= 0 ? '+' : '−') ?>$<?= number_format(abs($fSum['total_pnl']),2) ?>
  </span>
  <?php if ($where): ?>
  <a href="trades.php" style="margin-left:auto;font-size:12px;color:var(--text3)">× Clear filters</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Trade Table ────────────────────────────────────── -->
<div class="card">
<?php if (count($trades) > 0): ?>
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Symbol</th>
      <th>Dir</th>
      <th>Strategy</th>
      <th style="text-align:right">Entry</th>
      <th style="text-align:right">Exit</th>
      <th style="text-align:right">Size</th>
      <th style="text-align:right">P&amp;L</th>
      <th style="text-align:right">R:R</th>
      <th>Emotion</th>
      <th>Chart</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($trades as $t):
    $win  = (float)$t['pnl'] >= 0;
    $cls  = $win ? 'win' : 'loss';
  ?>
  <tr class="<?= $cls ?>">
    <td class="muted small"><?= $t['id'] ?></td>
    <td class="muted small"><?= htmlspecialchars($t['date']) ?></td>
    <td><strong><?= htmlspecialchars($t['symbol']) ?></strong></td>
    <td><span class="badge badge-<?= strtolower($t['direction']) ?>"><?= htmlspecialchars($t['direction']) ?></span></td>
    <td class="muted small"><?= $t['strategy'] ? htmlspecialchars($t['strategy']) : '—' ?></td>
    <td style="text-align:right" class="mono">$<?= number_format((float)$t['entry'],     4) ?></td>
    <td style="text-align:right" class="mono">$<?= number_format((float)$t['exit_price'],4) ?></td>
    <td style="text-align:right" class="mono"><?= number_format((float)$t['size'],       2) ?></td>
    <td style="text-align:right;font-weight:700;color:<?= $win ? 'var(--green)' : 'var(--red)' ?>">
      <?= ($t['pnl'] >= 0 ? '+' : '−') ?>$<?= number_format(abs((float)$t['pnl']), 2) ?>
    </td>
    <td style="text-align:right" class="muted small">
      <?= $t['rr'] !== null ? htmlspecialchars($t['rr']).'R' : '—' ?>
    </td>
    <td><?= isset($emotions[$t['emotion']-1]) ? $emotions[$t['emotion']-1] : '—' ?></td>
    <td>
      <?php if ($t['screenshot']): ?>
      <a href="<?= htmlspecialchars($t['screenshot']) ?>" target="_blank" rel="noopener"
        class="btn btn-ghost btn-xs" title="View chart">🖼</a>
      <?php else: ?>
      <span class="muted">—</span>
      <?php endif; ?>
    </td>
    <td>
      <div class="gap-row" style="gap:5px">
        <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-xs">Edit</a>
        <a href="trades.php?delete=<?= $t['id'] ?>&<?= http_build_query(array_filter([
            'from'      => $fFrom,
            'to'        => $fTo,
            'symbol'    => $fSymbol,
            'direction' => $fDir,
            'strategy'  => $fStrategy,
            'result'    => $fResult,
            'page'      => $page,
        ])) ?>"
          class="btn btn-danger btn-xs"
          onclick="return confirm('Delete trade #<?= $t['id'] ?> (<?= htmlspecialchars($t['symbol']) ?>)? This cannot be undone.')">
          Del
        </a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- ── Pagination ─────────────────────────────────────── -->
<?php if ($totalPages > 1): ?>
<div class="pager">
  <?php if ($page > 1): ?>
  <a href="<?= pageUrl($page - 1) ?>">← Prev</a>
  <?php endif; ?>

  <?php
  $start = max(1, $page - 2);
  $end   = min($totalPages, $page + 2);
  if ($start > 1)            echo '<span>…</span>';
  for ($p = $start; $p <= $end; $p++):
  ?>
    <?php if ($p === $page): ?>
    <span class="cur"><?= $p ?></span>
    <?php else: ?>
    <a href="<?= pageUrl($p) ?>"><?= $p ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($end < $totalPages) echo '<span>…</span>'; ?>

  <?php if ($page < $totalPages): ?>
  <a href="<?= pageUrl($page + 1) ?>">Next →</a>
  <?php endif; ?>

  <span class="info">Page <?= $page ?> of <?= $totalPages ?> · <?= $totalRows ?> trades</span>
</div>
<?php endif; ?>

<?php else: ?>
<div class="empty">
  <span class="ei">📭</span>
  <h3><?= $where ? 'No trades match your filters' : 'No trades recorded yet' ?></h3>
  <p>
    <?php if ($where): ?>
    <a href="trades.php" class="btn btn-ghost btn-sm" style="display:inline-flex;margin-top:8px">× Clear filters</a>
    <?php else: ?>
    <a href="add.php" class="btn btn-primary btn-sm" style="display:inline-flex;margin-top:8px">+ Add your first trade</a>
    <?php endif; ?>
  </p>
</div>
<?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
