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
		if ($this->_conf('cols') < 2 )
		{
			$b = $showIcons ? 'CONTAINER_OC_ICO.OC_ICO_PAGE' : 'CONTAINER_OC.OC_PAGE';
			for ($i=0; $i < sizeof($pages); $i++) 
			{ 
				$this->_te->assignBlockVars($b, $pages[$i], 1);
			}
		}
		else
		{
			if ($showIcons)
			{
				$c = 'CONTAINER_TC_ICO.TC_ICO_COL';
				$b = $c.'.TC_ICO_PAGE';
			}
			else
			{
				$c = 'CONTAINER_TC.TC_COL';
				$b = $c.'.TC_PAGE';
			}
			$size = sizeof($pages);
			$num = ceil(sizeof($pages)/2)-1; 
			for ($i=0; $i <$size ; $i++) 
			{ 
				$this->_te->assignBlockVars($b, $pages[$i], 2);
				if ( $i == $num )
				{
					$this->_te->assignBlockVars($c, null, 1);
				}
			}
		}
	}
} // END class 

?>