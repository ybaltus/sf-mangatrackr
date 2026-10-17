<?php

// PostToolUse hook : formate avec PHP-CS-Fixer le fichier PHP que Claude vient de modifier.
$input = json_decode((string) file_get_contents('php://stdin'), true);
$file = $input['tool_input']['file_path'] ?? '';

if (!is_string($file) || !str_ends_with($file, '.php') || !is_file($file)) {
    exit(0);
}

$projectDir = getenv('CLAUDE_PROJECT_DIR') ?: dirname(__DIR__, 2);
$fixer = $projectDir.'/vendor/bin/php-cs-fixer';

if (!is_file($fixer)) {
    exit(0);
}

exec(sprintf(
    'php %s fix %s --config=%s --quiet 2>&1',
    escapeshellarg($fixer),
    escapeshellarg($file),
    escapeshellarg($projectDir.'/.php-cs-fixer.dist.php'),
));
