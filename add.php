<?php
// add.php — Add a new trade
require_once 'db.php';
require_once 'header.php';

$db     = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ── Collect & sanitise ─────────────────────────── */
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

    /* ── Validate ───────────────────────────────────── */
    if (!$date)                                    $errors[] = 'Trade date is required.';
    if (!$symbol)                                  $errors[] = 'Asset / symbol is required.';
    if (!in_array($direction, ['Long','Short']))   $errors[] = 'Direction must be Long or Short.';
    if (!is_numeric($entry) || (float)$entry <= 0) $errors[] = 'A valid entry price is required.';
    if (!is_numeric($exit_price) || (float)$exit_price <= 0) $errors[] = 'A valid exit price is required.';
    if (!is_numeric($size) || (float)$size <= 0)  $errors[] = 'A valid position size is required.';

    if (empty($errors)) {
        $e = (float)$entry;
        $x = (float)$exit_price;
        $s = (float)$size;
        $sl = ($stop_loss !== '' && is_numeric($stop_loss)) ? (float)$stop_loss : null;

        /* P&L */
        $pnl = $direction === 'Long' ? ($x - $e) * $s : ($e - $x) * $s;
        $pnl = round($pnl, 4);

        /* R:R (requires stop loss) */
        $rr = null;
        if ($sl !== null) {
            $risk = $direction === 'Long' ? ($e - $sl) * $s : ($sl - $e) * $s;
            if ($risk > 0) $rr = round(abs($pnl) / $risk, 2);
        }

        $stmt = $db->prepare("
            INSERT INTO trades
                (date, symbol, direction, entry, exit_price, stop_loss,
                 size, pnl, rr, strategy, notes, emotion, screenshot)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $date, $symbol, $direction, $e, $x, $sl,
            $s, $pnl, $rr, $strategy, $notes, $emotion, $screenshot
        ]);

        header('Location: trades.php?added=1');
        exit;
    }
}

/* Restore posted values on error */
$v = function(string $k, string $default = '') use ($errors): string {
    return count($errors) > 0 ? htmlspecialchars($_POST[$k] ?? $default) : $default;
};
$vEmotion = count($errors) > 0 ? (int)($_POST['emotion'] ?? 3) : 3;
$vDir     = count($errors) > 0 ? ($_POST['direction'] ?? 'Long') : 'Long';

$emotions = ['😰','😟','😐','😊','🤩'];
$emotionLabels = ['Anxious','Uncertain','Neutral','Confident','On Fire'];
?>

<!-- ── Alerts ──────────────────────────────────────────────── -->
<?php foreach ($errors as $err): ?>
<div class="alert alert-err">⚠ <?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-title">Add Trade</h1>
  <a href="trades.php" class="btn btn-ghost btn-sm">← Back to Trades</a>
</div>

