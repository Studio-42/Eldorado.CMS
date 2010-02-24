<?php
define ('EL_DATE_FORMAT', 'd.m.Y');
define ('EL_TIME_FORMAT', 'H:i');
define ('EL_DATETIME_FORMAT', 'd.m.Y H:i');
define ('EL_MYSQL_DATE_FORMAT', '%d.%m.%Y');
define ('EL_MYSQL_TIME_FORMAT', '%H:%i');
define ('EL_MYSQL_DATETIME_FORMAT', '%d.%m.%Y %H:%i');
define ('EL_MYSQL_CHARSET',         'utf8');
$GLOBALS['EL_CURRENCY_LIST'] = array( 'USD'=>array('$',    'US dollars', '.', ','),
									  'EUR'=>array('€', 'Euro', ',', '.'),
                                      'RUR'=>array('руб.', 'Russhian rouble', ',', '.'),
 									  'UAH'=>array('грн.', 'Ukranian grivna', ',', '.'));
?>