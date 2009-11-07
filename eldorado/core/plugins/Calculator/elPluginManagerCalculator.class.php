<?php

include_once EL_DIR_CORE.'lib/elJSON.class.php';

class elPluginManagerCalculator {
	var $tbp = 'el_plugin_calc2page';
	var $tbv = 'el_plugin_calc_var';
	
	var $_mMap = array(
		'edit' => 'edit',
		'rm'   => 'rm',
		'type' => 'displayType',
		'new_type' => 'createType',
		'edit_type' => 'editType'
		);
	var $_args = array();
	
	function elPluginManagerCalculator($args) {
		$this->_args = $args;
	}
	
	function run() {
		
		$action = isset($_POST['action']) ? $_POST['action'] : '';
//		elPrintR($_POST);
		switch ($action) {
			
			case 'edit':
				$id = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;
				$calc = & new elPcCalculator(array('id' => $id));
				$calc->fetch();
				if ($calc->editAndSave()) 
				{
					elMsgBox::put( m('Data saved') );
					elLocation(EL_URL.'pl_conf/Calculator/');
				}
				$rnd = & elSingleton::getObj('elTE');
				$rnd->assignVars('PAGE', $calc->formToHtml(), true);

				break;
			
			case 'rm':
				$id = !empty($_POST['cid']) ? $_POST['cid'] : '';
				$calc = & new elPcCalculator(array('id' => (int)$id));
				if (!$calc->fetch()) {
					elThrow(E_USER_ERROR, 'There is no object "%s" with ID="%d"',
						array($calc->getObjName(), $id), EL_URL.'pl_conf/Calculator/');
				}
				$calc->delete( array($this->tbp => 'id', $this->tbv => 'cid') );
				elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $calc->getObjName(), $calc->name) );
				elLocation(EL_URL.'pl_conf/Calculator/');
				break;
			
			case 'var_data':
				$cid = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;	
				$calc = & new elPcCalculator(array('id' => (int)$cid));
				if (!$calc->fetch()) {
					exit(elJSON::encode(array('error' => m('Invalid data'))));
				}
				
						
				$id = !empty($_POST['vid']) ? (int)$_POST['vid'] : 0;
				$var = new elPcVar(array('id' => $id, 'cid' => $cid));
				$var->fetch();
//				echo('{msg : "OK"}');
				exit(elJSON::encode($var->toArray()));
				break;
			
			case 'var_edit':
				$cid = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;
				$id  = !empty($_POST['id']) ? (int)$_POST['id'] : 0;	
				$name = !empty($_POST['name']) ? trim($_POST['name']) : 0;
				
				$calc = & new elPcCalculator(array('id' => $cid));
				if (!$calc->fetch()) {
					exit(elJSON::encode(array('error' => m('Invalid data'))));
				}
				$var = & new elPcVar(array('id' => $id, 'cid' => $cid));
				$var->fetch();
				if (!$name) {
					exit(elJSON::encode(array('error' => m('Variable name could not be empty'))));
				}
				$title = !empty($_POST['title']) ? trim($_POST['title']) : '';
				if (!$title) {
					exit(elJSON::encode(array('error' => m('Variable title could not be empty'))));
				}
				if (!$var->checkName($name)) {
					exit(elJSON::encode(array('error' => m('Variable with same name already exists'))));
				}
				$var->name = $name;
				$var->title = $title;
				$var->dtype = !empty($_POST['dtype']) && $_POST['dtype'] == 'int' ? 'int' : 'double';
				$var->type = !empty($_POST['type']) && $_POST['type'] == 'select' && !empty($_POST['variants']) ? 'select' : 'input';
				$var->unit = !empty($_POST['unit']) ? trim($_POST['unit']) : '';
				$var->minval = !empty($_POST['minval']) ? trim($_POST['minval']) : '';
				$var->maxval = !empty($_POST['maxval']) ? trim($_POST['maxval']) : '';
				$var->variants = $var->type=='select' && !empty($_POST['variants']) ? trim($_POST['variants']) : '';
				if ($var->save()) {
					elMsgBox::put(m('Data saved'));
					exit(elJSON::encode(array('ok' => true)));
				} else {
					exit(elJSON::encode(array('error' => m('Unable to save data'))));
				}
				break;
				
