DROP TABLE IF EXISTS el_plugin;
CREATE TABLE el_plugin (
id tinyint(2) NOT NULL auto_increment,
name char(25) NOT NULL,
descrip char(200) NOT NULL,
is_on enum('0', '1') NOT NULL DEFAULT '1',
conf enum('0', '1') NOT NULL DEFAULT '1',
PRIMARY KEY(id),
KEY(is_on)
);

DROP TABLE IF EXISTS el_plugin_on_page;
CREATE TABLE el_plugin_on_page (
pl_id tinyint(2) NOT NULL,
page_id int(3) NOT NULL,
PRIMARY KEY(pl_id, page_id)
);