<?php
// edit.php — Edit an existing trade
require_once 'db.php';
require_once 'header.php';

$db = getDB();

/* ── Load trade ──────────────────────────────────────── */
$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: trades.php'); exit; }

$stmt = $db->prepare("SELECT * FROM trades WHERE id = ?");
$stmt->execute([$id]);
$trade = $stmt->fetch();

if (!$trade) {
    header('Location: trades.php?msg=notfound');
    exit;
}

$errors = [];

/* ── Handle POST ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date       = trim($_POST['date']       ?? '');
    $symbol     = strtoupper(trim($_POST['symbol']    ?? ''));
    $direction  = trim($_POST['direction']  ?? '');
    $entry      = trim($_POST['entry']      ?? '');
    $exit_price = trim($_POST['exit_price'] ?? '');
    $stop_loss  = trim($_POST['stop_loss']  ?? '');
    $size       = trim($_POST['size']       ?? '');
    $strategy   = trim($_POST['strategy']   ?? '');
    $notes      = trim($_POST['notes']      ?? '');
    $emotion    = max(1, min(5, (int)($_POST['emotion'] ?? 3)));
    $screenshot = trim($_POST['screenshot'] ?? '');

    if (!$date)                                        $errors[] = 'Trade date is required.';
    if (!$symbol)                                      $errors[] = 'Asset / symbol is required.';
    if (!in_array($direction, ['Long','Short']))        $errors[] = 'Direction must be Long or Short.';
    if (!is_numeric($entry) || (float)$entry <= 0)     $errors[] = 'A valid entry price is required.';
    if (!is_numeric($exit_price)||(float)$exit_price<=0) $errors[] = 'A valid exit price is required.';
    if (!is_numeric($size)  || (float)$size  <= 0)    $errors[] = 'A valid position size is required.';

    if (empty($errors)) {
        $e  = (float)$entry;
        $x  = (float)$exit_price;
        $s  = (float)$size;
        $sl = ($stop_loss !== '' && is_numeric($stop_loss)) ? (float)$stop_loss : null;

        $pnl = $direction === 'Long' ? ($x - $e) * $s : ($e - $x) * $s;
        $pnl = round($pnl, 4);

        $rr = null;
        if ($sl !== null) {
            $risk = $direction === 'Long' ? ($e - $sl) * $s : ($sl - $e) * $s;
            if ($risk > 0) $rr = round(abs($pnl) / $risk, 2);
        }

        $upd = $db->prepare("
            UPDATE trades SET
                date        = ?,
                symbol      = ?,
                direction   = ?,
                entry       = ?,
                exit_price  = ?,
                stop_loss   = ?,
                size        = ?,
                pnl         = ?,
                rr          = ?,
                strategy    = ?,
                notes       = ?,
                emotion     = ?,
                screenshot  = ?
            WHERE id = ?
        ");
        $upd->execute([
            $date, $symbol, $direction, $e, $x, $sl,
            $s, $pnl, $rr, $strategy, $notes, $emotion, $screenshot,
            $id
        ]);

        header('Location: trades.php?msg=updated');
        exit;
    }

    /* Restore POST values into $trade on validation error */
    $trade = array_merge($trade, [
        'date'       => $date,
        'symbol'     => $symbol,
        'direction'  => $direction,
        'entry'      => $entry,
        'exit_price' => $exit_price,
        'stop_loss'  => $stop_loss,
        'size'       => $size,
        'strategy'   => $strategy,
        'notes'      => $notes,
        'emotion'    => $emotion,
        'screenshot' => $screenshot,
    ]);
}

$t = $trade;
$emotions      = ['😰','😟','😐','😊','🤩'];
$emotionLabels = ['Anxious','Uncertain','Neutral','Confident','On Fire'];
?>

<!-- ── Alerts ────────────────────────────────────────────── -->
<?php foreach ($errors as $err): ?>
<div class="alert alert-err">⚠ <?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<div class="page-header">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="trades.php" style="color:var(--text3);font-size:20px;line-height:1;text-decoration:none">←</a>
    <h1 class="page-title">
      Edit Trade
      <span class="badge-count">#<?= $id ?> · <?= htmlspecialchars($t['symbol']) ?></span>
    </h1>
  </div>
  <a href="trades.php?delete=<?= $id ?>"
     class="btn btn-danger btn-sm"
     onclick="return confirm('Delete this trade permanently?')">🗑 Delete Trade</a>
</div>

