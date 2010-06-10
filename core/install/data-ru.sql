SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

INSERT INTO `el_email` (`id`, `label`, `email`, `is_default`) VALUES
(1, 'admin', 'admin@eldorado-cms.ru', '1');

INSERT INTO `el_group` (`gid`, `name`, `perm`, `mtime`) VALUES
(1, 'root', 8, UNIX_TIMESTAMP()),
(2, 'guests', 0, UNIX_TIMESTAMP());

INSERT INTO `el_menu` (`id`, `name`, `name_alt`, `page_descrip`, `dir`, `_left`, `_right`, `level`, `module`, `visible`, `visible_limit`, `perm`, `is_menu`, `redirect_url`, `ico_main`, `ico_add_menu_top`, `ico_add_menu_bot`, `in_add_menu_top`, `in_add_menu_bot`, `alt_tpl`) VALUES
(1, '--', '', '', '', 1, 26, 0, 'Container', '2', '0', '1', '0', '', '', '', '', '0', '0', ''),
(2, 'Начало', '', '', 'home', 2, 3, 1, 'SimplePage', '2', '0', '1', '0', '', 'home.png', 'home.png', 'home.png', '0', '0', ''),
(3, 'Обратная связь', '', '', 'feedback', 4, 5, 1, 'Mailer', '2', '0', '1', '0', '', 'default.png', 'default.png', 'default.png', '0', '0', ''),
(4, 'Контрольный центр', '', '', 'cc', 6, 25, 1, 'Container', '2', '0', '0', '0', '', 'default.png', 'default.png', 'default.png', '0', '0', ''),
(5, 'Пользователи/группы', '', '', 'users', 7, 8, 2, 'UsersControl', '2', '0', '0', '0', '', 'users.gif', 'default.png', 'default.png', '0', '0', '0'),
(6, 'Управление структурой', '', '', 'menu', 9, 10, 2, 'NavigationControl', '2', '0', '1', '0', '', 'nav.gif', 'default.png', 'default.png', '0', '0', ''),
(7, 'Настройки сайта', '', '', 'site_conf', 11, 12, 2, 'SiteControl', '2', '0', '0', '0', '', 'options.gif', 'default.png', 'default.png', '0', '0', ''),
(8, 'Файлы', '', '', 'fm', 13, 14, 2, 'Finder', '2', '0', '0', '0', '', 'files.gif', 'default.png', 'default.png', '0', '0', ''),
(9, 'Доп. модули', '', '', 'plc', 15, 16, 2, 'PluginsControl', '2', '0', '0', '0', '', 'modules.gif', 'default.png', 'default.png', '0', '0', ''),
(10, 'Резервные копии', '', '', 'backup', 17, 18, 2, 'SiteBackup', '2', '0', '0', '0', '', 'backup.gif', 'default.png', 'default.png', '0', '0', ''),
(11, 'Карта XML', '', '', 'sitemap', 19, 20, 2, 'SitemapGenerator', '2', '0', '0', '0', '', 'map.gif', 'default.png', 'default.png', '0', '0', ''),
(12, 'Редактор шаблонов', '', '', 'tpl', 21, 22, 2, 'TemplatesEditor', '2', '0', '0', '0', '', 'tpl-editor.gif', 'default.png', 'default.png', '0', '0', ''),
(13, 'Справочники', '', '', 'directory', 23, 24, 2, 'Directory', '2', '0', '1', '0', '', 'directory.png', 'default.png', 'default.png', '0', '0', '');

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
('ICartConf', 'Настройки оформления заказа', '0', '0'),
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
('Calculator',   'Калькулятор',             '', '0', 'off'),
('InfoBlock',    'Информационные блоки',    'Показ информационных блоков на произвольно выбранных страницах', '1', 'on'),
('NewsTopics',   'Заголовки новостей',      'Показ заголовков новостей', '0', 'off'),
('Poll',         'Голосования',             'Отображение голосований', '1', 'off'),
('RandomImage',  'Случайная картинка',      'Показ случайных картинок из альбомов изображений', '0', 'off'),
('SpecialOffer', 'Специальные предложения', 'Показ спец. предложений из Интернет магазина', '0', 'off');

