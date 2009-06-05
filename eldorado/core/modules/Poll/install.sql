DROP TABLE IF EXISTS el_poll_{pageID};

CREATE TABLE el_poll_{pageID} (
	id          int(3) unsigned auto_increment primary key,
	name        varchar(255) not null,
	descrip     mediumtext,
	begin_ts    int(11) unsigned not null,
	end_ts      int(11) unsigned not null,
	is_complete enum('0', '1') not null DEFAULT '0',
	key(begin_ts),
	key(end_ts),
    key(is_complete)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_poll_{pageID}_vars;

CREATE TABLE el_poll_{pageID}_vars (
	id       int(4) unsigned auto_increment primary key,
	poll_id  int(3) unsigned not null,
	name     varchar(255) not null,
    vote_num int not null default 0,
    prc      tinyint(3) NOT NULL default 0,
	key(poll_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS el_poll_{pageID}_vote;

CREATE TABLE el_poll_{pageID}_vote (
	var_id  int unsigned not null,
	sid     char(32) not null,
	primary key (var_id, sid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
