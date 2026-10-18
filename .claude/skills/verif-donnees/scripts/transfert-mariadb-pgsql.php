<?php

declare(strict_types=1);

/*
 * Transfert MariaDB (source, lecture seule) -> PostgreSQL (cible, schéma déjà créé par les
 * migrations Doctrine). Rejouable : la cible est vidée (TRUNCATE des tables de données) puis
 * rechargée dans UNE transaction ; en cas d'erreur tables ET séquences restent inchangées
 * (les séquences ne sont recalées qu'après le COMMIT, une fois les comptages contrôlés).
 *
 * Usage :
 *   MARIADB_PASSWORD=... [PGSQL_PASSWORD=...] php transfert-mariadb-pgsql.php --yes --replace-existing [options]
 *
 * Options :
 *   --source-dsn=...   défaut mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import
 *   --source-user=...  défaut mangatrackr (mot de passe : variable MARIADB_PASSWORD)
 *   --target-dsn=...   défaut déduit de DATABASE_URL (pgsql:host=…;port=…;dbname=…)
 *   --target-user=...  défaut utilisateur de DATABASE_URL (mot de passe : PGSQL_PASSWORD, sinon DATABASE_URL)
 *   --batch=N          lignes max par INSERT (défaut 500, borné par le nombre de paramètres)
 *   --dry-run          exécute tout puis ROLLBACK (les séquences ne sont pas recalées).
 *                      Attention : le TRUNCATE prend un verrou exclusif sur la cible pendant l'essai.
 *   --yes              requis : confirme que la cible sera vidée
 *   --replace-existing requis aussi : confirme que le contenu actuel de la cible (affiché avant) sera détruit
 *   --expected-host=H / --expected-db=B  hôte et base que la cible DOIT avoir (sinon code 5). Défauts :
 *                      variables TRANSFER_EXPECTED_HOST / TRANSFER_EXPECTED_DB, puis `postgres` /
 *                      `sf_mangatrackr_app` (dev). L'hôte et la base sont lus dans le DSN réellement utilisé.
 *   --confirm-target=<hôte>/<base>  (ou variable CONFIRM_TARGET) requis : doit être EXACTEMENT la cible
 *                      réelle (même pour --dry-run, le TRUNCATE verrouillant la cible), sinon code 5.
 *                      Aucune valeur par défaut : la confirmation vient de l'appelant, jamais d'un script.
 *   --inject-failure=count  TEST UNIQUEMENT : fausse le contrôle des comptages pour prouver le rollback
 *
 * Avant le TRUNCATE, le script affiche hôte, base et comptages de la cible. Une base finissant
 * par _test ou égale à la source est toujours refusée. La session source est en lecture seule ;
 * doctrine_migration_versions n'est jamais touchée (la baseline PostgreSQL a sa propre version) ;
 * aucun DDL. Les messages d'erreur ne contiennent ni mot de passe ni valeur de ligne (SQLSTATE
 * et table seulement).
 *
 * Codes de sortie : 0 succès ; 2 erreur d'usage ; 3 échec avec ROLLBACK (rien modifié), connexion
 * impossible ou exception inattendue avant tout TRUNCATE ; 4 COMMIT fait mais recalage des séquences
 * échoué (relancer le transfert) ; 5 cible refusée par le garde-fou (hôte, base, base _test/source,
 * confirmation absente ou différente) : rien n'a été lu ni écrit sur la cible ; 6 AUTO_INCREMENT de la
 * source illisible (NULL/absent) pour une table à séquence : rien n'a été modifié (ADR 0012 D3).
 *
 * Séquences (ADR 0012 D3) : chaque séquence IDENTITY est recalée à GREATEST(MAX(id), AUTO_INCREMENT_source - 1)
 * (calculé dans la transaction, appliqué par setval après le COMMIT) : la prochaine valeur est >= AUTO_INCREMENT
 * de la source, donc aucun ID supprimé en fin de table n'est réutilisé ; table vide : AUTO_INCREMENT_source (1 si 1).
 * AUTO_INCREMENT est lu dans information_schema.tables de la base source ; il n'existe dans `_import` que si
 * l'export phpMyAdmin contenait l'option AUTO_INCREMENT (sinon NULL/1 : code 6 ou valeur trop basse, à vérifier).
 *
 * Pré-contrôles avant tout TRUNCATE : server_encoding de la cible = UTF8, et chaque colonne source doit
 * appartenir à une combinaison de types prévue (tinyint(1)→boolean, int, bigint, varchar, longtext→text/json,
 * double, datetime→timestamp) sinon code 3, rien de modifié.
 *
 * Conversions (pilotées par le type de la colonne cible) :
 *   boolean   : 0/1 strictement (tinyint(1)) ; toute autre valeur = erreur
 *   json      : texte conservé tel quel, mais doit être un JSON valide (sinon erreur)
 *   timestamp : chaîne conservée ; '0000-00-00…' = erreur
 *   autres    : valeur inchangée (chaîne vide et NULL ne sont JAMAIS convertis l'un en l'autre)
 * Table renommée : user -> app_user (ADR 0012, D2).
 */