INSERT INTO `el_user` (`uid`, `login`, `pass`, `f_name`, `l_name`, `email`, `crtime`, `mtime`, `atime`, `visits`) VALUES
(1, 'root', 'b78bb582523a89da07ce348eb5e16d88', 'Администратор', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1);

INSERT INTO `el_user_in_group` (`user_id`, `group_id`) VALUES
(1, 1);

INSERT INTO `el_user_profile` (`id`, `label`, `type`, `value`, `opts`, `directory`, `required`, `rule`, `file_size`, `error`, `sort_ndx`) VALUES
('login',  'Login',   'text', '', '', '', 1, '', 1, '', 1),
('email',  'E-mail',  'text', '', '', '', 1, '', 1, '', 2),
('f_name', 'Имя',     'text', '', '', '', 0, '', 1, '', 3),
('l_name', 'Фамилия', 'text', '', '', '', 0, '', 1, '', 4);

-- Special elDirectory content for russian version
INSERT INTO `el_directories_list` (`id`, `label`, `master_id`, `master_key`) VALUES
('icart_region', 'Город',                  '',             0),
('metro_msk',    'Метро: Москва',          'icart_region', 1),
('metro_spb',    'Метро: Санкт-Петербург', 'icart_region', 2);

CREATE TABLE IF NOT EXISTS `el_directory_city` (
  `id`       int(11) NOT NULL AUTO_INCREMENT,
  `value`    mediumtext COLLATE utf8_bin,
  `sort_ndx` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `el_directory_city` (`id`, `value`, `sort_ndx`) VALUES
(1, 'Москва', 1),
(2, 'Санкт-Петербург', 2);

CREATE TABLE IF NOT EXISTS `el_directory_metro_msk` (
  `id`       int(11) NOT NULL AUTO_INCREMENT,
  `value`    mediumtext COLLATE utf8_bin,
  `sort_ndx` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `el_directory_metro_msk` (`id`, `value`, `sort_ndx`) VALUES
(1, 'Нет', 1),
(2, 'Авиамоторная', 0),
(3, 'Автозаводская', 0),
(4, 'Академическая', 0),
(5, 'Александровский сад', 0),
(6, 'Алексеевская', 0),
(7, 'Алтуфьево', 0),
(8, 'Аннино', 0),
(9, 'Арбатская', 0),
(10, 'Аэропорт', 0),
(11, 'Бабушкинская', 0),
(12, 'Багратионовская', 0),
(13, 'Баррикадная', 0),
(14, 'Бауманская', 0),
(15, 'Беговая', 0),
(16, 'Белорусская', 0),
(17, 'Беляево', 0),
(18, 'Бибирево', 0),
(19, 'Библиотека имени Ленина', 0),
(20, 'Боровицкая', 0),
(21, 'Ботанический сад', 0),
(22, 'Братиславская', 0),
(23, 'Бульвар адмирала Ушакова', 0),
(24, 'Бульвар Дмитрия Донского', 0),
(25, 'Бунинская аллея', 0),
(26, 'Варшавская', 0),
(27, 'ВДНХ', 0),
(28, 'Владыкино', 0),
(29, 'Водный стадион', 0),
(30, 'Войковская', 0),
(31, 'Волгоградский проспект', 0),
(32, 'Волжская', 0),
(33, 'Волоколамская', 0),
(34, 'Воробьёвы горы', 0),
(35, 'Выставочная', 0),
(36, 'Выхино', 0),
(37, 'Деловой центр', 0),
(38, 'Динамо', 0),
(39, 'Дмитровская', 0),
(40, 'Добрынинская', 0),
(41, 'Домодедовская', 0),
(42, 'Дубровка', 0),
(43, 'Измайловская', 0),
(44, 'Калужская', 0),
(45, 'Кантемировская', 0),
(46, 'Каховская', 0),
(47, 'Каширская', 0),
(48, 'Киевская', 0),
(49, 'Китай-город', 0),
(50, 'Кожуховская', 0),
(51, 'Коломенская', 0),
(52, 'Комсомольская', 0),
(53, 'Коньково', 0),
(54, 'Конюшковская', 0),
(55, 'Красногвардейская', 0),
(56, 'Краснопресненская', 0),
(57, 'Красносельская', 0),
(58, 'Красные ворота', 0),
(59, 'Крестьянская застава', 0),
(60, 'Кропоткинская', 0),
(61, 'Крылатское', 0),
(62, 'Кузнецкий мост', 0),
(63, 'Кузьминки', 0),
(64, 'Кунцевская', 0),
(65, 'Курская', 0),
(66, 'Кутузовская', 0),
(67, 'Ленинский проспект', 0),
(68, 'Лубянка', 0),
(69, 'Люблино', 0),
(70, 'Марксистская', 0),
(71, 'Марьино', 0),
(72, 'Маяковская', 0),
(73, 'Медведково', 0),
(74, 'Международная', 0),
(75, 'Менделеевская', 0),
(76, 'Митино', 0),
(77, 'Молодёжная', 0),
(78, 'Мякинино', 0),
(79, 'Нагатинская', 0),
(80, 'Нагорная', 0),
(81, 'Нахимовский проспект', 0),
(82, 'Новогиреево', 0),
(83, 'Новокузнецкая', 0),
(84, 'Новослободская', 0),
(85, 'Новоясеневская', 0),
(86, 'Новые Черёмушки', 0),
(87, 'Октябрьская', 0),
(88, 'Октябрьское поле', 0),
(89, 'Орехово', 0),
(90, 'Отрадное', 0),
(91, 'Охотный ряд', 0),
(92, 'Павелецкая', 0),
(93, 'Парк культуры', 0),
(94, 'Парк Победы', 0),
(95, 'Партизанская', 0),
(96, 'Первомайская', 0),
(97, 'Перово', 0),
(98, 'Петровско-Разумовская', 0),
(99, 'Печатники', 0),
(100, 'Пионерская', 0),
(101, 'Планерная', 0),
(102, 'Площадь Ильича', 0),
(103, 'Площадь Революции', 0),
(104, 'Полежаевская', 0),
(105, 'Полянка', 0),
(106, 'Пражская', 0),
(107, 'Преображенская площадь', 0),
(108, 'Пролетарская', 0),
(109, 'Проспект Вернадского', 0),
(110, 'Проспект Мира', 0),
(111, 'Профсоюзная', 0),
(112, 'Пушкинская', 0),
(113, 'Речной вокзал', 0),
(114, 'Рижская', 0),
(115, 'Римская', 0),
(116, 'Рязанский проспект', 0),
(117, 'Савёловская', 0),
(118, 'Свиблово', 0),
(119, 'Севастопольская', 0),
(120, 'Семёновская', 0),
(121, 'Серпуховская', 0),
(122, 'Славянский бульвар', 0),
(123, 'Смоленская', 0),
(124, 'Сокол', 0),
(125, 'Сокольники', 0),
(126, 'Спортивная', 0),
(127, 'Сретенский бульвар', 0),
(128, 'Строгино', 0),
(129, 'Студенческая', 0),
(130, 'Сухаревская', 0),
(131, 'Сходненская', 0),
(132, 'Таганская', 0),
(133, 'Тверская', 0),
(134, 'Театральная', 0),
(135, 'Текстильщики', 0),
(136, 'Тёплый стан', 0),
(137, 'Тимирязевская', 0),
(138, 'Третьяковская', 0),
(139, 'Трубная', 0),
(140, 'Тульская', 0),
(141, 'Тургеневская', 0),
(142, 'Тушинская', 0),
(143, 'Улица 1905 года', 0),
(144, 'Улица академика Королёва', 0),
(145, 'Улица академика Янгеля', 0),
(146, 'Улица Горчакова', 0),
(147, 'Улица Милашенкова', 0),
(148, 'Улица Подбельского', 0),
(149, 'Улица Сергея Эйзенштейна', 0),
(150, 'Улица Скобелевская', 0),
(151, 'Улица Старокачаловская', 0),
(152, 'Университет', 0),
(153, 'Филёвский парк', 0),
(154, 'Фили', 0),
(155, 'Фрунзенская', 0),
(156, 'Царицыно', 0),
(157, 'Цветной бульвар', 0),
(158, 'Черкизовская', 0),
(159, 'Чертановская', 0),
(160, 'Чеховская', 0),
(161, 'Чистые пруды', 0),
(162, 'Чкаловская', 0),
(163, 'Шаболовская', 0),
(164, 'Шоссе Энтузиастов', 0),
(165, 'Щёлковская', 0),
(166, 'Щукинская', 0),
(167, 'Электрозаводская', 0),
(168, 'Юго-Западная', 0),
(169, 'Южная', 0),
(170, 'Ясенево', 0);

CREATE TABLE IF NOT EXISTS `el_directory_metro_spb` (
  `id`       int(11) NOT NULL AUTO_INCREMENT,
  `value`    mediumtext COLLATE utf8_bin,
  `sort_ndx` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `el_directory_metro_spb` (`id`, `value`, `sort_ndx`) VALUES
(1, 'Нет', 1),
(2, 'Автово', 0),
(3, 'Академическая', 0),
(4, 'Балтийская', 0),
(5, 'Василеостровская', 0),
(6, 'Владимирская', 0),
(7, 'Волковская', 0),
(8, 'Выборгская', 0),
(9, 'Горьковская', 0),
(10, 'Гостиный двор', 0),
(11, 'Гражданский проспект', 0),
(12, 'Девяткино', 0),
(13, 'Достоевская', 0),
(14, 'Елизаровская', 0),
(15, 'Звенигородская', 0),
(16, 'Звёздная', 0),
(17, 'Кировский завод', 0),
(18, 'Комендантский проспект', 0),
(19, 'Крестовский остров', 0),
(20, 'Купчино', 0),
(21, 'Ладожская', 0),
(22, 'Ленинский проспект', 0),
(23, 'Лесная', 0),
(24, 'Лиговский проспект', 0),
(25, 'Ломоносовская', 0),
(26, 'Маяковская', 0),
(27, 'Московская', 0),
(28, 'Московские ворота', 0),
(29, 'Нарвская', 0),
(30, 'Невский проспект', 0),
(31, 'Новочеркасская', 0),
(32, 'Обухово', 0),
(33, 'Озерки', 0),
(34, 'Парк Победы', 0),
(35, 'Парнас', 0),
(36, 'Петроградская', 0),
(37, 'Пионерская', 0),
(38, 'Площадь Александра Невского-1', 0),
(39, 'Площадь Александра Невского-2', 0),
(40, 'Площадь Восстания', 0),
(41, 'Площадь Ленина', 0),
(42, 'Площадь Мужества', 0),
(43, 'Политехническая', 0),
(44, 'Приморская', 0),
(45, 'Пролетарская', 0),
(46, 'Проспект Большевиков', 0),
(47, 'Проспект Ветеранов', 0),
(48, 'Проспект Просвещения', 0),
(49, 'Пушкинская', 0),
(50, 'Рыбацкое', 0),
(51, 'Садовая', 0),
(52, 'Сенная площадь', 0),
(53, 'Спасская', 0),
(54, 'Спортивная', 0),
(55, 'Старая Деревня', 0),
(56, 'Технологический институт-1', 0),
(57, 'Технологический институт-2', 0),
(58, 'Удельная', 0),
(59, 'Улица Дыбенко', 0),
(60, 'Фрунзенская', 0),
(61, 'Чернышевская', 0),
(62, 'Чкаловская', 0),
(63, 'Чёрная речка', 0),
(64, 'Электросила', 0);

