<?php

declare(strict_types=1);

/*
 * Inventaire d'une base (MariaDB/MySQL ou PostgreSQL) : nombre de lignes et empreinte du
 * contenu de chaque table, normalisée pour être comparable d'un moteur à l'autre.
 *
 * Usage :
 *   php inventaire.php --dsn="mysql:host=database;port=3306;dbname=x_import" --user=root [--password=...] [--out=inv.json] [--strict]
 *   php inventaire.php --dsn="pgsql:host=postgres;port=5432;dbname=x" --user=app [--password=...] [--out=inv.json] [--strict]
 *   php inventaire.php --compare=a.json,b.json [--rename=user:app_user,...] [--ignore=<table>]... [--require-baseline=<Version>]
 *     compare deux inventaires (lignes + empreintes + colonnes + séquences de l'inventaire PostgreSQL) ;
 *     --rename   correspondance nom de table gauche:droite (ex. user renommée app_user ; côté gauche seulement)
 *     --ignore   table exclue de la parité (répétable ou séparée par des virgules) ; affichée IGNORÉE, jamais en silence
 *     --require-baseline  la liste des versions de doctrine_migration_versions du fichier de DROITE doit contenir cette version
 *   php inventaire.php --expected=attendu.json --inventory=inv.json   compare aux comptages d'un dump
 *   Le mot de passe peut aussi venir de la variable INVENTAIRE_DB_PASSWORD.
 *
 * Codes de sortie : 0 parité ; 1 écart réel ; 2 erreur d'usage ou de lecture ; 3 connexion impossible
 * ou exception inattendue pendant l'inventaire (message générique, sans DSN ni mot de passe).
 *
 * Mode par défaut (tolérant) : NULL → \N, booléens → 1/0, JSON ré-encodé, colonnes numériques
 * (decimal, float, double) comparées sans zéros finals, colonnes triées par nom, lignes triées
 * par clé primaire (composite incluse). Les colonnes texte ne sont jamais modifiées sauf JSON.
 * Mode --strict : valeurs brutes lues en texte (NULL → \N, booléens → 1/0, rien d'autre) ;
 * à utiliser des deux côtés, la comparaison refuse de mélanger les modes.
 * L'inventaire MariaDB contient AUTO_INCREMENT par table ; l'inventaire PostgreSQL contient les séquences.
 * Règle D3 (ADR 0012) à la comparaison : prochaine valeur attendue = GREATEST(MAX(colonne)+1, AUTO_INCREMENT source) ;
 * AUTO_INCREMENT source absent pour une table à séquence = écart (inventaire gauche sans cette donnée)
 * et la liste des versions de doctrine_migration_versions (identifiants techniques).
 */

ini_set('zend.exception_ignore_args', '1'); // pas d'argument (mot de passe) dans les traces

function usageError(string $message): never
{
    fwrite(STDERR, "ERREUR : $message\n");
    exit(2);
}

/** @return array<string, string|true|list<string>> */
function parseOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        $parts = explode('=', ltrim($arg, '-'), 2);
        if ('ignore' === $parts[0] && isset($parts[1])) {
            $options['ignore'] = [...($options['ignore'] ?? []), ...explode(',', $parts[1])];
            continue;
        }
        $options[$parts[0]] = $parts[1] ?? true;
    }

    return $options;
}

