<?php
/**
 * Отрисовщик модуля Контейнер
 *
 * @package eldorado.modules.Container
 * @author dio
 **/

class elRndContainer extends elModuleRenderer
{
	/**
	 * Рисует список вложенных в конейнер страниц
	 * Вид зависит от настроек модуля:
	 * одна или две колонки, с иконками или без
	 *
	 * @param  array  $pages  массив страниц
	 * @return void
	 **/
	function render( $pages )
	{
		$this->_setFile();
		$showIcons = $this->_conf('showIcons');
		
		$size = sizeof($pages);
		$num = ceil(sizeof($pages)/2)-1; 
		if ($this->_conf('cols') < 2)
		{
			$tc = false;
			$b = 'CONTAINER_OC';
		}
		else
		{
			$tc = true;
			$b = 'CONTAINER_TC';
		}

		for ($i=0; $i <$size ; $i++) 
		{ 
			$pages[$i]['cssClass'] = $showIcons ? '' : 'link forward2';
			$this->_te->assignBlockVars($b.'.PAGE', $pages[$i], 1);
			if ($showIcons)
			{
				
				$this->_te->assignBlockVars($b.'.PAGE.ICO', array('ico' => $pages[$i]['ico']), 2);
			}

			if ( $tc && $i == $num )
			{
				$this->_te->assignBlockVars($b);
			}
		}
	}
} // END class 

?>