<div class="card">
<form method="POST" action="add.php" id="tf" autocomplete="off">

  <div class="form-grid">

    <!-- Date -->
    <div class="form-group">
      <label>DATE *</label>
      <input type="date" name="date"
        value="<?= $v('date', date('Y-m-d')) ?>" required>
    </div>

    <!-- Symbol -->
    <div class="form-group">
      <label>ASSET / SYMBOL *</label>
      <input type="text" name="symbol" id="symbol"
        placeholder="BTC, AAPL, EURUSD, ES…"
        value="<?= $v('symbol') ?>" required maxlength="20">
    </div>

    <!-- Direction -->
    <div class="form-group">
      <label>DIRECTION *</label>
      <select name="direction" id="dir">
        <option value="Long"  <?= $vDir === 'Long'  ? 'selected' : '' ?>>📈 Long (Buy)</option>
        <option value="Short" <?= $vDir === 'Short' ? 'selected' : '' ?>>📉 Short (Sell)</option>
      </select>
    </div>

    <!-- Strategy -->
    <div class="form-group">
      <label>STRATEGY</label>
      <input type="text" name="strategy"
        placeholder="Breakout, Mean Reversion, Trend Follow…"
        value="<?= $v('strategy') ?>" maxlength="80">
    </div>

    <!-- Entry -->
    <div class="form-group">
      <label>ENTRY PRICE *</label>
      <input type="number" name="entry" id="entry"
        step="any" min="0" placeholder="0.00"
        value="<?= $v('entry') ?>" required>
    </div>

    <!-- Exit -->
    <div class="form-group">
      <label>EXIT PRICE *</label>
      <input type="number" name="exit_price" id="exit"
        step="any" min="0" placeholder="0.00"
        value="<?= $v('exit_price') ?>" required>
    </div>

    <!-- Stop loss -->
    <div class="form-group">
      <label>STOP LOSS <span class="muted small">(optional — needed for R:R)</span></label>
      <input type="number" name="stop_loss" id="sl"
        step="any" min="0" placeholder="0.00"
        value="<?= $v('stop_loss') ?>">
    </div>

    <!-- Size -->
    <div class="form-group">
      <label>POSITION SIZE * <span class="muted small">(units / shares / lots)</span></label>
      <input type="number" name="size" id="sz"
        step="any" min="0" placeholder="1"
        value="<?= $v('size') ?>" required>
    </div>

    <!-- Live Calc Preview -->
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

    <!-- Notes -->
    <div class="form-group full">
      <label>SETUP NOTES</label>
      <textarea name="notes" rows="3"
        placeholder="Describe your thesis, entry trigger, market context, confluences…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
    </div>

    <!-- Screenshot -->
    <div class="form-group">
      <label>SCREENSHOT URL <span class="muted small">(optional)</span></label>
      <input type="url" name="screenshot"
        placeholder="https://i.imgur.com/…"
        value="<?= $v('screenshot') ?>">
    </div>

    <!-- Emotion -->
    <div class="form-group">
      <label>EMOTIONAL STATE</label>
      <div class="emotion-row" id="emo-row">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <button type="button" class="emo-btn <?= $vEmotion===$i ? 'active':'' ?>"
          data-val="<?= $i ?>" title="<?= $emotionLabels[$i-1] ?> (<?= $i ?>/5)">
          <?= $emotions[$i-1] ?>
        </button>
        <?php endfor; ?>
        <span id="emo-label" style="font-size:12px;color:var(--text3);margin-left:4px">
          <?= $emotionLabels[$vEmotion-1] ?>
        </span>
      </div>
      <input type="hidden" name="emotion" id="emotion" value="<?= $vEmotion ?>">
    </div>

  </div><!-- /form-grid -->

  <hr>
  <div class="gap-row">
    <button type="submit" class="btn btn-primary">💾 Save Trade</button>
    <a href="trades.php" class="btn btn-ghost">Cancel</a>
  </div>

</form>
</div>

<script>
/* ── Emotion picker ──────────────────────────────────────── */
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

/* ── Live calc ───────────────────────────────────────────── */
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
        pnlEl.textContent = '—'; pnlEl.className = 'cval';
        rrEl.textContent  = '—'; rrEl.className  = 'cval';
        outEl.textContent = '—'; outEl.className = 'cval';
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
            const rr = Math.abs(pnl) / risk;
            rrEl.textContent = rr.toFixed(2) + 'R';
            rrEl.className   = 'cval ' + (pos ? 'pos' : 'neg');
        } else {
            rrEl.textContent = 'Invalid SL';
            rrEl.className   = 'cval';
        }
    } else {
        rrEl.textContent = 'No SL set';
        rrEl.className   = 'cval muted';
    }
}

['dir','entry','exit','sl','sz'].forEach(id =>
    document.getElementById(id).addEventListener('input', calc));
document.getElementById('dir').addEventListener('change', calc);
calc();
</script>

<?php require_once 'footer.php'; ?>
