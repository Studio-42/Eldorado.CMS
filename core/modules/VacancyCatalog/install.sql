DROP TABLE IF EXISTS el_vaccat_{pageID}_cat;
CREATE TABLE el_vaccat_{pageID}_cat (
	id      int(3)       NOT NULL auto_increment,
	_left   int(3)       NOT NULL,
	_right  int(3)       NOT NULL,
	level   tinyint(1)   NOT NULL default '1',
	name    varchar(250) NOT NULL,
	descrip mediumtext   NOT NULL,
	PRIMARY KEY(id),
	KEY(_left,_right,level)
);
INSERT INTO el_vaccat_{pageID}_cat (_left, _right, level, name) VALUES (1,2,0, '{pageName}');

DROP TABLE IF EXISTS el_vaccat_{pageID}_item;
CREATE TABLE el_vaccat_{pageID}_item (
	id      int(3)       NOT NULL auto_increment,
	name    varchar(250) NOT NULL,
	altername varchar(350) NOT NULL,
	descrip mediumtext   not null,
	req     mediumtext   not null,
	func    mediumtext   not null,
	cond    mediumtext   not null,
	salary  varchar(255) not null,
  crtime  int(11)      unsigned NOT NULL default '0',
	primary KEY(id)
);
DROP TABLE IF EXISTS el_vaccat_{pageID}_i2c;
CREATE TABLE el_vaccat_{pageID}_i2c (
  i_id     int(3) NOT NULL default '0',
  c_id     int(3) NOT NULL default '0',
  sort_ndx int(3) unsigned NOT NULL default '0',
 PRIMARY KEY(i_id, c_id)
);
DROP TABLE IF EXISTS el_vaccat_{pageID}_form;
CREATE TABLE el_vaccat_{pageID}_form (
	fid tinyint(2) NOT NULL auto_increment,
	flabel varchar(255) NOT NULL,
	ftype enum('comment', 'subtitle','text', 'textarea', 'select', 'checkbox', 'radio', 'date', 'file', 'captcha') NOT NULL default 'comment',
	fvalue varchar(255) NOT NULL,
	fopts varchar(255) NOT NULL,
	fchecked enum('0', '1') NOT NULL default '0',
	fvalid enum('none', 'noempty', 'email', 'url', 'phone', 'numbers', 'letters_or_space') NOT NULL default 'none',
        fsize    int(3)         NOT NULL default 1,
	ferror varchar(255) NOT NULL default '',
	fsort tinyint(2) NOT NULL default 0,
	PRIMARY KEY(fid)
);