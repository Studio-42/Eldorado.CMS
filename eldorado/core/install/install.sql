DROP TABLE IF EXISTS `el_action_log`;
--
CREATE TABLE IF NOT EXISTS `el_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `object` varchar(63) COLLATE utf8_bin NOT NULL,
  `action` varchar(63) COLLATE utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `link` varchar(255) COLLATE utf8_bin NOT NULL,
  `value` varchar(63) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_amenu` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_amenu`;
--
CREATE TABLE `el_amenu` (
  `id` tinyint(3) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_bin NOT NULL,
  `pos` enum('l','r') collate utf8_bin NOT NULL default 'l',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_amenu` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_amenu_dest`;
--
CREATE TABLE `el_amenu_dest` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY  (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_amenu_dest` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_amenu_source`;
--
CREATE TABLE `el_amenu_source` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY  (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_amenu_source` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_catalogs_crosslink`;
--
CREATE TABLE `el_catalogs_crosslink` (
  `id` int(5) NOT NULL auto_increment,
  `mpid` int(3) NOT NULL default '0',
  `mid` int(3) NOT NULL default '0',
  `spid` int(3) NOT NULL default '0',
  `scatid` int(3) NOT NULL default '0',
  `sid` int(3) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `mpid` (`mpid`,`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_catalogs_crosslink` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_email`;
