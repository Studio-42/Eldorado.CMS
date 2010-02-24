<?php

// Возвращает ключ аутентификации для системы автоматического обновления

// Дополнительный уровень защиты - отвечать только на адрес сервера обновлений
define('EL_UC_CHECK_SERVER_ADDRESS', false);

class elServiceUpdateAuth extends elService
{
	var $nav        = null;

	function defaultMethod()
	{
		// Do nothing
	}

	function toXML()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$key = $conf->get('authKey', 'updateClient');
		// Удаляем ключ для того, чтобы исключить возможность его использования третьей стороной
		$conf->set('lastCheck', '', 'updateClient');
		$conf->save();
		
		// Отвечаем только серверу обновлений
		if (EL_UC_CHECK_SERVER_ADDRESS) {
			$addr = $conf->get('serverAddress', 'updateClient');
			if ($_SERVER['REMOTE_ADDR'] != $addr)
				return '';
		}
		
		$reply  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$reply .= "<auth>\n";
		$reply .= "<key>$key</key>\n";
		$reply .= "</auth>\n";
		return $reply;
	}
}

?>