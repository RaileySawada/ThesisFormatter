<?php
spl_autoload_register(function ($class) {
  if (str_starts_with($class, 'App\\')) {
    $class = str_replace('App\\', 'app/', $class);
  }

  $path = BASE_PATH . str_replace('\\', '/', $class) . '.php';

  if (file_exists($path)) {
    require_once $path;
  }
});