<?php
// header.php — shared HTML <head>, CSS, and <nav>
$_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ucfirst($_page === 'index' ? 'Dashboard' : $_page) ?> — TradeLog</title>
<style>
/* ─── CSS Variables ─────────────────────────────────── */
:root {
  --bg:       #060d1a;
  --bg2:      #0b1628;
  --bg3:      #101f38;
  --bg4:      #162440;
  --border:   #1a2e4a;
  --borderL:  #23406a;
  --text:     #dce8f8;
  --text2:    #6e91b8;
  --text3:    #354f6e;
  --accent:   #3d8ef0;
  --accentH:  #2575de;
  --accentD:  rgba(61,142,240,.13);
  --green:    #00cc99;
  --greenD:   rgba(0,204,153,.10);
  --red:      #ff3f5c;
  --redD:     rgba(255,63,92,.10);
  --yellow:   #f5c430;
  --yellowD:  rgba(245,196,48,.10);
  --purple:   #a259f7;
  --purpleD:  rgba(162,89,247,.10);
  --nav:      62px;
  --r:        9px;
  --shadow:   0 4px 28px rgba(0,0,0,.55);
  --t:        .17s ease;
}

/* ─── Reset ─────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:var(--bg); color:var(--text);
  min-height:100vh; font-size:14px; line-height:1.6;
}
a{color:var(--accent);text-decoration:none;transition:color var(--t)}
a:hover{color:var(--accentH)}
button,input,select,textarea{font-family:inherit}
img{max-width:100%}

/* ─── Navigation ─────────────────────────────────────── */
.nav{
  position:fixed;top:0;left:0;right:0;z-index:200;
  height:var(--nav);
  background:rgba(6,13,26,.96);
  backdrop-filter:blur(16px);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 28px;gap:8px;
}
.nav-logo{
  font-size:17px;font-weight:800;color:var(--text);
  display:flex;align-items:center;gap:7px;margin-right:10px;
  letter-spacing:-.3px;white-space:nowrap;
}
.nav-logo .icon{font-size:20px}
.nav-logo em{color:var(--accent);font-style:normal}
.nav-links{display:flex;align-items:center;gap:2px;flex:1}
.nav-link{
  padding:7px 15px;border-radius:var(--r);
  color:var(--text2);font-size:13px;font-weight:500;
  transition:all var(--t);white-space:nowrap;
}
.nav-link:hover{background:var(--bg3);color:var(--text)}
.nav-link.active{background:var(--accentD);color:var(--accent)}
.nav-link.add-btn{
  background:var(--accent);color:#fff;margin-left:4px;
  padding:7px 16px;
}
.nav-link.add-btn:hover{background:var(--accentH);color:#fff}

/* ─── Layout ─────────────────────────────────────────── */
.main{
  padding:calc(var(--nav) + 30px) 28px 56px;
  max-width:1320px;margin:0 auto;
}
.page-header{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:26px;flex-wrap:wrap;gap:12px;
}
.page-title{
  font-size:21px;font-weight:700;color:var(--text);
  display:flex;align-items:center;gap:10px;
}
.page-title .badge-count{
  font-size:12px;font-weight:500;color:var(--text2);
  background:var(--bg3);padding:2px 10px;border-radius:20px;
  border:1px solid var(--border);
}

/* ─── Stat Cards ─────────────────────────────────────── */
.stats-grid{
  display:grid;grid-template-columns:repeat(4,1fr);gap:16px;
  margin-bottom:24px;
}
.stat-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--r);padding:20px 22px;
  transition:border-color var(--t),transform var(--t);
  position:relative;overflow:hidden;
}
.stat-card::after{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:var(--stat-color,var(--accent));opacity:.7;
}
.stat-card:hover{border-color:var(--borderL);transform:translateY(-1px)}
.stat-label{
  font-size:10.5px;font-weight:700;letter-spacing:.09em;
  text-transform:uppercase;color:var(--text3);margin-bottom:10px;
}
.stat-value{font-size:30px;font-weight:800;color:var(--text);line-height:1.1}
.stat-value.green{color:var(--green)}
.stat-value.red{color:var(--red)}
.stat-value.accent{color:var(--accent)}
.stat-value.purple{color:var(--purple)}
.stat-sub{margin-top:6px;font-size:11.5px;color:var(--text3)}

