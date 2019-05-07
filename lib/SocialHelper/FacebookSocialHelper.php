<?php

class FacebookSocialHelper extends SocialHelper
{

  private $fb;

  function beforeAction()
  {
    $useDefaultConfig = $this->getApplicationData('facebook.use_default') !== "false" ? true : false;

    $this->fb = new Facebook\Facebook([
      'app_id' => $useDefaultConfig ? $this->config('app_id') : $this->getApplicationData('facebook.app_id'),
      'app_secret' => $useDefaultConfig ? $this->config('app_secret') : $this->getApplicationData('facebook.app_secret'),
      'default_graph_version' => $this->config('api_version')
    ]);
  }

  public function auth_callback()
  {
    header('Content-Type: text/html; charset=UTF-8');
    $helper = $this->fb->getRedirectLoginHelper();

    $errMessage = "";
    try
    {
      $accessToken = $helper->getAccessToken();
    }
    catch(Exception $e)
    {
      $errMessage = $e->getMessage() . " ";
    }

    if (!isset($accessToken))
    {
      if ($helper->getError()) {
        $errMessage .= "Error: " . $helper->getError() . ". ";
        $errMessage .= "Error Code: " . $helper->getErrorCode() . ". ";
        $errMessage .= "Error Reason: " . $helper->getErrorReason() . ". ";
        $errMessage .= "Error Description: " . $helper->getErrorDescription() . ". ";
      }
      else
      {
        if ($errMessage == "") $errMessage = "AccessToken is null";
      }
    }

    if ($errMessage != "") return $this->returnOrigin(array('success' => 'error', 'message' => $errMessage));

    if (isset($_SESSION[$this->_session_id]['return_func']) && !empty($_SESSION[$this->_session_id]['return_func'])) $this->{$_SESSION[$this->_session_id]['return_func']}($accessToken->getValue());
  }

  public function login($accessToken = null)
  {
    if (is_null($accessToken)) return $this->__authorize('login');

    try
    {
      $response = $this->fb->get('/me?fields=email,first_name,last_name', $accessToken);
    }
    catch(Exception $e)
    {
      return $this->returnOrigin(array('success' => 'error', 'message' => $e->getMessage()));
    }

    $fb_user = $response->getGraphUser();

    if (!isset($fb_user['email'])) return $this->returnOrigin(array('success' => 'error', 'message' => 'Üye olmak için mail adresinize izin vermelisiniz.'));

    try
    {
      $user = array(
        'id' => $fb_user['id'],
        'first_name' => $fb_user['first_name'],
        'last_name' => $fb_user['last_name'],
        'email' => $fb_user['email']
      );
    }
    catch (Exception $e)
    {
      return $this->returnOrigin(array('success' => 'error', 'message' => $e->getMessage()));
    }

    return $this->returnOrigin(array_merge(array('success' => 'ok', 'message' => 'Giriş başarılı'), $user));
  }

  private function __authorize($returnFunc = null)
  {
    if (isset($_SESSION[$this->_session_id]['return_func'])) unset($_SESSION[$this->_session_id]['return_func']);
    if ($returnFunc) $_SESSION[$this->_session_id]['return_func'] = $returnFunc;
    $helper = $this->fb->getRedirectLoginHelper();

    $loginUrl = $helper->getLoginUrl($this->buildUrl('facebook', 'auth_callback') . "?session=" . $this->_session_id, $this->config("permission"));
    header('Location: ' . $loginUrl);
    exit();
  }

}
