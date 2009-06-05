DROP TABLE IF EXISTS el_mail_form_{pageID};
CREATE TABLE el_mail_form_{pageID} (
	fid      tinyint(2)     NOT NULL auto_increment,
	flabel   varchar(255)   NOT NULL,
	ftype    enum('comment', 'subtitle', 'text', 'textarea', 'select', 'checkbox', 'radio', 'date', 'file', 'captcha') NOT NULL default 'comment',
	fvalue   text           NOT NULL,
	fopts    varchar(255)   NOT NULL,
	fchecked enum('0', '1') NOT NULL default '0',
	fvalid   enum('none', 'noempty', 'email', 'url', 'phone', 'numbers', 'letters_or_space') NOT NULL default 'none',
	fsize    int(3)         NOT NULL default 1,
	ferror   varchar(255)   NOT NULL default '',
	fsort    tinyint(3)     NOT NULL default 0,
	PRIMARY KEY(fid)
);