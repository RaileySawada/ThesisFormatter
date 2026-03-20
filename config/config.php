<?php
declare(strict_types= 1);
$ENV = parse_ini_file(__DIR__ . '/../.env', true, INI_SCANNER_TYPED);

ini_set('date.timezone', 'Asia/Manila');
date_default_timezone_set('Asia/Manila');

// App Environment
define("ENV", $ENV);
define("VER", ENV["APP_VERSION"]);
define("ENVIRONMENT", ENV["APP_ENV"]);
define("DEBUG", ENV["APP_DEBUG"]);

// Path and Url
define("BASE_URL", ENVIRONMENT == 'local' ? "http://localhost/ThesisFormatter" : 'https://thesisformatter-production.up.railway.app');
define("BASE_PATH",dirname(__DIR__)."/");

if (DEBUG) {
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', BASE_PATH.'/php-error.log');
}

// Config Files
define("CONFIG_PATH",BASE_PATH."config/");
define("APP_CONF",CONFIG_PATH."app.php");
define("META_CONF",CONFIG_PATH."meta.php");
define("AUTOLOAD_CONF", CONFIG_PATH."autoload.php");

// CSS Files
define("CSS_URL",BASE_URL."/public/css/");
define("STYLES_CSS",CSS_URL."styles.css?v=".VER);

// JS Files
define("JS_URL",BASE_URL."/public/js/");
define("SCRIPT",JS_URL."script.js?v=".VER);

// Framework / Libraries
define("FONTAWESOME", "https://kit.fontawesome.com/828a48be28.js");
define("TAILWIND", "https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4");

// Icon & Images
define("IMAGES_URL",BASE_URL."/public/images/");
define("FAVICON", IMAGES_URL."favicon.ico");
define("LOGO", IMAGES_URL."logo.png");

// Uploads
define("UPLOADS", BASE_URL."/public/uploads/");

// Components
define("COMPONENTS_PATH", BASE_PATH."app/Views/Components/");
define("TOAST", COMPONENTS_PATH."ToastMessage.php");

// Layouts
define("LAYOUTS_PATH", BASE_PATH."app/Views/Layouts/");
define("HEADER", LAYOUTS_PATH."Header.php");
define("FOOTER", LAYOUTS_PATH."Footer.php");
define("SIDEBAR", LAYOUTS_PATH."Sidebar.php");

// Pages
define("PAGE_PATH", BASE_PATH."/app/Views/Pages/");
define("UI", PAGE_PATH."UI.php");