DROP TABLE IF EXISTS el_lcat_{pageID}_cat;
CREATE TABLE el_lcat_{pageID}_cat (
  id int(3) NOT NULL auto_increment,
  _left int(3) NOT NULL,  
  _right int(3) NOT NULL,
  level tinyint(1) NOT NULL default '1',
  name varchar(250) NOT NULL,
  descrip mediumtext NOT NULL,
  PRIMARY KEY(id),
  KEY(_left,_right,level)
);
INSERT INTO el_lcat_{pageID}_cat (_left, _right, level, name) VALUES (1,2,0, '{pageName}');

DROP TABLE IF EXISTS el_lcat_{pageID}_item;
CREATE TABLE el_lcat_{pageID}_item (
  id int(3) NOT NULL auto_increment,
  name varchar(250) NOT NULL,
  content mediumtext not null,
  url varchar(150) NOT NULL,
  crtime int(11)  UNSIGNED NOT NULL default '0',
  primary KEY(id),
  KEY(crtime)
);

DROP TABLE IF EXISTS el_lcat_{pageID}_i2c;
CREATE TABLE el_lcat_{pageID}_i2c (
  i_id int(3) NOT NULL default '0',
  c_id int(3) NOT NULL default '0',
  sort_ndx int(3) unsigned NOT NULL default '0',
 PRIMARY KEY(i_id, c_id)
);


