DROP TABLE IF EXISTS `el_bb_{pageID}_cat`;
CREATE TABLE `el_bb_{pageID}_cat` (
	id int(2) NOT NULL auto_increment,
	name varchar(256) NOT NULL,
	descrip mediumtext,
	sort_ndx int(2) NOT NULL,
	PRIMARY KEY(id),
	KEY(sort_ndx)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `el_bb_{pageID}_item`;
CREATE TABLE `el_bb_{pageID}_item` (
	id int(5) NOT NULL auto_increment,
	cat_id int(2) NOT NULL,
	uid int(3) NOT NULL,
	author varchar(256) NOT NULL,
	title varchar(256) NOT NULL,
	content mediumtext,
	phone varchar(50),
	email varchar(100),
	crtime int(11) NOT NULL,
	published tinyint(1) NOT NULL default 0,
	PRIMARY KEY(id),
	KEY(cat_id),
	KEY(crtime),
	KEY(published)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `el_bb_{pageID}_floodcontrol`;
CREATE TABLE `el_bb_{pageID}_floodcontrol` (
	uid int(3) NOT NULL,
	ts int(11) NOT NULL,
	PRIMARY KEY(uid, ts)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;