<?php
// Labor Virus-Jagd – einfache Server-Highscore-API
// Speichert die Top 20 in ../data/highscores.json
// Voraussetzung: PHP 7.4+ und Schreibrechte für den Ordner /data.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$file = __DIR__ . '/../data/highscores.json';
$maxEntries = 20;

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_scores($file) {
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    if ($json === false || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }
    return array_values(array_filter($data, 'is_array'));
}

function clean_name($name) {
    $name = trim((string)$name);
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = preg_replace('/[^\p{L}\p{N} _\-.!?#äöüÄÖÜß]/u', '', $name);
    if ($name === '') {
        $name = 'Anonym';
    }
    return mb_substr($name, 0, 18, 'UTF-8');
}

function sort_scores($scores) {
    usort($scores, function($a, $b) {
        $sa = (int)($a['score'] ?? 0);
        $sb = (int)($b['score'] ?? 0);
        if ($sa === $sb) {
            return strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));
        }
        return $sb <=> $sa;
    });
    return $scores;
}

if ($method === 'GET') {
    $scores = sort_scores(read_scores($file));
    respond(['ok' => true, 'scores' => array_slice($scores, 0, $maxEntries)]);
}

if ($method !== 'POST') {
    respond(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    respond(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$name = clean_name($input['name'] ?? 'Anonym');
$score = max(0, min(999999, (int)($input['score'] ?? 0)));
$hits = max(0, min(9999, (int)($input['hits'] ?? 0)));
$accuracy = max(0, min(100, (int)($input['accuracy'] ?? 0)));
$combo = max(0, min(999, (int)($input['combo'] ?? 0)));
$difficulty = preg_replace('/[^a-z]/', '', (string)($input['difficulty'] ?? ''));
if (!in_array($difficulty, ['easy', 'normal', 'hard'], true)) {
    $difficulty = 'easy';
}

if ($score <= 0) {
    respond(['ok' => false, 'error' => 'Score must be greater than zero'], 400);
}

$dir = dirname($file);
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    respond(['ok' => false, 'error' => 'Could not create data directory'], 500);
}

$fp = fopen($file, 'c+');
if (!$fp) {
    respond(['ok' => false, 'error' => 'Could not open highscore file'], 500);
}

flock($fp, LOCK_EX);
$contents = stream_get_contents($fp);
$scores = $contents ? json_decode($contents, true) : [];
if (!is_array($scores)) {
    $scores = [];
}

$scores[] = [
    'name' => $name,
    'score' => $score,
    'hits' => $hits,
    'accuracy' => $accuracy,
    'combo' => $combo,
    'difficulty' => $difficulty,
    'created_at' => gmdate('c'),
    'ip_hash' => substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'labor-virus-jagd'), 0, 12)
];

$scores = array_slice(sort_scores($scores), 0, $maxEntries);

rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

respond(['ok' => true, 'scores' => $scores]);
