<?php

declare(strict_types=1);

/*
 * Importe un dump SQL (phpMyAdmin / mysqldump) dans une base MariaDB locale dédiée, via PDO.
 * Le conteneur app n'a pas de client mysql et le dump dépasse max_allowed_packet :
 * le fichier est donc découpé en instructions en tenant compte des chaînes et commentaires.
 *
 * Usage :
 *   php import-dump.php <dump.sql> --dry-run [--report=attendu.json]
 *   php import-dump.php <dump.sql> --db=<nom>_import [--host=database] [--port=3306]
 *       [--user=root] [--password=...] [--create-db] [--recreate] [--report=attendu.json]
 *
 * Garde-fous : la base cible doit se terminer par « _import » ; les instructions USE,
 * CREATE DATABASE et DROP DATABASE du dump sont refusées ; une base cible non vide fait
 * échouer l'import sauf avec --recreate.
 */

/** @return array<string, string|true> */
function parseOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = $parts[1] ?? true;
        } else {
            $options['_file'] = $arg;
        }
    }

    return $options;
}

/**
 * Découpe un script SQL en instructions. Les commentaires « -- » et « # » sont retirés,
 * les commentaires « /* *\/ » sont conservés (les /*!40101 ... *\/ sont exécutables).
 *
 * @return Generator<int, string>
 */
function splitStatements(string $sql): Generator
{
    $length = strlen($sql);
    $buffer = '';
    $i = 0;
    while ($i < $length) {
        $span = strcspn($sql, "'\"`;-#/", $i);
        $buffer .= substr($sql, $i, $span);
        $i += $span;
        if ($i >= $length) {
            break;
        }
        $char = $sql[$i];
        if ("'" === $char || '"' === $char || '`' === $char) {
            $end = $i + 1;
            while ($end < $length) {
                $next = strcspn($sql, $char.'\\', $end);
                $end += $next;
                if ($end >= $length) {
                    break;
                }
                if ('\\' === $sql[$end] && '`' !== $char) {
                    $end += 2;
                    continue;
                }
                break;
            }
            $buffer .= substr($sql, $i, $end - $i + 1);
            $i = $end + 1;
        } elseif (';' === $char) {
            $statement = trim($buffer);
            if ('' !== $statement) {
                yield $statement;
            }
            $buffer = '';
            ++$i;
        } elseif ('#' === $char || ('-' === $char && '-' === ($sql[$i + 1] ?? '') && ctype_space($sql[$i + 2] ?? "\n"))) {
            $eol = strpos($sql, "\n", $i);
            $i = false === $eol ? $length : $eol + 1;
            $buffer .= "\n";
        } elseif ('/' === $char && '*' === ($sql[$i + 1] ?? '')) {
            $end = strpos($sql, '*/', $i + 2);
            $end = false === $end ? $length : $end + 2;
            $buffer .= substr($sql, $i, $end - $i);
            $i = $end;
        } else {
            $buffer .= $char;
            ++$i;
        }
    }
    $statement = trim($buffer);
    if ('' !== $statement) {
        yield $statement;
    }
}

/** Compte les tuples d'un INSERT ... VALUES (...), (...) en ignorant le contenu des chaînes. */
function countInsertedRows(string $statement): int
{
    $start = stripos($statement, ' VALUES');
    if (false === $start) {
        return 0;
    }
    $rows = 0;
    $depth = 0;
    $length = strlen($statement);
    for ($i = $start + 7; $i < $length; ++$i) {
        $char = $statement[$i];
        if ("'" === $char || '"' === $char) {
            for (++$i; $i < $length && $statement[$i] !== $char; ++$i) {
                if ('\\' === $statement[$i]) {
                    ++$i;
                }
            }
        } elseif ('(' === $char) {
            if (0 === $depth++) {
                ++$rows;
            }
        } elseif (')' === $char) {
            --$depth;
        }
    }

    return $rows;
}

