<?php

class SocialHelper
{
  private $_configFile;
  private $_config;
  private $_provider;

  function __construct($provider)
  {
    $this->_provider = $provider;
    $this->_configFile = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . strtolower($this->_provider) . '.config.json';
    $this->_config = array();
    if (file_exists($this->_configFile)) $this->_config = json_decode(file_get_contents($this->_configFile), true);
  }

  protected function config($path = null)
  {
    if (is_null($path)) return $this->_config;
    $loc = $this->_config;
    foreach(explode('.', $path) as $step)
    {
      if (!isset($loc[$step])) return;
      $loc = $loc[$step];
    }
    return $loc;
  }

}