/* ─── Cards ──────────────────────────────────────────── */
.card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--r);padding:22px;
  box-shadow:var(--shadow);
}
.card-title{
  font-size:10.5px;font-weight:700;letter-spacing:.09em;
  text-transform:uppercase;color:var(--text3);margin-bottom:16px;
  display:flex;align-items:center;gap:6px;
}
.card-title::before{
  content:'';display:inline-block;width:3px;height:12px;
  background:var(--accent);border-radius:2px;
}

/* ─── Tables ─────────────────────────────────────────── */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13.5px}
thead th{
  text-align:left;padding:9px 14px;
  font-size:10.5px;font-weight:700;letter-spacing:.07em;
  text-transform:uppercase;color:var(--text3);
  border-bottom:1px solid var(--border);white-space:nowrap;
  background:var(--bg);
}
tbody td{
  padding:12px 14px;border-bottom:1px solid rgba(26,46,74,.7);
  vertical-align:middle;
}
tbody tr{transition:background var(--t)}
tbody tr:hover{background:rgba(255,255,255,.025)}
tbody tr:last-child td{border-bottom:none}
tbody tr.win{background:rgba(0,204,153,.03)}
tbody tr.win:hover{background:rgba(0,204,153,.06)}
tbody tr.win td:first-child{border-left:3px solid var(--green)}
tbody tr.loss{background:rgba(255,63,92,.03)}
tbody tr.loss:hover{background:rgba(255,63,92,.06)}
tbody tr.loss td:first-child{border-left:3px solid var(--red)}

/* ─── Badges ─────────────────────────────────────────── */
.badge{
  display:inline-flex;align-items:center;
  padding:3px 9px;border-radius:20px;
  font-size:11px;font-weight:700;letter-spacing:.04em;white-space:nowrap;
}
.badge-long {background:var(--accentD);color:var(--accent)}
.badge-short{background:var(--redD);color:var(--red)}
.badge-win  {background:var(--greenD);color:var(--green)}
.badge-loss {background:var(--redD);color:var(--red)}
.badge-neutral{background:var(--bg3);color:var(--text2)}

/* ─── Forms ──────────────────────────────────────────── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
.form-group.col3{grid-column:span 1}
label{font-size:11px;font-weight:700;color:var(--text2);letter-spacing:.06em}
input[type="text"],
input[type="number"],
input[type="date"],
input[type="url"],
select,textarea{
  background:var(--bg3);border:1.5px solid var(--border);
  border-radius:7px;padding:10px 13px;
  color:var(--text);font-size:13.5px;
  transition:border-color var(--t),box-shadow var(--t);
  outline:none;width:100%;
}
input:focus,select:focus,textarea:focus{
  border-color:var(--accent);
  box-shadow:0 0 0 3px var(--accentD);
}
input::placeholder,textarea::placeholder{color:var(--text3)}
select option{background:var(--bg3)}
textarea{resize:vertical;min-height:80px}
input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(.5)}

/* Emotion picker */
.emotion-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.emo-btn{
  width:38px;height:38px;border-radius:50%;cursor:pointer;
  border:2px solid var(--border);background:var(--bg3);
  font-size:18px;display:flex;align-items:center;justify-content:center;
  transition:all var(--t);
}
.emo-btn:hover{border-color:var(--borderL);transform:scale(1.1)}
.emo-btn.active{border-color:var(--accent);background:var(--accentD);transform:scale(1.1)}

/* Calc preview */
.calc-box{
  background:var(--bg);border:1.5px solid var(--border);
  border-radius:var(--r);padding:16px 20px;
  display:grid;grid-template-columns:repeat(3,1fr);gap:14px;
  margin-top:2px;
}
.calc-item .clabel{font-size:10px;color:var(--text3);font-weight:700;letter-spacing:.07em;text-transform:uppercase;margin-bottom:4px}
.calc-item .cval{font-size:20px;font-weight:800;color:var(--text2)}
.calc-item .cval.pos{color:var(--green)}
.calc-item .cval.neg{color:var(--red)}

