CREATE TABLE IF NOT EXISTS `el_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `object` varchar(256) COLLATE utf8_bin NOT NULL,
  `action` varchar(256) COLLATE utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  `link` varchar(256) COLLATE utf8_bin NOT NULL,
  `value` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