<div class="card">
<form method="POST" id="tf" autocomplete="off">

  <div class="form-grid">

    <div class="form-group">
      <label>DATE *</label>
      <input type="date" name="date" value="<?= htmlspecialchars($t['date']) ?>" required>
    </div>

    <div class="form-group">
      <label>ASSET / SYMBOL *</label>
      <input type="text" name="symbol" value="<?= htmlspecialchars($t['symbol']) ?>" required maxlength="20">
    </div>

    <div class="form-group">
      <label>DIRECTION *</label>
      <select name="direction" id="dir">
        <option value="Long"  <?= $t['direction']==='Long'  ? 'selected':'' ?>>📈 Long (Buy)</option>
        <option value="Short" <?= $t['direction']==='Short' ? 'selected':'' ?>>📉 Short (Sell)</option>
      </select>
    </div>

    <div class="form-group">
      <label>STRATEGY</label>
      <input type="text" name="strategy" value="<?= htmlspecialchars($t['strategy'] ?? '') ?>" maxlength="80">
    </div>

    <div class="form-group">
      <label>ENTRY PRICE *</label>
      <input type="number" name="entry" id="entry"
        step="any" min="0" value="<?= htmlspecialchars($t['entry']) ?>" required>
    </div>

    <div class="form-group">
      <label>EXIT PRICE *</label>
      <input type="number" name="exit_price" id="exit"
        step="any" min="0" value="<?= htmlspecialchars($t['exit_price']) ?>" required>
    </div>

    <div class="form-group">
      <label>STOP LOSS <span class="muted small">(optional)</span></label>
      <input type="number" name="stop_loss" id="sl"
        step="any" min="0" value="<?= htmlspecialchars($t['stop_loss'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>POSITION SIZE *</label>
      <input type="number" name="size" id="sz"
        step="any" min="0" value="<?= htmlspecialchars($t['size']) ?>" required>
    </div>

    <!-- Live calc -->
    <div class="form-group full">
      <label>REAL-TIME CALCULATION</label>
      <div class="calc-box">
        <div class="calc-item">
          <div class="clabel">P&amp;L</div>
          <div class="cval" id="c-pnl">—</div>
        </div>
        <div class="calc-item">
          <div class="clabel">R:R Ratio</div>
          <div class="cval" id="c-rr">—</div>
        </div>
        <div class="calc-item">
          <div class="clabel">Outcome</div>
          <div class="cval" id="c-out">—</div>
        </div>
      </div>
    </div>

    <div class="form-group full">
      <label>SETUP NOTES</label>
      <textarea name="notes" rows="3"><?= htmlspecialchars($t['notes'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>SCREENSHOT URL <span class="muted small">(optional)</span></label>
      <input type="url" name="screenshot" value="<?= htmlspecialchars($t['screenshot'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>EMOTIONAL STATE</label>
      <div class="emotion-row" id="emo-row">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <button type="button" class="emo-btn <?= (int)$t['emotion']===$i ? 'active':'' ?>"
          data-val="<?= $i ?>" title="<?= $emotionLabels[$i-1] ?> (<?= $i ?>/5)">
          <?= $emotions[$i-1] ?>
        </button>
        <?php endfor; ?>
        <span id="emo-label" style="font-size:12px;color:var(--text3);margin-left:4px">
          <?= $emotionLabels[min(4, max(0, (int)$t['emotion']-1))] ?>
        </span>
      </div>
      <input type="hidden" name="emotion" id="emotion" value="<?= (int)$t['emotion'] ?>">
    </div>

  </div><!-- /form-grid -->

  <!-- Meta info -->
  <div style="margin:16px 0;padding:12px 16px;background:var(--bg);border-radius:var(--r);
    border:1px solid var(--border);display:flex;gap:24px;flex-wrap:wrap">
    <span class="muted small">ID: <strong><?= $id ?></strong></span>
    <span class="muted small">Created: <strong><?= htmlspecialchars($t['created_at']) ?></strong></span>
    <span class="muted small">Current P&amp;L: <strong style="color:<?= (float)$t['pnl']>=0?'var(--green)':'var(--red)' ?>">
      <?= ((float)$t['pnl']>=0?'+':'−') ?>$<?= number_format(abs((float)$t['pnl']),2) ?>
    </strong></span>
    <?php if ($t['rr'] !== null): ?>
    <span class="muted small">R:R: <strong><?= htmlspecialchars($t['rr']) ?>R</strong></span>
    <?php endif; ?>
  </div>

  <hr>
  <div class="gap-row">
    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    <a href="trades.php" class="btn btn-ghost">Cancel</a>
  </div>

</form>
</div>

<script>
const emoLabels = <?= json_encode($emotionLabels) ?>;
document.querySelectorAll('.emo-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const val = parseInt(this.dataset.val);
        document.getElementById('emotion').value = val;
        document.getElementById('emo-label').textContent = emoLabels[val - 1];
        document.querySelectorAll('.emo-btn').forEach((b, i) =>
            b.classList.toggle('active', i + 1 === val));
    });
});

function calc() {
    const dir   = document.getElementById('dir').value;
    const entry = parseFloat(document.getElementById('entry').value) || 0;
    const exit  = parseFloat(document.getElementById('exit').value)  || 0;
    const sl    = parseFloat(document.getElementById('sl').value)    || 0;
    const size  = parseFloat(document.getElementById('sz').value)    || 0;

    const pnlEl = document.getElementById('c-pnl');
    const rrEl  = document.getElementById('c-rr');
    const outEl = document.getElementById('c-out');

    if (!entry || !exit || !size) {
        ['c-pnl','c-rr','c-out'].forEach(id => {
            const el = document.getElementById(id);
            el.textContent = '—'; el.className = 'cval';
        });
        return;
    }

    const pnl = dir === 'Long' ? (exit - entry) * size : (entry - exit) * size;
    const pos = pnl >= 0;

    pnlEl.textContent = (pos ? '+' : '−') + '$' + Math.abs(pnl).toFixed(2);
    pnlEl.className   = 'cval ' + (pos ? 'pos' : 'neg');

    outEl.textContent = pos ? 'WIN ✅' : 'LOSS ❌';
    outEl.className   = 'cval ' + (pos ? 'pos' : 'neg');

    if (sl > 0) {
        const risk = dir === 'Long' ? (entry - sl) * size : (sl - entry) * size;
        if (risk > 0) {
            rrEl.textContent = (Math.abs(pnl) / risk).toFixed(2) + 'R';
            rrEl.className   = 'cval ' + (pos ? 'pos' : 'neg');
        } else {
            rrEl.textContent = 'Invalid SL'; rrEl.className = 'cval';
        }
    } else {
        rrEl.textContent = 'No SL'; rrEl.className = 'cval muted';
    }
}

['dir','entry','exit','sl','sz'].forEach(id =>
    document.getElementById(id).addEventListener('input', calc));
document.getElementById('dir').addEventListener('change', calc);
calc();
</script>

<?php require_once 'footer.php'; ?>