			case 'var_rm':
				$id  = !empty($_POST['vid']) ? $_POST['vid'] : '';
				$cid = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;
				$var = & new elPcVar(array('id' => (int)$id));
				if (!$var->fetch()) {
					elThrow(E_USER_ERROR, 'There is no object "%s" with ID="%d"',
						array($var->getObjName(), $id), EL_URL.'pl_conf/Calculator/'.$cid.'/');
				}
				$var->delete();
				elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $var->getObjName(), $var->title) );
				elLocation(EL_URL.'pl_conf/Calculator/'.$cid.'/');
				break;
			
			case 'formula_data':
				$cid = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;	
				$calc = & new elPcCalculator(array('id' => (int)$cid));
				if (!$calc->fetch()) {
					exit(elJSON::encode(array('error' => m('Invalid data'))));
				}
				$var = new elPcVar();
				$vars = $var->collection(false, true, 'cid='.$calc->ID, 'sort_ndx, id');
				
				$_vars = array();
				foreach ($vars as $v) {
					$_vars[$v['name']] = $v['title'];
				}
				exit(elJSON::encode( array('cid' => $cid, 'formula' => $calc->formula, 'vars' => $_vars) ));
				break;
			
			case 'formula_edit':
				
				$cid = !empty($_POST['cid']) ? (int)$_POST['cid'] : 0;	
				$calc = & new elPcCalculator(array('id' => (int)$cid));
				if (!$calc->fetch()) {
					exit(elJSON::encode(array('error' => m('Invalid data'))));
				}
				$formula = !empty($_POST['formula']) ? $_POST['formula'] : '';
				if (!$formula) {
					exit(elJSON::encode(array('error' => m('Formula could not be empty'))));
				}
				$calc->formula = $formula;
				if ($calc->save()) {
					elMsgBox::put(m('Data saved'));
					exit(elJSON::encode(array('ok' =>true)));
				} else {
					exit(elJSON::encode(array('error' => m('Unable to save data'))));
				}
				
				break;
			
			default:
			if (isset($this->_args[0])) {
				$cid = $this->_args[0];
				$c = & new elPcCalculator(array('id' => (int)$cid));
				if (!$c->fetch()) {
					elThrow(E_USER_ERROR, 'There is no object "%s" with ID="%d"',
						array($c->getObjName(), $cid), EL_URL.'pl_conf/Calculator/');
				}
				$this->_displayCalculator($c);
			} 
			else 
			{
				$this->_displayAll();
			}
		}
		
		
	}
	
	
	function _displayCalculator($calc) {
		//elPrintR($calc);
		elLoadJQueryUI();
		elAddCss('eldialogform.css', EL_JS_CSS_FILE);
		elAddJs('ellib/el.lib.complite.js', EL_JS_CSS_FILE);
		elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.js', EL_JS_CSS_FILE);
		elAddJs('jquery.validate.min.js', EL_JS_CSS_FILE);
		elAddJs('el.lib.validate-methods.js', EL_JS_CSS_FILE);
		if (EL_LANG != 'en') {
			elAddJs('i18n/jquery.validate/messages_'.EL_LANG.'.js', EL_JS_CSS_FILE);
		}

		$rnd   = & elSingleton::getObj('elTE');
		$rnd->setFile('calc', 'plugins/Calculator/adminCalculator.html');
		$data = $calc->toArray();
		$data['dtype'] = $data['dtype'] == 'int' ? m('Integer') : m('Double');
		
		$rnd->assignVars($data);
		$rnd->assignVars('calc_edit_json', elJSON::encode(array('cid'=>$data['id'], 'action' => 'edit')));
		$rnd->assignVars('formula_edit_json', elJSON::encode(array('cid'=>$data['id'], 'action' => 'formula_edit')));
		$rnd->assignVars('calc_rm_json', elJSON::encode(array('cid'=>$data['id'], 'action' => 'rm')));		
		$rnd->assignVars('var_edit_json', elJSON::encode(array('cid'=>$data['id'], 'action' => 'var_edit')));		
		
		$var = new elPcVar();
		$vars = $var->collection(false, false, 'cid='.$calc->ID, 'sort_ndx, id');

		foreach ($vars as $v) {
			
			$v['dtype'] = $v['dtype'] == 'int' ? m('Integer') : m('Double');
			$v['type'] = $v['type'] == 'input' ? m('Text field') : m('Select in list');
			$v['variants'] = $v['variants'] ? '<br />'.nl2br($v['variants']) : '';
			$v['edit_json'] = elJSON::encode(array('vid' => $v['id'], 'cid'=>$data['id'], 'action' => 'var_edit'));
			$v['rm_json'] = elJSON::encode(array('vid' => $v['id'], 'cid'=>$data['id'], 'action' => 'var_rm'));
			$rnd->assignBlockVars('PL_CALC_VAR', $v);
		}
		
		$rnd->parse('calc', 'PAGE', true);
	}
	
	function _displayAll() {
		elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
		$c = & new elPcCalculator();
		$calcList = $c->collection(true); 
		$rnd   = & elSingleton::getObj('elTE');
		$rnd->setFile('list', 'plugins/Calculator/adminList.html');
		$rnd->assignVars('pluginName', $this->master->name);
		$rnd->assignVars('json', elJSON::encode(array('cid'=>0, 'action' => 'edit')));
		
		foreach ($calcList as $one) {
			$pages  = $one->getPagesNames();
			$ptitle = '';
			if (sizeof($pages) > 7) {
				$ptitle = implode("\n,", $pages);
				$pages = implode(', ', array_slice($pages, 0, 7)).' '.sprintf(m('and %d more pages'), sizeof($pages)-7);
			} else {
				$pages = implode(', ', $pages);
			}
			
			$attrs         = array(
				'id'       => $one->ID,
				'name'     => $one->name ? $one->name : m('Calculator'),
				'pages'    => $pages,
				'ptitle'   => $ptitle,
				'position' => $GLOBALS['posLRTB'][$one->pos],
				'edit_json' => elJSON::encode(array('cid'=>$one->ID, 'action' => 'edit')),
				'rm_json' => elJSON::encode(array('cid'=>$one->ID, 'action' => 'rm'))
				
				);
			$rnd->assignBlockVars('CALC', $attrs);
		}
		
		$rnd->parse('list', 'PAGE', true);
	}
	
}
?>