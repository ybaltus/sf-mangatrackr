<?php

declare(strict_types=1);

/*
 * Inventaire d'une base (MariaDB/MySQL ou PostgreSQL) : nombre de lignes et empreinte du
 * contenu de chaque table, normalisée pour être comparable d'un moteur à l'autre.
 *
 * Usage :
 *   php inventaire.php --dsn="mysql:host=database;port=3306;dbname=x_import" --user=root [--password=...] [--out=inv.json]
 *   php inventaire.php --dsn="pgsql:host=postgres;port=5432;dbname=x" --user=app [--password=...] [--out=inv.json]
 *   php inventaire.php --compare=a.json,b.json       compare deux inventaires (lignes + empreintes)
 *   php inventaire.php --expected=attendu.json --inventory=inv.json   compare aux comptages d'un dump
 *   Le mot de passe peut aussi venir de la variable INVENTAIRE_DB_PASSWORD.
 *
 * Normalisation : NULL → \N, booléens → 1/0, JSON ré-encodé, flottants via (string)(float),
 * colonnes triées par nom, lignes triées par clé primaire. Limite : un écart de précision
 * des flottants ou de fuseau sur les dates apparaîtra comme une différence d'empreinte.
 */

/** @return array<string, string|true> */
function parseOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        $parts = explode('=', ltrim($arg, '-'), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }

    return $options;
}

function normalize(mixed $value): string
{
    if (null === $value) {
        return '\N';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_float($value)) {
        return (string) $value;
    }
    $value = (string) $value;
    if ('' !== $value && ('{' === $value[0] || '[' === $value[0])) {
        $decoded = json_decode($value, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
    if (preg_match('/^-?\d+\.\d+$/', $value)) {
        return rtrim(rtrim($value, '0'), '.');
    }

    return $value;
}

/** @return array<string, mixed> */
function inventory(PDO $pdo): array
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $quote = 'pgsql' === $driver ? static fn (string $n): string => '"'.$n.'"' : static fn (string $n): string => '`'.$n.'`';
    $schema = 'pgsql' === $driver ? "'public'" : 'DATABASE()';

    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = $schema AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    $result = [];
    foreach ($tables as $table) {
        $pk = $pdo->query("SELECT k.column_name FROM information_schema.table_constraints t
            JOIN information_schema.key_column_usage k ON k.constraint_name = t.constraint_name AND k.table_name = t.table_name AND k.table_schema = t.table_schema
            WHERE t.constraint_type = 'PRIMARY KEY' AND t.table_schema = $schema AND t.table_name = ".$pdo->quote($table).' ORDER BY k.ordinal_position')->fetchAll(PDO::FETCH_COLUMN);
        $columns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = $schema AND table_name = ".$pdo->quote($table).' ORDER BY column_name')->fetchAll(PDO::FETCH_COLUMN);
        $order = implode(', ', array_map($quote, [] !== $pk ? $pk : $columns));

        $hash = hash_init('sha256');
        $rows = 0;
        $statement = $pdo->query('SELECT * FROM '.$quote($table).' ORDER BY '.$order);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            ksort($row);
            hash_update($hash, implode("\x1F", array_map('normalize', $row))."\x1E");
            ++$rows;
        }
        $result[$table] = ['rows' => $rows, 'sha256' => hash_final($hash), 'primary_key' => $pk, 'columns' => $columns];
    }

    return ['driver' => $driver, 'generated_at' => date('c'), 'tables' => $result];
}

/** @return array<string, mixed> */
function readJson(string $path): array
{
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        fwrite(STDERR, "JSON illisible : $path\n");
        exit(2);
    }

    return $data;
}

$options = parseOptions($argv);

if (is_string($options['compare'] ?? null)) {
    [$left, $right] = array_map('readJson', explode(',', $options['compare'], 2));
    $names = array_unique([...array_keys($left['tables']), ...array_keys($right['tables'])]);
    sort($names);
    $differences = 0;
    printf("%-32s %10s %10s %s\n", 'table', 'gauche', 'droite', 'résultat');
    foreach ($names as $name) {
        $a = $left['tables'][$name] ?? null;
        $b = $right['tables'][$name] ?? null;
        $status = match (true) {
            null === $a || null === $b => 'TABLE ABSENTE',
            $a['rows'] !== $b['rows'] => 'LIGNES DIFFÉRENTES',
            $a['sha256'] !== $b['sha256'] => 'CONTENU DIFFÉRENT',
            $a['columns'] !== $b['columns'] => 'COLONNES DIFFÉRENTES',
            default => 'OK',
        };
        $differences += 'OK' === $status ? 0 : 1;
        printf("%-32s %10s %10s %s\n", $name, $a['rows'] ?? '-', $b['rows'] ?? '-', $status);
    }
    echo 0 === $differences ? "Parité complète.\n" : "$differences table(s) en écart.\n";
    exit(0 === $differences ? 0 : 1);
}

if (is_string($options['expected'] ?? null) && is_string($options['inventory'] ?? null)) {
    $expected = readJson($options['expected'])['expected_rows'];
    $actual = readJson($options['inventory'])['tables'];
    $names = array_unique([...array_keys($expected), ...array_keys($actual)]);
    sort($names);
    $differences = 0;
    printf("%-32s %10s %10s %s\n", 'table', 'dump', 'base', 'résultat');
    foreach ($names as $name) {
        $want = $expected[$name] ?? null;
        $got = $actual[$name]['rows'] ?? null;
        $ok = $want === $got;
        $differences += $ok ? 0 : 1;
        printf("%-32s %10s %10s %s\n", $name, $want ?? '-', $got ?? '-', $ok ? 'OK' : 'ÉCART');
    }
    echo 0 === $differences ? "Toutes les tables du dump sont complètes.\n" : "$differences table(s) en écart.\n";
    exit(0 === $differences ? 0 : 1);
}

if (!is_string($options['dsn'] ?? null)) {
    fwrite(STDERR, "Usage : --dsn=... --user=... | --compare=a.json,b.json | --expected=attendu.json --inventory=inv.json\n");
    exit(2);
}

$password = is_string($options['password'] ?? null) ? $options['password'] : (getenv('INVENTAIRE_DB_PASSWORD') ?: '');
$pdo = new PDO($options['dsn'], is_string($options['user'] ?? null) ? $options['user'] : null, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$json = (string) json_encode(inventory($pdo), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (is_string($options['out'] ?? null)) {
    file_put_contents($options['out'], $json."\n");
}
echo $json, "\n";
