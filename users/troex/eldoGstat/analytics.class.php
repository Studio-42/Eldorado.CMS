<?php

// TODO: make login() auto from call()
class Google_Analytics {
	
	var $auth;
	// TODO: move account info to analytics.class
	var $google_account = 'troex@fury.scancode.ru';
	var $google_password = 'btcf786315';

	function ClientLogin() {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");	// TODO: move globar var ???
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_POST, true);

		$data = array(
			'accountType' => 'GOOGLE',
			'Email' => $this->google_account,
			'Passwd' => $this->google_password,
			'service' => 'analytics',
			'source' => ''		// TODO: put here software version, better without spaces e.g.: "Eldorado.CMS"
			);

		curl_setopt($c, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($c);
		$info = curl_getinfo($c);
		curl_close($c);

		print_r($info);
		
		$this->auth = '';
		if ($info['http_code'] == 200) {
			preg_match('/Auth=(.*)/', $output, $matches);
			if (isset($matches[1])) {
				$this->auth = $matches[1];
			}
		}
		
		return $this->auth != '';	// TODO: El error_reporting add here
	}

	function GetFeed($url) {
		if ($auth == null)
			$this->ClientLogin();
			
		$headers = array("Authorization: GoogleLogin auth=$this->auth");

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($c);
		$info = curl_getinfo($c);
		curl_close($c);

		if ($info['http_code'] == 200) {
			return $output;
		} else {
			echo 'ERROR: ' . $output;	// TODO: move El error_reporting, here $output is Google text error which is human readable 
			return false;	
		}
	}
	
}