<?php
if ( !defined('EL_LANG') )
{
	define('EL_LANG', 'ru');
}
if ( !defined('EL_CHARSET') )
{
	define('EL_CHARSET', 'UTF-8');
}
//TO DO - user iconv extension to convert strings
class elPostman
{
	var $from            = '';
	var $to              = array();
	var $cc              = array();
	var $bcc             = array();
	var $subject         = '';
	var $msg             = '';
	var $sign            = '';
	var $msgFormat       = 'text/plain';
	var $encoding        = '8bit';
	var $contentType     = '';
	var $attachments     = array();
	var $transport       = 'PHP';
	var $smtpHost        = 'localhost';
	var $smtpPort        = 25;
	var $smtpAuthRequire = false;
	var $smtpUser        = '';
	var $smtpPass        = '';
	var $_smtpConnect    = null;
	var $_boundary       = '';
	var $error           = '';
	var $_nl             = "\n";
	var $_cyrChars       = array( 'koi8-r'=>'k',
	'windows-1251'=>'w',
	'cp1251'=>'w',
	'iso8859-5'=>'i',
	'x-mac-cyrillic'=>'m',
	'x-cp866'=>'d');
	var $_chars          = array( 'ru' => 'koi8-r',
	'en' => 'iso8859-1',
	'jp' => 'utf-8');
	var $_lang           = '';
	var $_charsetIn      = '';
	var $_charsetOut     = '';
	var $_cyrConv        = false;
	var $_appendUrlFrom  = true;
	var $_logSend        = false;
	var $_logFailed      = true;

	function elPostman($lang=EL_LANG, $charset=EL_CHARSET, $enc='8bit', $appUrl=true)
	{
		
		$this->_lang          = strtolower($lang);
		$this->_charsetIn     = strtolower($charset);
		$this->encoding       = $enc;
		$this->_appendUrlFrom = $appUrl;

		if ( !empty($this->_chars[$this->_lang]) )
		{
			$this->_charsetOut = $this->_chars[$this->_lang];
		}
		else
		{
			$this->_charsetOut = $this->_charsetIn;
		}

		if ( $this->_charsetIn <> $this->_charsetOut && !function_exists('iconv')  )
		{
			if ('ru' == $this->_lang && !empty($this->_cyrChars[$this->_charsetIn]) )
			{
				$this->_cyrConv = true;
			}
			else
			{
				$this->_charsetOut = $this->_charsetIn;
			}
		}

		if ( defined('EL_VER') )
		{
			$conf = & elSingleton::getObj('elXmlConf');
			if ( 'SMTP' == $conf->get('transport', 'mail') )
			{
				$this->setTtransportSmtp($conf->get('smtpHost', 'mail'), $conf->get('smtpPort', 'mail'),
				$conf->get('smtpAuth', 'mail'), $conf->get('smtpUser', 'mail'),
				$conf->get('smtpPass', 'mail') );
			}
			else
			{
				$this->setTransportPhp();
			}
			$this->_logSend   = (bool)$conf->get('logSend',   'mail');
			$this->_logFailed = (bool)$conf->get('logFailed', 'mail');
		}
	}

	/**
   * @param     $from string адрес отправителя
   * @param     $subject string
   * @param     $msg    string
   * @param     $is_html_msg флаг - тело сообщения в хтмл формате?
   */
	function newMail($from, $to, $subject, $msg, $is_html_msg=false, $sign='')
	{
		$this->to = $this->cc = $this->bcc = array();
		$this->sign           = $sign;
		$this->msgFormat      = $is_html_msg ? 'text/html' : 'text/plain';
		$this->setFrom($from);
		$this->setTo($to);
		$this->setSubject($subject);
		$this->setBody($msg);
	}


	function setFrom( $from )
	{
		$this->from = $this->addrToBase64( $from );
	}
	/**
   * устанавливает одного или нескольких получателей письма
   * @param     $addr   mixed(array or string)
  */
	function setTo( $addr )
	{
	   if ( is_array($addr))
	   {
	      foreach ($addr as $one)
	      {
	         $this->setTo($one);
	      }
	   }
		elseif (!empty($addr))
		{
			$this->_add_recipient( $this->addrToBase64($addr) );
		}
	}

	/**
   * устанавливает одного или нескольких получателей копий
   * @param     $addr   mixed(array or string)
   */
	function setCc( $addr )
	{
		if (!empty($addr))
		{
			$this->_add_recipient( $this->addrToBase64($addr), 'Cc' );
		}
	}

