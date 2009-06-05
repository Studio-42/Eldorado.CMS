DROP TABLE IF EXISTS `el_news_{pageID}`;
CREATE TABLE `el_news_{pageID}` (
  `id`           int(3) NOT NULL auto_increment,
  `title`        varchar(250) NOT NULL default '',
  `altername`    varchar(250) NOT NULL default '',
  `announce`     text NOT NULL,
  `content`      text NOT NULL,
  `published`    int(11) NOT NULL default '0',
  `export_param` varchar(10),
  PRIMARY KEY  (`id`),
  KEY (`published`)
) TYPE=MyISAM;
    