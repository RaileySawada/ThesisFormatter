<?php
ob_start();
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size',       '210M');
ini_set('memory_limit',        '256M');
ini_set('max_execution_time',  '120');
ini_set('max_input_time',      '120');
require_once __DIR__ . '/../config/config.php';
require_once AUTOLOAD_CONF;
$autoloadPath = __DIR__.'/../vendor/autoload.php';
$meta = require META_CONF;

use App\Controllers\FormatController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = trim($uri, '/');
$request = trim($request, 'ThesisFormatter');

$page = str_replace('/', '', $request);
$page = str_replace('_', ' ', $page);
print_r($request);

if(session_status() == PHP_SESSION_NONE) session_start();

require HEADER;

if(ENVIRONMENT == 'production' && DEBUG == false):
  switch ($request) {
    case '':
      $login = new FormatController;
      $login->handleRequest();
      break;

    default:
      http_response_code(404);
      echo '404 Page Not Found';
      break;
  }
else:
  http_response_code(503);
  echo 'Under Maintenance';
endif;

require FOOTER;
ob_end_flush();