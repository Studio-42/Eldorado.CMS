DROP TABLE IF EXISTS `el_fa_{pageID}_cat`;
CREATE TABLE IF NOT EXISTS `el_fa_{pageID}_cat` (
  `id` int(3) NOT NULL auto_increment,
  `_left` int(3) NOT NULL default '0',
  `_right` int(3) NOT NULL default '0',
  `level` tinyint(1) NOT NULL default '1',
  `name` varchar(250) NOT NULL default '',
  `descrip` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `_left` (`_left`,`_right`,`level`)
); 
INSERT INTO el_fa_{pageID}_cat (_left, _right, level, name) VALUES (1,2,0, '{pageName}');

DROP TABLE IF EXISTS `el_fa_{pageID}_item`;
CREATE TABLE IF NOT EXISTS `el_fa_{pageID}_item` (
  `id` int(3) NOT NULL auto_increment,
  `parent_id` int(3) NOT NULL default '1',
  `name` varchar(250) NOT NULL default '',
  `altername` varchar(350) NOT NULL,
  `f_url` varchar(250) NOT NULL default '',
  `descrip` mediumtext NOT NULL,
  `f_size` double(9,2) NOT NULL default '0',
  `cnt` int(3) NOT NULL default '0',
  `mtime` int(11) NOT NULL default '0',
  `ltd` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);
DROP TABLE IF EXISTS el_fa_{pageID}_i2c;
CREATE TABLE el_fa_{pageID}_i2c (
  i_id int(3) NOT NULL default '0',
  c_id int(3) NOT NULL default '0',
  sort_ndx int(3) unsigned NOT NULL default '0',
 PRIMARY KEY(i_id, c_id)
);
