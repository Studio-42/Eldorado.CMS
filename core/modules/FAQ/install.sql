DROP TABLE IF EXISTS `el_faq_{pageID}_cat`;
CREATE TABLE IF NOT EXISTS `el_faq_{pageID}_cat` (
  `cid` tinyint(2) NOT NULL auto_increment,
  `cname` varchar(250) collate utf8_bin NOT NULL,
  `csort` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;
 
DROP TABLE IF EXISTS `el_faq_{pageID}`;
CREATE TABLE IF NOT EXISTS `el_faq_{pageID}` (
  `id` int(3) NOT NULL auto_increment,
  `cat_id` tinyint(2) NOT NULL,
  `question` mediumtext collate utf8_bin NOT NULL,
  `answer` mediumtext collate utf8_bin,
  `status` enum('0','1') collate utf8_bin NOT NULL default '0',
  `atime` int(11) NOT NULL,
  `qsort` tinyint(3) NOT NULL default '0',
  `email` varchar(100) collate utf8_bin NOT NULL,
  `notified` enum('0','1') collate utf8_bin NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cat_id` (`cat_id`),
  KEY `enabled` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;
