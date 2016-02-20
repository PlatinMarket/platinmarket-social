<?php

  session_start();
  date_default_timezone_set('Europe/Istanbul');

  if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
  if (!defined('BASE_FOLDER')) define('BASE_FOLDER', dirname(__FILE__));
  if (!defined('BASE_PATH')) define('BASE_PATH', str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']));

  require_once 'vendor/autoload.php';

  function pr($obj)
  {
    if (!is_string($obj)) $obj = print_r($obj, true);
    echo "<pre>";
    echo $obj;
    echo "</pre>";
  }

  function notFoundPage()
  {
    http_response_code(404);
    header('Content-Type: text/plain;');
    echo "404 not found";
    exit();
  }

  function errorPage($message, $code = 500)
  {
    http_response_code($code);
    header('Content-Type: text/plain;');
    echo $code . " " . $message;
    exit();
  }

  if (!isset($_GET['do']) || empty($_GET['do'])) notFoundPage();
  $do = preg_split('/\/+/', $_GET['do']);
  if (count($do) < 2) notFoundPage();

  $provider = ucfirst(strtolower($do[0]));
  $providerClass = $provider . "SocialHelper";
  $providerAction = strtolower($do[1]);

  $providerClassFile = dirname(__FILE__) . DS . 'lib' . DS . 'SocialHelper' . DS . $providerClass . '.php';
  $baseClassFile = dirname(__FILE__) . DS . 'lib' . DS . 'SocialHelper.php';

  if (!file_exists($providerClassFile)) notFoundPage();
  require_once $baseClassFile;
  require_once $providerClassFile;
  if (!class_exists($providerClass)) notFoundPage();

  try
  {
    $socialHelper = new $providerClass($provider);
    if (!method_exists($socialHelper, $providerAction)) notFoundPage();
    $socialHelper->{$providerAction}();
  }
  catch (Exception $e)
  {
    errorPage($e->getMessage(), ($e->getCode() ? $e->getCode() : 500));
  }
?>
