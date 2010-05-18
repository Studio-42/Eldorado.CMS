SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

INSERT INTO `el_email` (`id`, `label`, `email`, `is_default`) VALUES
(1, 'admin', 'admin@yoursite.com', '1');

INSERT INTO `el_group` (`gid`, `name`, `perm`, `mtime`) VALUES
(1, 'root', 8, UNIX_TIMESTAMP()),
(2, 'guests', 0, UNIX_TIMESTAMP());

INSERT INTO `el_menu` (`id`, `name`, `name_alt`, `page_descrip`, `dir`, `_left`, `_right`, `level`, `module`, `visible`, `visible_limit`, `perm`, `is_menu`, `redirect_url`, `ico_main`, `ico_add_menu_top`, `ico_add_menu_bot`, `in_add_menu_top`, `in_add_menu_bot`, `alt_tpl`) VALUES
(1, '--', '', '', '', 1, 26, 0, 'Container', '2', '0', '1', '0', '', '', '', '', '0', '0', ''),
(2, 'Начало', '', '', 'home', 2, 3, 1, 'SimplePage', '2', '0', '1', '0', '', 'home.png', 'home.png', 'home.png', '0', '0', ''),
(3, 'Обратная связь', '', '', 'conf', 4, 5, 1, 'Mailer', '2', '0', '1', '0', '', 'mail.png', 'mail.png', 'mail.png', '0', '0', '0'),
(5, 'Контрольный центр', '', '', 'cc', 6, 25, 1, 'Container', '2', '0', '0', '0', '', 'default.png', 'default.png', 'default.png', '0', '0', ''),
(6, 'Пользователи/группы', '', '', 'users', 7, 8, 2, 'UsersControl', '2', '0', '0', '0', '', 'users.gif', 'default.png', 'default.png', '0', '0', '0'),
(7, 'Управление структурой', '', '', 'menu', 9, 10, 2, 'NavigationControl', '2', '0', '1', '0', '', 'nav.gif', 'default.png', 'default.png', '0', '0', ''),
(8, 'Настройки сайта', '', '', 'site_conf', 11, 12, 2, 'SiteControl', '2', '0', '0', '0', '', 'options.gif', 'default.png', 'default.png', '0', '0', ''),
(9, 'Файлы', '', '', 'fm', 13, 14, 2, 'Finder', '2', '0', '0', '0', '', 'files.gif', 'default.png', 'default.png', '0', '0', ''),
(10, 'Доп. модули', '', '', 'plc', 15, 16, 2, 'PluginsControl', '2', '0', '0', '0', '', 'modules.gif', 'default.png', 'default.png', '0', '0', ''),
(24, 'Резервные копии', '', '', 'backup', 17, 18, 2, 'SiteBackup', '2', '0', '0', '0', '', 'backup.gif', 'default.png', 'default.png', '0', '0', ''),
(25, 'Карта XML', '', '', 'sitemap', 19, 20, 2, 'SitemapGenerator', '2', '0', '0', '0', '', 'map.gif', 'default.png', 'default.png', '0', '0', ''),
(26, 'Обновление системы', '', '', 'update', 21, 22, 2, 'UpdateClient', '2', '0', '0', '0', '', 'updates.gif', 'default.png', 'default.png', '0', '0', ''),
(27, 'Редактор шаблонов', '', '', 'tpl', 23, 24, 2, 'TemplatesEditor', '2', '0', '0', '0', '', 'tpl-editor.gif', 'default.png', 'default.png', '0', '0', '');

INSERT INTO `el_module` (`module`, `descrip`, `multi`, `search`) VALUES
('ActionLog', 'Журнал событий', '0', '0'),
('Container', 'Контейнер', '1', '0'),
('Directory', 'Справочники', '0', '0'),
('DocsCatalog', 'Каталог документов', '1', '1'),
('EventSchedule', 'Расписание событий', '1', '0'),
('FAQ', 'Часто Задаваемые Вопросы', '1', '0'),
('FileArchive', 'Файловый архив', '1', '1'),
('Finder', 'Файловый менеджер', '1', '0'),
('Forum', 'Форум', '0', '1'),
('GAStat', 'Статистика', '0', '0'),
('Glossary', 'Словарь терминов', '1', '0'),
('GoodsCatalog', 'Каталог товаров', '1', '1'),
('IShop', 'Интернет-магазин', '1', '1'),
('ImageGalleries', 'Альбомы изображений', '1', '0'),
('LinksCatalog', 'Каталог ссылок', '1', '1'),
('MailFormator', 'Почтовые формы', '1', '0'),
('Mailer', 'Отправка e-mail', '1', '0'),
('NavigationControl', 'Управление навигацией', '0', '0'),
('News', 'Новости', '1', '1'),
('OrderHistory', 'История заказов', '0', '0'),
('PluginsControl', 'Управление доп. модулями', '0', '0'),
('Poll', 'Голосования', '1', '0'),
('SimplePage', 'Одиночная страница', '1', '1'),
('SiteBackup', 'Резервные копии', '0', '0'),
('SiteControl', 'Настройки сайта', '0', '0'),
('SiteMap', 'Карта сайта', '1', '0'),
('SitemapGenerator', 'Генератор XML-карты сайта', '0', '0'),
('TechShop', 'Магазин тех. товаров', '1', '1'),
('TemplatesEditor', 'Редактор дизайна', '0', '0'),
('UpdateClient', 'Обновление системы', '0', '0'),
('UsersControl', 'Управление пользователями', '0', '0'),
('VacancyCatalog', 'Каталог вакансий', '1', '1');

INSERT INTO `el_page` (`id`, `content`) VALUES
(1, '');

INSERT INTO `el_plugin` (`name`, `label`, `descrip`, `is_on`, `status`) VALUES
('Calculator', 'Калькулятор', '', '0', 'off'),
('InfoBlock', 'Информационные блоки', 'Показ информационных блоков на произвольно выбранных страницах', '1', 'on'),
('NewsTopics', 'Заголовки новостей', 'Показ заголовков новостей', '0', 'off'),
('Poll', 'Голосования', 'Отображение голосований', '1', 'off'),
('RandomImage', 'Случайная картинка', 'Показ случайных картинок из альбомов изображений', '0', 'off');

INSERT INTO `el_user` (`uid`, `login`, `pass`, `f_name`, `l_name`, `email`, `crtime`, `mtime`, `atime`, `visits`) VALUES
(1, 'root', 'b78bb582523a89da07ce348eb5e16d88', 'Администратор', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1);

INSERT INTO `el_user_in_group` (`user_id`, `group_id`) VALUES
(1, 1);

INSERT INTO `el_user_profile` (`id`, `label`, `type`, `value`, `opts`, `directory`, `required`, `rule`, `file_size`, `error`, `sort_ndx`) VALUES
('login', 'Login', 'text', '', '', '', '', '', 1, '', 1),
('email', 'E-mail', 'text', '', '', '', '', '', 1, '', 2),
('f_name', 'Имя', 'text', '', '', '', '', '', 1, '', 3),
('l_name', 'Фамилия', 'text', '', '', '', '', '', 1, '', 4);

