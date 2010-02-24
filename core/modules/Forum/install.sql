DROP TABLE IF EXISTS `el_forum_cat`;
CREATE TABLE IF NOT EXISTS `el_forum_cat` (
	`id`           tinyint(4) NOT NULL auto_increment,
	`_left`        int(3) NOT NULL default '0',
	`_right`       int(3) NOT NULL default '0',
	`level`        tinyint(2) NOT NULL default '1',
	`name`         varchar(250) NOT NULL default '',
	`descrip`      mediumtext NOT NULL,
	`num_topics`   mediumint(8) NOT NULL default 0,
	`num_posts`    mediumint(8) NOT NULL default 0,
	`last_post_id` int(10) NOT NULL default 0,
	`allow_posts`  tinyint(1) NOT NULL default 1,
	`count_posts`  tinyint(1) NOT NULL default 1,
	PRIMARY KEY  (`id`),
	KEY `_left` (`_left`,`_right`,`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;
INSERT INTO `el_forum_cat` (id, _left, _right, level, name) VALUES (1, 1, 2, 0, '{pageName}');

DROP TABLE IF EXISTS `el_forum_topic`;
CREATE TABLE IF NOT EXISTS `el_forum_topic` (
	`id`             mediumint(8) NOT NULL auto_increment,
	`cat_id`         tinyint(4) NOT NULL default 1,
	`first_post_id`  int(10) NOT NULL,
	`last_post_id`   int(10) NOT NULL,
	`num_replies`    int(10) NOT NULL default 0,
	`num_views`      int(10) NOT NULL default 0,
	`sticky`         tinyint(1) NOT NULL default 0,
	`locked`         tinyint(1) NOT NULL default 0,
	PRIMARY KEY(`id`),
	KEY (`cat_id`),
	KEY (`sticky`),
	KEY (`first_post_id`),
	KEY (`last_post_id`)
	
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_forum_post`;
CREATE TABLE IF NOT EXISTS `el_forum_post` (
  `id`            int(10) NOT NULL auto_increment,
  `cat_id`        tinyint(4) NOT NULL default '1',
  `topic_id`      mediumint(8) NOT NULL,
  `crtime`        int(11) NOT NULL default '0',
  `mtime`         int(11) NOT NULL default '0',
  `author_uid`    int(3) NOT NULL,
  `author_name`   varchar(256) collate utf8_bin default NULL,
  `author_email`  varchar(256) collate utf8_bin default NULL,
  `author_ip`     varchar(15) collate utf8_bin default NULL,
  `modified_uid`  int(3) NOT NULL,
  `modified_name` varchar(256) collate utf8_bin default NULL,
  `subject`       tinytext collate utf8_bin,
  `message`       text collate utf8_bin,
  `ico`           varchar(75) collate utf8_bin NOT NULL default 'default.png',
  `smiley_enabled` tinyint(1) NOT NULL default 1,
  PRIMARY KEY  (`id`),
  KEY `cat_id` (`cat_id`),
  KEY `topic_id` (`topic_id`),
  KEY `author_uid` (`author_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `el_forum_attach`;
CREATE TABLE IF NOT EXISTS `el_forum_attach` (
  `id` int(10) NOT NULL auto_increment,
  `post_id`   int(10) NOT NULL,
  `filename`  varchar(255) collate utf8_bin NOT NULL,
  `is_img`    tinyint(1) NOT NULL default '0',
  `img_w`     mediumint(8) NOT NULL,
  `img_h`     mediumint(8) NOT NULL,
  `size`      int(10) NOT NULL,
  `downloads` int(10) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `el_forum_moderator`;
CREATE TABLE IF NOT EXISTS `el_forum_moderator` (
  `cat_id` tinyint(4) NOT NULL default '1',
  `uid`    int(5) NOT NULL,
  `rid`    tinyint(2) NOT NULL default '8',
  PRIMARY KEY  (`cat_id`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_forum_log_read_forum`;
CREATE TABLE IF NOT EXISTS `el_forum_log_read_forum` (
	`uid`    int(5) NOT NULL,
	`cat_id` tinyint(4) NOT NULL,
	`lvt`    int(11) NOT NULL,
	PRIMARY KEY(`uid`, `cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `el_forum_log_read_topic`;
CREATE TABLE IF NOT EXISTS `el_forum_log_read_topic` (
	`uid`     int(5) NOT NULL,
	`t_id`    mediumint(8) NOT NULL,
	`post_id` int(10) NOT NULL default 0,
	PRIMARY KEY(`uid`, `t_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

	
DROP TABLE IF EXISTS `el_forum_role`;
CREATE TABLE IF NOT EXISTS `el_forum_role` (
	`id`   tinyint(2) NOT NULL auto_increment,
	`name` varchar(25) NOT NULL,
	PRIMARY KEY(`id`),
	UNIQUE (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO `el_forum_role` (id, name) VALUES 
	(1,  'Guest'), 
	(2,  'Reader'), 
	(3,  'Commentator'), 
	(4,  'Jr. Author'),
	(5,  'Author'),
	(6,  'Sr. Author'),
	(7,  'Expert Author'),
	(8,  'Jr. Moderator'),
	(9,  'Moderator')
	;

DROP TABLE IF EXISTS `el_forum_role_action`;
CREATE TABLE IF NOT EXISTS `el_forum_role_action` (
	`rid`    tinyint(2) NOT NULL,
	`action` varchar(50) NOT NULL,
	PRIMARY KEY(`rid`, `action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO `el_forum_role_action` (rid, action) VALUES 
	(1, 'view'),
	
	(2, 'view'),
	(2, 'attach_view'),
	
	(3, 'view'),
	(3, 'attach_view'),
	(3, 'post_reply'),
	(3, 'profile_view'),
	
	(4, 'view'),
	(4, 'attach_view'),
	(4, 'profile_view'),
	(4, 'pm_send'),
	(4, 'post_reply'),
	(4, 'post_new'),
	
	(5, 'view'),
	(5, 'attach_view'),
	(5, 'profile_view'),
	(5, 'pm_send'),
	(5, 'attach'),
	(5, 'post_reply'),
	(5, 'post_new'),
	(5, 'post_edit_own'),
	
	(6, 'view'),
	(6, 'attach_view'),
	(6, 'profile_view'),
	(6, 'pm_send'),
	(6, 'attach'),
	(6, 'post_reply'),
	(6, 'post_new'),
	(6, 'post_edit_own'),
	(6, 'topic_lock_own'),
	
	(7, 'view'),
	(7, 'attach_view'),
	(7, 'profile_view'),
	(7, 'pm_send'),
	(7, 'attach'),
	(7, 'post_reply'),
	(7, 'post_new'),
	(7, 'post_edit_own'),
	(7, 'post_rm_own'),
	(7, 'topic_lock_own'),
	(7, 'topic_sticky_own'),
	
	(8, 'view'),
	(8, 'attach_view'),
	(8, 'profile_view'),
	(8, 'pm_send'),
	(8, 'attach'),
	(8, 'attach_edit_any'),
	(8, 'post_reply'),
	(8, 'post_new'),
	(8, 'post_edit_own'),
	(8, 'post_edit_any'),	
	(8, 'post_rm_own'),
	(8, 'post_rm_any'),	
	(8, 'topic_lock_own'),
	(8, 'topic_lock_any'),	
	(8, 'topic_sticky_own'),
	(8, 'topic_sticky_any'),	
	
	(9, 'view'),
	(9, 'attach_view'),
	(9, 'profile_view'),
	(9, 'profile_edit_any'),
	(9, 'pm_send'),
	(9, 'attach'),
	(9, 'attach_edit_any'),
	(9, 'post_reply'),
	(9, 'post_new'),
	(9, 'post_edit_own'),
	(9, 'post_edit_any'),	
	(9, 'post_rm_own'),
	(9, 'post_rm_any'),	
	(9, 'topic_lock_own'),
	(9, 'topic_lock_any'),	
	(9, 'topic_sticky_own'),
	(9, 'topic_sticky_any'),
	(9, 'topic_rm'),
	(9, 'topic_move')
	;

DROP TABLE IF EXISTS `el_forum_rbac`;
CREATE TABLE `el_forum_rbac` (
	`cat_id` tinyint(2) NOT NULL,
	`gid`    tinyint(3) NOT NULL,
	`rid`    tinyint(2) NOT NULL,
	PRIMARY KEY(`cat_id`, `gid`, `rid`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO `el_forum_rbac` (cat_id, gid, rid) VALUES (1, -1, 1), (1, 0, 4);

DROP TABLE IF EXISTS `el_forum_floodcontrol`;
CREATE TABLE `el_forum_floodcontrol` (
	`ip` char(16) NOT NULL,
	`ts` int(11) NOT NULL,
	PRIMARY KEY(`ip`, `ts`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
	
	
	