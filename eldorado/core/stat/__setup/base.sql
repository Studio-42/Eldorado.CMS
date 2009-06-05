DROP TABLE IF EXISTS `wc_browser`;
CREATE TABLE IF NO EXISTS `wc_browser` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `value` varchar(20) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_browser` VALUES (1,'microsoft internet explorer','IE','Internet Explorer');
INSERT INTO `wc_browser` VALUES (2,'msie','IE','Internet Explorer');
INSERT INTO `wc_browser` VALUES (3,'netscape6','NS','Netscape');
INSERT INTO `wc_browser` VALUES (4,'netscape','NS','Netscape');
INSERT INTO `wc_browser` VALUES (5,'mozilla','MZ','Mozila');
INSERT INTO `wc_browser` VALUES (6,'opera','OP','Opera');
INSERT INTO `wc_browser` VALUES (7,'konqueror','KQ','Konqueror');
INSERT INTO `wc_browser` VALUES (8,'icab','IC','iCab');
INSERT INTO `wc_browser` VALUES (9,'lynx','LX','Lynx');
INSERT INTO `wc_browser` VALUES (10,'links','LI','Links');
INSERT INTO `wc_browser` VALUES (11,'ncsa mosaic','MO','NCSA Mosaic');
INSERT INTO `wc_browser` VALUES (12,'amaya','AM','Amaya');
INSERT INTO `wc_browser` VALUES (13,'omniweb','OW','OmniWeb');
INSERT INTO `wc_browser` VALUES (14,'hotjava','HJ','HotJAVA');
INSERT INTO `wc_browser` VALUES (15,'browsex','BX','Browsex');
INSERT INTO `wc_browser` VALUES (16,'unknown','unknown','Неизвестно');

DROP TABLE IF EXISTS `wc_browser_features`;
CREATE TABLE IF NO EXISTS `wc_browser_features` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(20) default NULL,
  `value` varchar(20) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_browser_features` VALUES (1,'css2','IE5UP,NS5UP','Поддержка CSS версии 2');
INSERT INTO `wc_browser_features` VALUES (2,'css1','NS4UP,IE4UP','Поддержка CSS версии 1');
INSERT INTO `wc_browser_features` VALUES (3,'iframes','IE3UP,NS5UP','Поддержка IFrames');
INSERT INTO `wc_browser_features` VALUES (4,'xml','IE5UP,NS5UP','Поддержка XML');
INSERT INTO `wc_browser_features` VALUES (5,'dom','IE5UP,NS5UP','Поддержка DOM');
INSERT INTO `wc_browser_features` VALUES (6,'avoid_popup','IE3,LI,LX','Закрытие всплывающих окон');
INSERT INTO `wc_browser_features` VALUES (7,'cache_forms','NS','Кеширование форм');
INSERT INTO `wc_browser_features` VALUES (8,'cache_ssl','IE','Кеширование SSL');

