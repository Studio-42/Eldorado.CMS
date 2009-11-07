DROP TABLE IF EXISTS `el_techshop_{pageID}_cat`;
CREATE TABLE `el_techshop_{pageID}_cat` (
  `id`      int(3)        NOT NULL auto_increment,
  `_left`   int(3)        NOT NULL default '0',
  `_right`  int(3)        NOT NULL default '0',
  `level`   tinyint(2)    NOT NULL default '1',
  `name`    varchar(255)  NOT NULL default '',
  `descrip` mediumtext    NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `_left` (`_left`,`_right`,`level`)
) TYPE=MyISAM ;
INSERT INTO `el_techshop_{pageID}_cat` (_left, _right, level, name) VALUES (1,2,0, '{pageName}');

DROP TABLE IF EXISTS `el_techshop_{pageID}_item`;
CREATE TABLE `el_techshop_{pageID}_item` (
  `id`       int(3)       NOT NULL auto_increment,
  `mnf_id`   int(2)       NOT NULL default '0',
  `code`     varchar(30)  NOT NULL default '',
  `name`     varchar(150) NOT NULL default '',
  `announce` mediumtext   NOT NULL,
  `descrip`  mediumtext   NOT NULL,
  `price`    double(12,2)  NOT NULL,
  `crtime`    int(11)     NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY (`mnf_id`),
  KEY (`code`)
) TYPE=MyISAM ;

DROP TABLE IF EXISTS `el_techshop_{pageID}_model`;
CREATE TABLE `el_techshop_{pageID}_model` (
  `id`       int(4)       NOT NULL auto_increment,
  `i_id`     int(3)       NOT NULL default '0',
  `code`     varchar(75)  NOT NULL default '',
  `name`     varchar(255) NOT NULL default '',
  `price`    double(12,2)  NOT NULL,
  `descrip`  tinytext,
  `img`      varchar(250)  NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY (`code`),
  KEY (`i_id`)
) TYPE=MyISAM ;

DROP TABLE IF EXISTS `el_techshop_{pageID}_manufact`;
CREATE TABLE `el_techshop_{pageID}_manufact` (
  `id`            int(3)       NOT NULL auto_increment,
  `name`          varchar(255) NOT NULL default '',
  `country`       varchar(75)  NOT NULL default '',
  `logo_img`      varchar(50)  default NULL,
  `logo_img_mini` varchar(50)  default NULL,
  `announce`      mediumtext   NOT NULL,
  `descrip`       mediumtext   NOT NULL,
  `sort_ndx`      int(3)       NOT NULL default '0',
  `url`           varchar(250) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY (`sort_ndx`)
) TYPE=MyISAM;



DROP TABLE IF EXISTS `el_techshop_{pageID}_i2c`;
CREATE TABLE `el_techshop_{pageID}_i2c` (
  `i_id`     int(3) NOT NULL default '0',
  `c_id`     int(3) NOT NULL default '0',
  `sort_ndx` int(3) NOT NULL default '0',
  PRIMARY KEY  (`i_id`,`c_id`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `el_techshop_{pageID}_ft_group`;
CREATE TABLE `el_techshop_{pageID}_ft_group` (
  `id`    int(3)       NOT NULL auto_increment,
  `name`  varchar(250) NOT NULL default '',
  `sort_ndx` int(3)       NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY (`sort_ndx`)
) TYPE=MyISAM;

DROP TABLE IF EXISTS `el_techshop_{pageID}_feature`;
CREATE TABLE `el_techshop_{pageID}_feature` (
  `id`    int(3)       NOT NULL auto_increment,
  `gid`   int(3)       NOT NULL default '0',
  `name`  varchar(250) NOT NULL default '',
  `unit`  varchar(10)  default NULL,
  `sort_ndx` int(3)    NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY (`gid`),
  KEY (`sort_ndx`)
) TYPE=MyISAM ;

DROP TABLE IF EXISTS `el_techshop_{pageID}_ft2i`;
CREATE TABLE `el_techshop_{pageID}_ft2i` (
  `i_id`         int(3)         NOT NULL default '0',
  `ft_id`        int(3)         NOT NULL default '0',
  `value`        varchar(150)   NOT NULL default '',
	`is_announced` enum('0', '1') NOT NULL default '0',
    `is_split`     enum('0', '1') NOT NULL default '0',
  PRIMARY KEY  (`i_id`,`ft_id`)
) TYPE=MyISAM;


DROP TABLE IF EXISTS `el_techshop_{pageID}_ft2m`;
CREATE TABLE `el_techshop_{pageID}_ft2m` (
  `m_id`         int(3)         NOT NULL default '0',
  `ft_id`        int(3)         NOT NULL default '0',
  `value`        varchar(150)   NOT NULL default '',
  `is_announced` enum('0', '1') NOT NULL default '0',
  `is_split`     enum('0', '1') NOT NULL default '0',
  PRIMARY KEY  (`m_id`,`ft_id`)
) TYPE=MyISAM;
