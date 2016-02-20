<?php

class SocialHelper
{
  private $_config;
  private $_provider;
  private $_return_url = null;
  private $_env;
  protected $_session_id;

  function __construct($provider)
  {
    $this->_env = ((strpos($this->buildUrl(), "http://localhost") === 0) ? 'dev' : 'prod');
    $this->_provider = $provider;
    $configFile = dirname(dirname(__FILE__)) . DS . 'config' . DS . strtolower($this->_provider) . '.config.json';
    $this->_config = array();
    if (file_exists($configFile)) $this->_config = json_decode(file_get_contents($configFile), true);
    $configFile = dirname(dirname(__FILE__)) . DS . 'config' . DS . 'config.json';
    $this->_config['global'] = array();
    if (file_exists($configFile)) $this->_config['global'] = json_decode(file_get_contents($configFile), true);
    $this->startSession();
    $this->validateReturnUrl();
  }

  private function startSession()
  {
    $this->_session_id = isset($_GET['session']) ? $_GET['session'] : (isset($_POST['session']) ? $_POST['session'] : null);
    if (is_null($this->_session_id) && isset($_GET['token'])) $this->_session_id = uniqid('client_');
    if (is_null($this->_session_id)) return;
    if (!$_SESSION[$this->_session_id]) $_SESSION[$this->_session_id] = array();
    if (!$_SESSION[$this->_session_id]['token']) $_SESSION[$this->_session_id]['token'] = isset($_GET['token']) ? $_GET['token'] : null;
    if (!$_SESSION[$this->_session_id]['return_url']) $_SESSION[$this->_session_id]['return_url'] = isset($_GET['return_url']) ? $_GET['return_url'] : $_SERVER['HTTP_REFERER'];
    if (!$_SESSION[$this->_session_id]['params']) $_SESSION[$this->_session_id]['params'] = isset($_GET['params']) ? $_GET['params'] : null;
  }

  private function validateReturnUrl()
  {
    if (is_null($this->_session_id) || is_null($_SESSION[$this->_session_id]['token'])) throw new Exception('Unauthorized', 401);
    if ($this->_env != "dev" && !$_SESSION[$this->_session_id]['return_url']) throw new Exception('Unauthorized', 401);
    if ($_SESSION[$this->_session_id]['return_url'])
    {
      $hostname = parse_url($_SESSION[$this->_session_id]['return_url']);
      $hostname = $hostname['host'];
      $serverIp = gethostbyname($hostname);
      if (strpos($serverIp, '192.168.') !== 0 && $serverIp != '127.0.0.1' && strpos($serverIp, '212.174.10.') !== 0) throw new Exception('Unauthorized', 401);
    }
  }

  protected function returnOrigin($data)
  {
    $returnUrl = $_SESSION[$this->_session_id]['return_url'];
    $params = $_SESSION[$this->_session_id]['params'];
    if (!is_null($params) && is_string($params) && !empty($params))
    {
      parse_str($params, $params);
      $params = http_build_query($params);
      if (strpos($returnUrl, $params) === false) $returnUrl .= strpos($returnUrl, "?") === false ? "?" . $params : "&" . $params;
    }
    $token = $_SESSION[$this->_session_id]['token'];
    unset($_SESSION[$this->_session_id]);
    $data['_token'] = $token;
    $data['_provider'] = strtolower($this->_provider);
    $data['_rand'] = $this->__createSalt();
    $data['_hash'] = $this->__hash($data, array('id', 'first_name', 'last_name', 'email', 'gender', '_provider', '_token', '_rand'));
    if ($returnUrl) return $this->formRedirect($data, $returnUrl);
    pr($data);
  }

  private function __createSalt()
  {
    $text = md5(uniqid(rand(), TRUE));
    return substr($text, 0, 10);
  }

  private function __hash($data, $fields)
  {
    if (empty($data) || !is_array($data)) return null;
    $hashStr = '';
    foreach ($fields as $key) {
      if (!isset($data[$key])) return null;
      $hashStr .= $data[$key];
    }
    $secretKey = $this->config('global.secret');
    if (empty($secretKey)) return null;
    return hash('sha256', $hashStr . md5($secretKey));
  }

  protected function formRedirect($formData, $location, $message = "Lütfen bekleyin...")
  {

    ?>
    <html>
      <head>
        <meta charset="utf-8">
        <title><?php echo $message; ?></title>
      </head>
      <body>
        <h3><?php echo $message; ?></h2>
        <form action="<?php echo $location; ?>" method="POST">
          <?php foreach ($formData as $key => $value) { ?>
            <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
          <?php } ?>
        </form>
        <script>
          document.getElementsByTagName("form")[0].submit();
        </script>
      </body>
    </html>
    <?php
    exit();
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

  protected function buildUrl()
  {
    $host = $_SERVER['HTTP_HOST'];
    $protocol = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == "on") || (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) == "https") ? 'https:' : 'http:';
    return $protocol . "//" . $host . BASE_PATH . "/" . implode("/", func_get_args());
  }

}
