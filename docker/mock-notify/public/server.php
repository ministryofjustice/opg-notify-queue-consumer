<?php

declare(strict_types=1);

$path = $_SERVER['PHP_SELF'];
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if ($method === 'GET' && strpos($uri, '/v2/notifications?reference=') !== false) {
    http_response_code(200);
    echo <<<'TAG'
{"links":{"current":"https://api.notifications.service.gov.uk/v2/notifications?reference=test-handle-success-2020081917530885155929386"},"notifications":[]}
TAG
        . "\n";
}

if ($method === 'GET' && strpos($uri, '/v2/notifications/') !== false) {
    http_response_code(200);
    echo <<<'TAG'
{"body":"","completed_at":null,"created_at":"2020-08-19T18:44:58.053712Z","created_by_name":null,"email_address":null,"estimated_delivery":"2020-08-24T15:00:58.053712Z","id":"3b53e050-2664-4796-8972-7293cdbf5658","line_1":"Provided as PDF","line_2":null,"line_3":null,"line_4":null,"line_5":null,"line_6":null,"phone_number":null,"postage":"second","postcode":null,"reference":"test-handle-success-2020081917530885155929386","scheduled_for":null,"sent_at":null,"status":"pending-virus-check","subject":"Pre-compiled PDF","template":{"id":"9bcb8848-185f-4101-a192-ce4ef065a301","uri":"https://api.notifications.service.gov.uk/v2/template/9bcb8848-185f-4101-a192-ce4ef065a301/version/1","version":1},"type":"letter"}
TAG
        . "\n";
}

if ($method === 'POST' && strpos($path, '/v2/notifications/letter') !== false) {
    http_response_code(201);
    echo '{"id":"ab30bf7b-ae70-4d63-8552-916285218dea","postage":"second","reference":"test-handle-success-2020081917530831167221038"}' . "\n";
}