--
CREATE TABLE `el_email` (
  `id` tinyint(2) NOT NULL auto_increment,
  `label` varchar(50) collate utf8_bin NOT NULL default '',
  `email` varchar(75) collate utf8_bin NOT NULL default '',
  `is_default` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_email` WRITE;
--
INSERT INTO el_email (id, label, email, is_default) VALUES (1, "admin", "admin@yoursite.com", "1");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_group`;
--
CREATE TABLE `el_group` (
  `gid` tinyint(2) NOT NULL auto_increment,
  `name` char(30) collate utf8_bin NOT NULL default '',
  `perm` tinyint(1) NOT NULL default '0',
  `mtime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`gid`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_group` WRITE;
--
INSERT INTO el_group (gid, name, perm, mtime) VALUES (1, "root", 8, 1122463302), 
(2, "guests", 0, 1122464829);
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_group_acl`;
--
CREATE TABLE `el_group_acl` (
  `group_id` tinyint(2) NOT NULL default '0',
  `page_id` int(3) NOT NULL default '0',
  `perm` enum('1','3','7') collate utf8_bin NOT NULL default '1',
  PRIMARY KEY  (`page_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_group_acl` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_icart`;
--
CREATE TABLE `el_icart` (
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
  `price` double(12,2) NOT NULL,
  `props` text collate utf8_bin,
  `crtime` int(11) NOT NULL,
  `mtime` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_icart` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_menu`;
--
CREATE TABLE `el_menu` (
  `id` int(3) NOT NULL auto_increment,
  `name` varchar(100) collate utf8_bin NOT NULL default '',
  `name_alt` varchar(255) collate utf8_bin NOT NULL default '',
  `page_descrip` varchar(250) collate utf8_bin NOT NULL default '',
  `dir` varchar(30) collate utf8_bin NOT NULL default '',
  `_left` int(3) NOT NULL default '0',
  `_right` int(3) NOT NULL default '0',
  `level` int(2) NOT NULL default '0',
  `module` varchar(30) collate utf8_bin NOT NULL default 'Container',
  `visible` enum('2','1','0') collate utf8_bin NOT NULL default '2',
  `visible_limit` enum('0','1','2') collate utf8_bin NOT NULL default '0',
  `perm` enum('0','1') collate utf8_bin NOT NULL default '1',
  `is_menu` enum('0','1') collate utf8_bin NOT NULL default '0',
  `redirect_url` varchar(100) collate utf8_bin NOT NULL default '',
  `ico_main` varchar(50) collate utf8_bin NOT NULL default '',
  `ico_add_menu_top` varchar(50) collate utf8_bin NOT NULL default '',
  `ico_add_menu_bot` varchar(50) collate utf8_bin NOT NULL default '',
  `in_add_menu_top` enum('0','1') collate utf8_bin NOT NULL default '0',
  `in_add_menu_bot` enum('0','1') collate utf8_bin NOT NULL default '0',
  `alt_tpl` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `_left` (`_left`),
  KEY `module` (`module`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_menu` WRITE;
--
INSERT INTO el_menu (id, name, name_alt, page_descrip, dir, _left, _right, level, module, visible, visible_limit, perm, is_menu, redirect_url, ico_main, ico_add_menu_top, ico_add_menu_bot, in_add_menu_top, in_add_menu_bot, alt_tpl) VALUES (1, "--", "", "", "", 1, 26, 0, "Container", "2", "0", "1", "0", "", "", "", "", "0", "0", ""), 
(2, "Начало", "", "", "home", 2, 3, 1, "SimplePage", "2", "0", "1", "0", "", "home.png", "home.png", "home.png", "0", "0", ""), 
(3, "Обратная связь", "", "", "conf", 4, 5, 1, "Mailer", "2", "0", "1", "0", "", "mail.png", "mail.png", "mail.png", "0", "0", "0"), 
(5, "Контрольный центр", "", "", "cc", 6, 25, 1, "Container", "2", "0", "0", "0", "", "default.png", "default.png", "default.png", "0", "0", ""), 
(6, "Пользователи/группы", "", "", "users", 7, 8, 2, "UsersControl", "2", "0", "0", "0", "", "users.gif", "default.png", "default.png", "0", "0", "0"), 
(7, "Управление структурой", "", "", "menu", 9, 10, 2, "NavigationControl", "2", "0", "1", "0", "", "nav.gif", "default.png", "default.png", "0", "0", ""), 
(8, "Настройки сайта", "", "", "site_conf", 11, 12, 2, "SiteControl", "2", "0", "0", "0", "", "options.gif", "default.png", "default.png", "0", "0", ""), 
(9, "Файлы", "", "", "fm", 13, 14, 2, "Finder", "2", "0", "0", "0", "", "files.gif", "default.png", "default.png", "0", "0", ""), 
(10, "Доп. модули", "", "", "plc", 15, 16, 2, "PluginsControl", "2", "0", "0", "0", "", "modules.gif", "default.png", "default.png", "0", "0", ""), 
(24, "Резервные копии", "", "", "backup", 17, 18, 2, "SiteBackup", "2", "0", "0", "0", "", "backup.gif", "default.png", "default.png", "0", "0", ""), 
(25, "Карта XML", "", "", "sitemap", 19, 20, 2, "SitemapGenerator", "2", "0", "0", "0", "", "map.gif", "default.png", "default.png", "0", "0", ""), 
(26, "Обновление системы", "", "", "update", 21, 22, 2, "UpdateClient", "2", "0", "0", "0", "", "updates.gif", "default.png", "default.png", "0", "0", ""), 
(27, "Редактор шаблонов", "", "", "tpl", 23, 24, 2, "TemplatesEditor", "2", "0", "0", "0", "", "tpl-editor.gif", "default.png", "default.png", "0", "0", "");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_metatag`;
--
CREATE TABLE `el_metatag` (
  `page_id` int(3) NOT NULL,
  `c_id` int(3) NOT NULL,
  `i_id` int(3) NOT NULL,
  `name` varchar(100) collate utf8_bin NOT NULL default 'DESCRIPTION',
  `content` mediumtext collate utf8_bin,
  PRIMARY KEY  (`page_id`,`c_id`,`i_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_metatag` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_module`;
--
CREATE TABLE `el_module` (
  `module` varchar(50) collate utf8_bin NOT NULL default '',
  `descrip` varchar(200) collate utf8_bin NOT NULL default '',
  `multi` enum('1','0') collate utf8_bin NOT NULL default '1',
  `search` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_module` WRITE;
--
INSERT INTO el_module (module, descrip, multi, search) VALUES ("Container", "Контейнер", "1", "0"), 
("DocsCatalog", "Каталог документов", "1", "1"), 
("EventSchedule", "Расписание событий", "1", "0"), 
("FAQ", "Часто Задаваемые Вопросы", "1", "0"), 
("FileArchive", "Файловый архив", "1", "1"), 
("Finder", "Файловый менеджер", "1", "0"), 
("Forum", "Форум", "0", "1"), 
("GAStat", "Статистика", "0", "0"), 
("Glossary", "Словарь терминов", "1", "0"), 
("GoodsCatalog", "Каталог товаров", "1", "1"), 
("IShop", "Интернет-магазин", "1", "1"), 
("ImageGalleries", "Альбомы изображений", "1", "0"), 
("LinksCatalog", "Каталог ссылок", "1", "1"), 
("MailFormator", "Почтовые формы", "1", "0"), 
("Mailer", "Отправка e-mail", "1", "0"), 
("NavigationControl", "Управление навигацией", "0", "0"), 
("News", "Новости", "1", "1"), 
("OrderHistory", "История заказов", "0", "0"), 
("PluginsControl", "Управление доп. модулями", "0", "0"), 
("Poll", "Голосования", "1", "0"), 
("SimplePage", "Одиночная страница", "1", "1"), 
("SiteBackup", "Резервные копии", "0", "0"), 
("SiteControl", "Настройки сайта", "0", "0"), 
("SiteMap", "Карта сайта", "1", "0"), 
("SitemapGenerator", "Генератор XML-карты сайта", "0", "0"), 
("TechShop", "Магазин тех. товаров", "1", "1"), 
("TemplatesEditor", "Редактор дизайна", "0", "0"), 
("UpdateClient", "Обновление системы", "0", "0"), 
("UsersControl", "Управление пользователями", "0", "0"), 
("VacancyCatalog", "Каталог вакансий", "1", "1");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_order`;
--
CREATE TABLE `el_order` (
  `id` int(5) NOT NULL auto_increment,
  `uid` int(5) NOT NULL,
  `crtime` int(11) NOT NULL,
  `mtime` int(11) NOT NULL,
  `state` enum('send','accept','deliver','complite','aborted') collate utf8_bin NOT NULL default 'send',
  `amount` double(10,2) NOT NULL,
  `discount` double(6,2) NOT NULL,
  `delivery_price` double(6,2) NOT NULL,
  `total` double(12,2) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_order` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_order_customer`;
--
CREATE TABLE `el_order_customer` (
  `id` int(5) NOT NULL auto_increment,
  `order_id` int(5) NOT NULL,
  `uid` int(5) NOT NULL,
  `label` varchar(256) collate utf8_bin NOT NULL,
  `value` mediumtext collate utf8_bin,
  PRIMARY KEY  (`id`),
  KEY `order_id` (`order_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_order_customer` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_order_item`;
--
CREATE TABLE `el_order_item` (
  `id` int(8) NOT NULL auto_increment,
  `order_id` int(5) NOT NULL,
  `uid` int(5) NOT NULL,
  `shop` enum('IShop','TechShop') collate utf8_bin NOT NULL default 'IShop',
  `i_id` int(5) NOT NULL,
  `m_id` int(5) NOT NULL,
  `code` varchar(256) collate utf8_bin NOT NULL,
  `name` varchar(256) collate utf8_bin NOT NULL,
  `qnt` int(5) NOT NULL default '1',
  `price` double(12,2) NOT NULL,
  `props` text collate utf8_bin,
  `crtime` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_order_item` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_page`;
--
CREATE TABLE `el_page` (
  `id` int(3) NOT NULL default '0',
  `content` text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_page` WRITE;
--
INSERT INTO el_page (id, content) VALUES (2, "");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin`;
--
CREATE TABLE `el_plugin` (
  `name` varchar(25) collate utf8_bin NOT NULL,
  `label` varchar(75) collate utf8_bin NOT NULL,
  `descrip` varchar(200) collate utf8_bin NOT NULL,
  `is_on` enum('0','1') collate utf8_bin NOT NULL default '1',
  `status` enum('disable','off','on') collate utf8_bin NOT NULL default 'off',
  PRIMARY KEY  (`name`),
  KEY `is_on` (`is_on`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin` WRITE;
--
INSERT INTO el_plugin (name, label, descrip, is_on, status) VALUES ("Calculator", "Калькулятор", "", "0", "off"), 
("InfoBlock", "Информационные блоки", "Показ информационных блоков на произвольно выбранных страницах", "1", "on"), 
("NewsTopics", "Заголовки новостей", "Показ заголовков новостей", "0", "off"), 
("Poll", "Голосования", "Отображение голосований", "1", "off"), 
("RandomImage", "Случайная картинка", "Показ случайных картинок из альбомов изображений", "0", "off");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin_calc`;
--
CREATE TABLE `el_plugin_calc` (
  `id` tinyint(2) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_bin NOT NULL,
  `pos` enum('l','r','t','b') collate utf8_bin default 'l',
  `tpl` varchar(250) collate utf8_bin NOT NULL,
  `formula` mediumtext collate utf8_bin,
  `unit` varchar(20) collate utf8_bin default NULL,
  `dtype` enum('int','double') collate utf8_bin NOT NULL default 'int',
  `view` enum('inline','dialog') collate utf8_bin NOT NULL default 'inline',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin_calc` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin_calc2page`;
--
CREATE TABLE `el_plugin_calc2page` (
  `id` tinyint(2) NOT NULL,
  `page_id` int(3) NOT NULL,
  PRIMARY KEY  (`id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin_calc2page` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin_calc_var`;
--
CREATE TABLE `el_plugin_calc_var` (
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
  `sort_ndx` int(3) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `cid_2` (`cid`,`name`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin_calc_var` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin_ib`;
--
CREATE TABLE `el_plugin_ib` (
  `id` tinyint(2) NOT NULL auto_increment,
  `name` varchar(150) collate utf8_bin NOT NULL,
  `content` text collate utf8_bin,
  `pos` enum('l','r','t','b') collate utf8_bin default 'l',
  `ts` int(11) NOT NULL,
  `tpl` varchar(250) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin_ib` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_plugin_ib2page`;
--
CREATE TABLE `el_plugin_ib2page` (
  `id` tinyint(2) NOT NULL,
  `page_id` int(3) NOT NULL,
  PRIMARY KEY  (`id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_plugin_ib2page` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_uplog`;
--
CREATE TABLE `el_uplog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `act` enum('Upgrade','Downgrade') collate utf8_bin NOT NULL,
  `result` enum('Success','Failed') collate utf8_bin NOT NULL default 'Success',
  `version` varchar(32) collate utf8_bin NOT NULL,
  `log` mediumtext collate utf8_bin NOT NULL,
  `changelog` text collate utf8_bin NOT NULL,
  `crtime` int(11) NOT NULL,
  `backup_file` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_uplog` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_user`;
--
CREATE TABLE `el_user` (
  `uid` int(3) NOT NULL auto_increment,
  `login` varchar(25) collate utf8_bin NOT NULL default '',
  `pass` varchar(32) collate utf8_bin NOT NULL default '',
  `f_name` varchar(100) collate utf8_bin NOT NULL default '',
  `s_name` varchar(100) collate utf8_bin NOT NULL default '',
  `l_name` varchar(100) collate utf8_bin NOT NULL default '',
  `email` varchar(80) collate utf8_bin NOT NULL default '',
  `phone` varchar(25) collate utf8_bin NOT NULL default '',
  `fax` varchar(25) collate utf8_bin NOT NULL default '',
  `company` varchar(100) collate utf8_bin default NULL,
  `postal_code` varchar(20) collate utf8_bin NOT NULL,
  `address` varchar(255) collate utf8_bin NOT NULL,
  `icq_uin` varchar(8) collate utf8_bin NOT NULL,
  `web_site` varchar(50) collate utf8_bin NOT NULL,
  `crtime` int(11) NOT NULL default '0',
  `mtime` int(11) NOT NULL default '0',
  `atime` int(11) NOT NULL default '0',
  `visits` int(3) NOT NULL default '0',
  `auto_login` enum('0','1') collate utf8_bin NOT NULL default '0',
  `forum_posts_count` int(5) NOT NULL,
  `avatar` varchar(150) collate utf8_bin NOT NULL,
  `signature` mediumtext collate utf8_bin,
  `personal_text` varchar(256) collate utf8_bin default NULL,
  `location` varchar(256) collate utf8_bin default NULL,
  `birthdate` int(11) default NULL,
  `gender` enum('','male','female') collate utf8_bin NOT NULL default '',
  `show_email` tinyint(1) NOT NULL default '0',
  `show_online` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`uid`),
  KEY `email` (`email`),
  KEY `login` (`login`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_user` WRITE;
--
INSERT INTO el_user (uid, login, pass, f_name, s_name, l_name, email, phone, fax, company, postal_code, address, icq_uin, web_site, crtime, mtime, atime, visits, auto_login, forum_posts_count, avatar, signature, personal_text, location, birthdate, gender, show_email, show_online) VALUES (1, "root", "b78bb582523a89da07ce348eb5e16d88", "Administrator", "", "", "", "", "", "", "", "", "", "", 1255951355, 1255951355, 1255951530, 1, "0", 0, "", "", "", "", 0, "", 0, 0);
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_user_in_group`;
--
CREATE TABLE `el_user_in_group` (
  `user_id` int(5) NOT NULL default '0',
  `group_id` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_user_in_group` WRITE;
--
INSERT INTO el_user_in_group (user_id, group_id) VALUES (1, 1);
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_user_pref`;
--
CREATE TABLE `el_user_pref` (
  `user_id` int(3) NOT NULL default '0',
  `name` varchar(50) collate utf8_bin NOT NULL default '',
  `val` text collate utf8_bin,
  `is_serialized` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_user_pref` WRITE;
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_user_profile`;
--
CREATE TABLE `el_user_profile` (
  `field` char(15) collate utf8_bin NOT NULL,
  `label` char(50) collate utf8_bin NOT NULL,
  `type` enum('text','textarea','select') collate utf8_bin NOT NULL default 'text',
  `opts` varchar(255) collate utf8_bin NOT NULL,
  `rule` char(25) collate utf8_bin NOT NULL,
  `is_func` enum('0','1','2') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`field`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_user_profile` WRITE;
--
INSERT INTO el_user_profile (field, label, type, opts, rule, is_func) VALUES ("address", "Address", "textarea", "", "", ""), 
("company", "Company name", "text", "", "", ""), 
("email", "E-mail", "text", "", "elCheckUserUniqFields", "1"), 
("f_name", "First name", "text", "", "letters", ""), 
("fax", "Fax number", "text", "", "phone", ""), 
("icq_uin", "ICQ UIN", "text", "", "", ""), 
("l_name", "Last name", "text", "", "letters", ""), 
("login", "Login", "text", "", "elCheckUserUniqFields", "1"), 
("phone", "Phone", "text", "", "phone", ""), 
("postal_code", "Postal code", "text", "", "numbers", ""), 
("s_name", "Second name", "text", "", "letters", ""), 
("web_site", "Website URL", "text", "", "", "");
--

UNLOCK TABLES;
--


DROP TABLE IF EXISTS `el_user_profile_use`;
--
CREATE TABLE `el_user_profile_use` (
  `field` varchar(50) collate utf8_bin NOT NULL,
  `rq` enum('0','1','2') collate utf8_bin NOT NULL default '1',
  `sort_ndx` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`field`),
  KEY `rq` (`rq`),
  KEY `sort_ndx` (`sort_ndx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
--
LOCK TABLES `el_user_profile_use` WRITE;
--
INSERT INTO el_user_profile_use (field, rq, sort_ndx) VALUES ("address", "1", 9), 
("company", "0", 7), 
("email", "2", 4), 
("f_name", "2", 1), 
("fax", "0", 6), 
("icq_uin", "0", 10), 
("l_name", "2", 3), 
("login", "2", 0), 
("phone", "1", 5), 
("postal_code", "0", 8), 
("s_name", "1", 2), 
("web_site", "0", 11);
--

UNLOCK TABLES;
--