	/**
   * устанавливает одного или нескольких получателей скрытых копий письма
   * @param     $addr   mixed(array or string)
  */
	function setBcc( $addr )
	{
		if (!empty($addr))
		{
			$this->_add_recipient( $this->addrToBase64($addr), 'Bcc' );
		}
	}

	function addrToBase64( $str )
	{
		$str = $this->_convert($str);
		if ( preg_match('/(.+)(\<[^\s]+\@[^\s]+>)/', $str, $m) )
		{
			$str = "=?".$this->_charsetOut."?b?".base64_encode( str_replace('"', '', $m[1]) )."?=".$m[2];
		}
		return $str;
	}

	function setSubject( $subj )
	{
		$this->subject = preg_replace('/^([^\n]*)\n.*/ism', "\\1", $subj);
		$this->subject = $this->_convert($subj);
		$this->subject = "=?".$this->_charsetOut."?b?".base64_encode($this->subject)."?=";
	}

	function setBody( $msg )
	{
		if ( $this->sign )
		{
			$msg .= $this->_nl.$this->_nl.str_repeat('-', 25).$this->_nl.$this->sign.$this->_nl;
		}
		if ( $this->_appendUrlFrom )
		{
			$URL  = 'http://'.$_SERVER['HTTP_HOST'].dirname( $_SERVER['PHP_SELF'] ) ;
			$patt = 'Message was sent from %s';
			if ( function_exists('m') )
			{
				$patt = m($patt);
			}
			$msg .= $this->_nl.str_repeat('-', 25).$this->_nl.sprintf( $patt, $URL ).$this->_nl;
		}
		$this->msg = $this->_convert($msg);
	}

	/**
   * Пристегивает файл к письму
   * @param     $filename       string
   * @param     $mime_type      string
   * @param     $disp                   string
   * @param     $encode         string
   */
	function attach($filename, $mime_type='application/octet-stream', $disp='attachment', $encode='base64')
	{
		if ( false == ($fp = fopen($filename, 'rb')) )
		{
			return $this->_putError( "Cant open file $filename. Skipped\n" );
		}
		$content = fread($fp, filesize($filename));

		$this->attachments[] = array(
		'name'        => basename($filename),
		'content'     => $content,
		'mime_type'   => $mime_type,
		'disposition' => $disp,
		'encoding'    => $encode
		);
	}

	/**
   * Отправка письма
   */
	function deliver()
	{
		if ( empty($this->to) )
		{
			die('No one recipient was defined');
		}

		$this->_content_type();
		if ( 'SMTP' == $this->transport )
		{
			$status = $this->_smtp_send();
		}
		else
		{
			$status = $this->_php_send();
		}
		if ( $status && $this->_logSend )
		{
			$this->_log('mail-send.log');
		}
		elseif ( !$status || $this->_logFailed )
		{
			$this->_log('mail-failed.log');
		}
		return $status;
	}

	function _log($file)
	{
		if ( false != ($fp = @fopen(EL_DIR_LOG.$file, 'a') ))
		{
			$log = date('d.m.Y H:i:s')."\n";
			$log .= "IP: ".getenv('REMOTE_ADDR')."\n";
			$log .= "User-Agent: ".getenv('HTTP_USER_AGENT')."\n";
			$log .= "From: ".$this->from."\n";
			$log .= "To: ".implode(', ', $this->to)."\n";
			$log .= "Cc: ".implode(', ', $this->cc)."\n";
			$log .= "Bcc: ".implode(', ', $this->bcc)."\n";
			$log .= "Subject: ".$this->subject."\n";
			$log .= "Headers: \n".$this->_headers()."\n";
			$log .= "Body: \n".$this->_body()."\n\n";
			@fwrite($fp, $log);
			@fclose($fp);
		}

	}

	function hasError()
	{
		return (bool)$this->error;
	}

	function getError()
	{
		return $this->error;
	}

	function setTransportPhp()
	{
		$this->transport = 'PHP';
	}

	function setTtransportSmtp($host='localhost', $port=25, $require_auth=false, $user=null, $pass=null)
	{
		$this->transport       = 'SMTP';
		$this->smtpHost        = $host;
		$this->smtpPort        = 0<$port ? (int)$port : 25;
		$this->smtpAuthRequire = (bool)$require_auth;
		$this->smtpUser        = $user;
		$this->smtpPass        = $pass;
	}
	/****************************************************/

	function _getConv()
	{
		if ( $this->_charsetIn == $this->_charsetOut )
		{
			return null;
		}
		return $this->_cyrConv ? 'cyr' : 'iconv';
	}

