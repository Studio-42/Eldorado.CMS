<?php

// ActionLog

class elCronActionLog {

	function run()
	{
		$conf   = & elSingleton::getObj('elXmlConf');
		$nav    = & elSingleton::getObj('elNavigator');
		$module = $nav->findByModule('ActionLog');
		$module = !empty($module[0]) ? $module[0] : 0;
		if ($module > 0)
		{
			if (($conf->get('reportNext', $module) < time()) and ($conf->get('reportPeriod', $module) > 0))
			{
				elSingleton::incLib('./modules/ActionLog/elModuleActionLog.class.php', true);
				$this->module = & elSingleton::getObj('elModuleActionLog');
				$this->module->init($this->pageID, $this->args, $this->mName, EL_FULL);
				$this->module->report(
					$conf->get('reportLast', $module),
					$conf->get('reportNext', $module),
					$conf->get('reportType', $module),
					$conf->get('reportPeriod', $module),
					$conf->get('reportEmail', $module)
					);
				$conf->set('reportLast', time(), $module);
				$conf->set('reportNext', time() + ($conf->get('reportPeriod', $module) * 86400), $module);
				$conf->save();
			}
		}
	}

}