function normalize(mixed $value, string $type = '', bool $strict = false): string
{
    if (null === $value) {
        return '\N';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if ($strict) {
        return (string) $value;
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
    if (in_array($type, ['decimal', 'numeric', 'float', 'double', 'double precision', 'real'], true) && preg_match('/^-?\d+\.\d+$/', $value)) {
        return rtrim(rtrim($value, '0'), '.');
    }

    return $value;
}

/** @return array<string, mixed> */
function inventory(PDO $pdo, bool $strict): array
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $quote = 'pgsql' === $driver ? static fn (string $n): string => '"'.$n.'"' : static fn (string $n): string => '`'.$n.'`';
    $schema = 'pgsql' === $driver ? "'public'" : 'DATABASE()';

    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = $schema AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    $result = [];
    $sequences = [];
    $migrations = [];
    foreach ($tables as $table) {
        $pk = $pdo->query("SELECT k.column_name FROM information_schema.table_constraints t
            JOIN information_schema.key_column_usage k ON k.constraint_name = t.constraint_name AND k.table_name = t.table_name AND k.table_schema = t.table_schema
            WHERE t.constraint_type = 'PRIMARY KEY' AND t.table_schema = $schema AND t.table_name = ".$pdo->quote($table).' ORDER BY k.ordinal_position')->fetchAll(PDO::FETCH_COLUMN);
        $columnTypes = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = $schema AND table_name = ".$pdo->quote($table).' ORDER BY column_name')->fetchAll(PDO::FETCH_KEY_PAIR);
        $columns = array_keys($columnTypes);
        $order = implode(', ', array_map($quote, [] !== $pk ? $pk : $columns));

        $hash = hash_init('sha256');
        $rows = 0;
        $statement = $pdo->query('SELECT * FROM '.$quote($table).' ORDER BY '.$order);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            ksort($row);
            $values = [];
            foreach ($row as $column => $value) {
                $values[] = normalize($value, (string) $columnTypes[$column], $strict);
            }
            hash_update($hash, implode("\x1F", $values)."\x1E");
            ++$rows;
        }
        $autoIncrement = null;
        if ('pgsql' !== $driver) {
            // AUTO_INCREMENT de la table (null si la table n'a pas de colonne auto-incrémentée ou si non conservé par l'import)
            $ai = $pdo->query('SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '.$pdo->quote($table))->fetchColumn();
            $autoIncrement = false === $ai || null === $ai ? null : (int) $ai;
        }
        $result[$table] = ['rows' => $rows, 'sha256' => hash_final($hash), 'primary_key' => $pk, 'columns' => $columns] + ('pgsql' !== $driver ? ['auto_increment' => $autoIncrement] : []);

        if ('doctrine_migration_versions' === $table) {
            $migrations = $pdo->query('SELECT version FROM '.$quote($table).' ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
        }
        if ('pgsql' === $driver) {
            foreach ($columns as $column) {
                $seq = $pdo->query('SELECT pg_get_serial_sequence('.$pdo->quote($quote($table)).', '.$pdo->quote($column).')')->fetchColumn();
                if (is_string($seq)) {
                    [$last, $called] = $pdo->query("SELECT last_value, is_called FROM $seq")->fetch(PDO::FETCH_NUM);
                    $max = $pdo->query('SELECT MAX('.$quote($column).') FROM '.$quote($table))->fetchColumn();
                    $sequences["$table.$column"] = [
                        'next' => filter_var($called, FILTER_VALIDATE_BOOLEAN) ? (int) $last + 1 : (int) $last,
                        'table' => $table,
                        'expected' => null === $max || false === $max ? 1 : (int) $max + 1, // MAX+1 ; la règle D3 y ajoute AUTO_INCREMENT source à la comparaison
                    ];
                }
            }
        }
    }

    return [
        'driver' => $driver, 'mode' => $strict ? 'strict' : 'tolerant', 'generated_at' => date('c'),
        'tables' => $result, 'sequences' => $sequences, 'migrations' => $migrations,
    ];
}

/** @return array<string, mixed> */
function readJson(string $path): array
{
    $data = json_decode(is_readable($path) ? (string) file_get_contents($path) : '', true);
    if (!is_array($data)) {
        usageError("JSON illisible : $path");
    }

    return $data;
}

$options = parseOptions($argv);

if (is_string($options['compare'] ?? null)) {
    $files = explode(',', $options['compare'], 2);
    if (2 !== count($files)) {
        usageError('--compare attend deux fichiers : a.json,b.json');
    }
    [$left, $right] = array_map('readJson', $files);
    if (($left['mode'] ?? 'tolerant') !== ($right['mode'] ?? 'tolerant')) {
        usageError('inventaires de modes différents (strict / tolérant) : non comparables.');
    }
    if (is_string($options['rename'] ?? null)) {
        foreach (explode(',', $options['rename']) as $pair) {
            if (!str_contains($pair, ':')) {
                usageError("--rename : paire invalide « $pair » (attendu gauche:droite).");
            }
            [$from, $to] = explode(':', $pair, 2);
            if (isset($left['tables'][$to])) {
                usageError("--rename : la table « $to » existe déjà à gauche.");
            }
            if (isset($left['tables'][$from])) {
                $left['tables'][$to] = $left['tables'][$from];
                unset($left['tables'][$from]);
            }
        }
    }
    $ignored = is_array($options['ignore'] ?? null) ? $options['ignore'] : [];
    $names = array_unique([...array_keys($left['tables']), ...array_keys($right['tables'])]);
    sort($names);
    $differences = 0;
    printf("%-32s %10s %10s %s\n", 'table', 'gauche', 'droite', 'résultat');
    foreach ($names as $name) {
        $a = $left['tables'][$name] ?? null;
        $b = $right['tables'][$name] ?? null;
        $status = match (true) {
            in_array($name, $ignored, true) => 'IGNORÉE (demandé par --ignore)',
            null === $a || null === $b => 'TABLE ABSENTE',
            $a['rows'] !== $b['rows'] => 'LIGNES DIFFÉRENTES',
            $a['sha256'] !== $b['sha256'] => 'CONTENU DIFFÉRENT',
            $a['columns'] !== $b['columns'] => 'COLONNES DIFFÉRENTES',
            default => 'OK',
        };
        $differences += 'OK' === $status || str_starts_with($status, 'IGNORÉE') ? 0 : 1;
        printf("%-32s %10s %10s %s\n", $name, $a['rows'] ?? '-', $b['rows'] ?? '-', $status);
    }
    foreach ($ignored as $name) {
        if (!in_array($name, $names, true)) {
            usageError("--ignore : table inconnue « $name ».");
        }
    }

    // Séquences de l'inventaire de droite (règle D3) : prochaine valeur attendue =
    // GREATEST(MAX(colonne) + 1, AUTO_INCREMENT de la table source), la source étant l'inventaire de gauche.
    $sequences = $right['sequences'] ?? [];
    $badSequences = [];
    foreach ($sequences as $col => $q) {
        $leftTable = $left['tables'][$q['table'] ?? explode('.', $col)[0]] ?? null;
        $ai = is_array($leftTable) ? ($leftTable['auto_increment'] ?? null) : null;
        if (null === $ai) {
            $badSequences[$col] = $q + ['reason' => 'AUTO_INCREMENT source absent'];
            continue;
        }
        $want = max((int) $q['expected'], (int) $ai);
        if ($q['next'] !== $want) {
            $badSequences[$col] = ['next' => $q['next'], 'expected' => $want];
        }
    }
    if ([] !== $sequences || 'pgsql' === ($right['driver'] ?? '')) {
        printf("\nSéquences (droite, règle D3 GREATEST(MAX+1, AUTO_INCREMENT source)) : %d, en écart : %d\n", count($sequences), count($badSequences));
        foreach ($badSequences as $col => $q) {
            printf("  %-40s prochaine=%d attendue=%s ÉCART%s\n", $col, $q['next'], isset($q['reason']) ? '?' : (string) $q['expected'], isset($q['reason']) ? ' ('.$q['reason'].')' : '');
        }
        $differences += count($badSequences) + ([] === $sequences ? 1 : 0);
    }

    if (is_string($options['require-baseline'] ?? null)) {
        $wanted = $options['require-baseline'];
        $has = [] !== array_filter($right['migrations'] ?? [], static fn (string $v): bool => $v === $wanted || str_ends_with($v, '\\'.$wanted));
        printf("doctrine_migration_versions (droite) : %d version(s), baseline %s %s\n", count($right['migrations'] ?? []), $wanted, $has ? 'présente' : 'ABSENTE');
        $differences += $has ? 0 : 1;
    }

    echo 0 === $differences ? "Parité complète.\n" : "$differences écart(s) réel(s).\n";
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
    usageError('Usage : --dsn=... --user=... [--strict] | --compare=a.json,b.json | --expected=attendu.json --inventory=inv.json');
}

$password = is_string($options['password'] ?? null) ? $options['password'] : (getenv('INVENTAIRE_DB_PASSWORD') ?: '');
try {
    $pdo = new PDO($options['dsn'], is_string($options['user'] ?? null) ? $options['user'] : null, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_STRINGIFY_FETCHES => true]);
} catch (PDOException $e) {
    fwrite(STDERR, 'ERREUR : connexion impossible (code '.(string) $e->getCode().").\n"); // générique : ni DSN ni mot de passe
    exit(3);
}
// Toute exception inattendue : code 3 documenté (et non 255), sans message du serveur (peut contenir des valeurs).
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'ERREUR : échec inattendu pendant l\'inventaire ('.($e instanceof PDOException ? 'SQLSTATE '.(string) ($e->errorInfo[0] ?? $e->getCode()) : get_class($e)).").\n");
    exit(3);
});
$json = (string) json_encode(inventory($pdo, isset($options['strict'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (is_string($options['out'] ?? null)) {
    file_put_contents($options['out'], $json."\n");
}
echo $json, "\n";
