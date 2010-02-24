
CREATE TABLE IF NOT EXISTS el_license (
  id     int(5)       NOT NULL auto_increment PRIMARY KEY,
	lkey   char(32)     NOT NULL,
	url    varchar(250) NOT NULL,
	expire int(11)      NOT NULL,
	KEY(lkey)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

CREATE TABLE IF NOT EXISTS el_userv_log (
  id     int            NOT NULL auto_increment,
  lkey   char(32)       NOT NULL,
  url    varchar(250)   NOT NULL,
	ip     char(15)       NOT NULL,
	act    enum('version', 'changelog', 'update') NOT NULL,
	is_ok  enum('1', '0') NOT NULL,
	error  text           NOT NULL,
	crtime int(11)        NOT NULL,
	debug  text           NOT NULL,
	PRIMARY KEY(id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
