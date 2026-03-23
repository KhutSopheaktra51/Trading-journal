<?php
// db.php — SQLite connection & schema bootstrap

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dbFile = __DIR__ . '/journal.db';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode = WAL;");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trades (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            date        TEXT    NOT NULL,
            symbol      TEXT    NOT NULL,
            direction   TEXT    NOT NULL,
            entry       REAL    NOT NULL,
            exit_price  REAL    NOT NULL,
            stop_loss   REAL,
            size        REAL    NOT NULL,
            pnl         REAL    NOT NULL DEFAULT 0,
            rr          REAL,
            strategy    TEXT    NOT NULL DEFAULT '',
            notes       TEXT    NOT NULL DEFAULT '',
            emotion     INTEGER NOT NULL DEFAULT 3,
            screenshot  TEXT    NOT NULL DEFAULT '',
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    return $pdo;
}
