<?php

function jsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function jsonSuccess(string $message = 'Operation completed successfully.', array $data = []): void
{
    jsonResponse(true, $message, $data, 200);
}

function jsonError(string $message = 'An error occurred.', int $statusCode = 400, array $data = []): void
{
    jsonResponse(false, $message, $data, $statusCode);
}