ini_set('zend.exception_ignore_args', '1'); // aucune trace ne doit afficher les arguments (mot de passe)

const DEFAULT_TARGET_HOST = 'postgres';
const DEFAULT_TARGET_DB = 'sf_mangatrackr_app';
const EXIT_TARGET_REFUSED = 5;
const EXIT_AUTO_INCREMENT_MISSING = 6;
const RENAMED = ['user' => 'app_user'];      // source => cible
const SKIPPED = ['doctrine_migration_versions'];

function fail(string $message, int $code = 3): never
{
    fwrite(STDERR, "ERREUR : $message\n");
    exit($code);
}

// Exception inattendue : code 3 documenté (et non 255), sans message du serveur ni trace.
set_exception_handler(static function (Throwable $e): never {
    fwrite(STDERR, 'ERREUR : échec inattendu ('.($e instanceof PDOException ? 'SQLSTATE '.(string) ($e->errorInfo[0] ?? $e->getCode()) : get_class($e)).") ; vérifier l'état de la cible avant de relancer.\n");
    exit(3);
});

function q(string $name): string
{
    return '"'.str_replace('"', '""', $name).'"';
}

/** @return array<string, string|true> */
function options(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        $parts = explode('=', ltrim($arg, '-'), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }

    return $options;
}

function convert(mixed $value, string $type, string $table, string $column): mixed
{
    if (null === $value) {
        return null;
    }
    $value = (string) $value;

    switch ($type) {
        case 'boolean':
            if ('0' !== $value && '1' !== $value) {
                throw new RuntimeException("$table.$column : booléen inattendu (valeur non 0/1).");
            }

            return $value;
        case 'json':
            json_decode($value);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException("$table.$column : JSON invalide (".json_last_error_msg().').');
            }

            return $value;
        case 'timestamp without time zone':
            if (str_starts_with($value, '0000-00-00')) {
                throw new RuntimeException("$table.$column : date à zéro.");
            }

            return $value;
        default:
            return $value;
    }
}

$opt = options($argv);
if (true !== ($opt['yes'] ?? false) || true !== ($opt['replace-existing'] ?? false)) {
    fail('--yes ET --replace-existing requis : la cible sera vidée avant rechargement.', 2);
}
$dryRun = isset($opt['dry-run']);

$sourceDsn = is_string($opt['source-dsn'] ?? null) ? $opt['source-dsn'] : 'mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import';
$sourceUser = is_string($opt['source-user'] ?? null) ? $opt['source-user'] : 'mangatrackr';
$sourcePassword = getenv('MARIADB_PASSWORD');
if (false === $sourcePassword) {
    fail('variable MARIADB_PASSWORD absente.', 2);
}

$url = parse_url((string) getenv('DATABASE_URL'));
$targetDsn = is_string($opt['target-dsn'] ?? null) ? $opt['target-dsn'] : null;
if (null === $targetDsn) {
    if (!is_array($url) || !isset($url['host'], $url['path'])) {
        fail('--target-dsn absent et DATABASE_URL inutilisable.', 2);
    }
    $targetDsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $url['host'], $url['port'] ?? 5432, ltrim($url['path'], '/'));
}
$targetUser = is_string($opt['target-user'] ?? null) ? $opt['target-user'] : ($url['user'] ?? null);
$targetPassword = getenv('PGSQL_PASSWORD');
if (false === $targetPassword || '' === $targetPassword) {
    $targetPassword = isset($url['pass']) ? rawurldecode($url['pass']) : '';
}

if (!str_starts_with($targetDsn, 'pgsql:')) {
    fail('la cible doit être PostgreSQL.', 2);
}
preg_match('/dbname=([^;]+)/', $targetDsn, $m);
$targetDb = $m[1] ?? '';
preg_match('/host=([^;]+)/', $targetDsn, $m);
$targetHost = $m[1] ?? '';
preg_match('/dbname=([^;]+)/', $sourceDsn, $m);
$sourceDb = $m[1] ?? '';
$targetUser = null === $targetUser ? null : rawurldecode($targetUser);

