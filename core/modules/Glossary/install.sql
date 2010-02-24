DROP TABLE IF EXISTS el_gloss_{pageID};

CREATE TABLE el_gloss_{pageID} (
	id int unsigned auto_increment primary key,
	word varchar(255) not null,
	descr mediumtext,
	url varchar(255) not null
);