DROP TABLE IF EXISTS `wc_catalog`;
CREATE TABLE IF NO EXISTS `wc_catalog` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `value` varchar(20) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_catalog` VALUES (1,'www.freeware.ru','freeware','Freeware.ru');
INSERT INTO `wc_catalog` VALUES (2,'www.woweb.ru','woweb','Woweb.ru');
INSERT INTO `wc_catalog` VALUES (3,'www.download.ru','download.ru','Download.ru');
INSERT INTO `wc_catalog` VALUES (4,'www.myweb.ru','myweb','Myweb.ru');
INSERT INTO `wc_catalog` VALUES (5,'www.ru','www.ru','Www.ru');
INSERT INTO `wc_catalog` VALUES (6,'www.zabor.com','zabor.com','Zabor.Com');
INSERT INTO `wc_catalog` VALUES (7,'www.1job.ru','1job.ru','1Job.Ru');
INSERT INTO `wc_catalog` VALUES (8,'www.ruspoisk.ru','ruspoisk.ru','Ruspoisk.Ru');
INSERT INTO `wc_catalog` VALUES (9,'www.autovista.ru','autovista.ru','Autovista.Ru');
INSERT INTO `wc_catalog` VALUES (10,'www.allrunet.ru','allrunet.ru','Allrunet.Ru');
INSERT INTO `wc_catalog` VALUES (11,'www.bizz.ru','bizz.ru','Bizz.Ru');
INSERT INTO `wc_catalog` VALUES (12,'www.jumplink.ru','jumplink.ru','Jumplink.Ru');
INSERT INTO `wc_catalog` VALUES (13,'www.ramblers.ru','ramblers.ru','Ramblers.Ru');
INSERT INTO `wc_catalog` VALUES (14,'dir.org.ru','dir.org.ru','Dir.Org.Ru');
INSERT INTO `wc_catalog` VALUES (15,'topcatalog.com.ua','topcatalog.com.ua','Topcatalog.Com.Ua');
INSERT INTO `wc_catalog` VALUES (16,'search.centre.ru','search.centre.ru','Search.Centre.Ru');
INSERT INTO `wc_catalog` VALUES (17,'www.ulitka.ru','ulitka.ru','Ulitka.Ru');
INSERT INTO `wc_catalog` VALUES (18,'www.infopiter.ru','infopiter.ru','Infopiter.Ru');
INSERT INTO `wc_catalog` VALUES (19,'www.susanin.net','susanin.net','Susanin.Net');
INSERT INTO `wc_catalog` VALUES (20,'www.ipclub.ru','ipclub.ru','Ipclub.Ru');
INSERT INTO `wc_catalog` VALUES (21,'www.hotlinks.ru','hotlinks.ru','Hotlinks.Ru');
INSERT INTO `wc_catalog` VALUES (22,'www.vsego.ru','vsego.ru','Vsego.Ru');
INSERT INTO `wc_catalog` VALUES (23,'www.saiteka.ru','saiteka.ru','Saiteka.Ru');
INSERT INTO `wc_catalog` VALUES (24,'www.poisk.com','poisk.com','Poisk.Com');
INSERT INTO `wc_catalog` VALUES (25,'www.masteru.ru','masteru.ru','Masteru.Ru');
INSERT INTO `wc_catalog` VALUES (26,'www.ngo.ru','ngo.ru','Ngo.Ru');
INSERT INTO `wc_catalog` VALUES (27,'www.top100.uka.ru','top100.uka.ru','Top100.Uka.Ru');
INSERT INTO `wc_catalog` VALUES (28,'www.aweb.ru','aweb.ru','Aweb.Ru');
INSERT INTO `wc_catalog` VALUES (29,'www.theall.net','theall.net','Theall.Net');
INSERT INTO `wc_catalog` VALUES (30,'www.1-2.ru','1-2.ru','1-2.Ru');
INSERT INTO `wc_catalog` VALUES (31,'search.pp.ru','search.pp.ru','Search.Pp.Ru');
INSERT INTO `wc_catalog` VALUES (32,'www.iptop.net','iptop.net','Iptop.Net');
INSERT INTO `wc_catalog` VALUES (33,'www.yandex-rambler.ru','yandex-rambler.ru','Yandex-Rambler.Ru');
INSERT INTO `wc_catalog` VALUES (34,'www.webprofy.ru','webprofy.ru','Webprofy.Ru');
INSERT INTO `wc_catalog` VALUES (35,'www.bizcat.ru','bizcat.ru','Bizcat.Ru');
INSERT INTO `wc_catalog` VALUES (36,'www.bestcapshop.info','bestcapshop.info','Bestcapshop.Info');
INSERT INTO `wc_catalog` VALUES (37,'www.geo-line.ru','geo-line.ru','Geo-Line.Ru');
INSERT INTO `wc_catalog` VALUES (38,'www.find-it.ru','find-it.ru','Find-It.Ru');
INSERT INTO `wc_catalog` VALUES (39,'www.findme.ru','findme.ru','Findme.Ru');
INSERT INTO `wc_catalog` VALUES (40,'www.ibn.ru','ibn.ru','Ibn.Ru');
INSERT INTO `wc_catalog` VALUES (41,'top100.rambler.ru','top100.rambler.ru','Рейтинг top100.rambler.ru');
INSERT INTO `wc_catalog` VALUES (42,'www.spylog.ru','spylog.ru','Рейтинг spylog.ru');
INSERT INTO `wc_catalog` VALUES (43,'www.rax.ru','rax.ru','Рейтинг rax.ru');
INSERT INTO `wc_catalog` VALUES (44,'top.one.ru','top.one.ru','Рейтинг top.one.ru');
INSERT INTO `wc_catalog` VALUES (45,'top.bigmir.net','top.bigmir.net','Рейтинг top.bigmir.net');
INSERT INTO `wc_catalog` VALUES (46,'index.agava.ru','index.agava.ru','Рейтинг index.agava.ru');
INSERT INTO `wc_catalog` VALUES (47,'www.cool-tops.com','cool-tops.com','Рейтинг cool-tops.com');
INSERT INTO `wc_catalog` VALUES (48,'www.paid.ru','paid.ru','Рейтинг paid.ru');
INSERT INTO `wc_catalog` VALUES (49,'www.all-web.ru','all-web.ru','Рейтинг all-web.ru');
INSERT INTO `wc_catalog` VALUES (50,'www.directrix.ru','directrix.ru','Рейтинг directrix.ru');

DROP TABLE IF EXISTS `wc_color`;
CREATE TABLE IF NO EXISTS `wc_color` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(10) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_color` VALUES (1,'16','High Color (16 бит)');
INSERT INTO `wc_color` VALUES (2,'32','True Color (32 бит)');
INSERT INTO `wc_color` VALUES (3,'256','256 цветов');
INSERT INTO `wc_color` VALUES (4,'24','True Color (24 бита)');

