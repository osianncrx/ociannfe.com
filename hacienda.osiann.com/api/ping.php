<?php
// ping.php - devuelve 200 como cuerpo y código HTTP 200

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');

echo 'ping';