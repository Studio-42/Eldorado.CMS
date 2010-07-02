DROP TABLE IF EXISTS el_ig_{pageID}_gallery ;
CREATE TABLE el_ig_{pageID}_gallery (
g_id        tinyint(3) NOT NULL auto_increment,
g_name      varchar(255) NOT NULL,
g_comment   mediumtext NOT NULL,
g_sort_ndx  tinyint(3) NOT NULL DEFAULT 0,
g_crtime    int(11) NOT NULL,
g_mtime     int(11) NOT NULL,
PRIMARY KEY (g_id),
KEY         (g_sort_ndx)
);
DROP TABLE IF EXISTS el_ig_{pageID}_image ;
CREATE TABLE el_ig_{pageID}_image (
i_id         int(3) NOT NULL auto_increment,
i_gal_id     tinyint(3) NOT NULL,
i_file       varchar(255) NOT NULL,
i_file_size  int(7) NOT NULL,
i_name       varchar(255) NOT NULL,
i_comment    varchar(255) NOT NULL,
i_width      int(4) NOT NULL DEFAULT 0,
i_height     int(4) NOT NULL DEFAULT 0,
i_width_tmb  int(3) NOT NULL DEFAULT 0,
i_height_tmb int(3) NOT NULL DEFAULT 0,
i_sort_ndx   tinyint(3) NOT NULL DEFAULT 0,
i_crtime     int(11) NOT NULL,
i_mtime      int(11) NOT NULL,
PRIMARY KEY  (i_id),
KEY          (i_gal_id),
KEY          (i_sort_ndx)
);

INSERT INTO el_ig_{pageID}_gallery (g_name, g_comment, g_sort_ndx, g_crtime, g_mtime)
VALUES ('Album 1', '', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())

