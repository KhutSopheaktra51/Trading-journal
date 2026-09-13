# 📈 TradeLog — Trading Journal
URL link🔗 : https://aleanjournalfx.up.railway.app/

A professional, dark-themed trading journal built with pure PHP + SQLite.
No framework, no MySQL setup needed — just PHP.

## Quick Start

```bash
# 1. Place all files in one folder
# 2. Start the built-in PHP server
php -S localhost:8000

# 3. Open your browser
open http://localhost:8000
```

The SQLite database (`journal.db`) is created automatically on first run.

## Requirements

- PHP 8.0+ with PDO and SQLite extensions enabled
  ```bash
  php -m | grep -E "pdo|sqlite"  # should show PDO and pdo_sqlite
  ```

## File Structure

```
trading-journal/
├── db.php          # Database connection & schema (SQLite via PDO)
├── header.php      # Shared HTML head, CSS variables, nav bar
├── footer.php      # Closing HTML tags
├── index.php       # Dashboard — stats, equity curve, recent trades
├── add.php         # Add trade form with live P&L/R:R calculator
├── trades.php      # Paginated trade list with filters & inline delete
├── edit.php        # Edit trade (pre-filled form)
├── stats.php       # Statistics — strategy breakdown, charts, best/worst
└── journal.db      # Auto-created SQLite database (git-ignored)
```

## Features

| Feature | Details |
|---------|---------|
| **Dashboard** | Win rate, total P&L, avg R:R, equity curve (Chart.js), recent trades |
| **Add Trade** | Date, symbol, direction, entry/exit/stop, size, strategy, notes, emotion (1–5), screenshot URL |
| **Auto-calc** | P&L and R:R calculated server-side on save; live preview in browser |
| **Trade List** | Paginated (25/page), filterable by date range · symbol · direction · strategy · win/loss |
| **Edit Trade** | Full pre-filled form, recalculates P&L/R:R on save |
| **Statistics** | Win rate by strategy, profit factor, expected value, day-of-week chart, monthly bar chart, best/worst 5 trades, emotion vs performance |
| **Security** | Prepared statements throughout, `htmlspecialchars()` on all output |
| **Design** | Dark trading aesthetic, CSS variables, responsive grid, mobile-friendly |

## P&L Calculation

```
Long:  P&L = (exit_price - entry) × size
Short: P&L = (entry - exit_price) × size
```

## R:R Calculation (requires stop loss)

```
Risk   = |entry - stop_loss| × size
R:R    = |P&L| / Risk
```

## Database Schema

```sql
CREATE TABLE trades (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    date        TEXT    NOT NULL,
    symbol      TEXT    NOT NULL,
    direction   TEXT    NOT NULL,      -- 'Long' or 'Short'
    entry       REAL    NOT NULL,
    exit_price  REAL    NOT NULL,
    stop_loss   REAL,                  -- nullable, needed for R:R
    size        REAL    NOT NULL,
    pnl         REAL    NOT NULL DEFAULT 0,
    rr          REAL,                  -- nullable if no stop loss
    strategy    TEXT    NOT NULL DEFAULT '',
    notes       TEXT    NOT NULL DEFAULT '',
    emotion     INTEGER NOT NULL DEFAULT 3,  -- 1–5
    screenshot  TEXT    NOT NULL DEFAULT '', -- URL
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
```

## License

MIT — use freely for personal or commercial trading journaling.
