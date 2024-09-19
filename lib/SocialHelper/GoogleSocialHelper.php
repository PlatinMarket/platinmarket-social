<?php

class GoogleSocialHelper extends SocialHelper
{
  public function login()
  {
	$credential = @$_GET['credential'];

	if ($credential !== null) {
		$client = new Google_Client();
		$client->setClientId('188360825577-197abfqgpp1q273guk6rqs48p01s04n2.apps.googleusercontent.com');	
		$credential = $client->verifyIdToken($credential);
		$credential = $credential->getAttributes();
		$credential = $credential['payload'];
		/*
			https://developers.google.com/identity/gsi/web/guides/verify-google-id-token#php
			in this version of the library, if the credential couldn't be verified or tried to be tempered, instead of throwing an exception, google decided to kill the program. so the else statement or try-catch block will never be executed.
		*/
		if (is_array($credential)) {
			$user = [
				"id" => $credential['sub'],
				"first_name" => $credential['given_name'],
				"last_name" => $credential['family_name'],
				"email" => $credential['email'],
			];

			return $this->returnOrigin(array_merge(array('success' => 'ok', 'message' => 'Giriş başarılı'), $user));
		} else {
			return $this->returnOrigin(array('success' => 'error', 'message' => 'Giriş bilgileri doğrulanamadı'));
		}
	} else {
		$SESSION = @$_SESSION[$this->_session_id];
		
		echo "<html>";
		echo "<head>";
		echo '<meta charset="UTF-8">';
		echo '<title>PBX Social</title>';
		echo '<script src="https://accounts.google.com/gsi/client" async></script>';
		echo '<script>window.google_auth_next = function ({ credential }) { document.getElementById("credential").value = credential; document.getElementById("do_login").submit();}</script>';
		echo '<style>* { padding: 0; margin: 0; } html, body, .container { width: 100%; height: 100%; } .container { display: flex; align-items: center; justify-content: center; } .g_id_signin { transform: scale(1.5); } </style>';
		echo '</head>';
		echo '<body>';
		echo '<div class="container">';
		echo '<form method="GET" id="do_login">';
		echo sprintf('<input type="hidden" name="token" value="%s" />', $SESSION['token']);
		echo sprintf('<input type="hidden" name="return_url" value="%s" />', $SESSION['return_url']);
		echo sprintf('<input type="hidden" name="params" value="%s" />', $SESSION['params']);
		echo '<input type="hidden" name="credential" id="credential" />';
		echo '</form>';	
		echo '<div data-callback="google_auth_next" class="g_id_signin" data-locale="tr" data-logo_alignment="left" data-shape="pill" data-size="medium" data-text="continue_with" data-theme="outline" data-type="standard" id="g_id_onload" data-client_id="188360825577-197abfqgpp1q273guk6rqs48p01s04n2.apps.googleusercontent.com">&nbsp;</div>';
		echo '</div>';
		echo '</body>';
	}
  }
}