DROP TABLE IF EXISTS `wc_forum`;
CREATE TABLE IF NO EXISTS `wc_forum` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `value` varchar(20) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_forum` VALUES (1,'www.kadets.info','kadets.info','Kadets.info');
INSERT INTO `wc_forum` VALUES (2,'forum.vstre4a.info','forum.vstre4a.info','Forum.vstre4a.info');
INSERT INTO `wc_forum` VALUES (3,'www.forum.exler.ru','forum.exler.ru','Forum.exler.ru');
INSERT INTO `wc_forum` VALUES (4,'www.softboard.ru','softboard.ru','Softboard.ru');
INSERT INTO `wc_forum` VALUES (5,'forum.softweb.ru','forum.softweb.ru','Forum.softweb.ru');
INSERT INTO `wc_forum` VALUES (6,'forum.rin.ru','forum.rin.ru','Forum.rin.ru');
INSERT INTO `wc_forum` VALUES (7,'forum.rus-chat.de','forum.rus-chat.de','Forum.rus-chat.de');
INSERT INTO `wc_forum` VALUES (8,'forum.mp3s.ru','forum.mp3s.ru','Forum.mp3s.ru');
INSERT INTO `wc_forum` VALUES (9,'www.designforum.ru','designforum.ru','Designforum.ru');
INSERT INTO `wc_forum` VALUES (10,'www.open-forum.ru','open-forum.ru','Open-forum.ru');
INSERT INTO `wc_forum` VALUES (11,'www.libforum.ru','libforum.ru','Libforum.ru');
INSERT INTO `wc_forum` VALUES (12,'forum.oszone.net','forum.oszone.net','Forum.oszone.net');
INSERT INTO `wc_forum` VALUES (13,'www.ins-forum.ru','ins-forum.ru','Ins-forum.ru');
INSERT INTO `wc_forum` VALUES (14,'www.ru-forum.com','ru-forum.com','Ru-forum.com');
INSERT INTO `wc_forum` VALUES (15,'www.ru-board.ru','ru-board','Ru-board.com');

DROP TABLE IF EXISTS `wc_java`;
CREATE TABLE IF NO EXISTS `wc_java` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(10) default NULL,
  `value` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_java` VALUES (1,'1.5','IE5.5UP,NS5UP,MZ5UP');
INSERT INTO `wc_java` VALUES (2,'1.4',NULL);
INSERT INTO `wc_java` VALUES (3,'1.3','NS4.05UP,OP5UP,IE5UP');
INSERT INTO `wc_java` VALUES (4,'1.2','NS4UP,IE4UP');
INSERT INTO `wc_java` VALUES (5,'1.1','NS3UP,OP,KQ');
INSERT INTO `wc_java` VALUES (6,'1.0','NS2UP,IE3UP');
INSERT INTO `wc_java` VALUES (7,'0','LI,LX,HJ');