// --- Garde-fou de cible : hôte ET base attendus, puis confirmation explicite de l'appelant.
$expectedHost = is_string($opt['expected-host'] ?? null) ? $opt['expected-host'] : ((string) getenv('TRANSFER_EXPECTED_HOST') ?: DEFAULT_TARGET_HOST);
$expectedDb = is_string($opt['expected-db'] ?? null) ? $opt['expected-db'] : ((string) getenv('TRANSFER_EXPECTED_DB') ?: DEFAULT_TARGET_DB);
if ('' === $targetDb || '' === $targetHost || str_ends_with($targetDb, '_test') || $sourceDb === $targetDb) {
    fail("cible refusée (hôte « $targetHost », base « $targetDb ») : vide, base de test ou identique à la source.", EXIT_TARGET_REFUSED);
}
if ($targetHost !== $expectedHost || $targetDb !== $expectedDb) {
    fail("cible refusée : $targetHost/$targetDb, attendu $expectedHost/$expectedDb (--expected-host/--expected-db ou TRANSFER_EXPECTED_HOST/TRANSFER_EXPECTED_DB).", EXIT_TARGET_REFUSED);
}
$confirm = is_string($opt['confirm-target'] ?? null) ? $opt['confirm-target'] : (string) getenv('CONFIRM_TARGET');
if ("$targetHost/$targetDb" !== $confirm) {
    fail("confirmation absente ou différente : la cible réelle est $targetHost/$targetDb ; fournir CONFIRM_TARGET=$targetHost/$targetDb (ou --confirm-target=…) pour autoriser la destruction de son contenu.", EXIT_TARGET_REFUSED);
}

function connect(string $label, string $dsn, ?string $user, string $password, array $options): PDO
{
    try {
        return new PDO($dsn, $user, $password, $options);
    } catch (PDOException $e) {
        // Message volontairement générique : ni DSN, ni mot de passe, ni message du serveur.
        fail("connexion $label impossible (code ".(string) $e->getCode().'). Vérifier le service, l\'hôte, le port et les identifiants.', 3);
    }
}

$src = connect('source', $sourceDsn, $sourceUser, $sourcePassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_STRINGIFY_FETCHES => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
]);
$src->exec('SET NAMES utf8mb4');
$src->exec('SET SESSION TRANSACTION READ ONLY');
$src->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

$dst = connect('cible', $targetDsn, $targetUser, $targetPassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_STRINGIFY_FETCHES => true,
]);

// --- Métadonnées de la cible
$targetTables = array_values(array_diff(
    $dst->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN),
    SKIPPED,
));
$columns = [];
foreach ($dst->query("SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' ORDER BY table_name, ordinal_position")->fetchAll(PDO::FETCH_NUM) as [$t, $c, $type]) {
    $columns[$t][$c] = $type;
}
$deps = array_fill_keys($targetTables, []);
foreach ($dst->query("SELECT conrelid::regclass::text, confrelid::regclass::text FROM pg_constraint WHERE contype = 'f'")->fetchAll(PDO::FETCH_NUM) as [$child, $parent]) {
    $child = trim($child, '"');
    $parent = trim($parent, '"');
    if (!isset($deps[$child])) {
        continue;   // table hors périmètre (ex. doctrine_migration_versions)
    }
    if ($child === $parent) {
        fwrite(STDERR, "AVERTISSEMENT : $child a une clé étrangère auto-référente, ignorée pour l'ordre des tables ; l'ordre des lignes DANS la table n'est pas garanti (si une ligne précède son parent, la FK échoue : code 3, ROLLBACK, cible inchangée).\n");
    } elseif (isset($deps[$parent])) {
        $deps[$child][] = $parent;
    } else {
        fwrite(STDERR, "AVERTISSEMENT : $child référence $parent, hors périmètre du transfert : dépendance ignorée.\n");
    }
}
// Tri topologique (parents d'abord)
$order = [];
$remaining = $deps;
while ([] !== $remaining) {
    $ready = array_keys(array_filter($remaining, static fn (array $d): bool => [] === array_diff($d, $order)));
    if ([] === $ready) {
        fail('dépendances circulaires : '.implode(', ', array_keys($remaining)));
    }
    sort($ready);
    foreach ($ready as $t) {
        $order[] = $t;
        unset($remaining[$t]);
    }
}

// --- Contrôle de correspondance source/cible
$sourceNames = array_flip(RENAMED);
$sourceTables = $src->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_COLUMN);
$expectedSource = array_map(static fn (string $t): string => $sourceNames[$t] ?? $t, $order);
$diff = array_merge(array_diff($sourceTables, [...$expectedSource, ...SKIPPED]), array_diff($expectedSource, $sourceTables));
if ([] !== $diff) {
    fail('tables non appariées entre source et cible : '.implode(', ', $diff));
}

