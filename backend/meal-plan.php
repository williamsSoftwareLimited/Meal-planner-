<?php

declare(strict_types=1);

header('Content-Type: application/json');

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$mealSlots = ['breakfast', 'lunch', 'dinner', 'snacks'];
$dataFile = __DIR__ . '/meal-plans.json';

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

function saveAllPlans(string $dataFile, array $allPlans): bool
{
    $json = json_encode($allPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    return file_put_contents($dataFile, $json, LOCK_EX) !== false;
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
            if (strlen($value) > 120) {
                $value = substr($value, 0, 120);
            }

            $plan[$day][$slot] = $value;
        }
    }

    return $plan;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

    $allPlans = loadAllPlans($dataFile);
    $allPlans[$shopperId] = $normalizedPlan;

    if (!saveAllPlans($dataFile, $allPlans)) {
        respond(500, ['error' => 'Unable to save meal plan']);
    }

    respond(200, [
        'message' => 'Meal plan saved',
        'shopperId' => $shopperId,
        'plan' => $normalizedPlan,
    ]);
}

respond(405, ['error' => 'Method not allowed']);
