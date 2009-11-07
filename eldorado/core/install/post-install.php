<?php

//define('EL_NEW_VER', '3.9.0');

function elPostInstall()
{
  global $msgs;
  $fName = 'elPSUpdate_'.str_replace('.', '_', EL_VER);
  if ( function_exists($fName) )
  {
    if ( $fName() )
    {
      $msgs[] = m('Post-install script: Success!');
      $fp = fopen('./tmp/log1', 'w');
      fwrite($fp, implode('<br>', $msgs) );
      fclose($fp);
      return true;
    }
    else
    {
      $msgs[] = m('Post-install script: Failed!');
      return false;
    }
  }

  $msgs[] = m('Post-install script: Do nothing for this version');
  return true;
}

function elPSUpdate_3_9_0()
{
	$msgs[] = 'Update 3.8.0 version';
	if ( false == ($db = & elSingleton::getObj('elDb'))  )
	{
		$msgs[] = 'Could not get DB object';
		return false;
	}
	if ( !isFieldExists($db, 'el_user', 'forum_posts_count') )
	{
		if ( !$db->query("ALTER TABLE el_user ADD `forum_posts_count` INT( 5 ) NOT NULL") )
        {
        	$msgs[] = 'Could not add field `forum_posts_count` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `avatar` varchar(150) collate utf8_bin NOT NULL") )
        {
        	$msgs[] = 'Could not add field `avatar` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `signature` mediumtext collate utf8_bin") )
        {
        	$msgs[] = 'Could not add field `signature` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `personal_text` varchar(256) collate utf8_bin default NULL") )
        {
        	$msgs[] = 'Could not add field `personal_text` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `location` varchar(256) collate utf8_bin default NULL") )
        {
        	$msgs[] = 'Could not add field `location` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `birthdate` int(11) default NULL") )
        {
        	$msgs[] = 'Could not add field `birthdate` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `gender` enum('','male','female') collate utf8_bin NOT NULL default ''") )
        {
        	$msgs[] = 'Could not add field `gender` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `show_email` tinyint(1) NOT NULL default '0'") )
        {
        	$msgs[] = 'Could not add field `show_email` to table el_user';
        }
		if ( !$db->query("ALTER TABLE el_user ADD `show_online` tinyint(1) NOT NULL default '0'") )
        {
        	$msgs[] = 'Could not add field `show_online` to table el_user';
        }
	}
	
	if ( !$db->isTableExists('el_icart')  )
	{
		$sql = "CREATE TABLE `el_icart` (
		  `id` int(8) NOT NULL auto_increment,
		  `sid` varchar(32) collate utf8_bin NOT NULL,
		  `uid` int(5) NOT NULL,
		  `shop` enum('IShop','TechShop') collate utf8_bin NOT NULL default 'IShop',
		  `i_id` int(5) NOT NULL,
		  `m_id` int(5) NOT NULL,
		  `code` varchar(256) collate utf8_bin NOT NULL,
		  `display_code` tinyint(1) NOT NULL default '1',
		  `name` varchar(256) collate utf8_bin NOT NULL,
		  `qnt` int(5) NOT NULL default '1',
		  `price` double(8,2) NOT NULL,
		  `props` text collate utf8_bin,
		  `crtime` int(11) NOT NULL,
		  `mtime` int(11) NOT NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		if (!$db->query($sql))
		{
			$msgs[] = 'Could not create table el_icart';
		}
	}
	if ( !$db->isTableExists('el_order')  )
	{
		$sql = "CREATE TABLE `el_order` (
		  `id` int(5) NOT NULL auto_increment,
		  `uid` int(5) NOT NULL,
		  `crtime` int(11) NOT NULL,
		  `mtime` int(11) NOT NULL,
		  `state` enum('send','accept','deliver','complite','aborted') collate utf8_bin NOT NULL default 'send',
		  `amount` double(10,2) NOT NULL,
		  `delivery_price` double(6,2) NOT NULL,
		  `total` double(10,2) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `uid` (`uid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		if (!$db->query($sql))
		{
			$msgs[] = 'Could not create table el_order';
		}
	}
	
	if ( !$db->isTableExists('el_order_customer')  )
	{
		$sql = "CREATE TABLE `el_order_customer` (
		  `id` int(5) NOT NULL auto_increment,
		  `order_id` int(5) NOT NULL,
		  `uid` int(5) NOT NULL,
		  `label` varchar(256) collate utf8_bin NOT NULL,
		  `value` mediumtext collate utf8_bin,
		  PRIMARY KEY  (`id`),
		  KEY `order_id` (`order_id`),
		  KEY `uid` (`uid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		if (!$db->query($sql))
		{
			$msgs[] = 'Could not create table el_order_customer';
		}
	}
	
	if ( !$db->isTableExists('el_order_item')  )
	{
		$sql = "CREATE TABLE `el_order_item` (
		  `id` int(8) NOT NULL auto_increment,
		  `order_id` int(5) NOT NULL,
		  `uid` int(5) NOT NULL,
		  `shop` enum('IShop','TechShop') collate utf8_bin NOT NULL default 'IShop',
		  `i_id` int(5) NOT NULL,
		  `m_id` int(5) NOT NULL,
		  `code` varchar(256) collate utf8_bin NOT NULL,
		  `name` varchar(256) collate utf8_bin NOT NULL,
		  `qnt` int(5) NOT NULL default '1',
		  `price` double(8,2) NOT NULL,
		  `props` text collate utf8_bin,
		  `crtime` int(11) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `order_id` (`order_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		if (!$db->query($sql))
		{
			$msgs[] = 'Could not create table el_order_item';
		}
	}
	
	$sql = 'UPDATE el_module SET module="GAStat" WHERE module="Stat"';
	$db->query($sql);
	
	if ( !$db->isTableExists('el_plugin_calc')  )
	{
		$sql = 'REPLACE INTO el_plugin (name, lable, descrip, is_on, status) VALUES ("Calculator", "", "", "0", "off")';
		$db->query($sql);
		
		$sql = "CREATE TABLE `el_plugin_calc` (
		  `id` tinyint(2) NOT NULL auto_increment,
		  `name` varchar(255) collate utf8_bin NOT NULL,
		  `pos` enum('l','r','t','b') collate utf8_bin default 'l',
		  `tpl` varchar(250) collate utf8_bin NOT NULL,
		  `formula` mediumtext collate utf8_bin,
		  `unit` varchar(20) collate utf8_bin default NULL,
		  `dtype` enum('int','double') collate utf8_bin NOT NULL default 'int',
		  `view` enum('inline','dialog') collate utf8_bin NOT NULL default 'inline',
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		$db->query($sql);
		
		$sql = "CREATE TABLE `el_plugin_calc2page` (
		  `id` tinyint(2) NOT NULL,
		  `page_id` int(3) NOT NULL,
		  PRIMARY KEY  (`id`,`page_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		$db->query($sql);
		
		$sql = "CREATE TABLE `el_plugin_calc_var` (
		  `id` int(3) NOT NULL auto_increment,
		  `cid` tinyint(3) NOT NULL,
		  `name` varchar(255) collate utf8_bin NOT NULL,
		  `title` varchar(255) collate utf8_bin NOT NULL,
		  `type` enum('input','select') collate utf8_bin NOT NULL default 'input',
		  `dtype` enum('int','double') collate utf8_bin NOT NULL default 'int',
		  `variants` mediumtext collate utf8_bin,
		  `minval` varchar(24) collate utf8_bin NOT NULL,
		  `maxval` varchar(24) collate utf8_bin NOT NULL,
		  `unit` varchar(20) collate utf8_bin default NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `cid_2` (`cid`,`name`),
		  KEY `cid` (`cid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		$db->query($sql);
	}

	return true;
}


function elPsUpdate_3_8_1()
{
	global $msgs;
	$htaccess = file_get_contents('./.htaccess');
	if ( !preg_match('/ErrorDocument 404(.*)/i', $htaccess))
	{
		preg_match('/RewriteBase(.*)/i', $htaccess, $m);
		$rewriteBase = $m[1];
		$htaccess .= "\nErrorDocument 404 ".$rewriteBase."\n";
		if ( false == ($fp = fopen('./.htaccess', 'w')) )
		{
			$msgs[] = 'Could not replace ./.htaccess file! Do it manualy!';
		}
		else
		  {
		    fwrite($fp, $htaccess);
		    fclose($fp);
		    @chmod("./storage/.htaccess", 0444);
		  }
	}
	return elPSUpdate_3_9_0();
}

/**
 * Обновляет версию 3.8.0
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_8_0()
{
	global $msgs;
  $msgs[] = 'Update 3.8.0 version';
  if ( false == ($db = & elSingleton::getObj('elDb'))  )
  {
    $msgs[] = 'Could not get DB object';
    return false;
  }
  if ( false == ($conf = & elSingleton::getObj('elXmlConf'))  )
  {
    $msgs[] = 'Could not get elXmlConf object';
    return false;
  }
  
  if ( false != ($pages = $conf->findGroup('module', 'MailFormator', true)) )
  {
    foreach ( $pages as $pageID )
    {
      $tb = 'el_mail_form_'.$pageID;
      
      if ( $db->isTableExists($tb)  )
      {
        $db->query("ALTER TABLE  `".$tb."` CHANGE  `ftype`  `ftype` ENUM(  'comment',  'subtitle',  'text',  'textarea',  'select',  'checkbox',  'radio',  'date',  'file', 'captcha' ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT  'comment'");
        if ( !isFieldExists($db, $tb, 'fsize') )
        {
          if ( !$db->query("ALTER TABLE ".$tb." ADD  `fsize` TINYINT( 3 ) NOT NULL DEFAULT  '1'") )
          {
            $msgs[] = 'Could not add field `fsize` to table '.$tb;
          }  
        }
      }
    }
    $msgs[] = 'Module MailFormator was updated';
  }
  
  if ( false != ($pages = $conf->findGroup('module', 'VacancyCatalog', true)) )
  {
    foreach ( $pages as $pageID )
    {
      $tb = 'el_vaccat_'.$pageID.'_form';
      
      if ( $db->isTableExists($tb)  )
      {
        $db->query("ALTER TABLE  `".$tb."` CHANGE  `ftype`  `ftype` ENUM(  'comment',  'subtitle',  'text',  'textarea',  'select',  'checkbox',  'radio',  'date',  'file', 'captcha' ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT  'comment'");
        if ( !isFieldExists($db, $tb, 'fsize') )
        {
          if ( !$db->query("ALTER TABLE ".$tb." ADD  `fsize` TINYINT( 3 ) NOT NULL DEFAULT  '1'") )
          {
            $msgs[] = 'Could not add field `fsize` to table '.$tb;
          }  
        }
      }
    }
    $msgs[] = 'Module VacancyCatalog was updated';
  }
  
  if ( false != ($pages = $conf->findGroup('module', 'Mailer', true)) )
  {
    foreach ( $pages as $pageID )
    {
      $conf->set('spamProtect', '1', $pageID);
    }
    $conf->save();
    $msgs[] = 'Module Mailer was updated';
  }
  
  return elPsUpdate_3_8_1();
}


/**
 * Обновляет версию 3.7.7
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_7_7()
{
  global $msgs;
  $msgs[] = 'Update 3.7.7 version';
  if ( false == ($db = & elSingleton::getObj('elDb'))  )
  {
    $msgs[] = 'Could not get DB object';
    return false;
  }
  // новая табл метатегов
  if ( !$db->isTableExists('el_metatag') )
  {
    $sql = "CREATE TABLE IF NOT EXISTS `el_metatag` (
            `page_id` int(3) NOT NULL,
            `c_id` int(3) NOT NULL default 0,
            `i_id` int(3) NOT NULL default 0,
            `name` varchar(100) collate utf8_bin NOT NULL default 'DESCRIPTION',
            `content` mediumtext collate utf8_bin,
            PRIMARY KEY  (`page_id`,`c_id`,`i_id`,`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
    if ( !$db->query($sql) )
    {
      $msgs[] = 'Table "el_metatag" could not created';
      return false;
    }
    $meta = $db->queryToArray('SELECT page_id, name, content FROM el_meta');
    foreach ($meta as $one)
    {
      $sql = 'INSERT INTO el_metatag (page_id, c_id, i_id, name, content) '
            .'VALUES ('.intval($one['page_id']).', 0, 0, \''.mysql_real_escape_string($one['name']).'\', \''.mysql_real_escape_string($one['content']).'\')';
      $db->query($sql);
    }
    $db->query('DROP TABLE IF EXISTS el_meta');
    $msgs[] = 'Table el_metatag was created and updated';
    copy('./core/styles/default/modules/NavigationControl/adminMenuAdd.html',  './style/modules/NavigationControl/adminMenuAdd.html');
    copy('./core/styles/default/modules/NavigationControl/menuAdd.html',       './style/modules/NavigationControl/menuAdd.html');
    copy('./core/styles/default/modules/NavigationControl/adminMetaList.html', './style/modules/NavigationControl/adminMetaList.html');
    copy('./core/styles/default/modules/NavigationControl/adminDefault.html', './style/modules/NavigationControl/adminDefault.html');
    
    copy('./core/styles/default/css/moduleNavigationControl.css',   './style/css/moduleNavigationControl.css');
    copy('./core/styles/default/icons/mini/expand.gif',   './style/images/expand.gif');
    copy('./core/styles/default/icons/mini/collapse.gif', './style/images/collapse.gif');
    copy('./core/styles/default/images/meta.gif',        './style/images/meta.gif');
    copy('./core/styles/default/images/dummy.gif',       './style/images/dummy.gif');    
    
  }
  
  // новые доп меню
  if ( !$db->isTableExists('el_amenu'))
  {
    $sql = "CREATE TABLE IF NOT EXISTS `el_amenu` (
          `id` tinyint(3) NOT NULL auto_increment,
          `name` varchar(255) collate utf8_bin NOT NULL,
          `pos` enum('l','r') collate utf8_bin NOT NULL default 'l',
          PRIMARY KEY  (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ";
    if ( !$db->query($sql) )
    {
      $msgs[] = 'Table "el_amenu" could not created';
      return false;
    }
    $msgs[] = 'Table el_amenu was created';
  }
  
  
  if ( !$db->isTableExists('el_amenu_dest'))
  {
    $sql = "CREATE TABLE IF NOT EXISTS `el_amenu_dest` (
          `m_id` tinyint(3) NOT NULL,
          `p_id` int(3) NOT NULL,
          `sort` int(3) NOT NULL,
          PRIMARY KEY  (`m_id`,`p_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
    if ( !$db->query($sql) )
    {
      $msgs[] = 'Table "el_amenu_dest" could not created';
      return false;
    }
    $msgs[] = 'Table el_amenu_dest was created';
  }
  
  if ( !$db->isTableExists('el_amenu_source'))
  {
    $sql = "CREATE TABLE IF NOT EXISTS `el_amenu_source` (
          `m_id` tinyint(3) NOT NULL,
          `p_id` int(3) NOT NULL,
          `sort` int(3) NOT NULL,
          PRIMARY KEY  (`m_id`,`p_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
    if ( !$db->query($sql) )
    {
      $msgs[] = 'Table "el_amenu_source" could not created';
      return false;
    }
    $msgs[] = 'Table el_amenu_source was created';
  }
  
  if ( false == ($conf = & elSingleton::getObj('elXmlConf'))  )
  {
    $msgs[] = 'Could not get elXmlConf object';
    return false;
  }
  
  // конструктор форм - поле "fsize"
  if ( false != ($pages = $conf->findGroup('module', 'MailFormator', true)) )
  {
    foreach ( $pages as $pageID )
    {
      $tb = 'el_mail_form_'.$pageID;
      
      if ( $db->isTableExists($tb)  )
      {
        $db->query("ALTER TABLE  `".$tb."` CHANGE  `ftype`  `ftype` ENUM(  'comment',  'subtitle',  'text',  'textarea',  'select',  'checkbox',  'radio',  'date',  'file' ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT  'comment'");
        if ( !isFieldExists($db, $tb, 'fsize') )
        {
          if ( !$db->query("ALTER TABLE ".$tb." ADD  `fsize` TINYINT( 3 ) NOT NULL DEFAULT  '1'") )
          {
            $msgs[] = 'Could not add field `fsize` to table '.$tb;
          }  
        }
      }
    }
    $msgs[] = 'Module MailFormator was updated';
  }
  
  // поле is_split в каталоге техтоваров
  $pages = $db->queryToArray('SELECT id FROM el_menu WHERE module=\'TechShop\'', 'id', 'id');
  //if ( false != ($pages = $conf->findGroup('module', 'TechShop', true)) )
  if ( !empty($pages) )
  {
    foreach ($pages as $pageID)
    {
      $tb1   = 'el_techshop_'.$pageID.'_ft2i';
      $tb2   = 'el_techshop_'.$pageID.'_ft2m';
      $tbi2c = 'el_techshop_'.$pageID.'_i2c';
      $tbi   = 'el_techshop_'.$pageID.'_item';
      $tbm   = 'el_techshop_'.$pageID.'_model';
      if ( $db->isTableExists($tb1) && !isFieldExists(&$db, $tb1, 'is_split')  )
      {
        $sql = "ALTER TABLE ".$tb1." ADD  `is_split` TINYINT( 3 ) NOT NULL DEFAULT  '0'";
        if ( !$db->query($sql) )
        {
          $msgs[] = 'Could not add field `is_split` to table '.$tb1;
        }
      }
      if ( $db->isTableExists($tb2) && !isFieldExists(&$db, $tb2, 'is_split') )
      {
        $sql = "ALTER TABLE ".$tb2." ADD  `is_split` TINYINT( 3 ) NOT NULL DEFAULT  '0'";
        if ( !$db->query($sql) )
        {
          $msgs[] = 'Could not add field `is_split` to table '.$tb2;
        }
      }
      if ( $db->isTableExists($tbm) && isFieldExists(&$db, $tbm, 'app_title') )
      {
        $sql  = 'SELECT i.id, c.c_id, i.name, m.price FROM '.$tbm.' AS m, '.$tbi.' AS i, '.$tbi2c.' AS c  WHERE m.app_title="1" AND i.id=m.i_id AND c.i_id=i.id ORDER BY m.price';
        $data = $db->queryToArray($sql);
        $siteName = $conf->get('siteName', 'common');
        foreach ($data as $one)
        {
          $cont = mysql_real_escape_string($siteName.' / '.$one['name'].' / Розничная цена '.intval($one['price']).' руб.');
          $sql = 'REPLACE INTO el_metatag SET page_id='.intval($pageID).', c_id='.intval($one['c_id']).', i_id='.intval($one['id']).', name=\'title\', content=\''.$cont.'\'';
          $db->query($sql);
        }  
      }
      
    }
    $msgs[] = 'Module TechShop was updated';
  }
  

  $htaccess = file_get_contents('./.htaccess');
  if ( preg_match('/RewriteBase(.*)/i', $htaccess, $m))
  {
    $rewriteBase = $m[1];
  }
  $htaccess  = "RewriteEngine On\n";
  $htaccess .= "RewriteBase ".$rewriteBase."\n";
  $htaccess .= "RewriteRule ^conf\/(.*)\.xml index.php [NS,F]\n";
  $htaccess .= "RewriteRule ^(index\.php|counter\.php|robots\.txt)(.*) $1$2 [L]\n";
  $htaccess .= "RewriteRule ^(core\/editor\/editor\/dialog\/fck_spellerpages\/spellerpages\/server-scripts\/spellchecker.php) $1 [L]\n";
  $htaccess .= "RewriteRule (.*)\.(php|phtml) index.php [NC,F]\n";
  $htaccess .= "RewriteRule ^storage(.*)  storage$1 [L]\n";
  $htaccess .= "RewriteRule ^(.*)\.(jpg|gif|png|swf|ico|html|css|js|xml|gz)(.*) $1.$2$3 [L]\n";
  $htaccess .= "RewriteRule (.*) index.php [L]\n";
  if ( false == ($fp = fopen('./.htaccess', 'w')) )
  {
    $msgs[] = 'Could not replace ./.htaccess file! Do it manualy!';
  }
  else
  {
    fwrite($fp, $htaccess);
    fclose($fp);
    $msgs[] = '.htaccess file was updated';
  }
  if ( false == ($fp = fopen('./storage/.htaccess', 'w')) )
  {
    $msgs[] = 'Could not replace ./storage/.htaccess file! Do it manualy!';
  }
  else
  {
    fwrite($fp, "\n");
    fclose($fp);
    @chmod("./storage/.htaccess", 0444);
  }
  if ( @unlink('./core/.htaccess') )
  {
    $msgs[] = 'Could not remove ./core/.htaccess file! Do it manualy!';
  }
  if ( @unlink('./style/.htaccess') )
  {
    $msgs[] = 'Could not remove ./style/.htaccess file! Do it manualy!';
  }
  if ( @unlink('./backup/.htaccess') )
  {
    $msgs[] = 'Could not remove ./backup/.htaccess file! Do it manualy!';
  }
  
  // новые админские иконки 
  $icons = glob('./core/styles/default/icons/*.gif');
  foreach ($icons as $i)
  {
    if (basename($i) != 'arrow.gif')
    {
      copy($i, './style/icons/'.basename($i));  
    }
  }
  $icons = glob('./core/styles/default/icons/mimetypes/*.gif');
  foreach ($icons as $i)
  {
    copy($i, './style/icons/mimetypes/'.basename($i));
  }
  $icons = glob('./core/styles/default/icons/mini/*.gif');
  foreach ($icons as $i)
  {
    if ('print.gif'!=basename($i) && 'find.gif' != basename($i) )
    {
      copy($i, './style/icons/mimetypes/'.basename($i));  
    }
  }
  copy('./core/styles/default/icons/icons.conf.php', './style/icons/icons.conf.php');
  $msgs[] = 'New icons for admin mode was loaded';
  
  // иконки для контр центра
  copy('./core/styles/default/pageIcons/backup.gif',     './storage/pageIcons/backup.gif');
  copy('./core/styles/default/pageIcons/files.gif',      './storage/pageIcons/files.gif');
  copy('./core/styles/default/pageIcons/map.gif',        './storage/pageIcons/map.gif');
  copy('./core/styles/default/pageIcons/modules.gif',    './storage/pageIcons/modules.gif');
  copy('./core/styles/default/pageIcons/nav.gif',        './storage/pageIcons/nav.gif');
  copy('./core/styles/default/pageIcons/options.gif',    './storage/pageIcons/options.gif');
  copy('./core/styles/default/pageIcons/tpl-editor.gif', './storage/pageIcons/tpl-editor.gif');
  copy('./core/styles/default/pageIcons/updates.gif',    './storage/pageIcons/updates.gif');
  copy('./core/styles/default/pageIcons/users.gif',      './storage/pageIcons/users.gif');
  
  $db->query('UPDATE el_menu SET ico_main=\'backup.gif\'     WHERE module=\'SiteBackup\'');
  $db->query('UPDATE el_menu SET ico_main=\'files.gif\'      WHERE module=\'FileManager\'');
  $db->query('UPDATE el_menu SET ico_main=\'map.gif\'        WHERE module=\'SitemapGenerator\'');
  $db->query('UPDATE el_menu SET ico_main=\'modules.gif\'    WHERE module=\'PluginsControl\'');
  $db->query('UPDATE el_menu SET ico_main=\'nav.gif\'        WHERE module=\'NavigationControl\'');
  $db->query('UPDATE el_menu SET ico_main=\'options.gif\'    WHERE module=\'SiteControl\'');
  $db->query('UPDATE el_menu SET ico_main=\'tpl-editor.gif\' WHERE module=\'TemplatesEditor\'');
  $db->query('UPDATE el_menu SET ico_main=\'updates.gif\'    WHERE module=\'UpdateClient\'');
  $db->query('UPDATE el_menu SET ico_main=\'users.gif\'      WHERE module=\'UsersControl\'');
  // меняем режим показа кц
  $db->query('SELECT id FROM el_menu WHERE dir=\'cc\' AND module=\'Container\'');
  if ( $db->numRows() )
  {
    $r = $db->nextRecord();
    $conf->set('goFirstChild', 0, $r['id']);
    $conf->set('deep',         1, $r['id']);
    $conf->set('showIcons',    1, $r['id']);
    $conf->save();
  }
  // новые шаблоны для контейнера
  copy('./core/styles/default/css/moduleContainer.css',        './style/css/moduleContainer.css');
  copy('./core/styles/default/modules/Container/default.html', './style/modules/Container/default.html');
  
  // fix file archive template errors
  copy('./core/styles/default/modules/FileArchive/default.html',      './style/modules/FileArchive/default.html');
  copy('./core/styles/default/modules/FileArchive/adminDefault.html', './style/modules/FileArchive/adminDefault.html');
  $msgs[] = 'FileArchive templates was fixed';
  
  // обновления "управление навигацией" и генератора xml карты сайта
  copy('./core/styles/default/modules/NavigationControl/modules.html', './style/modules/NavigationControl/modules.html');
  if ( is_dir('./style/modules/SitemapGenerator') )
  {
    copy('./core/styles/default/modules/SitemapGenerator/default.html', './style/modules/SitemapGenerator/default.html');  
  }
  
  copy('./core/styles/default/modules/SimplePage/default.html', './style/modules/SimplePage/default.html');
  
  $cont = file_get_contents('./style/menus/addMenuTop.html');
  if ( strstr($cont, '{ico_add_menu_top}'))
  {
    if ( false != ($fp = fopen('./style/menus/addMenuTop.html', 'w')) )
    {
      fwrite($fp, str_replace('{ico_add_menu_top}', '{ico}', $cont));
      fclose($fp);
    }
  }
  $cont = file_get_contents('./style/menus/addMenuBottom.html');
  if ( strstr($cont, '{ico_add_menu_bottom}'))
  {
    if ( false != ($fp = fopen('./style/menus/addMenuBottom.html', 'w')) )
    {
      fwrite($fp, str_replace('{ico_add_menu_bottom}', '{ico}', $cont));
      fclose($fp);
    }
  }
  
  $iCartState = $conf->get('iCartDisplayEmpty', 'layout');
  if ( empty($iCartState) && $iCartState != 0 )
  {
    $conf->set('iCartDisplayEmpty', '1', 'layout');
    $conf->save();
  }
  
  // удалить старый EShop если он дожил до этого обновления
  $db->query('UPDATE el_menu SET module="Container" WHERE module="EShop"');
  $db->query('DELETE FROM el_module WHERE module="EShop"');
  $db->optimizeTable('el_module');
  
  if (is_dir('./core/modules/EShop'))
  {
    exec('rm -rf ./core/modules/EShop');
  }
  if ( is_dir('./style/modules/EShop') )
  {
    exec('rm- rf ./style/modules/EShop');
  }
  if (is_file('./core/locale/ru_RU.UTF-8/elModuleEShop.php'))
  {
    @unlink('./core/locale/ru_RU.UTF-8/elModuleEShop.php');
  }
  if (is_file('./core/locale/ru_RU.UTF-8/elModuleAdminEShop.php'))
  {
    @unlink('./core/locale/ru_RU.UTF-8/elModuleAdminEShop.php');
  }
  if ( is_file('./style/css/moduleEShop.css') )
  {
    @unlink('./style/css/moduleEShop.css');
  }
  
  return elPsUpdate_3_8_0();;
}

/**
 * Обновляет версию 3.7.6
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_7_6()
{
  global $msgs;
  if ( false == ($db = & elSingleton::getObj('elDb'))  )
  {
    $msgs[] = 'Could not get DB object';
    return true;
  }
  $db->query('UPDATE el_menu SET module="Container" WHERE module="EShop"');
  $db->query('DELETE FROM el_module WHERE module="EShop"');
  $db->optimizeTable('el_module');
  
  if (is_dir('./core/modules/EShop'))
  {
    exec('rm -rf ./core/modules/EShop');
  }
  if ( is_dir('./style/modules/EShop') )
  {
    exec('rm- rf ./style/modules/EShop');
  }
  if (is_file('./core/locale/ru_RU.UTF-8/elModuleEShop.php'))
  {
    @unlink('./core/locale/ru_RU.UTF-8/elModuleEShop.php');
  }
  if (is_file('./core/locale/ru_RU.UTF-8/elModuleAdminEShop.php'))
  {
    @unlink('./core/locale/ru_RU.UTF-8/elModuleAdminEShop.php');
  }
  if ( is_file('./style/css/moduleEShop.css') )
  {
    @unlink('./style/css/moduleEShop.css');
  }
  if ( !file_exists('./style/modules/IShop') )
  {
    mkdir('./style/modules/IShop'); 
    copy('./core/styles/default/modules/IShop/adminDefault.html',   './style/modules/IShop/adminDefault.html');
    copy('./core/styles/default/modules/IShop/adminItem.html',      './style/modules/IShop/adminItem.html');
    copy('./core/styles/default/modules/IShop/adminMnfs.html',      './style/modules/IShop/adminMnfs.html');
    copy('./core/styles/default/modules/IShop/adminSearchConf.html', './style/modules/IShop/adminSearchConf.html');
    copy('./core/styles/default/modules/IShop/adminDefault.html',   './style/modules/IShop/adminDefault.html');
    copy('./core/styles/default/modules/IShop/adminTypes.html',     './style/modules/IShop/adminTypes.html');
    copy('./core/styles/default/modules/IShop/default.html',        './style/modules/IShop/default.html');
    copy('./core/styles/default/modules/IShop/item.html',           './style/modules/IShop/item.html');
    copy('./core/styles/default/modules/IShop/ItemImg.html',        './style/modules/IShop/ItemImg.html');
    copy('./core/styles/default/modules/IShop/searchForm.html',     './style/modules/IShop/searchForm.html');
    copy('./core/styles/default/modules/IShop/searchGridForm.html', './style/modules/IShop/searchGridForm.html');
    copy('./core/styles/default/css/moduleIShop.css',               './style/css/moduleIShop.css');
    $msgs[] = 'Copy module templates';
  }
  if ( !file_exists('./style/common/ICart') )
  {
    mkdir('./style/common/ICart');
    copy('./core/styles/default/css/iCart.css',                   './style/css/iCart.css');
    copy('./core/styles/default/common/ICart/default.html',       './style/common/ICart/default.html');
    copy('./core/styles/default/common/ICart/delivery.html',      './style/common/ICart/delivery.html');
    copy('./core/styles/default/common/ICart/icart.html',         './style/common/ICart/icart.html');
    copy('./core/styles/default/common/ICart/iCartInfoLeft.html', './style/common/ICart/iCartInfoLeft.html');
    copy('./core/styles/default/common/ICart/summary.html',       './style/common/ICart/summary.html');
    $msgs[] = 'Copy shopping cart  templates';
  }
  if ( !$db->isTableExists('el_icart') )
  {
      $sql = "
      CREATE TABLE IF NOT EXISTS `el_icart` (
      id     int(8) NOT NULL auto_increment,
      sid    varchar(32) NOT NULL,
      uid    int(5) NOT NULL,
      shop   enum('IShop', 'TechShop') NOT NULL default 'IShop',
      i_id   int(5) NOT NULL,
      code   varchar(256) NOT NULL,
      name   varchar(256) NOT NULL,
      qnt    int(5) NOT NULL default 1,
      price  double(8,2) NOT NULL,
      props  text,
      crtime int(11) NOT NULL,
      mtime  int(11) NOT NULL,
      PRIMARY KEY(id)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin";
      $db->query($sql);
  }
  if ( false != ($conf = & elSingleton::getObj('elXmlConf'))  )
  {
    if (false != ($EShopGroups = $conf->findGroup('module', 'EShop', true)) )
    {
      foreach ($EShopGroups as $gID)
      {
        $conf->set('module', 'Container', $gID);
      }
    }
    $conf->dropGroup('eshopOrder');
    $conf->save();
  }
  return elPsUpdate_3_7_7();
}

/**
 * Обновляет версию 3.7.4
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_7_4()
{
  global $msgs;
  if ( file_exists('./core/modules/UpdateServer/elModuleUpdateServer.class.php') )
  {
    copy('./core/styles/default/modules/UpdateServer/log.html', './style/modules/UpdateServer/log.html');
  }
  return elPsUpdate_3_7_6();
}

/**
 * Обновляет версию 3.7.3
 * В таблицу техкаталога  (el_techshop_'.$ID.'_ft2m) добавляет поле is_split
 * обновляет некоторые шаблоны техкаталога
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_7_3()
{
  global $msgs;
  if ( false == ($db = & elSingleton::getObj('elDb'))  )
  {
    $msgs[] = 'Could not get DB object';
    return true;
  }
  if ( false == ($conf = & elSingleton::getObj('elXmlConf'))  )
  {
    $msgs[] = 'Could not get elXmlConf object';
    return true;
  }
  $pages = $conf->findGroup('module', 'TechShop', true );
  if (!empty($pages))
  {
    foreach ($pages as $ID)
    {
      $tb = 'el_techshop_'.$ID.'_ft2m';
      $fList = $db->queryToArray('SHOW COLUMNS FROM '.$tb, 'Field');
      if ( !empty($fList['is_split']) )
      {
        $msgs[] = 'DataBase modifications: do nothing';
      }
      $msg = 'DataBase modifications: Add field '.$tb.'.is_split';
      if ( !$db->query('ALTER TABLE `'.$tb.'` ADD `is_split` ENUM(\'0\', \'1\') NOT NULL DEFAULT 0') )
      {
        $msgs[] = $msg.'Failed';
        return false;
      }
      $msgs[] = $msg.'Success';
    }
  }
  $files = array(
    'TechShop/item.html',
    'TechShop/adminItem.html',
    'TechShop/default.html',
    'TechShop/adminDefault.html',
    'NavigationControl/adminDefault.html',
    'NavigationControl/default.html',
    'MrControl/domainsList.html'
    );
  foreach ($files as $file)
  {
    if ( !@copy('./core/styles/default/modules/'.$file, './style/modules/'.$file) )
    {
      $msgs[] = 'Could not copy '.$file;
    }
  }
  return elPsUpdate_3_7_4();
}

/**
 * Для версии 3.7.2
 * В таблицах el_mail_form_ меняем тип поля fvalue на text
 * В normal.css добавляем классы для связанных объектов в каталогах
 *
 * @param array $msgs
 * @return bool
 */
function elPsUpdate_3_7_2()
{
  global $msgs;
  if ( false == ($db = & elSingleton::getObj('elDb'))  )
  {
    $msgs[] = 'Could not get DB object';
    return true;
  }
  if ( false == ($conf = & elSingleton::getObj('elXmlConf'))  )
  {
    $msgs[] = 'Could not get elXmlConf object';
    return true;
  }
  $mailPages = $conf->findGroup('module', 'MailFormator', true );
  if (!empty($mailPages))
  {
    foreach ($mailPages as $ID)
    {
      $tb = 'el_mail_form_'.$ID;
      if ($db->isTableExists($tb))
      {
        if ($db->query('ALTER TABLE '.$tb.' CHANGE `fvalue` `fvalue` text NOT NULL'))
        {
          $msgs[] = 'Table '.$tb.' modified - Success';
        }
        else
        {
          $msgs[] = 'Table '.$tb.' modified - Failes';
        }
      }
    }
  }
  else
  {
    $msgs[] = 'No one page by module MailFormator was found. Do nothing';
  }

  $cssAdd = "
  table.dcLOTb      { width:100%; margin:5px 0; border:none;  }
  table.dcLOTb td   { padding:7px; background:#fafafa; border:1px solid #eaeaea; }
  table.dcLOTb td.dcLOGroup { font-weight:bold; background:#efefef; border:1px solid #bfbfbf ;}\n";
  $cssNotice = "Add the following lines to style/css/normal.css by hand:\n".$cssAdd;

  if (false == ($c = file_get_contents('./style/css/normal.css')))
  {
    $msgs[] = sprintf(m('Could read file %s'), './style/css/normal.css');
    $msgs[] = $cssNotice;
    return true;
  }
  if (!strstr($c, 'dcLOTb'))
  {
    $c .= $cssAdd;
    if ( false == ($fp = fopen('./style/css/normal.css', 'w')))
    {
      $msgs[] = sprintf(m('Could write to file %s'), './style/css/normal.css');
      $msgs[] = $cssNotice;
      return true;
    }
    fwrite($fp, $c);
    fclose($fp);
    $msgs[] = 'Added new css classes - Success';
  }
  return elPsUpdate_3_7_3();
}

/**
 * Для версии 3.7.1
 * В таблицу el_plugin_ib добавляет поле tpl
 *
 * @param array $msgs
 * @return bool
 */
function elPSUpdate_3_7_1()
{
  global $msgs;
  $db = & elSingleton::getObj('elDb');
  if ( !$db )
  {
    $msgs[] = 'Could not get DB object';
    return true;
  }
  $fList = $db->queryToArray('SHOW COLUMNS FROM el_plugin_ib', 'Field');
  if ( !empty($fList['tpl']) )
  {
    $msgs[] = 'DataBase modifications: do nothing';
    return true;
  }
  $msg = 'DataBase modifications: Add field el_plugin_ib.tpl ';
  if ( !$db->query('ALTER TABLE `el_plugin_ib` ADD `tpl` VARCHAR( 250 ) NOT NULL ') )
  {
    $msgs[] = $msg.'Failed';
    return false;
  }
  $msgs[] = $msg.'Success';
  return elPsUpdate_3_7_2();
}

// версии до 3.8 не имеют метода db->isFieldExists()
function isFieldExists(&$db, $tb, $f)
{
  if ( method_exists($db, 'isFieldExists') )
  {
    return $db->isFieldExists($tb, $f);
  }
  else
  {
    $db->query('SHOW COLUMNS FROM '.$tb);
    while ( $r = $db->nextRecord() )
    {
      if ($r['Field'] == $f)
      {
        return true;
      }
    }
  }
}


?>