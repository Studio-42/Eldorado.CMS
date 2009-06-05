CREATE TABLE IF NOT EXISTS `el_uplog` (
  `id`         int(10)                      unsigned NOT NULL auto_increment,
  `act`         enum('Upgrade', 'Downgrade') NOT NULL,
  `result`      enum('Success', 'Failed')    NOT NULL default 'Success',
  `version`     varchar(32)                  NOT NULL,
  `log`         mediumtext                   NOT NULL,
  `changelog`   text                         NOT NULL,
  `crtime`      int(11)                      NOT NULL,
  `backup_file` varchar(255)                 NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;