$options = parseOptions($argv);
$file = $options['_file'] ?? null;
if (!is_string($file) || !is_readable($file)) {
    fwrite(STDERR, "Dump introuvable. Usage : php import-dump.php <dump.sql> --dry-run | --db=<nom>_import\n");
    exit(2);
}

$dryRun = isset($options['dry-run']);
$dbName = is_string($options['db'] ?? null) ? $options['db'] : null;
if (!$dryRun && (null === $dbName || !preg_match('/^[A-Za-z0-9_]+_import$/', $dbName))) {
    fwrite(STDERR, "--db est obligatoire et doit se terminer par _import (ex. sf_mangatrackr_app_import).\n");
    exit(2);
}

$pdo = null;
if (!$dryRun) {
    $host = is_string($options['host'] ?? null) ? $options['host'] : 'database';
    $port = is_string($options['port'] ?? null) ? $options['port'] : '3306';
    $user = is_string($options['user'] ?? null) ? $options['user'] : 'root';
    $password = is_string($options['password'] ?? null) ? $options['password'] : (getenv('IMPORT_DB_PASSWORD') ?: '');
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $exists = (bool) $pdo->query('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '.$pdo->quote($dbName))->fetchColumn();
    if ($exists && isset($options['recreate'])) {
        $pdo->exec("DROP DATABASE `$dbName`");
        $exists = false;
    }
    if (!$exists) {
        if (!isset($options['create-db']) && !isset($options['recreate'])) {
            fwrite(STDERR, "La base $dbName n'existe pas : ajouter --create-db.\n");
            exit(2);
        }
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '.$pdo->quote($dbName))->fetchColumn();
    if ($tables > 0) {
        fwrite(STDERR, "La base $dbName contient déjà $tables table(s) : relancer avec --recreate.\n");
        exit(2);
    }
    $pdo->exec("USE `$dbName`");
}

$expectedRows = [];
$tablesCreated = [];
$counts = ['statements' => 0, 'create_table' => 0, 'insert' => 0, 'alter' => 0, 'other' => 0];
$startedAt = microtime(true);

foreach (splitStatements((string) file_get_contents($file)) as $statement) {
    ++$counts['statements'];
    if (preg_match('/^(USE\s|CREATE\s+DATABASE|DROP\s+DATABASE|CREATE\s+SCHEMA|DROP\s+SCHEMA)/i', $statement)) {
        fwrite(STDERR, 'Instruction refusée (changement de base) : '.substr($statement, 0, 80)."\n");
        exit(3);
    }
    if (preg_match('/^INSERT\s+INTO\s+`?([^`\s(]+)`?/i', $statement, $match)) {
        ++$counts['insert'];
        $expectedRows[$match[1]] = ($expectedRows[$match[1]] ?? 0) + countInsertedRows($statement);
    } elseif (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^`\s(]+)`?/i', $statement, $match)) {
        ++$counts['create_table'];
        $tablesCreated[] = $match[1];
        $expectedRows[$match[1]] ??= 0;
    } elseif (preg_match('/^ALTER\s/i', $statement)) {
        ++$counts['alter'];
    } else {
        ++$counts['other'];
    }

    if (null !== $pdo) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            fwrite(STDERR, sprintf("Échec à l'instruction n°%d (%s) : %s\n", $counts['statements'], substr($statement, 0, 120), $e->getMessage()));
            exit(4);
        }
    }
}

ksort($expectedRows);
$report = [
    'dump' => realpath($file),
    'dump_sha256' => hash_file('sha256', $file),
    'mode' => $dryRun ? 'dry-run' : 'import',
    'target_db' => $dbName,
    'duration_s' => round(microtime(true) - $startedAt, 2),
    'statements' => $counts,
    'tables' => count($tablesCreated),
    'expected_rows' => $expectedRows,
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (is_string($options['report'] ?? null)) {
    file_put_contents($options['report'], $json."\n");
}
echo $json, "\n";
