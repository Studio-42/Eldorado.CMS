DROP TABLE IF EXISTS el_ishop_{pageID}_cat;
CREATE TABLE el_ishop_{pageID}_cat (
	id      int(3)       NOT NULL auto_increment,
	_left   int(3)       NOT NULL,
	_right  int(3)       NOT NULL,
	level   tinyint(1)   NOT NULL default '1',
	name    varchar(250) NOT NULL,
	descrip mediumtext   NOT NULL,
	PRIMARY KEY(id),
	KEY(_left,_right,level)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO el_ishop_{pageID}_cat (_left, _right, level, name) VALUES (1,2,0, '{pageName}');

CREATE TABLE IF NOT EXISTS el_ishop_{pageID}_gallery (
  id   int(11)      NOT NULL AUTO_INCREMENT,
  i_id int(11)      NOT NULL,
  img  varchar(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Image gallery for IShopItem';

DROP TABLE IF EXISTS el_ishop_{pageID}_i2c;
CREATE TABLE el_ishop_{pageID}_i2c (
  i_id     int(3)          NOT NULL default '0',
  c_id     int(3)          NOT NULL default '0',
  sort_ndx int(3) unsigned NOT NULL default '0',
 PRIMARY KEY(i_id, c_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_item;
CREATE TABLE el_ishop_{pageID}_item (
	id        int(5)       NOT NULL auto_increment,
	type_id   tinyint(3)   NOT NULL,
	mnf_id    tinyint(3)   NOT NULL default '0',
	tm_id     tinyint(3)   NOT NULL default '0',
	code      varchar(255) NOT NULL,
	name      varchar(255) NOT NULL,
	altername varchar(350) NOT NULL,
	img       varchar(255) NOT NULL,
	announce  text         NOT NULL,
	content   mediumtext   NOT NULL,
	price     double(12,2) NOT NULL,
    crtime    int(11)      unsigned NOT NULL default '0',
    mtime     int(11)      unsigned NOT NULL default '0',
	primary KEY(id),
	KEY(type_id),
	KEY(mnf_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_itype;
CREATE TABLE el_ishop_{pageID}_itype (
  id     tinyint(3)   NOT NULL auto_increment,
  name   varchar(255) NOT NULL,
  crtime int(11)      unsigned NOT NULL default '0',
  mtime  int(11)      unsigned NOT NULL default '0',
  primary KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO el_ishop_{pageID}_itype (name) VALUES ("Default item");

DROP TABLE IF EXISTS el_ishop_{pageID}_prop;
CREATE TABLE el_ishop_{pageID}_prop (
  id            int(5)         NOT NULL auto_increment,
  t_id          tinyint(3)     NOT NULL,
  depend_id     int(5)         NOT NULL,
  type          enum('1', '2', '3', '4') NOT NULL default '1',
  name          varchar(255)   NOT NULL,
  display_pos   enum('top', 'middle', 'table', 'bottom') NOT NULL default 'middle',
  display_name  enum('0', '1') NOT NULL default 1,
  is_hidden     enum('0', '1') NOT NULL default 0,
  is_announced  enum('0', '1') NOT NULL default 0,
  is_searched   enum('0', '1') NOT NULL default 0,
  is_compared   enum('0', '1') NOT NULL default 0,
  sort_ndx      int(3)         NOT NULL default 0,
  PRIMARY KEY(id),
  KEY(t_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_prop_value;
CREATE TABLE el_ishop_{pageID}_prop_value (
  id         int(10)        NOT NULL auto_increment,
  p_id       int(5)         NOT NULL,
  value      text           NOT NULL,
  is_default enum('0', '1') NOT NULL default '0',
  PRIMARY KEY(id),
  KEY(p_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS el_ishop_{pageID}_p2i;
CREATE TABLE el_ishop_{pageID}_p2i (
  id    int(10) NOT NULL auto_increment,
  i_id  int(5)  NOT NULL,
  p_id  int(5)  NOT NULL,
  value text,
  pv_id int(10) NOT NULL,
  PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_prop_depend;
CREATE TABLE el_ishop_{pageID}_prop_depend (
  id      int(10)  NOT NULL auto_increment,
  m_id    int(5)   NOT NULL,
  m_value int(10)  NOT NULL,
  s_id    int(5)   NOT NULL,
  s_value int(10)  NOT NULL,
  PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_mnf;
CREATE TABLE el_ishop_{pageID}_mnf (
  id       int(3)       NOT NULL auto_increment,
  name     varchar(255) NOT NULL,
  country  varchar(255),
  logo     varchar(255) NOT NULL,
  announce text   NOT NULL,
  content  text   NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_tm;
CREATE TABLE el_ishop_{pageID}_tm (
  id              tinyint(3)   NOT NULL auto_increment,
  mnf_id          int(3)       NOT NULL,
  name            varchar(255) NOT NULL,
  announce text   NOT NULL,
  content  text   NOT NULL,
  PRIMARY KEY (id),
  KEY (mnf_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_search;
CREATE TABLE         el_ishop_{pageID}_search (
    id       tinyint(2)   NOT NULL auto_increment,
    label    varchar(255) NOT NULL,
    sort_ndx tinyint(2)   NOT NULL,
    PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_st;
CREATE TABLE         el_ishop_{pageID}_st (
    s_id tinyint(2) NOT NULL,
    t_id tinyint(3) NOT NULL,
    PRIMARY KEY(s_id, t_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_se;
CREATE TABLE         el_ishop_{pageID}_se (
    id          tinyint(3)   NOT NULL auto_increment,
    s_id        tinyint(2)   NOT NULL,
    label       varchar(255) NOT NULL,
    source      enum('mnftm', 'price', 'name', 'prop') NOT NULL default 'name',
    sort_ndx    tinyint(3)   NOT NULL,
    conf        varchar(255) NOT NULL,
    def_val     varchar(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_ishop_{pageID}_sp;
CREATE TABLE         el_ishop_{pageID}_sp (
    e_id tinyint(3) NOT NULL,
    p_id int(5)     NOT NULL,
    PRIMARY KEY(e_id, p_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

