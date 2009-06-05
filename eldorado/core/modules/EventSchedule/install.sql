DROP TABLE IF EXISTS el_event_{pageID};
CREATE TABLE el_event_{pageID} (
	id int(3) not null auto_increment,
	name varchar(250) not null,
	announce mediumtext not null,
	content text not null,
	place varchar(100),
	begin_ts int(11) not null,
	end_ts int(11) not null,
	export_param varchar(20),
	primary key(id),
	key(begin_ts),
	key(end_ts),
	key(export_param)
);
