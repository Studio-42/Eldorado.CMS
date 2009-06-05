<?php

class elBBCode
{
	var $_init   = null;
	var $_groups = array(
		'text' => array(
			'b'     => array('/\[b\](.+?)\[\/b\]/ism',       '<strong>$1</strong>'),
			'i'     => array('/\[i\](.+?)\[\/i\]/ism',       '<em>$1</em>'),
			'u'     => array('/\[u\](.+?)\[\/u\]/ism',       '<u>$1</u>'),
			's'     => array('/\[s\](.+?)\[\/s\]/ism',       '<del>$1</del>'),
			'sub'   => array('/\[sub\](.+?)\[\/sub\]/ism',   '<sub>$1</sub>'),
			'sup'   => array('/\[sup\](.+?)\[\/sup\]/ism',   '<sup>$1</sup>'),
			'code'  => array('/\[code\](.+?)\[\/code\]/ism', '<code>$1</code>'),
			'move'  => array('/\[move\](.+?)\[\/move\]/ism', '<marquee>$1</marquee>'),
			'color' => array('/\[color\=([^\]]+)\](.+?)\[\/color\]/ism', '<span style="color:$1">$2</span>')
			//'color' => array('/\[color\=([^\]]+)\]/ism', '<span style="color:$1">'),
			//'_color' => array('/\[\/color\]/ism', '</span>'),
			),
		'alignment' => array(
			'left'   => array('/\[left\](.+?)\[\/left\]/ism',     '<p class="bb-left">$1</p>'), 
			'center' => array('/\[center\](.+?)\[\/center\]/ism', '<p style="text-align:center">$1</p>'), 
			'right'  => array('/\[right\](.+?)\[\/right\]/ism',   '<p class="bb-right">$1</p>'), 
			'pre'    => array('/\[pre\](.+?)\[\/pre\]/ism',       '<pre>$1</pre>')
			),
		'elements'  => array(
			'list'     => array('/\[list\]\s*(.+?)\[\/list\]\s*/ism',       '<ul>$1</ul>'), 
			'li'       => array('/\[li\](.+?)\[\/li\]\s*/ism',           '<li>$1</li>'), 
			'hr'       => array('/\[hr\]/i',                          '<hr />'), 
			'url'      => array('/\[url\=(.+?)\](.+?)\[\/url\]/ism',  '<a href="$1" target="_blank" class="popup">$2</a>'),
			'_url'     => array('/\[url\=(.+?)\]\[\/url\]/ism',       '<a href="$1" target="_blank" class="popup">$1</a>'), 
			'img'      => array('/\[img\](.+?)\[\/img\]/ism',         '<img src="$1" />'), 
			// 'spoiler'  => array('/\[spoiler\=(.+?)\](.+?)\[\/spoiler\]/ism', '<div class="spoiler"><a href="#" class="el-collapsed">$1</a><div class="hide">$2</div></div>'),
			// '_spoiler' => array('/\[spoiler\](.+?)\[\/spoiler\]/ism', '<div class="spoiler"><a href="#" class="el-collapsed">Spoiler Title</a><div class="hide">$1</div></div>')
			'spoiler'  => array('/\[spoiler\=(.+?)\]/is', '<div class="spoiler"><a href="#" class="el-collapsed">$1</a><div class="hide">'),
			'_spoiler' => array('/\[spoiler\](.+?)\[\/spoiler\]/ism', '<div class="spoiler"><a href="#" class="el-collapsed">Spoiler Title</a><div class="hide">$1</div></div>'),
			'_spoiler2' => array('/\[\/spoiler\]/i', '</div></div>')
			),
		'table'     => array(
			'table' => array('/\[table\]\s*(.+?)\[\/table\]/ism', '<table class="nice">$1</table>'), 
			'tr'    => array('/\[tr\]\s*(.+?)\[\/tr\]/ism',       '<tr>$1</tr>'), 
			'td'    => array('/\[td\]\s*(.+?)\[\/td\]/ism',       '<td>$1</td>')
			)
		);
	var $_colors = array('black', 'grey', 'brown', 'maroon', 'purple', 'red', 'pink', 'orange', 'yellow', 'limegreen', 'green', 'teal', 'blue', 'navy', 'beige', 'white');
	var $_smiley = array(
		':)'   => 'regular.png', 
		';)'   => 'wink.png', 
		':D'   => 'lol.png',
		';D'   => 'yell.png',
		'>:('  => 'angry.png',
		':('   => 'sad.png',
		':o'   => 'omg.png',
		'8)'   => 'shades.png',
		'???'  => 'huh.png',
		':P'   => 'tounge.png',
		':-['  => 'embaressed.png',
		':-\\' => 'confused.png',
		':-*'  => 'kissu.png',
		':\'(' => 'cry.png',
		);

