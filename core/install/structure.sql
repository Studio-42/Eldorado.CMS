SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `el_action_log`;
CREATE TABLE IF NOT EXISTS `el_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `object` varchar(256) COLLATE utf8_bin NOT NULL,
  `action` varchar(256) COLLATE utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `link` varchar(256) COLLATE utf8_bin NOT NULL,
  `value` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_amenu`;
CREATE TABLE IF NOT EXISTS `el_amenu` (
  `id` tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `pos` enum('l','r') COLLATE utf8_bin NOT NULL DEFAULT 'l',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_amenu_dest`;
CREATE TABLE IF NOT EXISTS `el_amenu_dest` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_amenu_source`;
CREATE TABLE IF NOT EXISTS `el_amenu_source` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_catalogs_crosslink`;
CREATE TABLE IF NOT EXISTS `el_catalogs_crosslink` (
  `id`     int(5) NOT NULL AUTO_INCREMENT,
  `mpid`   int(3) NOT NULL DEFAULT '0',
  `mid`    int(3) NOT NULL DEFAULT '0',
  `spid`   int(3) NOT NULL DEFAULT '0',
  `scatid` int(3) NOT NULL DEFAULT '0',
  `sid`    int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `mpid` (`mpid`,`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_directories_list`;
CREATE TABLE IF NOT EXISTS `el_directories_list` (
  `id`         varchar(256) COLLATE utf8_bin NOT NULL,
  `label`      varchar(256) COLLATE utf8_bin NOT NULL,
  `master_id`  varchar(256) COLLATE utf8_bin NOT NULL,
  `master_key` int(11) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
	KEY(`master_id`, `master_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_email`;
CREATE TABLE IF NOT EXISTS `el_email` (
  `id`         tinyint(2) NOT NULL AUTO_INCREMENT,
  `label`      varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email`      varchar(128) COLLATE utf8_bin NOT NULL DEFAULT '',
  `is_default` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_form`;
CREATE TABLE IF NOT EXISTS `el_form` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `form_id` varchar(256) COLLATE utf8_bin NOT NULL,
  `label` varchar(256) COLLATE utf8_bin NOT NULL,
  `type` enum('comment','title','text','textarea','select','checkbox','date','file','captcha','directory') COLLATE utf8_bin NOT NULL DEFAULT 'comment',
  `value` mediumtext COLLATE utf8_bin NOT NULL,
  `opts` mediumtext COLLATE utf8_bin NOT NULL,
  `directory` varchar(256) COLLATE utf8_bin NOT NULL,
  `required` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `rule` enum('','noempty','email','url','phone','numbers','letters_or_space') COLLATE utf8_bin NOT NULL DEFAULT '',
  `file_size` int(3) NOT NULL DEFAULT '1',
  `file_type` varchar(256) collate utf8_bin NOT NULL,
  `error` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `sort_ndx` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_group`;
CREATE TABLE IF NOT EXISTS `el_group` (
  `gid`   tinyint(2) NOT NULL AUTO_INCREMENT,
  `name`  char(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `perm`  tinyint(1) NOT NULL DEFAULT '0',
  `mtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_group_acl`;
CREATE TABLE IF NOT EXISTS `el_group_acl` (
  `group_id` tinyint(2) NOT NULL DEFAULT '0',
  `page_id`  int(3) NOT NULL DEFAULT '0',
  `perm`     enum('1','3','7') COLLATE utf8_bin NOT NULL DEFAULT '1',
  PRIMARY KEY (`page_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_icart`;
CREATE TABLE IF NOT EXISTS `el_icart` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `sid` varchar(64) COLLATE utf8_bin NOT NULL,
  `uid` int(5) NOT NULL,
  `page_id` int(5) NOT NULL,
  `i_id` int(5) NOT NULL,
  `m_id` int(5) NOT NULL,
  `code` varchar(256) COLLATE utf8_bin NOT NULL,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `qnt` int(5) NOT NULL DEFAULT '1',
  `price` double(12,2) NOT NULL,
  `props` mediumtext COLLATE utf8_bin,
  `crtime` int(11) NOT NULL,
  `mtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_icart_conf`;
CREATE TABLE IF NOT EXISTS `el_icart_conf` (
  `region_id` int(5) NOT NULL,
  `delivery_id` int(5) NOT NULL,
  `payment_id` int(5) NOT NULL,
  `fee` double(10,2) DEFAULT NULL,
  `formula` mediumtext COLLATE utf8_bin,
  `comment` mediumtext COLLATE utf8_bin,
  `online_payment` enum('0', '1') NOT NULL default '0',
  PRIMARY KEY (`region_id`,`delivery_id`,`payment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_menu`;
CREATE TABLE IF NOT EXISTS `el_menu` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `name_alt` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `page_descrip` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `dir` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `_left` int(3) NOT NULL DEFAULT '0',
  `_right` int(3) NOT NULL DEFAULT '0',
  `level` int(2) NOT NULL DEFAULT '0',
  `module` varchar(30) COLLATE utf8_bin NOT NULL DEFAULT 'Container',
  `visible` enum('2','1','0') COLLATE utf8_bin NOT NULL DEFAULT '2',
  `visible_limit` enum('0','1','2') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `perm` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '1',
  `is_menu` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `redirect_url` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ico_main` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ico_add_menu_top` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ico_add_menu_bot` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `in_add_menu_top` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `in_add_menu_bot` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `alt_tpl` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `_left` (`_left`),
  KEY `module` (`module`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_metatag`;
CREATE TABLE IF NOT EXISTS `el_metatag` (
  `page_id` int(3) NOT NULL,
  `c_id` int(3) NOT NULL,
  `i_id` int(3) NOT NULL,
  `name` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT 'DESCRIPTION',
  `content` mediumtext COLLATE utf8_bin,
  PRIMARY KEY (`page_id`,`c_id`,`i_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_module`;
CREATE TABLE IF NOT EXISTS `el_module` (
  `module` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `descrip` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `multi` enum('1','0') COLLATE utf8_bin NOT NULL DEFAULT '1',
  `search` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  PRIMARY KEY (`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_order`;
CREATE TABLE IF NOT EXISTS `el_order` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `uid` int(5) NOT NULL,
  `crtime` int(11) NOT NULL,
  `mtime` int(11) NOT NULL,
  `state` enum('send','accept','deliver','complete','aborted') COLLATE utf8_bin NOT NULL DEFAULT 'send',
  `amount` double(10,2) NOT NULL,
  `discount` double(6,2) NOT NULL,
  `delivery_price` double(6,2) NOT NULL,
  `total` double(12,2) NOT NULL,
  `region` varchar(256) NOT NULL,
  `delivery` varchar(256) NOT NULL,
  `payment` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_order_customer`;
CREATE TABLE IF NOT EXISTS `el_order_customer` (
  `id`       int(5) NOT NULL AUTO_INCREMENT,
  `order_id` int(5) NOT NULL,
  `uid`      int(5) NOT NULL,
  `field_id` varchar(256) COLLATE utf8_bin NOT NULL,
  `label`    varchar(256) COLLATE utf8_bin NOT NULL,
  `value`    mediumtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_order_item`;
CREATE TABLE IF NOT EXISTS `el_order_item` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `order_id` int(5) NOT NULL,
  `uid` int(5) NOT NULL,
  `page_id` int(5) NOT NULL,
  `i_id` int(5) NOT NULL,
  `m_id` int(5) NOT NULL,
  `code` varchar(256) COLLATE utf8_bin NOT NULL,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `qnt` int(5) NOT NULL DEFAULT '1',
  `price` double(12,2) NOT NULL,
  `props` text COLLATE utf8_bin,
  `crtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_page`;
CREATE TABLE IF NOT EXISTS `el_page` (
  `id`      int(3) NOT NULL DEFAULT '0',
  `content` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin`;
CREATE TABLE IF NOT EXISTS `el_plugin` (
  `name`    varchar(64) COLLATE utf8_bin NOT NULL,
  `label`   varchar(128) COLLATE utf8_bin NOT NULL,
  `descrip` varchar(256) COLLATE utf8_bin NOT NULL,
  `is_on`   enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '1',
  `status`  enum('disable','off','on') COLLATE utf8_bin NOT NULL DEFAULT 'off',
  PRIMARY KEY (`name`),
  KEY `is_on` (`is_on`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin_calc`;
CREATE TABLE IF NOT EXISTS `el_plugin_calc` (
  `id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `pos` enum('l','r','t','b') COLLATE utf8_bin DEFAULT 'l',
  `tpl` varchar(256) COLLATE utf8_bin NOT NULL,
  `formula` mediumtext COLLATE utf8_bin,
  `unit` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `dtype` enum('int','double') COLLATE utf8_bin NOT NULL DEFAULT 'int',
  `view` enum('inline','dialog') COLLATE utf8_bin NOT NULL DEFAULT 'inline',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin_calc2page`;
CREATE TABLE IF NOT EXISTS `el_plugin_calc2page` (
  `id` tinyint(2) NOT NULL,
  `page_id` int(3) NOT NULL,
  PRIMARY KEY (`id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin_calc_var`;
CREATE TABLE IF NOT EXISTS `el_plugin_calc_var` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `cid` tinyint(3) NOT NULL,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `title` varchar(256) COLLATE utf8_bin NOT NULL,
  `type` enum('input','select') COLLATE utf8_bin NOT NULL DEFAULT 'input',
  `dtype` enum('int','double') COLLATE utf8_bin NOT NULL DEFAULT 'int',
  `variants` mediumtext COLLATE utf8_bin,
  `minval` varchar(32) COLLATE utf8_bin NOT NULL,
  `maxval` varchar(32) COLLATE utf8_bin NOT NULL,
  `unit` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `sort_ndx` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cid_2` (`cid`,`name`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin_ib`;
CREATE TABLE IF NOT EXISTS `el_plugin_ib` (
  `id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `content` text COLLATE utf8_bin,
  `pos` enum('l','r','t','b') COLLATE utf8_bin DEFAULT 'l',
  `ts` int(11) NOT NULL,
  `tpl` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_plugin_ib2page`;
CREATE TABLE IF NOT EXISTS `el_plugin_ib2page` (
  `id` tinyint(2) NOT NULL,
  `page_id` int(3) NOT NULL,
  PRIMARY KEY (`id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_uplog`;
CREATE TABLE IF NOT EXISTS `el_uplog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `act` enum('Upgrade','Downgrade') COLLATE utf8_bin NOT NULL,
  `result` enum('Success','Failed') COLLATE utf8_bin NOT NULL DEFAULT 'Success',
  `version` varchar(32) COLLATE utf8_bin NOT NULL,
  `log` mediumtext COLLATE utf8_bin NOT NULL,
  `changelog` text COLLATE utf8_bin NOT NULL,
  `crtime` int(11) NOT NULL,
  `backup_file` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_user`;
CREATE TABLE IF NOT EXISTS `el_user` (
  `uid`    int(3)  NOT NULL AUTO_INCREMENT,
  `login`  varchar(32)  COLLATE utf8_bin NOT NULL DEFAULT '',
  `pass`   varchar(64)  COLLATE utf8_bin NOT NULL DEFAULT '',
  `f_name` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `l_name` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email`  varchar(128)  COLLATE utf8_bin NOT NULL DEFAULT '',
  `crtime` int(11) NOT NULL DEFAULT '0',
  `mtime`  int(11) NOT NULL DEFAULT '0',
  `atime`  int(11) NOT NULL DEFAULT '0',
  `visits` int(3)  NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `email` (`email`),
  KEY `login` (`login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_user_in_group`;
CREATE TABLE IF NOT EXISTS `el_user_in_group` (
  `user_id`  int(5) NOT NULL DEFAULT '0',
  `group_id` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_user_pref`;
CREATE TABLE IF NOT EXISTS `el_user_pref` (
  `user_id` int(3) NOT NULL DEFAULT '0',
  `name`    varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
  `val`     text COLLATE utf8_bin,
  `is_serialized` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_user_profile`;
CREATE TABLE IF NOT EXISTS `el_user_profile` (
  `id`        varchar(256) COLLATE utf8_bin NOT NULL,
  `label`     varchar(256) COLLATE utf8_bin NOT NULL,
  `type`      enum('text','textarea','select','checkbox','date','directory','slave-directory') COLLATE utf8_bin NOT NULL DEFAULT 'comment',
  `value`     mediumtext COLLATE utf8_bin NOT NULL,
  `opts`      mediumtext COLLATE utf8_bin NOT NULL,
  `directory` varchar(256) COLLATE utf8_bin NOT NULL,
  `required`  enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '0',
  `rule`      enum('','noempty','email','url','phone','numbers','letters_or_space') COLLATE utf8_bin NOT NULL DEFAULT '',
  `file_size` int(3) NOT NULL DEFAULT '1',
  `file_type` varchar(256) collate utf8_bin NOT NULL,
  `error`     varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
  `sort_ndx`  int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

