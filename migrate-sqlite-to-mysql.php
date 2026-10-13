<?php
/**
 * Copies the legacy SQLite database (db/site.sqlite) into the MySQL database
 * configured in includes/config.php.
 *
 * Usage (command line only):
 *   php migrate-sqlite-to-mysql.php             # fills empty MySQL tables
 *   php migrate-sqlite-to-mysql.php --truncate  # empties MySQL tables first (re-run)
 *
 * Tables that already contain rows are skipped unless --truncate is given, so
 * re-running the script never destroys data that was already entered in MySQL.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo "Usage: php migrate-sqlite-to-mysql.php [--truncate]\n";
    exit(0);
}

require_once __DIR__ . '/includes/database.php';

$sqliteFile = __DIR__ . '/db/site.sqlite';
$truncate = in_array('--truncate', $argv, true);

/**
 * SQLite is loosely typed: coerce each value so it fits the MySQL column
 * type (empty strings are not valid for INT/DATETIME columns in strict mode).
 */
function coerceValue($value, $type) {
    if ($value === null) {
        return null;
    }
    $type = strtolower($type);

    if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double|year)/', $type)) {
        if ($value === '') return 0;
        return is_numeric($value) ? $value + 0 : 0;
    }

    if (preg_match('/^(date|datetime|timestamp|time)/', $type)) {
        return ($value === '') ? null : $value;
    }

    return (string)$value;
}

function out($message) {
    echo $message . "\n";
}

if (!file_exists($sqliteFile)) {
    fwrite(STDERR, "SQLite database not found: {$sqliteFile}\n");
    exit(1);
}

if (!in_array('sqlite', PDO::getAvailableDrivers())) {
    fwrite(STDERR, "The pdo_sqlite extension is required to read the legacy database.\n");
    exit(1);
}

try {
    $dst = $db->getConnection();
    $db->initSchema();

    $src = new PDO('sqlite:' . $sqliteFile);
    $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = $src->query(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);

    out('Source: ' . $sqliteFile);
    out('Target: mysql://' . DB_HOST . ':' . DB_PORT . '/' . DB_NAME);
    out(str_repeat('-', 60));

    $migrated = 0;
    $skipped = 0;

    foreach ($tables as $table) {
        if (!$db->tableExists($table)) {
            out("[skip] {$table}: no such table in MySQL");
            $skipped++;
            continue;
        }

        $srcColumns = $src->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
        $dstColumns = $dst->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

        $types = [];
        foreach ($dstColumns as $column) {
            $types[$column['Field']] = $column['Type'];
        }

        $columns = [];
        foreach ($srcColumns as $column) {
            if (isset($types[$column['name']])) {
                $columns[] = $column['name'];
            }
        }

        if (!$columns) {
            out("[skip] {$table}: no shared columns");
            $skipped++;
            continue;
        }

        $existing = (int)$dst->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($existing > 0 && !$truncate) {
            out("[skip] {$table}: {$existing} row(s) already in MySQL (use --truncate to replace)");
            $skipped++;
            continue;
        }

        if ($truncate) {
            $dst->exec("TRUNCATE TABLE `$table`");
        }

        $rows = $src->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            $quoted = array_map(fn($name) => "`{$name}`", $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $insert = $dst->prepare(
                'INSERT INTO `' . $table . '` (' . implode(', ', $quoted) . ') VALUES (' . $placeholders . ')'
            );

            $dst->beginTransaction();
            try {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $name) {
                        $values[] = coerceValue($row[$name] ?? null, $types[$name]);
                    }
                    $insert->execute($values);
                }
                $dst->commit();
            } catch (PDOException $e) {
                $dst->rollBack();
                fwrite(STDERR, "[fail] {$table}: " . $e->getMessage() . "\n");
                exit(1);
            }
        }

        out("[ok]   {$table}: " . count($rows) . " row(s) copied");
        $migrated += count($rows);
    }

    out(str_repeat('-', 60));
    out("Done. {$migrated} row(s) copied, {$skipped} table(s) skipped.");
    out('');

    $summary = $dst->prepare(
        'SELECT table_name AS t, table_rows AS r FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name'
    );
    $summary->execute([DB_NAME]);
    out('MySQL row counts:');
    foreach ($summary->fetchAll(PDO::FETCH_ASSOC) as $row) {
        out(sprintf('  %-24s %s', $row['t'], $row['r'] ?? 0));
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