	var $_regexp = array(
		'patterns' => array(
			'/\[quote(\s+author=([^\]\=]+))?(\s+link=([^\]\=]+))?(\s+date=([^\]\=]+))?\]/ism',
			'/\[quote([^\]])*\]/ism',
			'/\[\/quote\]/is',


			
			), 
		'replacement' => array(
			'<div><strong>Quote: $2 <a href="{URL}$4">$6</a></strong></div> <blockquote>',
			'<div><strong>Quote:</strong></div> <blockquote>',
			'</blockquote>',


			)
		);
	
	function init( $allowedGroups, $smiley, $autolinks )
	{
		if (is_null($this->_init))
		{
			$this->_init = true;
			$this->_groups['elements']['_spoiler'][1] = str_replace('Spoiler Title', m('Spoiler'), $this->_groups['elements']['_spoiler'][1]);
			$this->_regexp['replacement'][0] = str_replace(array('{URL}', 'Quote'), array(EL_URL, m('Quote')), $this->_regexp['replacement'][0]);
			foreach ($this->_groups as $name=>$group)
			{
				if ( !in_array($name, $allowedGroups) )
				{
					unset($this->_groups[$name]);
				}
				else
				{
					foreach ($this->_groups[$name] as $k=>$v)
					{
						$this->_regexp['patterns'][]    = $v[0];
						$this->_regexp['replacement'][] = $v[1];
					}
				}
			}
			if ( $autolinks )
			{
				$this->_regexp['patterns'][]    = '|\s+(https?)://([^\s]{3,})|i';
				$this->_regexp['replacement'][] = '<a href="$1://$2" target="_blank" class="popup">$2</a>';
			}
			if (!$smiley)
			{
				$this->_smiley = array();
			}
			else
			{
				foreach ($this->_smiley as $sm=>$img)
				{
					$this->_regexp['patterns'][]    = "|".preg_quote($sm)."|";
					$this->_regexp['replacement'][] = '<img src="'.EL_BASE_URL.'/style/images/forum/smiley/'.$img.'" /> ';
				}
			}
		}
	}
	
	function parse($str)
	{
		if ( !empty($this->_regexp['patterns']) )
		{
			$str = preg_replace($this->_regexp['patterns'], $this->_regexp['replacement'], $str);
			//$str = str_replace("</li>\n", '</li>', $str);
		}
		return $str;
		return !empty($this->_regexp['patterns']) ? preg_replace($this->_regexp['patterns'], $this->_regexp['replacement'], $str) : $str;
	}
	
	function getGroups()
	{
		$groups = array();
		foreach($this->_groups as $name=>$g)
		{
			$groups[$name] = array();
			if ('elements' == $name)
			{
				$groups[$name][] = 'quote';
			}
			foreach ($g as $k=>$v)
			{
				if ('_' != $k{0})
				{
					$groups[$name][] = $k;
				}
			}
		}
		return $groups;
	}
	
	function getColors()
	{
		return $this->_colors;
	}
	
	function getSmiley()
	{
		return $this->_smiley;
	}
	
}

?>