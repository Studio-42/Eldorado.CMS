DROP TABLE IF EXISTS el_ig_{pageID}_gallery ;
CREATE TABLE el_ig_{pageID}_gallery (
g_id        tinyint(3) NOT NULL auto_increment,
g_name      varchar(150) NOT NULL,
g_comment   varchar(255) NOT NULL,
g_sort_ndx  tinyint(3) NOT NULL DEFAULT 0,
g_crtime    int(11) NOT NULL,
g_mtime     int(11) NOT NULL,
PRIMARY KEY (g_id),
KEY         (g_crtime),
KEY         (g_mtime),
KEY         (g_sort_ndx)
);
DROP TABLE IF EXISTS el_ig_{pageID}_image ;
CREATE TABLE el_ig_{pageID}_image (
i_id         int(3) NOT NULL auto_increment,
i_gal_id     tinyint(3) NOT NULL,
i_file       varchar(150) NOT NULL,
i_file_size  int(7) NOT NULL,
i_name       varchar(150) NOT NULL,
i_comment    varchar(255) NOT NULL,
i_width_0      int(4) NOT NULL DEFAULT 800,
i_height_0     int(4) NOT NULL DEFAULT 600,
i_width_1      int(4) NOT NULL DEFAULT 640,
i_height_1     int(4) NOT NULL DEFAULT 480,
i_width_2      int(4) NOT NULL DEFAULT 800,
i_height_2     int(4) NOT NULL DEFAULT 600,
i_width_3      int(4) NOT NULL DEFAULT 1024,
i_height_3     int(4) NOT NULL DEFAULT 864,
i_width_4      int(4) NOT NULL DEFAULT 1280,
i_height_4     int(4) NOT NULL DEFAULT 1024,
i_width_5      int(4) NOT NULL DEFAULT 1400,
i_height_5     int(4) NOT NULL DEFAULT 1200,

i_width_tmb  int(3) NOT NULL DEFAULT 120,
i_height_tmb int(3) NOT NULL DEFAULT 100,
i_sort_ndx   tinyint(3) NOT NULL DEFAULT 0,
i_crtime     int(11) NOT NULL,
i_mtime      int(11) NOT NULL,
PRIMARY KEY  (i_id),
KEY          (i_gal_id),
KEY          (i_file),
KEY          (i_file_size),
KEY          (i_crtime),
KEY          (i_mtime),
KEY          (i_sort_ndx)
);