DROP TABLE IF EXISTS `wc_main` ;
CREATE TABLE IF NO EXISTS `wc_main` (
  `id` int(10) NOT NULL auto_increment,
  `time` int(11) NOT NULL default '0',
  `ip` bigint(20) NOT NULL default '0',
  `browser` varchar(20) default NULL,
  `os` varchar(20) default NULL,
  `platform` varchar(20) default NULL,
  `screen` varchar(10) default NULL,
  `color` varchar(10) default NULL,
  `java` varchar(10) default NULL,
  `cookies` varchar(10) default NULL,
  `language` varchar(20) default NULL,
  `country` varchar(50) default NULL,
  `region` varchar(100) default NULL,
  `organization` varchar(255) default NULL,
  `city` varchar(50) default NULL,
  `css2` varchar(5) default NULL,
  `css1` varchar(5) default NULL,
  `iframes` varchar(5) default NULL,
  `xml` varchar(5) default NULL,
  `dom` varchar(5) default NULL,
  `cache_forms` varchar(5) default NULL,
  `avoid_popup` varchar(5) default NULL,
  `cache_ssl` varchar(5) default NULL,
  `referer` varchar(255) default NULL,
  `searcheng` varchar(20) default NULL,
  `search_words` varchar(255) default NULL,
  `catalog` varchar(20) default NULL,
  `forum` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `wc_online`;
CREATE TABLE IF NO EXISTS `wc_online` (
  `id` int(10) NOT NULL auto_increment,
  `ses_id` int(10) default NULL,
  `ip` varchar(15) NOT NULL default '',
  `timestamp` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `wc_os` ;
CREATE TABLE IF NO EXISTS `wc_os` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_os` VALUES (1,'win','Windows');
INSERT INTO `wc_os` VALUES (2,'*nix','Unix');
INSERT INTO `wc_os` VALUES (3,'os2','OS/2');
INSERT INTO `wc_os` VALUES (4,'mac','Macintosh');
INSERT INTO `wc_os` VALUES (5,'unknown','Неизвестно');

DROP TABLE IF EXISTS `wc_screen`;
CREATE TABLE IF NO EXISTS `wc_screen` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(10) default NULL,
  `text` varchar(25) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_screen` VALUES (1,'800x600','800 на 600 пикселей');
INSERT INTO `wc_screen` VALUES (2,'1024x768','1024 на 768 пикселей');
INSERT INTO `wc_screen` VALUES (3,'640x480','640 на 480 пикселей');
INSERT INTO `wc_screen` VALUES (4,'1280x960','1280 на 960 пикселей');
INSERT INTO `wc_screen` VALUES (5,'720x480','720 на 480 пикселей');
INSERT INTO `wc_screen` VALUES (6,'1280x720','1280 на 720 пикселей');
INSERT INTO `wc_screen` VALUES (7,'1280x1024','1280 на1024 пикселей');
INSERT INTO `wc_screen` VALUES (8,'1600x900','1600 на 900 пикселей');
INSERT INTO `wc_screen` VALUES (9,'1600x1024','1600 на 1024 пикселей');
INSERT INTO `wc_screen` VALUES (10,'1600x1200','1600 на 1200 пикселей');

DROP TABLE IF EXISTS `wc_search`;
CREATE TABLE IF NO EXISTS `wc_search` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(30) default NULL,
  `value` varchar(20) default NULL,
  `text` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_search` VALUES (1,'yand','text=','Яндекс');
INSERT INTO `wc_search` VALUES (2,'google.','q=','Google');
INSERT INTO `wc_search` VALUES (3,'go.mail.ru','q=','Mail.ru');
INSERT INTO `wc_search` VALUES (4,'rambler','words=','Rambler');
INSERT INTO `wc_search` VALUES (5,'sm.aport','r=','Апорт');
INSERT INTO `wc_search` VALUES (6,'search.yahoo','p=','Yahoo');
INSERT INTO `wc_search` VALUES (7,'aolsearch','query=','AOL');
INSERT INTO `wc_search` VALUES (8,'rubrik.ru','phrase=','Rubrik');
INSERT INTO `wc_search` VALUES (9,'punto.ru','text=','Punto');

DROP TABLE IF EXISTS `wc_session`;
CREATE TABLE IF NO EXISTS `wc_session` (
  `id` int(10) NOT NULL auto_increment,
  `statid` int(10) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `page` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `wc_settings`;
CREATE TABLE IF NO EXISTS `wc_settings` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) TYPE=MyISAM;

INSERT INTO `wc_settings` VALUES (1,'graph','counter');
INSERT INTO `wc_settings` VALUES (2,'ttffont','tahoma');
INSERT INTO `wc_settings` VALUES (3,'linesh','3');
INSERT INTO `wc_settings` VALUES (4,'default','consolidated');
INSERT INTO `wc_settings` VALUES (5,'default_gr','b1');
INSERT INTO `wc_settings` VALUES (6,'gr_3d','1');
INSERT INTO `wc_settings` VALUES (7,'gr_w','500');
INSERT INTO `wc_settings` VALUES (8,'gr_h','300');
INSERT INTO `wc_settings` VALUES (9,'password','admin');
INSERT INTO `wc_settings` VALUES (10,'access','0');
INSERT INTO `wc_settings` VALUES (11,'url','http://www.site.ru/stat/');
INSERT INTO `wc_settings` VALUES (12,'per_page','5');
INSERT INTO `wc_settings` VALUES (13,'item_per_page','10');
INSERT INTO `wc_settings` VALUES (14,'export_period','none');
INSERT INTO `wc_settings` VALUES (15,'export_period_day','');
INSERT INTO `wc_settings` VALUES (16,'export_date','');
INSERT INTO `wc_settings` VALUES (17,'export_email','info@site.ru');
INSERT INTO `wc_settings` VALUES (18,'export_subject','Отчет посещаямости');
INSERT INTO `wc_settings` VALUES (19,'export_statistics','');
INSERT INTO `wc_settings` VALUES (20,'export_format','excel');