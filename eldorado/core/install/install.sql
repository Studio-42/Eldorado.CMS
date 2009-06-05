-- MySQL dump 10.11
--
-- Host: localhost    Database: distr_en
-- ------------------------------------------------------
-- Server version	5.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `el_amenu`
--

DROP TABLE IF EXISTS `el_amenu`;
CREATE TABLE `el_amenu` (
  `id` tinyint(3) NOT NULL auto_increment,
  `name` varchar(255) collate utf8_bin NOT NULL,
  `pos` enum('l','r') collate utf8_bin NOT NULL default 'l',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_amenu`
--

LOCK TABLES `el_amenu` WRITE;
/*!40000 ALTER TABLE `el_amenu` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_amenu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_amenu_dest`
--

DROP TABLE IF EXISTS `el_amenu_dest`;
CREATE TABLE `el_amenu_dest` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY  (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_amenu_dest`
--

LOCK TABLES `el_amenu_dest` WRITE;
/*!40000 ALTER TABLE `el_amenu_dest` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_amenu_dest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_amenu_source`
--

DROP TABLE IF EXISTS `el_amenu_source`;
CREATE TABLE `el_amenu_source` (
  `m_id` tinyint(3) NOT NULL,
  `p_id` int(3) NOT NULL,
  `sort` int(3) NOT NULL,
  PRIMARY KEY  (`m_id`,`p_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_amenu_source`
--

LOCK TABLES `el_amenu_source` WRITE;
/*!40000 ALTER TABLE `el_amenu_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_amenu_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_catalogs_crosslink`
--

DROP TABLE IF EXISTS `el_catalogs_crosslink`;
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
-- Dumping data for table `el_catalogs_crosslink`
--

LOCK TABLES `el_catalogs_crosslink` WRITE;
/*!40000 ALTER TABLE `el_catalogs_crosslink` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_catalogs_crosslink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_email`
--

DROP TABLE IF EXISTS `el_email`;
CREATE TABLE `el_email` (
  `id` tinyint(2) NOT NULL auto_increment,
  `label` varchar(50) collate utf8_bin NOT NULL default '',
  `email` varchar(75) collate utf8_bin NOT NULL default '',
  `is_default` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_email`
--

LOCK TABLES `el_email` WRITE;
/*!40000 ALTER TABLE `el_email` DISABLE KEYS */;
INSERT INTO `el_email` VALUES (1,'admin','admin@yoursite.com','1');
/*!40000 ALTER TABLE `el_email` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_group`
--

DROP TABLE IF EXISTS `el_group`;
CREATE TABLE `el_group` (
  `gid` tinyint(2) NOT NULL auto_increment,
  `name` char(30) collate utf8_bin NOT NULL default '',
  `perm` tinyint(1) NOT NULL default '0',
  `mtime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`gid`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_group`
--

LOCK TABLES `el_group` WRITE;
/*!40000 ALTER TABLE `el_group` DISABLE KEYS */;
INSERT INTO `el_group` VALUES (1,'root',8,1122463302),(2,'guests',0,1122464829);
/*!40000 ALTER TABLE `el_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_group_acl`
--

DROP TABLE IF EXISTS `el_group_acl`;
CREATE TABLE `el_group_acl` (
  `group_id` tinyint(2) NOT NULL default '0',
  `page_id` int(3) NOT NULL default '0',
  `perm` enum('1','3','7') collate utf8_bin NOT NULL default '1',
  PRIMARY KEY  (`page_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_group_acl`
--

LOCK TABLES `el_group_acl` WRITE;
/*!40000 ALTER TABLE `el_group_acl` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_group_acl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_icart`
--

DROP TABLE IF EXISTS `el_icart`;
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
  `price` double(8,2) NOT NULL,
  `props` text collate utf8_bin,
  `crtime` int(11) NOT NULL,
  `mtime` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_icart`
--

LOCK TABLES `el_icart` WRITE;
/*!40000 ALTER TABLE `el_icart` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_icart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_menu`
--

DROP TABLE IF EXISTS `el_menu`;
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
-- Dumping data for table `el_menu`
--

LOCK TABLES `el_menu` WRITE;
/*!40000 ALTER TABLE `el_menu` DISABLE KEYS */;
INSERT INTO `el_menu` VALUES (1,'--','','','',1,26,0,'Container','2','0','1','0','','','','','0','0',''),(2,'Home','','','home',2,3,1,'SimplePage','2','0','1','0','','home.png','home.png','home.png','0','0',''),(3,'Mail us','','','conf',4,5,1,'Mailer','2','0','1','0','','mail.png','mail.png','mail.png','0','0','0'),(5,'Control center','','','cc',6,25,1,'Container','2','0','0','0','','default.png','default.png','default.png','0','0',''),(6,'Users/groups','','','users',7,8,2,'UsersControl','2','0','0','0','','users.gif','default.png','default.png','0','0','0'),(7,'Site navigation','','','menu',9,10,2,'NavigationControl','2','0','1','0','','nav.gif','default.png','default.png','0','0',''),(24,'Backups','','','backup',17,18,2,'SiteBackup','2','0','1','0','','backup.gif','default.png','default.png','0','0',''),(9,'Files','','','fm',13,14,2,'FileManager','2','0','1','0','','files.gif','default.png','default.png','0','0',''),(8,'Site options','','','site_conf',11,12,2,'SiteControl','2','0','1','0','','options.gif','default.png','default.png','0','0',''),(10,'Plugins','','','plc',15,16,2,'PluginsControl','2','0','1','0','','modules.gif','default.png','default.png','0','0',''),(25,'XML site map','','','sitemap',19,20,2,'SitemapGenerator','2','0','1','0','','map.gif','default.png','default.png','0','0',''),(26,'System update','','','update',21,22,2,'UpdateClient','2','0','1','0','','updates.gif','default.png','default.png','0','0',''),(27,'Design editor','','','tpl',23,24,2,'TemplatesEditor','2','0','1','0','','tpl-editor.gif','default.png','default.png','0','0','');
/*!40000 ALTER TABLE `el_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_metatag`
--

DROP TABLE IF EXISTS `el_metatag`;
CREATE TABLE `el_metatag` (
  `page_id` int(3) NOT NULL,
  `c_id` int(3) NOT NULL,
  `i_id` int(3) NOT NULL,
  `name` varchar(100) collate utf8_bin NOT NULL default 'DESCRIPTION',
  `content` mediumtext collate utf8_bin,
  PRIMARY KEY  (`page_id`,`c_id`,`i_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_metatag`
--

LOCK TABLES `el_metatag` WRITE;
/*!40000 ALTER TABLE `el_metatag` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_metatag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_module`
--

DROP TABLE IF EXISTS `el_module`;
CREATE TABLE `el_module` (
  `module` varchar(50) collate utf8_bin NOT NULL default '',
  `descrip` varchar(200) collate utf8_bin NOT NULL default '',
  `multi` enum('1','0') collate utf8_bin NOT NULL default '1',
  `search` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_module`
--

LOCK TABLES `el_module` WRITE;
/*!40000 ALTER TABLE `el_module` DISABLE KEYS */;
INSERT INTO `el_module` VALUES ('Container','Container','1','0'),('SimplePage','Simple page','1','1'),('UsersControl','Users and groups control','0','0'),('NavigationControl','Site navigation control','0','0'),('Mailer','Send E-mail','1','0'),('News','News line','1','1'),('DocsCatalog','Documents catalog','1','1'),('SiteControl','Site options','0','0'),('EventSchedule','Events schedule','1','1'),('SiteBackup','Backups','0','0'),('FileManager','File manager','1','0'),('PluginsControl','Plugins control','0','0'),('FileArchive','Fila archive','1','1'),('IShop','Internet shop','1','0'),('MailFormator','Mail form constructor','1','0'),('LinksCatalog','Links catalog','1','1'),('GoodsCatalog','Каталог товаров','1','1'),('ImageGalleries','Images galleries','1','0'),('FAQ','FAQ','1','0'),('SiteMap','Site map','1','0'),('Glossary','Glossary','1','0'),('Poll','Poll','1','0'),('GAStat','Statistics','0','0'),('TechShop','Technical internet shop','1','1'),('TemplatesEditor','Design editor','0','0'),('VacancyCatalog','Vacancies catalog','1','1'),('UpdateClient','System update','0','0'),('SitemapGenerator','XML site map generator','0','0'),('Forum','Forum','0','0');
/*!40000 ALTER TABLE `el_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_order`
--

DROP TABLE IF EXISTS `el_order`;
CREATE TABLE `el_order` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_order`
--

LOCK TABLES `el_order` WRITE;
/*!40000 ALTER TABLE `el_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_order_customer`
--

DROP TABLE IF EXISTS `el_order_customer`;
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
-- Dumping data for table `el_order_customer`
--

LOCK TABLES `el_order_customer` WRITE;
/*!40000 ALTER TABLE `el_order_customer` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_order_customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_order_item`
--

DROP TABLE IF EXISTS `el_order_item`;
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
  `price` double(8,2) NOT NULL,
  `props` text collate utf8_bin,
  `crtime` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_order_item`
--

LOCK TABLES `el_order_item` WRITE;
/*!40000 ALTER TABLE `el_order_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_order_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_page`
--

DROP TABLE IF EXISTS `el_page`;
CREATE TABLE `el_page` (
  `id` int(3) NOT NULL default '0',
  `content` text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_page`
--

LOCK TABLES `el_page` WRITE;
/*!40000 ALTER TABLE `el_page` DISABLE KEYS */;
INSERT INTO `el_page` VALUES (2,'');
/*!40000 ALTER TABLE `el_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_plugin`
--

DROP TABLE IF EXISTS `el_plugin`;
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
-- Dumping data for table `el_plugin`
--

LOCK TABLES `el_plugin` WRITE;
/*!40000 ALTER TABLE `el_plugin` DISABLE KEYS */;
INSERT INTO `el_plugin` VALUES ('NewsTopics','News topics','Display short news topics','0','off'),('RandomImage','random images','Display random images from images galleries','0','off'),('InfoBlock','Info blocks','Display blocks with texts and images','1','on'),('Poll','Poll','Display active polls','1','off');
/*!40000 ALTER TABLE `el_plugin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_plugin_ib`
--

DROP TABLE IF EXISTS `el_plugin_ib`;
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
-- Dumping data for table `el_plugin_ib`
--

LOCK TABLES `el_plugin_ib` WRITE;
/*!40000 ALTER TABLE `el_plugin_ib` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_plugin_ib` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_plugin_ib2page`
--

DROP TABLE IF EXISTS `el_plugin_ib2page`;
CREATE TABLE `el_plugin_ib2page` (
  `id` tinyint(2) NOT NULL,
  `page_id` int(3) NOT NULL,
  PRIMARY KEY  (`id`,`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_plugin_ib2page`
--

LOCK TABLES `el_plugin_ib2page` WRITE;
/*!40000 ALTER TABLE `el_plugin_ib2page` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_plugin_ib2page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_uplog`
--

DROP TABLE IF EXISTS `el_uplog`;
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
-- Dumping data for table `el_uplog`
--

LOCK TABLES `el_uplog` WRITE;
/*!40000 ALTER TABLE `el_uplog` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_uplog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_user`
--

DROP TABLE IF EXISTS `el_user`;
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
-- Dumping data for table `el_user`
--

LOCK TABLES `el_user` WRITE;
/*!40000 ALTER TABLE `el_user` DISABLE KEYS */;
INSERT INTO `el_user` VALUES (1,'root','e10adc3949ba59abbe56e057f20f883e','Administrator','','','','','',NULL,'','','','',1146696062,0,1244196208,1,'0',0,'',NULL,NULL,NULL,NULL,'',0,0);
/*!40000 ALTER TABLE `el_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_user_in_group`
--

DROP TABLE IF EXISTS `el_user_in_group`;
CREATE TABLE `el_user_in_group` (
  `user_id` int(5) NOT NULL default '0',
  `group_id` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_user_in_group`
--

LOCK TABLES `el_user_in_group` WRITE;
/*!40000 ALTER TABLE `el_user_in_group` DISABLE KEYS */;
INSERT INTO `el_user_in_group` VALUES (1,1);
/*!40000 ALTER TABLE `el_user_in_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_user_pref`
--

DROP TABLE IF EXISTS `el_user_pref`;
CREATE TABLE `el_user_pref` (
  `user_id` int(3) NOT NULL default '0',
  `name` varchar(50) collate utf8_bin NOT NULL default '',
  `val` text collate utf8_bin,
  `is_serialized` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_user_pref`
--

LOCK TABLES `el_user_pref` WRITE;
/*!40000 ALTER TABLE `el_user_pref` DISABLE KEYS */;
/*!40000 ALTER TABLE `el_user_pref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_user_profile`
--

DROP TABLE IF EXISTS `el_user_profile`;
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
-- Dumping data for table `el_user_profile`
--

LOCK TABLES `el_user_profile` WRITE;
/*!40000 ALTER TABLE `el_user_profile` DISABLE KEYS */;
INSERT INTO `el_user_profile` VALUES ('login','Login','text','','elCheckUserUniqFields','1'),('email','E-mail','text','','elCheckUserUniqFields','1'),('f_name','First name','text','','letters',''),('s_name','Second name','text','','letters',''),('l_name','Last name','text','','letters',''),('phone','Phone','text','','phone',''),('fax','Fax number','text','','phone',''),('company','Company name','text','','',''),('postal_code','Postal code','text','','numbers',''),('address','Address','textarea','','',''),('icq_uin','ICQ UIN','text','','',''),('web_site','Website URL','text','','','');
/*!40000 ALTER TABLE `el_user_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `el_user_profile_use`
--

DROP TABLE IF EXISTS `el_user_profile_use`;
CREATE TABLE `el_user_profile_use` (
  `field` varchar(50) collate utf8_bin NOT NULL,
  `rq` enum('0','1','2') collate utf8_bin NOT NULL default '1',
  `sort_ndx` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`field`),
  KEY `rq` (`rq`),
  KEY `sort_ndx` (`sort_ndx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `el_user_profile_use`
--

LOCK TABLES `el_user_profile_use` WRITE;
/*!40000 ALTER TABLE `el_user_profile_use` DISABLE KEYS */;
INSERT INTO `el_user_profile_use` VALUES ('f_name','2',1),('l_name','2',3),('s_name','1',2),('email','2',4),('phone','1',5),('fax','0',6),('company','0',7),('postal_code','0',8),('address','1',9),('icq_uin','0',10),('web_site','0',11),('login','2',0);
/*!40000 ALTER TABLE `el_user_profile_use` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-06-05 10:17:52
