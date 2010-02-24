CREATE TABLE IF NOT EXISTS el_email (
	id tinyint(2) NOT NULL auto_increment,
	label varchar(50) NOT NULL,
	email varchar(75) NOT NULL,
	is_default enum('0', '1') NOT NULL DEFAULT '0',
	PRIMARY KEY(id)
);