	function _convert($str)
	{
		$conv = $this->_getConv();
		if ( 'iconv' == $conv )
		{
			return iconv( $this->_charsetIn, $this->_charsetOut.'//TRANSLIT', $str );
		}
		elseif ('cyr' == $conv )
		{
			return convert_cyr_string($str, $this->_cyrChars[$this->_charsetIn], $this->_cyrChars[$this->_charsetOut]);
		}
		return $str;
	}



	/**
   * Отправка через php mail
   */
	function _php_send()
	{ 
		return mail( implode(',', $this->to), $this->subject, $this->_body(), $this->_headers());
	}

	function _smtp_send()
	{
		$this->_smtpConnect = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 10);
		if ( !$this->_smtpConnect )
		{
			$this->_putError('Cant connect to server ' . $errno . "\n" . $errstr);
			return false;
		}
		socket_set_timeout($this->_smtpConnect, 10);
		socket_set_blocking($this->_smtpConnect, TRUE);

		if ( !$this->_check_code('220') )
		{
			return $this->_putError('Failed connect to server');
		}

		fputs($this->_smtpConnect, "HELO " . $this->smtpHost . "\n");
		if ( !$this->_check_code('250') )
		{
			$this->_putError('HELO command failed');
		}

		if ( $this->smtpAuthRequire && !$this->_smtp_auth() )
		{
			fclose($this->_smtpConnect);
			return false;
		}

		fputs($this->_smtpConnect, "MAIL FROM:" . $this->from . "\n");

		if ( !$this->_check_code('250') )
		{
			return $this->_putError("MAIL FROM " . $this->from . " failed");
		}

		fputs( $this->_smtpConnect, "RCPT TO:".$this->to[0]."\n");

		if ( !$this->_check_code('250') )
		{
			return $this->_putError("RCPT TO ".$this->to[0].' failed');
		}

		fputs($this->_smtpConnect, "DATA\n");

		if ( !$this->_check_code('354') )
		{
			return $this->_putError('DATA command failed');
		}

		fputs($this->_smtpConnect, $this->_headers() . $this->_nl . $this->_body() . "\r\n.\r\n");

		if ( !$this->_check_code('250') )
		{
			return $this->_putError("DATA NOT ACCEPTED");
			return false;
		}

