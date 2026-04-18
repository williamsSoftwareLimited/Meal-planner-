<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$mealSlots = ['breakfast', 'lunch', 'dinner', 'snacks'];
$dataFile = __DIR__ . '/meal-plans.json';
const MEAL_TEXT_MAX_LENGTH = 120;

function buildEmptyPlan(array $days, array $mealSlots): array
{
    $plan = [];
    foreach ($days as $day) {
        $plan[$day] = [];
        foreach ($mealSlots as $slot) {
            $plan[$day][$slot] = '';
        }
    }

    return $plan;
}

function readJsonBody(): ?array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function setCorsHeaders(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!is_string($origin) || trim($origin) === '') {
        return;
    }

    $rawAllowedOrigins = getenv('MEAL_PLANNER_ALLOWED_ORIGINS');
    $allowedOrigins = [];
    if (is_string($rawAllowedOrigins) && trim($rawAllowedOrigins) !== '') {
        $allowedOrigins = array_map('trim', explode(',', $rawAllowedOrigins));
    } else {
        $allowedOrigins = [
            'http://localhost:8081',
            'http://127.0.0.1:8081',
            'http://localhost:19006',
            'http://127.0.0.1:19006',
        ];
    }

    if (in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
}

function loadAllPlans(string $dataFile): array
{
    if (!file_exists($dataFile)) {
        return [];
    }

    $json = file_get_contents($dataFile);
    if ($json === false || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function upsertPlan(string $dataFile, string $shopperId, array $plan): bool
{
    $handle = fopen($dataFile, 'c+');
    if ($handle === false) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    rewind($handle);
    $existingJson = stream_get_contents($handle);
    $allPlans = [];
    if (is_string($existingJson) && trim($existingJson) !== '') {
        $decoded = json_decode($existingJson, true);
        if (is_array($decoded)) {
            $allPlans = $decoded;
        }
    }

    $allPlans[$shopperId] = $plan;
    $json = json_encode($allPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    ftruncate($handle, 0);
    rewind($handle);
    $bytesWritten = fwrite($handle, $json);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $bytesWritten !== false;
}

function normalizePlan(array $inputPlan, array $days, array $mealSlots): array
{
    $plan = buildEmptyPlan($days, $mealSlots);

    foreach ($days as $day) {
        $dayInput = $inputPlan[$day] ?? [];
        if (!is_array($dayInput)) {
            continue;
        }

        foreach ($mealSlots as $slot) {
            $value = $dayInput[$slot] ?? '';
            if (!is_string($value)) {
                $value = '';
            }

            $value = trim($value);
            if (strlen($value) > MEAL_TEXT_MAX_LENGTH) {
                $value = substr($value, 0, MEAL_TEXT_MAX_LENGTH);
            }

            $plan[$day][$slot] = $value;
        }
    }

    return $plan;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
setCorsHeaders();

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method === 'GET') {
    $shopperId = $_GET['shopperId'] ?? 'default';
    if (!is_string($shopperId) || trim($shopperId) === '') {
        respond(400, ['error' => 'shopperId is required']);
    }

    $shopperId = trim($shopperId);
    $allPlans = loadAllPlans($dataFile);
    $plan = $allPlans[$shopperId] ?? buildEmptyPlan($days, $mealSlots);

    respond(200, [
        'shopperId' => $shopperId,
        'plan' => $plan,
    ]);
}

if ($method === 'POST') {
    $body = readJsonBody();
    if ($body === null) {
        respond(400, ['error' => 'Invalid JSON body']);
    }

    $shopperId = $body['shopperId'] ?? 'default';
    if (!is_string($shopperId) || trim($shopperId) === '') {
        respond(400, ['error' => 'shopperId is required']);
    }

    $rawPlan = $body['plan'] ?? null;
    if (!is_array($rawPlan)) {
        respond(400, ['error' => 'plan object is required']);
    }

    $shopperId = trim($shopperId);
    $normalizedPlan = normalizePlan($rawPlan, $days, $mealSlots);

    if (!upsertPlan($dataFile, $shopperId, $normalizedPlan)) {
        respond(500, ['error' => 'Unable to save meal plan']);
    }

    respond(200, [
        'message' => 'Meal plan saved',
        'shopperId' => $shopperId,
        'plan' => $normalizedPlan,
    ]);
}

respond(405, ['error' => 'Method not allowed']);
