SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

INSERT INTO `el_email` (`id`, `label`, `email`, `is_default`) VALUES
(1, 'admin', 'admin@eldorado-cms.ru', '1');

INSERT INTO `el_group` (`gid`, `name`, `perm`, `mtime`) VALUES
(1, 'root', 8, UNIX_TIMESTAMP()),
(2, 'guests', 0, UNIX_TIMESTAMP());

INSERT INTO `el_menu` (`id`, `name`, `name_alt`, `page_descrip`, `dir`, `_left`, `_right`, `level`, `module`, `visible`, `visible_limit`, `perm`, `is_menu`, `redirect_url`, `ico_main`, `ico_add_menu_top`, `ico_add_menu_bot`, `in_add_menu_top`, `in_add_menu_bot`, `alt_tpl`) VALUES
(1, '--', '', '', '', 1, 26, 0, 'Container', '2', '0', '1', '0', '', '', '', '', '0', '0', ''),
(2, 'Home', '', '', 'home', 2, 3, 1, 'SimplePage', '2', '0', '1', '0', '', 'home.png', 'home.png', 'home.png', '0', '0', ''),
(3, 'Feedback', '', '', 'feedback', 4, 5, 1, 'Mailer', '2', '0', '1', '0', '', 'mail.png', 'mail.png', 'mail.png', '0', '0', '0'),
(4, 'Control Centre', '', '', 'cc', 6, 25, 1, 'Container', '2', '0', '0', '0', '', 'default.png', 'default.png', 'default.png', '0', '0', ''),
(5, 'Users/Groups', '', '', 'users', 7, 8, 2, 'UsersControl', '2', '0', '0', '0', '', 'users.gif', 'default.png', 'default.png', '0', '0', '0'),
(6, 'Navigation Control', '', '', 'menu', 9, 10, 2, 'NavigationControl', '2', '0', '1', '0', '', 'nav.gif', 'default.png', 'default.png', '0', '0', ''),
(7, 'Site Settings', '', '', 'site_conf', 11, 12, 2, 'SiteControl', '2', '0', '0', '0', '', 'options.gif', 'default.png', 'default.png', '0', '0', ''),
(8, 'Files', '', '', 'fm', 13, 14, 2, 'Finder', '2', '0', '0', '0', '', 'files.gif', 'default.png', 'default.png', '0', '0', ''),
(9, 'Plugins', '', '', 'plc', 15, 16, 2, 'PluginsControl', '2', '0', '0', '0', '', 'modules.gif', 'default.png', 'default.png', '0', '0', ''),
(10, 'Backup', '', '', 'backup', 17, 18, 2, 'SiteBackup', '2', '0', '0', '0', '', 'backup.gif', 'default.png', 'default.png', '0', '0', ''),
(11, 'Sitemap XML', '', '', 'sitemap', 19, 20, 2, 'SitemapGenerator', '2', '0', '0', '0', '', 'map.gif', 'default.png', 'default.png', '0', '0', ''),
(12, 'System Update', '', '', 'update', 21, 22, 2, 'UpdateClient', '2', '0', '0', '0', '', 'updates.gif', 'default.png', 'default.png', '0', '0', ''),
(13, 'Template Editor', '', '', 'tpl', 23, 24, 2, 'TemplatesEditor', '2', '0', '0', '0', '', 'tpl-editor.gif', 'default.png', 'default.png', '0', '0', '');

INSERT INTO `el_module` (`module`, `descrip`, `multi`, `search`) VALUES
('ActionLog', 'Action Logger', '0', '0'),
('Container', 'Container', '1', '0'),
('Directory', 'Directory', '0', '0'),
('DocsCatalog', 'Documents Catalogue', '1', '1'),
('EventSchedule', 'Events Shedule', '1', '0'),
('FAQ', 'FAQ', '1', '0'),
('FileArchive', 'File Archive', '1', '1'),
('Finder', 'File Manager', '1', '0'),
('Forum', 'Forum', '0', '1'),
('GAStat', 'Statistics', '0', '0'),
('Glossary', 'Glossary', '1', '0'),
('GoodsCatalog', 'Goods Catalogue', '1', '1'),
('ICartConf', 'Cart/Order Configuration', '0', '0'),
('IShop', 'Internet Shop', '1', '1'),
('ImageGalleries', 'Image Gallery', '1', '0'),
('LinksCatalog', 'Link Catalogue', '1', '1'),
('MailFormator', 'Mail Forms', '1', '0'),
('Mailer', 'Send e-mail', '1', '0'),
('NavigationControl', 'Navigation Control', '0', '0'),
('News', 'News', '1', '1'),
('OrderHistory', 'Order History', '0', '0'),
('PluginsControl', 'Plugins', '0', '0'),
('Poll', 'Poll', '1', '0'),
('SimplePage', 'Simple Page', '1', '1'),
('SiteBackup', 'Backup', '0', '0'),
('SiteControl', 'Site Settings', '0', '0'),
('SiteMap', 'Sitemap', '1', '0'),
('SitemapGenerator', 'XML Sitemap generator', '0', '0'),
('TechShop', 'Tech Shop', '1', '1'),
('TemplatesEditor', 'Template Editor', '0', '0'),
('UpdateClient', 'Update', '0', '0'),
('UsersControl', 'Users Control', '0', '0'),
('VacancyCatalog', 'Vacancy Catalogue', '1', '1');

INSERT INTO `el_page` (`id`, `content`) VALUES
(1, '');

INSERT INTO `el_plugin` (`name`, `label`, `descrip`, `is_on`, `status`) VALUES
('Calculator',   'Calculator',     '', '0', 'off'),
('InfoBlock',    'Info blocks',    'Show info blocks on selected pages', '1', 'on'),
('NewsTopics',   'News Headlines', 'Show news headlines', '0', 'off'),
('Poll',         'Poll',           'Show poll', '1', 'off'),
('RandomImage',  'Random Image',   'Show randmon images from gallery', '0', 'off'),
('SpecialOffer', 'Special Offers', 'Show special offers from IShop', '0', 'off');

INSERT INTO `el_user` (`uid`, `login`, `pass`, `f_name`, `l_name`, `email`, `crtime`, `mtime`, `atime`, `visits`) VALUES
(1, 'root', 'b78bb582523a89da07ce348eb5e16d88', 'Administrator', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1);

INSERT INTO `el_user_in_group` (`user_id`, `group_id`) VALUES
(1, 1);

INSERT INTO `el_user_profile` (`id`, `label`, `type`, `value`, `opts`, `directory`, `required`, `rule`, `file_size`, `error`, `sort_ndx`) VALUES
('login',  'Login',      'text', '', '', '', 1, '', 1, '', 1),
('email',  'E-mail',     'text', '', '', '', 1, '', 1, '', 2),
('f_name', 'First name', 'text', '', '', '', 0, '', 1, '', 3),
('l_name', 'Last name',  'text', '', '', '', 0, '', 1, '', 4);

