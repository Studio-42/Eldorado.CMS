<?php

class elModuleSitemapGenerator extends elModule
{
	var $_sFile      = './sitemap.xml';
	var $_mMapAdmin  = array('generate'=>array('m'=>'generate', 'ico'=>'icoEdit', 'l'=>'Обновить файл', 'g'=>'Actions') );
	var $_mMapConf   = array();
	var $_catModules = array(
							 'TechShop'       => 'el_techshop',
							 'IShop'          => 'el_ishop',
							 'DocsCatalog'    => 'el_dcat',
							 'VacancyCatalog' => 'el_vaccat',
							 'FileArchive'    => 'el_fa');
	var $_conf = array('numLinks'=>0, 'lmd'=>0);
	
	function defaultMethod()
	{
		if ( (!is_file($this->_sFile) || !filesize($this->_sFile)) && false != ($num = $this->_create()) )
		{
			elMsgBox::put( sprintf( m('File %s was created. Contains %d links'), $this->_sFile, $num) );
			elLocation(EL_URL);
		}
		if ( file_exists($this->_sFile) )
		{
			$this->_initRenderer(); 
			$this->_rnd->rndSitemap($this->_sFile, filesize($this->_sFile), date(EL_DATETIME_FORMAT, (int)$this->_conf('lmd')/**fileatime($this->_sFile) **/), (int)$this->_conf('numLinks')  );
		}
	}

	function generate()
	{
		if (false != ($num = $this->_create()) )
		{
			elMsgBox::put( sprintf( m('File %s was updated. Contains %d links'), $this->_sFile, $num) );			
		}
		elLocation( EL_URL );
	}

	function _create()
	{
		// записываем sitemap.xml
		if ( false == ($fp = fopen($this->_sFile, 'w') ) )
		{
			return elThrow(E_USER_ERROR, 'File %s is not writable', $this->_sFile);
		}
		$urls = $this->_getUrls();
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
		foreach ($urls as $url)
		{
			$xml .= "<url>\n\t<loc>".$url."</loc>\n</url>\n";
		}
		$xml .= "</urlset>\n";
		if ( !fwrite($fp, $xml) )
		{
			return elThrow(E_USER_ERROR, 'Could write to file %s', $this->_sFile);
		}
		fclose($fp);
		
		// пишем путь к sitemap.xml в robots.txt
		$smstr = 'Sitemap: '.EL_BASE_URL.str_replace('./', '/', $this->_sFile).'';
		$rtxt = !file_exists('./robots.txt')
			? "User-Agent: *\nDisallow: /__search__/\nDisallow: /*_print_\n".$smstr."\n"
			: file_get_contents('./robots.txt');
		if ( false === strpos($rtxt, $smstr) )
		{
			$rtxt .= "\n".$smstr."\n";
		}
		if ( false != ($fp = fopen('./robots.txt', 'w')) )
		{
			fwrite($fp, $rtxt);
			fclose($fp);
		}
		
		$conf = &elSingleton::getObj('elXmlConf');
		$conf->set('numLinks', sizeof($urls), $this->pageID );
		$conf->set('lmd', time(), $this->pageID );
		$conf->save();
		return sizeof($urls);
	}

	function _getUrls()
	{
		$nav   = & elSingleton::getObj( 'elNavigator' );
		$pages = $nav->getPages(0, 0, true, true, 0, 1);

		foreach ($pages as $key => $value)
		{
			$url[] = $value['url'];

			if ( !empty($this->_catModules[$value['module']]) )
			{
				$db    = & elSingleton::getObj('elDb');
				$tbTpl = $this->_catModules[$value['module']];
				$db->query( 'SELECT id FROM '.$tbTpl.'_'.$value['id'].'_cat WHERE id<>1 ORDER BY id' );
				while ($r = $db->nextRecord())
				{
					$url[] = $value['url'].$r['id'].'/';
				}

				// в файловых архивах файлы не индексируем
				if ('FileArchive' != $value['module'])
				{
					$db->query('SELECT i_id, c_id FROM '.$tbTpl.'_'.$value['id'].'_i2c ORDER BY c_id, i_id');
					while ($r = $db->nextRecord())
					{
						$url[] = $value['url'].'item/'.$r['c_id'].'/'.$r['i_id'].'/';
					}	
				}
				
			}
		}
		$ret =  array_chunk($url, 50000);
		return $ret[0];
	}

	

}
?>