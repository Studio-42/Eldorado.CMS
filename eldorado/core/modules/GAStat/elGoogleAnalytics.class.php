<?php

class elGoogleAnalytics {
	
	var $auth;
	var $_gaAccount = '';
	var $_gaPassword = '';
	var $error;
	var $_cachePath = './cache/GAStat/';
	var $_cache = true;	// true/false = on/off
	
	var $_parser = null;
	
	function __construct($account, $password)
	{
		$this->_gaAccount  = $account;
		$this->_gaPassword = $password;
		$this->_parser = & elSingleton::getObj('elGAParser');
	}
	
	function elGoogleAnalytics($account, $password)
	{
		$this->__construct($account, $password);
	}

	function auth()
	{
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");	
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_POST, true);

		$data = array(
			'accountType' => 'GOOGLE',
			'Email'       => $this->_gaAccount,
			'Passwd'      => $this->_gaPassword,
			'service'     => 'analytics',
			'source'      => 'ELDORADO.CMS-'.EL_VER		
			);

		curl_setopt($c, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($c);
		$info = curl_getinfo($c);
		//elPrintR($info);
		curl_close($c);

		$this->auth = '';
		if ($info['http_code'] == 200)
		{
			preg_match('/Auth=(.*)/', $output, $matches);
			if (isset($matches[1]))
			{
				$this->auth = $matches[1];
			}
		}
		else
		{
			$this->error = $info['http_code'] . ' ' . strip_tags($output);	// Google error
			return false;
		}

		return $this->auth != '';	
	}

	function getFeed($url)
	{
		if ($this->auth() === false)
			return false;
		
			
		$headers = array("Authorization: GoogleLogin auth=$this->auth");

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($c);
		$info = curl_getinfo($c);
		curl_close($c);

		if ($info['http_code'] == 200)
		{
			return $output;
		}
		else
		{
			$this->error = $info['http_code'] . ' ' . strip_tags($output);	// Google error 
			return false;	
		}
	}
	
	function get($url)
	{
		if (!is_dir($this->_cachePath))
			mkdir($this->_cachePath, 0777);
			
		// if (($dynamicCache == true) && (!preg_match('!dynamic/$!', $this->_cachePath)))
		// {
		// 	$this->_cachePath .= 'dynamic/';			
		// 	if (!is_dir($this->_cachePath))
		// 		mkdir($this->_cachePath, 0777);
		// }
		
		$md5 = md5($url);
		$cacheFile = $this->_cachePath . $md5;
			
		if (false == ($cache = file_get_contents($cacheFile)))
		{	// data not found in cache
			
			$xml = $this->getFeed($url);
			$data = $this->_parser->parseDataFeed($xml);
			if (($data != false) && ($this->_cache))	// cache data
			{
				$fh = fopen($cacheFile, 'w');
				fwrite($fh, serialize($data));
				fclose($fh);
			}
	
			// GC for cache
			if ($dh = opendir($this->_cachePath))
			{
				while (false !== ($file = readdir($dh)))
				{
					$file = $this->_cachePath . $file;
					if ((!is_dir($file)) && ((time() - filectime($file)) > 86400))
						unlink($file);		// delete cache older than 1 day
				}
				closedir($dh);
			}
	
		}
		else	// data got from cache successful
			return unserialize($cache);

		return $data;
	}
	
	function getAccounts()
	{
		if (false == ($xml = $this->getFeed('https://www.google.com/analytics/feeds/accounts/default')))
			return false;
		$data = $this->_parser->parseDataFeed($xml);
		return $data;
	}
	
}

?>