/* ─── Buttons ────────────────────────────────────────── */
.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:9px 20px;border-radius:7px;font-size:13.5px;
  font-weight:600;cursor:pointer;border:none;
  transition:all var(--t);white-space:nowrap;text-decoration:none;
}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accentH);color:#fff;transform:translateY(-1px);box-shadow:0 4px 16px rgba(61,142,240,.35)}
.btn-ghost{background:var(--bg3);color:var(--text2);border:1.5px solid var(--border)}
.btn-ghost:hover{background:var(--bg4);color:var(--text);border-color:var(--borderL)}
.btn-danger{background:var(--redD);color:var(--red);border:1.5px solid rgba(255,63,92,.2)}
.btn-danger:hover{background:var(--red);color:#fff;border-color:var(--red)}
.btn-sm{padding:6px 13px;font-size:12px}
.btn-xs{padding:4px 10px;font-size:11px}

/* ─── Filters bar ────────────────────────────────────── */
.filters{
  display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--r);padding:16px 18px;margin-bottom:18px;
}
.filters .form-group{flex:1;min-width:130px}
.filters label{font-size:10px}
.filters input,.filters select{font-size:13px;padding:8px 11px}

/* ─── Pagination ─────────────────────────────────────── */
.pager{display:flex;gap:6px;align-items:center;justify-content:center;margin-top:20px;flex-wrap:wrap}
.pager a,.pager span{
  padding:6px 13px;border-radius:6px;font-size:13px;font-weight:500;
  border:1px solid var(--border);color:var(--text2);
  transition:all var(--t);text-decoration:none;
}
.pager a:hover{background:var(--bg3);color:var(--text)}
.pager .cur{background:var(--accent);color:#fff;border-color:var(--accent)}
.pager .info{border:none;background:none;color:var(--text3);font-size:12px}

/* ─── Alerts ─────────────────────────────────────────── */
.alert{
  padding:12px 18px;border-radius:var(--r);margin-bottom:18px;
  font-size:13.5px;font-weight:500;display:flex;align-items:center;gap:10px;
}
.alert-ok {background:var(--greenD);color:var(--green);border:1px solid rgba(0,204,153,.25)}
.alert-err{background:var(--redD);color:var(--red);border:1px solid rgba(255,63,92,.25)}

/* ─── Chart wrappers ─────────────────────────────────── */
.chart-box{position:relative;height:260px}
.chart-box.sm{height:200px}

/* ─── Two-col grid ───────────────────────────────────── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}

/* ─── Empty state ────────────────────────────────────── */
.empty{text-align:center;padding:48px 24px;color:var(--text3)}
.empty .ei{font-size:52px;margin-bottom:14px;opacity:.35;display:block}
.empty h3{font-size:16px;color:var(--text2);margin-bottom:6px;font-weight:600}
.empty p{font-size:13px}

/* ─── Progress bar ───────────────────────────────────── */
.prog-wrap{display:flex;align-items:center;gap:8px}
.prog{flex:1;height:5px;background:var(--bg4);border-radius:3px;overflow:hidden}
.prog-fill{height:100%;border-radius:3px;transition:width .4s ease}

/* ─── Misc ───────────────────────────────────────────── */
.mono{font-family:'Courier New',monospace;font-size:12.5px}
.muted{color:var(--text3)}
.small{font-size:12px}
hr{border:none;border-top:1px solid var(--border);margin:20px 0}
.gap-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

/* ─── Responsive ─────────────────────────────────────── */
@media(max-width:1000px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .three-col{grid-template-columns:1fr 1fr}
}
@media(max-width:700px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .two-col,.three-col{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
  .form-group.full{grid-column:1}
  .calc-box{grid-template-columns:1fr 1fr}
  .main{padding:calc(var(--nav) + 16px) 14px 40px}
  .nav{padding:0 14px}
  .nav-logo{font-size:15px}
  .nav-link{padding:6px 10px;font-size:12px}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .filters .form-group{min-width:100%}
}
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-logo">
    <span class="icon">📈</span> Trade<em>Log</em>
  </div>
  <div class="nav-links">
    <a href="index.php"  class="nav-link <?= $_page==='index'  ? 'active':'' ?>">Dashboard</a>
    <a href="trades.php" class="nav-link <?= $_page==='trades' ? 'active':'' ?>">Trades</a>
    <a href="stats.php"  class="nav-link <?= $_page==='stats'  ? 'active':'' ?>">Statistics</a>
    <a href="add.php"    class="nav-link add-btn <?= $_page==='add'||$_page==='edit' ? 'active':'' ?>">+ Add Trade</a>
  </div>
</nav>

<main class="main">