// --- Pré-contrôles M4 : encodage de la cible et types source (avant tout TRUNCATE)
$encoding = (string) $dst->query('SHOW server_encoding')->fetchColumn();
if ('UTF8' !== $encoding) {
    fail("encodage de la cible $encoding : UTF8 requis (rien n'a été modifié).");
}
// Combinaisons autorisées (type source MariaDB => types cibles PostgreSQL) ; toute autre = erreur.
$allowedTypes = [
    'tinyint(1)' => ['boolean'],
    'int' => ['integer'],
    'bigint' => ['bigint'],
    'varchar' => ['character varying'],
    'longtext' => ['text', 'json'],
    'double' => ['double precision'],
    'datetime' => ['timestamp without time zone'],
];
$typeProblems = [];
foreach ($order as $table) {
    $sourceTable = $sourceNames[$table] ?? $table;
    $stmt = $src->prepare('SELECT column_name, data_type, column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$sourceTable]);
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$column, $dataType, $columnType]) {
        $sourceType = 'tinyint' === $dataType ? $columnType : $dataType;
        $targetType = $columns[$table][$column] ?? null;
        if (null !== $targetType && !in_array($targetType, $allowedTypes[$sourceType] ?? [], true)) {
            $typeProblems[] = "$table.$column ($sourceType -> $targetType)";
        }
    }
}
if ([] !== $typeProblems) {
    fail('combinaison de types source/cible non prévue (rien n\'a été modifié) : '.implode(', ', $typeProblems));
}