		fputs($this->_smtpConnect, "QUIT\n");
		fclose($this->_smtpConnect);
		return true;
	}

	function _smtp_auth()
	{
		fputs($this->_smtpConnect, "AUTH LOGIN\n");
		if ( !$this->_check_code('334') )
		{
			$this->_putError("AUTH LOGIN not supported");
			return true;
		}
		if ( !$this->smtpUser || !$this->smtpPass )
		{
			return $this->_putError('Undefied user or password for SMTP server');
		}

		fputs($this->_smtpConnect, base64_encode($this->smtpUser) . "\n" );
		if ( !$this->_check_code('334') )
		{
			return $this->_putError('Username not accepted');
		}

		fputs($this->_smtpConnect, base64_encode($this->smtpPass) . "\n" );
		if ( !$this->_check_code('235') )
		{
			return $this->_putError('Password not accepted');
		}
		return true;
	}

	function _check_code( $code )
	{
		$reply = fgets($this->_smtpConnect, 512); 
		//elDebug($reply);
		return $code == substr($reply, 0, 3) ? true : false;
	}

	/**
   * Определяет Content-Type сообщения
   */
	function _content_type()
	{
		if ( 'text/plain' == $this->msgFormat )
		{
			$this->contentType = !$this->attachments ? $this->msgFormat : 'multipart/mixed';
		}
		else
		{
			$this->contentType = !$this->attachments ? 'multipart/alternative' : 'multipart/mixed';
		}
	}

	/**
   * Возвращает заголовки письма
   */
	function _headers()
	{
		$headers  = 'From: ' . $this->from . $this->_nl;
		$headers .= 'Reply-to: ' . $this->from . $this->_nl;
		$headers .= 'Return-path: ' . $this->from . $this->_nl;
		$headers .= 'X-Mailer: eldorado CMS Postman '.(defined('EL_VER') ? EL_VER : '').' (client IP: '.getenv('REMOTE_ADDR').')'.$this->_nl;
		if ('PHP' != $this->transport )
		{
			$headers .= 'Subject: ' . $this->subject . $this->_nl;
			$headers .= 'To: ' . implode(',', $this->to) . $this->_nl;
		}
		$headers .= 'Date: '. date('r') . $this->_nl;
		//                $headers .= 'Message-Id: ' . md5( uniqid(time()) ) . $this->_nl;

		if ( $this->cc )
		{
			$headers .= 'Cc: ' . implode(',', $this->cc) . $this->_nl;
		}
		if ( $this->bcc )
		{
			$headers .= 'Bcc: ' . implode(',', $this->bcc) . $this->_nl;
		}

		if ('text/plain' == $this->contentType )
		{
			$headers .= 'Content-Type: text/plain; charset=' . $this->_charsetOut . $this->_nl;
			$headers .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl;
		}
		else
		{
			$headers .= 'MIME-Version: 1.0' . $this->_nl;
			$headers .= 'Content-Type: ' . $this->contentType . ";
  boundary=\"" . $this->_boundary() . '"' . $this->_nl;

		}
		return $headers ;
	}

	/**
   * Возвращает тело сообщения
   */
	function _body()
	{
		if ( 'text/plain' == $this->contentType )
		{
			return $this->msg;
		}
		elseif ( 'multipart/alternative' == $this->contentType )
		{//txt+html
			$body = '--' . $this->_boundary() . $this->_nl;
			$body .= 'Content-Type: text/plain; charset="' . $this->_charsetOut . '"' . $this->_nl;
			$body .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl . $this->_nl;
			$body .= strip_tags($this->msg) . $this->_nl . $this->_nl;
			$body .= '--' . $this->_boundary() . $this->_nl;
			$body .= 'Content-Type: text/html; charset="' . $this->_charsetOut . '"' . $this->_nl;
			$body .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl . $this->_nl;
			$body .= $this->msg . $this->_nl . $this->_nl;
			$body .= '--' . $this->_boundary() . '--' . $this->_nl;
		}
		else
		{
			if ( 'text/plain' == $this->msgFormat )
			{//txt+attach
				$body = '--' . $this->_boundary() . $this->_nl;
				$body .= 'Content-Type: text/plain; charset="' . $this->_charsetOut . '"' . $this->_nl;
				$body .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl . $this->_nl;
				$body .= strip_tags($this->msg) . $this->_nl . $this->_nl;

			}
			else
			{//[txt+html]+attach
				$bound = 'alt' . md5(uniqid(time()));

				$body = '--' . $this->_boundary() . $this->_nl;
				$body .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"' . $this->_nl . $this->_nl;
				$body .= '--' . $bound . $this->_nl;
				$body .= 'Content-Type: text/plain; charset="' . $this->_charsetOut . '"' . $this->_nl;
				$body .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl . $this->_nl;
				$body .= strip_tags($this->msg) . $this->_nl . $this->_nl;
				$body .= '--' . $bound . $this->_nl;
				$body .= 'Content-Type: text/html; charset="' . $this->_charsetOut . '"' . $this->_nl;
				$body .= 'Content-Transfer-Encoding: ' . $this->encoding . $this->_nl . $this->_nl;
				$body .= $this->msg . $this->_nl . $this->_nl;
				$body .= '--' . $bound . '--' . $this->_nl . $this->_nl;
			}
			$body .= $this->_attach();
			$body .= '--' . $this->_boundary() . '--' . $this->_nl;
		}
		return $body;
	}

	/**
   * Возвращает разделитель
   */
	function _boundary()
	{
		if ( empty($this->_boundary) )
		{
			$this->_boundary = md5(uniqid(time()));
		}
		return $this->_boundary;
	}


	/**
   * Возвращает кодированные вложенные файлы
   */
	function _attach()
	{
		$ret = '';
		foreach ( $this->attachments as $file )
		{
			if ( 'base64' == $file['encoding'] )
			{
				$content = chunk_split( base64_encode($file['content']) );
			}
			else
			{
				$content = $file['content'];
			}
			$ret .= '--' . $this->_boundary() . $this->_nl;
			$ret .= 'Content-Type: ' . $file['mime_type'] . '; name="' . $file['name'] . '"' . $this->_nl;
			$ret .= 'Content-Transfer-Encoding: ' . $file['encoding'] . $this->_nl;
			$ret .= 'Content-Disposition: ' . $file['disposition'] . ';
                            filename="' . $file['name'] .'"'. $this->_nl . $this->_nl;
			$ret .= $content . $this->_nl;
		}
		return $ret;
	}

	/**
   * Добавляет адреса к заданому массиву получателей
   * @param     $addr   mixed (array or string)
   * @param     $member string  To, Cc or Bcc
   */
	function _add_recipient( $addr, $member = 'to' )
	{
		$member = strtolower($member);
		if ( !is_array($addr) )
		{
			array_push($this->$member, $addr);
		}
		else
		{
			foreach ( $addr as $address )
			{
				array_push($this->$member, $address);
			}
		}
	}

	function _putError( $msg )
	{
		$this->error .= $msg . "\n";
	}
}

?>
