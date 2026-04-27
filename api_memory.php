<?php
/**
 * @file   api_memory.php
 * @brief  JSON endpoint for AJAX memory-usage refresh.
 */
require_once __DIR__ . '/includes/session.php';
if (!isAppLoggedIn()) { http_response_code(401); echo '{}'; exit; }
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');
echo json_encode([
    'usage' => formatBytes(memory_get_usage(true)),
    'peak'  => formatBytes(memory_get_peak_usage(true)),
]);