// --- Séquences IDENTITY de la cible et AUTO_INCREMENT de la source (D3), avant tout TRUNCATE
$sequenceDefs = [];   // séquence => [table, colonne, AUTO_INCREMENT source]
$autoIncrementProblems = [];
$stmtAi = $src->prepare('SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
foreach ($dst->query("SELECT c.relname, a.attname, pg_get_serial_sequence(quote_ident(c.relname), a.attname) FROM pg_class c JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped WHERE c.relkind = 'r' AND c.relnamespace = 'public'::regnamespace AND pg_get_serial_sequence(quote_ident(c.relname), a.attname) IS NOT NULL ORDER BY 1")->fetchAll(PDO::FETCH_NUM) as [$t, $c, $seq]) {
    if (!in_array($t, $order, true)) {
        continue;
    }
    $stmtAi->execute([$sourceNames[$t] ?? $t]);
    $ai = $stmtAi->fetchColumn();
    $stmtAi->closeCursor();
    if (false === $ai || null === $ai || !ctype_digit((string) $ai) || (int) $ai < 1) {
        $autoIncrementProblems[] = $t;
        continue;
    }
    $sequenceDefs[$seq] = [$t, $c, (int) $ai];
}
if ([] !== $autoIncrementProblems) {
    fail('AUTO_INCREMENT de la source NULL ou absent pour : '.implode(', ', $autoIncrementProblems).". Réimporter le dump avec l'option AUTO_INCREMENT de l'export phpMyAdmin cochée (rien n'a été modifié).", EXIT_AUTO_INCREMENT_MISSING);
}

// --- Garde-fou : afficher ce qui sera détruit avant tout TRUNCATE
$before = [];
foreach ($order as $table) {
    $before[$table] = (int) $dst->query('SELECT COUNT(*) FROM '.q($table))->fetchColumn();
}
printf("CIBLE : hôte %s, base %s, confirmée par l'appelant (%d tables de données).\n", $targetHost, $targetDb, count($order));
printf("Contenu actuel de la cible, qui sera %s : %d ligne(s) au total.\n", $dryRun ? 'vidé puis rétabli (dry-run)' : 'DÉTRUIT', array_sum($before));
foreach (array_filter($before) as $t => $n) {
    printf("  %-32s %10d\n", $t, $n);
}

$started = microtime(true);
$summary = [];
$sequences = [];   // séquence => [table, colonne, prochaine valeur]
$current = '-';
try {
    $dst->beginTransaction();
    $dst->exec('TRUNCATE TABLE '.implode(', ', array_map('q', $order)).' RESTART IDENTITY');

    foreach ($order as $table) {
        $current = $table;
        $sourceTable = $sourceNames[$table] ?? $table;
        $targetColumns = array_keys($columns[$table]);
        $sourceColumns = $src->query('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '.$src->quote($sourceTable))->fetchAll(PDO::FETCH_COLUMN);
        if ([] !== array_merge(array_diff($sourceColumns, $targetColumns), array_diff($targetColumns, $sourceColumns))) {
            throw new RuntimeException("$table : colonnes différentes entre source et cible.");
        }

        $perBatch = max(1, min((int) ($opt['batch'] ?? 500), intdiv(10000, count($targetColumns))));
        $head = 'INSERT INTO '.q($table).' ('.implode(', ', array_map('q', $targetColumns)).') VALUES ';
        $rowPlaceholder = '('.implode(', ', array_fill(0, count($targetColumns), '?')).')';
        $select = 'SELECT '.implode(', ', array_map(static fn (string $c): string => '`'.$c.'`', $targetColumns)).' FROM `'.$sourceTable.'`';

        $buffer = [];
        $count = 0;
        $flush = static function () use (&$buffer, $dst, $head, $rowPlaceholder): void {
            if ([] === $buffer) {
                return;
            }
            $n = count($buffer);
            $dst->prepare($head.implode(', ', array_fill(0, $n, $rowPlaceholder)))->execute(array_merge(...$buffer));
            $buffer = [];
        };
        $statement = $src->query($select);
        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            $converted = [];
            foreach ($targetColumns as $i => $column) {
                $converted[] = convert($row[$i], $columns[$table][$column], $table, $column);
            }
            $buffer[] = $converted;
            ++$count;
            if (count($buffer) >= $perBatch) {
                $flush();
            }
        }
        $statement->closeCursor();
        $flush();
        $summary[$table] = $count;
    }
    $current = '(contrôle des comptages)';

    // 1. Contrôle des comptages, AVANT toute modification de séquence
    foreach ($order as $table) {
        $sourceTable = $sourceNames[$table] ?? $table;
        $s = (int) $src->query('SELECT COUNT(*) FROM `'.$sourceTable.'`')->fetchColumn();
        $d = (int) $dst->query('SELECT COUNT(*) FROM '.q($table))->fetchColumn();
        if ('count' === ($opt['inject-failure'] ?? null) && 'manga' === $table) {
            ++$d; // échec simulé, tardif (après tout le chargement)
        }
        if ($s !== $d || $d !== $summary[$table]) {
            throw new RuntimeException("$table : comptage source=$s cible=$d chargé={$summary[$table]}.");
        }
    }

    // 2. Calcul (lecture seule) : prochaine valeur = GREATEST(MAX(id) + 1, AUTO_INCREMENT source) (D3)
    foreach ($sequenceDefs as $seq => [$t, $c, $ai]) {
        $max = $dst->query('SELECT MAX('.q($c).') FROM '.q($t))->fetchColumn();
        $last = max(null === $max || false === $max ? 0 : (int) $max, $ai - 1);
        $sequences[$seq] = $last < 1 ? [1, false] : [$last, true];
    }

    $current = '(validation)';
    $dryRun ? $dst->rollBack() : $dst->commit();
} catch (Throwable $e) {
    if ($dst->inTransaction()) {
        $dst->rollBack();
    }
    // Jamais getMessage() d'une PDOException : PostgreSQL y met des valeurs de lignes (données personnelles).
    $detail = $e instanceof PDOException
        ? 'SQLSTATE '.(string) ($e->errorInfo[0] ?? $e->getCode()).' sur '.$current
        : $e->getMessage();
    fail("transfert annulé (ROLLBACK, tables et séquences inchangées) : $detail", 3);
}
$src->exec('ROLLBACK');

// 3. Après le COMMIT seulement : application des séquences
if (!$dryRun) {
    try {
        foreach ($sequences as $seq => [$value, $called]) {
            $dst->query('SELECT setval('.$dst->quote($seq).', '.$value.', '.($called ? 'true' : 'false').')');
        }
    } catch (Throwable $e) {
        fail('données validées (COMMIT) mais recalage des séquences échoué (SQLSTATE '.(string) ($e instanceof PDOException ? ($e->errorInfo[0] ?? $e->getCode()) : $e->getCode()).') : relancer le transfert pour recaler.', 4);
    }
}

printf("Transfert %s en %.1f s vers %s\n", $dryRun ? 'SIMULÉ (rollback)' : 'validé', microtime(true) - $started, $targetDb);
printf("%-32s %10s\n", 'table (cible)', 'lignes');
foreach ($summary as $t => $n) {
    printf("%-32s %10d\n", $t, $n);
}
printf("%-32s %10d\n", 'TOTAL', array_sum($summary));
echo "\nSéquences ".($dryRun ? '(prévues, non appliquées)' : 'recalées').' : '.count($sequences)."\n";
foreach ($sequences as $seq => [$value, $called]) {
    printf("  %-48s prochaine valeur %d\n", $seq, $called ? $value + 1 : 1);
}
echo "doctrine_migration_versions : non touchée.\n";
