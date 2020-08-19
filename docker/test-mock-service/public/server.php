<?php

declare(strict_types=1);

$path = $_SERVER['PHP_SELF'];
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if ($method === 'GET' && strpos($uri, '/v2/notifications?reference=') !== false) {
    http_response_code(200);
//    $headers = 'a:12:{s:28:"Access-Control-Allow-Headers";a:1:{i:0;s:26:"Content-Type,Authorization";}s:28:"Access-Control-Allow-Methods";a:1:{i:0;s:19:"GET,PUT,POST,DELETE";}s:27:"Access-Control-Allow-Origin";a:1:{i:0;s:1:"*";}s:12:"Content-Type";a:1:{i:0;s:16:"application/json";}s:4:"Date";a:1:{i:0;s:29:"Wed, 19 Aug 2020 19:03:06 GMT";}s:6:"Server";a:1:{i:0;s:5:"nginx";}s:25:"Strict-Transport-Security";a:1:{i:0;s:35:"max-age=31536000; includeSubdomains";}s:11:"X-B3-Spanid";a:1:{i:0;s:16:"aa9d857bbd571f00";}s:12:"X-B3-Traceid";a:1:{i:0;s:16:"aa9d857bbd571f00";}s:17:"X-Vcap-Request-Id";a:1:{i:0;s:36:"a76a3109-6d59-4328-6028-4db87b4a01a3";}s:14:"Content-Length";a:1:{i:0;s:3:"157";}s:10:"Connection";a:1:{i:0;s:10:"keep-alive";}}';
//    foreach (unserialize($headers) as $name => $value) {
//        header("{$name}: {$value[0]}");
//    }
    echo '{"links":{"current":"https://api.notifications.service.gov.uk/v2/notifications?reference=test-handle-success-2020081917530885155929386"},"notifications":[]}' . "\n";
}

if ($method === 'GET' && strpos($uri, '/v2/notifications/') !== false) {
    http_response_code(200);
//    $headers = 'a:12:{s:28:"Access-Control-Allow-Headers";a:1:{i:0;s:26:"Content-Type,Authorization";}s:28:"Access-Control-Allow-Methods";a:1:{i:0;s:19:"GET,PUT,POST,DELETE";}s:27:"Access-Control-Allow-Origin";a:1:{i:0;s:1:"*";}s:12:"Content-Type";a:1:{i:0;s:16:"application/json";}s:4:"Date";a:1:{i:0;s:29:"Wed, 19 Aug 2020 19:03:07 GMT";}s:6:"Server";a:1:{i:0;s:5:"nginx";}s:25:"Strict-Transport-Security";a:1:{i:0;s:35:"max-age=31536000; includeSubdomains";}s:11:"X-B3-Spanid";a:1:{i:0;s:16:"2655f611e5e77817";}s:12:"X-B3-Traceid";a:1:{i:0;s:16:"2655f611e5e77817";}s:17:"X-Vcap-Request-Id";a:1:{i:0;s:36:"ef76ebd6-d55f-4917-6d95-ce39d653ba27";}s:14:"Content-Length";a:1:{i:0;s:3:"715";}s:10:"Connection";a:1:{i:0;s:10:"keep-alive";}}';
//
//    foreach (unserialize($headers) as $name => $value) {
//        header("{$name}: {$value[0]}");
//    }

    echo '{"body":"","completed_at":null,"created_at":"2020-08-19T18:44:58.053712Z","created_by_name":null,"email_address":null,"estimated_delivery":"2020-08-24T15:00:58.053712Z","id":"3b53e050-2664-4796-8972-7293cdbf5658","line_1":"Provided as PDF","line_2":null,"line_3":null,"line_4":null,"line_5":null,"line_6":null,"phone_number":null,"postage":"second","postcode":null,"reference":"test-handle-success-2020081917530885155929386","scheduled_for":null,"sent_at":null,"status":"pending-virus-check","subject":"Pre-compiled PDF","template":{"id":"9bcb8848-185f-4101-a192-ce4ef065a301","uri":"https://api.notifications.service.gov.uk/v2/template/9bcb8848-185f-4101-a192-ce4ef065a301/version/1","version":1},"type":"letter"}' . "\n";
}

if ($method === 'POST' && strpos($path, '/v2/notifications/letter') !== false) {
    http_response_code(201);
//    $headers = 'a:12:{s:28:"Access-Control-Allow-Headers";a:1:{i:0;s:26:"Content-Type,Authorization";}s:28:"Access-Control-Allow-Methods";a:1:{i:0;s:19:"GET,PUT,POST,DELETE";}s:27:"Access-Control-Allow-Origin";a:1:{i:0;s:1:"*";}s:12:"Content-Type";a:1:{i:0;s:16:"application/json";}s:4:"Date";a:1:{i:0;s:29:"Wed, 19 Aug 2020 19:03:07 GMT";}s:6:"Server";a:1:{i:0;s:5:"nginx";}s:25:"Strict-Transport-Security";a:1:{i:0;s:35:"max-age=31536000; includeSubdomains";}s:11:"X-B3-Spanid";a:1:{i:0;s:16:"fb40180d58e0d420";}s:12:"X-B3-Traceid";a:1:{i:0;s:16:"fb40180d58e0d420";}s:17:"X-Vcap-Request-Id";a:1:{i:0;s:36:"58f7a206-bd7d-47c4-76fb-96b4288f08fa";}s:14:"Content-Length";a:1:{i:0;s:3:"125";}s:10:"Connection";a:1:{i:0;s:10:"keep-alive";}}';
//    foreach (unserialize($headers) as $name => $value) {
//        header("{$name}: {$value[0]}");
//    }
    echo '{"id":"ab30bf7b-ae70-4d63-8552-916285218dea","postage":"second","reference":"test-handle-success-2020081917530831167221038"}' . "\n";
}

