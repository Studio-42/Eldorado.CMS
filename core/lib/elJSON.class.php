<?php
/**
 * php - json encoder
 * based on Services_JSON by Michal Migurski <mike-json@teczno.com> , 
 * Matt Knapp <mdknapp[at]gmail[dot]com> and 
 * Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 *
 * @package el.lib
 * @author dio@eldorado-cms.ru
 **/
class elJSON
{
	function encode($var, $j=false)
	{
		if ($j && function_exists('json_encode'))
		{
			return json_encode($var);
		}
		
		switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
				if ( preg_match('/^function\s*\([^\)]*\)\s*{.+};*$/i', trim($var)) )
				{
					return $var;
				}
				$ascii = '';
                for ($c = 0, $s=strlen($var); $c < $s; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $ascii .= sprintf('\u%04s', bin2hex( elJSON::utf82utf16($char) ));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}), ord($var{$c + 2}));
                            $c += 2;
                            $ascii .= sprintf('\u%04s', bin2hex( elJSON::utf82utf16($char) ));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}), ord($var{$c + 2}), ord($var{$c + 3}));
                            $c += 3;
                            $ascii .= sprintf('\u%04s', bin2hex( elJSON::utf82utf16($char) ));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}), ord($var{$c + 2}), ord($var{$c + 3}), ord($var{$c + 4}));
                            $c += 4;
                            $ascii .= sprintf('\u%04s', bin2hex( elJSON::utf82utf16($char) ));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}), ord($var{$c + 2}), ord($var{$c + 3}), ord($var{$c + 4}), ord($var{$c + 5}));
                            $c += 5;
                            $ascii .= sprintf('\u%04s', bin2hex( elJSON::utf82utf16($char) ));
                            break;
                    }
                }

                return '"'.$ascii.'"';

            case 'array':
                // асоциативный массив
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) 
				{
                    return '{' . implode(',', array_map( array('elJSON', 'pair'), array_keys($var), array_values($var) )) . '}';
                }
                // обычный массив
                return '[' . implode(',', array_map( array('elJSON', 'encode'), $var)) . ']';

            case 'object':
                $vars = get_object_vars($var);
				return '{' . implode(',', array_map( array('elJSON', 'pair'), array_keys($vars), array_values($vars) )) . '}';

            default:
                return 'null';

        }
	}
	
	/**
    * возвращает фаорматированую в json пару ключ : значение
    *
    * @param    string  $name   ключ
    * @param    mixed   $value  значение
    *
    * @return   string  
    * @access   private
    */
    function pair($name, $value)
    {
		return elJSON::encode(strval($name)).':'.elJSON::encode($value);
    }
	
   /**
    * преобразует символ UTF-8 в символ UTF-16
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        if (function_exists('mb_convert_encoding')) 
		{
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                return $utf8;
            case 2:
                return chr(0x07 & (ord($utf8{0}) >> 2)).chr((0xC0 & (ord($utf8{0}) << 6)) | (0x3F & ord($utf8{1})));
            case 3:
                return chr((0xF0 & (ord($utf8{0}) << 4)) | (0x0F & (ord($utf8{1}) >> 2))).chr((0xC0 & (ord($utf8{1}) << 6)) | (0x7F & ord($utf8{2})));
        }
        return '';
    }
	
} // END class
?>