<?php

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

  if (!isset($_GET['do']) || empty($_GET['do'])) notFoundPage();
  $do = preg_split('/\/+/', $_GET['do']);
  if (count($do) < 2) notFoundPage();

  $provider = ucfirst(strtolower($do[0]));
  $providerClass = $provider . "SocialHelper";
  $providerAction = strtolower($do[1]);

  $providerClassFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'SocialHelper' . DIRECTORY_SEPARATOR . $providerClass . '.php';
  $baseClassFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'SocialHelper.php';

  if (!file_exists($providerClassFile)) notFoundPage();
  require_once $baseClassFile;
  require_once $providerClassFile;
  if (!class_exists($providerClass)) notFoundPage();

  $socialHelper = new $providerClass($provider);
  if (!method_exists($socialHelper, $providerAction)) notFoundPage();
  $socialHelper->{$providerAction}();
?>
