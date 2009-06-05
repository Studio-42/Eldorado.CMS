<?php


class elIShopSearchManager
{
    var $pageID       = 0;
    var $tbs          = array();
    var $_tbr         = '';
    var $_db          = null;
    var $_groups      = array();
    var $_labels      = array();
    var $_resticts    = array();
    var $_aGroupID    = 0;
    var $_ctlName     = 'isSearchGroup';
    var $_newElement  = null;
    var $_factory     = null;
    var $_form        = null;
    var $_sortForm    = null;
    var $_searchCriteria = false;
    var $_colNum      = 2;
    var $_ctlLabel    = '';
    var $skel         = array(
                        'name'  => array('elIShopSearchElName',  'search by name'),
                        'mnftm' => array('elIShopSearchElMnfTm', 'search by manufacturer or trade mark'),
                        'price' => array('elIShopSearchElPrice', 'search by price'),
                        'prop'  => array('elIShopSearchElProp',  'search by items properties')
                        );
    
    function elIShopSearchManager( $pageID, $tbs, $colNum, $label )
    {
        $this->pageID   = $pageID;
        if ( empty($tbs) ) // создание объекта минуя elIShopFactory
        {
            $this->_selfInit();
        }
        $this->tbs       = $tbs;
        $this->_db       = & elSingleton::getObj('elDb');
        $this->_tbr      = 'el_ishop_search_'.$this->pageID.'_'.md5(utime()); //echo $this->_tbr;
        $this->_form     = & elSingleton::getObj('elForm', 'elIShopSearch');
        $this->_colNum   = $colNum>0 && $colNum<10 ? $colNum : 2;
        $this->_ctlLabel = $label ? $label : m('Items type');
        $this->_load();
    }
    
    function isConfigured()
    {
        return !empty($this->_groups);
    }
    
    function hasSearchCriteria()
    {
        return $this->_searchCriteria;
    }
    
    function formToHtml()
    {
        return !empty($this->_form) ? $this->_form->toHtml() : null;
    }
     
      
    function formToXml( $gID )
    {
        $xml = "\n<response>\n";
		$xml .= "<method>updateSearchForm</method>\n";
		$xml .= "<result>\n";
        $xml .= "<colNum>".$this->_colNum."</colNum>\n";
        // groups list
        $xml .= "<element>\n";
        $xml .= "<name>".$this->_ctlName."</name>";
        $xml .= "<type>select</type>\n";
        $xml .= "<label>".$this->_ctlLabel."</label>\n";
        $xml .= "<selected>".$gID."</selected>\n";
        foreach ( $this->_groups as $ID=>$g )
        {
            $xml .= "<dict>\n";
            $xml .= "<value>".$ID."</value>\n";
            $xml .= "<label>".$g['label']."</label>\n";
            $xml .= "</dict>\n";
        }
        $xml .= "</element>\n";
        
        // elements list
        $gID = !empty($this->_groups[$gID]['elements']) ? $gID : $this->_AGID;
        foreach ($this->_groups[$gID]['elements'] as $e)
        {
            $xml .= $e->toXml();
        }
        $xml .= "</result>\n";
        $xml .= "</response>\n";
        return $xml;
    } 
     
    function find()
    {
        // если для группы не заданы типы товаров - ищем среди всех
        if ( empty($this->_groups[$this->_AGID]['restrict']) )
        {
            $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS '.$this->_tbr.'  '
                    .' ENGINE=MEMORY SELECT id, type_id FROM '.$this->tbs['tbi'];
        }
        else
        {
			$ids = array_map('intval', $this->_groups[$this->_AGID]['restrict']); 
            $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS '.$this->_tbr.'  '
                    .' ENGINE=MEMORY SELECT id, type_id FROM '.$this->tbs['tbi']
                    .' WHERE type_id IN ('.implode(',', $ids).')';
            
        }
        $this->_db->query($sql);
        //$this->_db->query('SELECT id FROM '.$this->_tbr);
        //$num = $this->_db->numRows(); echo 'Before '.$num.'<br>';
        foreach ($this->_groups[$this->_AGID]['elements'] as $ID=>$el)
        {
            $this->_groups[$this->_AGID]['elements'][$ID]->find(); //elPrintR($this->_groups[$this->_AGID]['elements'][$ID]);
        }

        $this->_db->query('SELECT id FROM '.$this->_tbr);
        $num = $this->_db->numRows(); //echo '<br>After '.$num;
        return $num;
    }
    
    function getResult()
    {
        $factory = & elSingleton::getObj('elIShopFactory');
        $item    = $factory->getItem(0);
        return $item->getBySearchResult( $this->_tbr );
    }
    
    function getGroups()
    {
        return $this->_groups;
    }
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
    
    
    function _load()
    {
        // загружаем ограничения групп по типам товара
        $restrict = array();
        $this->_db->query( 'SELECT s_id, t_id FROM '.$this->tbs['tbst'] );
        while ( $r = $this->_db->nextRecord() )
        {
            $restrict[$r['s_id']][] = $r['t_id'];
        }

        // создаем группы элементов поиска        
        $sql = 'SELECT se.id, se.s_id, se.label, s.label AS groupLabel, se.source, se.conf, se.def_val '
                .'FROM '.$this->tbs['tbse'].' AS se, '.$this->tbs['tbs'].' AS s '
                .'WHERE se.s_id=s.id ORDER BY se.sort_ndx, se.id';
        $elData = $this->_db->queryToArray($sql);
        foreach ( $elData as $g )
        {
            $el = $this->createElement( $g['source'] );
            if ( $el && $el->init( $g['id'], $g['label'], $this->_tbr, $g['conf'], $g['def_val'], !empty($restrict[$g['s_id']]) ? $restrict[$g['s_id']] : array())  )
            {
                if ( empty($this->_groups[$g['s_id']]) )
                {
                    $this->_groups[$g['s_id']] = array(
                                                         'label'    => $g['groupLabel'],
                                                         'restrict' => !empty($restrict[$g['s_id']]) ? $restrict[$g['s_id']] : array(),
                                                         'elements' => array()
                                                         );
                }
                $this->_groups[$g['s_id']]['elements'][$el->ID] = $el;
            }
            else
            {
                unset($el);
            }
        }
        
        
        
        // определяем активную группу
        if ( !empty($this->_groups) )
        { 
            $this->_AGID = !empty($_POST[$this->_ctlName]) && !empty($this->_groups[(int)$_POST[$this->_ctlName]])
                ? (int)$_POST[$this->_ctlName]
                : key($this->_groups);
            $this->_makeActiveFrom();
        }
    }
    
    function _makeActiveFrom()
    {
		// $this->_form->attrs['method'] = 'GET';
        $this->_form->setRenderer( elSingleton::getObj('elTplGridFormRenderer', $this->_colNum, 'modules/IShop/searchGridForm.html') );
        $labels = array(); //elPrintR($this->_groups);
        if ( 1 < sizeof($this->_groups) )
        {
            foreach ($this->_groups as $gID=>$g )
            {
                $labels[$gID] = $g['label']; //elPrintR($g);
            }
            $this->_form->add( new elSelect($this->_ctlName, $this->_ctlLabel, $this->_aGroupID, $labels, array('onChange'=>'reloadSearchForm()')), array('label'=>1) );
        }

        foreach ($this->_groups[$this->_AGID]['elements'] as $ID=>$el )
        {
            $this->_form->add( $this->_groups[$this->_AGID]['elements'][$ID]->fElement, array('label'=>1) );
        }
        if ( $this->_form->isSubmitAndValid() )
        {
            foreach ( $this->_groups[$this->_AGID]['elements'] as $ID=>$el )
            {
                if ( $this->_groups[$this->_AGID]['elements'][$ID]->hasSearchCriteria() )
                {
                    return $this->_searchCriteria = true;
                }
            }
        }
    }
    
    
    /**
     * Создает объект-элемент поиска
     * не инициализуе его
    */
    function createElement( $type )
    {
        return !empty($this->skel[$type]) && class_exists($this->skel[$type][0])
            ? new $this->skel[$type][0]( $this->tbs, $type )
            : null;
    }

    /**
     * Инициализация минуя фабрику
     * Возможно пригодиццо для плагина
     *
     **/
    function _selfInit()
    {
        $factory = & elSingleton::getObj('IShopFactory');
        if ( !$factory->pageID )
        {
            $factory->init($this->pageID);
        }
        $tbs    = $factory->getTbs();
        $conf   = & elSingleton::getObj('elXmlConf');
        $colNum = (int)$conf->get('searchColumnsNum', $this->pageID);
    }
    
}



/***************************************************
 * Абстрактный класс - элемент поиска
 *
 *
 **************************************************/

class elIShopSearchElement
{
    var $ID        = 0;
    var $label     = '';
    var $type      = '';
    var $tbs       = array();
    var $tbr       = '';
    var $defValue  = 'not selected';
    var $fElement  = null;
    var $form      = null;
    var $_fName    = '';
    var $_db       = null;
    var $_opts     = array();
    var $_conf     = array();
    var $_iTypes    = array();
    
    function elIShopSearchElement($tbs, $type)
    {
        $this->tbs  = $tbs;
        $this->type = $type;
        $this->_db  = & elSingleton::getObj('elDb');
    }
    
    function init($ID, $label, $tbr, $conf, $defValue, $iTypes)
    {
        $this->ID       = $ID;
        $this->_fName   = 'se['.$this->ID.']';
        $this->label    = $label;
        $this->tbr      = $tbr;
        $this->defValue = !empty($defValue) ? $defValue : m($this->defValue);
        $this->_iTypes  = $iTypes;
        $this->_parseConf($conf);
        return $this->_load() && $this->_createFormElement();
    }
    
    function hasSearchCriteria()
    {
        return (bool)$this->fElement->getValue();
    }
    
    function find()
    {
        return null;
    }
    
    function toXml()
    {
        $xml  = "<element>\n";
        $xml .= "<type>text</type>\n";
        $xml .= "<name><![CDATA[".$this->_fName."]]></name>";
        $xml .= "<label>".$this->label."</label>\n";
        $xml .= "<selected>0</selected>\n";;
        $xml .= "</element>\n";
        return $xml;
    }
    
    function toHtml()
    {
        return $this->fElement->toHtml();
    }
    
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
    
    function _createFormElement()
    {
        if ( empty($this->fElement) )
        {
            if ( !is_array($this->_opts) )
            {
                $this->fElement = & new elText($this->_fName, $this->label, null, array('size'=>17) );
            }
            else
            {
                $this->fElement = & new elSelect($this->_fName, $this->label, null, $this->_opts );
            }
        }
        return true;
    }
    
    function _parseConf( $conf ) {  }
    
    function _load()
    {
       return false; 
    }
 
}


/***************************************************
 * Класс - элемент поиска
 * поиск по названию товара
 *
 **************************************************/

class elIShopSearchElName extends elIShopSearchElement
{
    var $_opts = '';
    
    function find()
    {
        if ( false != ($str = trim($this->fElement->getValue())) )
        {
            $str = mysql_real_escape_string(substr($str, 0, 100));
            $r = array( '/\?|\[|\]|\\\|\/|\'|\"|\*|\||\(|\)|(\s{2,)/', '/\s+/i');
            $str = preg_replace( $r, array('', '|'),	preg_quote($str));
            $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i  '
                .'WHERE UPPER(i.name) NOT REGEXP UPPER("'.$str.'") AND r.id=i.id ';
            $this->_db->query($sql);
        }
    }
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
    
    function _load()
    {        
       return true; 
    }
    
}




/***************************************************
 * Класс - элемент поиска
 * поиск по производителю или торговой марке
 *
 **************************************************/

class elIShopSearchElMnfTm extends elIShopSearchElement
{
    /**
     * Настройки класса
     * Показывать или нет страну производителя
     */
    var $_conf   = array( 'mode'=>'mnf', 'useCountry' => false );
    
    
    function find()
    {
        if ( 'mnftm' != $this->_conf['mode'] && 0 < ($ID = (int)$this->fElement->getValue()) )
        {
            $sql = 'mnf' == $this->_conf['mode']
                ? 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.mnf_id<>'.$ID.' AND r.id=i.id'
                : 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.tm_id<>'.$ID.' AND r.id=i.id';
            $this->_db->query( $sql );
        }
        elseif ( 'mnftm' == $this->_conf['mode'] )
        {
            $ID = $this->fElement->getValue(); echo $ID;
            if ( strstr($ID, 'mnfID_') )
            {
                $ID  = str_replace('mnfID_', '', $ID);
                $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.mnf_id<>'.intval($ID).' AND r.id=i.id';
            }
            elseif ( $ID > 0 )
            {
                $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.tm_id<>'.intval($ID).' AND r.id=i.id';
            }
            $this->_db->query( $sql );
        }
    }
    
    
    function toXml()
    {
        $xml  = "<element>\n";
        $xml .= "<label>".$this->label."</label>\n";
        $xml .= "<name><![CDATA[".$this->_fName."]]></name>";
        $xml .= "<selected>0</selected>\n";
        
        if ('mnftm' == $this->_conf['mode'])
        {
            $xml .= "<type>opt-select</type>\n";
            foreach ( $this->_opts as $gID=>$group )
            {
                if ( 0 == $gID )
                {
                    $xml .= "<dict>\n";
                    $xml .= "<value>".$gID."</value>\n";
                    $xml .= "<label><![CDATA[".$group."]]></label>\n";
                    $xml .= "</dict>\n";
                    continue;
                }
                $xml .= "<dict>\n";
                $xml .= "<value>".$gID."</value>\n";
                $xml .= "<label><![CDATA[".$group['mnf']."]]></label>\n";
                
                foreach ( $group['tms'] as $tID=>$tm )
                {
                    $xml .= "<subdict>\n";
                    $xml .= "<value>".$tID."</value>\n";
                    $xml .= "<label><![CDATA[".$tm."]]></label>\n";
                    $xml .= "</subdict>\n";
                }
                
                $xml .= "</dict>\n";
            }
        }
        else
        {
            $xml .= "<type>select</type>\n";
            
            foreach ( $this->_opts as $ID=>$v )
            {
                $xml .= "<dict>\n";
                $xml .= "<value>".$ID."</value>\n";
                $xml .= "<label><![CDATA[".$v."]]></label>\n";
                $xml .= "</dict>\n";
            }
            
        }
        $xml .= "</element>\n";
        return $xml;
    }
    
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
    
    function _load()
    {
        $mnfName     =  empty($this->_conf['useCountry']) ? 'm.name AS mnfName ' : 'CONCAT(m.name, \', \', m.country) AS mnfName ';
        $this->_opts = array(0 => $this->defValue);
        // загружаем только торговые марки
        if ( 'tm' == $this->_conf['mode'] )
        {
            $sql = empty($this->_iTypes)
                ? 'SELECT t.id, t.name FROM '.$this->tbs['tbtm'].' AS t, '.$this->tbs['tbmnf'].' AS m ORDER BY t.name'
                : 'SELECT t.id, t.name FROM '.$this->tbs['tbtm'].' AS t, '.$this->tbs['tbi'].' AS i '
                    .'WHERE i.type_id IN ('.implode(',', $this->_iTypes).') AND t.mnf_id=i.mnf_id  ORDER BY t.name';
            $this->_opts = $this->_opts + $this->_db->queryToArray($sql, 'id', 'name');     
        } // загружаем производителей + торговые марки
        elseif ( 'mnftm' == $this->_conf['mode'] )
        {
            $sql = empty($this->_iTypes)
                ? 'SELECT m.id AS mnfID, '.$mnfName.', t.id, t.name AS tmName FROM '
                    .$this->tbs['tbmnf'].' AS m, '.$this->tbs['tbtm'].' AS t '
                    .'WHERE t.mnf_id=m.id ORDER BY mnfName, tmName'
                : 'SELECT m.id AS mnfID, '.$mnfName.', t.id, t.name AS tmName FROM '
                    .$this->tbs['tbmnf'].' AS m, '.$this->tbs['tbtm'].' AS t, '.$this->tbs['tbi'].' AS i '
                    .'WHERE i.type_id IN ('.implode(',', $this->_iTypes).') AND m.id=i.mnf_id '
                    .'AND t.mnf_id=m.id ORDER BY mnfName, tmName';
            $this->_db->query($sql);
            while ($r = $this->_db->nextRecord())
            {
                if ( !isset($this->_opts[$r['mnfID']]) )
                {
                    $this->_opts[$r['mnfID']] = array(
                                                      'mnf' => $r['mnfName'],
                                                      'tms' => array('mnfID_'.$r['mnfID'] => m('All trade marks')) );
                }
                $this->_opts[$r['mnfID']]['tms'][$r['id']] = $r['tmName'];
            }  
        } // загружаем только производителей
        else
        {
            $sql = empty($this->_iTypes)
                ? 'SELECT id, '.$mnfName.' FROM '.$this->tbs['tbmnf'].' AS m  ORDER BY mnfName'
                : 'SELECT m.id, '.$mnfName.' FROM '.$this->tbs['tbmnf'].' AS m, '.$this->tbs['tbi'].' AS i '
                        .'WHERE i.type_id IN ('.implode(',', $this->_iTypes).') AND m.id=i.mnf_id ORDER BY mnfName'; 
            
            $this->_opts = $this->_opts + $this->_db->queryToArray($sql, 'id', 'mnfName');
        }

        return 1<sizeof($this->_opts);
    }
    
    function _createFormElement()
    {
        if ( empty($this->fElement) )
        {
            if ('mnftm' == $this->_conf['mode'])
            {
                $this->fElement = & new elExtSelect($this->_fName, $this->label, null );
                foreach ( $this->_opts as $ID=>$v )
                {
                    if ( 0 == $ID )
                    {
                        $this->fElement->add( array($ID=>$v) );  
                    }
                    else
                    {
                        $this->fElement->addGroup($v['mnf'], $v['tms']);    
                    }
                }
            }
            else
            {
                $this->fElement = & new elSelect($this->_fName, $this->label, null, $this->_opts );
            }
        }
        return true;
    }
    
    function _parseConf($conf)
    {
        list($mode, $country)      = explode(';', $conf);
        $this->_conf['mode']       = $mode;
        $this->_conf['useCountry'] = !empty($country);
    }
 
}


/***************************************************
 * Класс - элемент поиска
 * поиск по цене
 *
 **************************************************/
class elIShopSearchElPrice extends elIShopSearchElement
{
    /**
     * Настройки класса
     * step - шаг цены в форме выбора
     * num - кол-во шагов
     */
    var $_conf = array('step'=>200, 'num'=>10);

    function find()
    {
        $range = $this->fElement->getValue();
        if ( !empty($range) && strstr($range, '-') )
        {
            list($min, $max) = explode('-', $range); 
            $clause = ( $max > 0 )
                ? 'i.price NOT BETWEEN '.intval($min).' AND '.intval($max)
                : 'i.price<'.intval($min);
            $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE '.$clause.' AND r.id=i.id';
            $this->_db->query($sql);
        }
    }

    function toXml()
    {
        $xml  = "<element>\n";
        $xml .= "<type>select</type>\n";
        $xml .= "<name><![CDATA[".$this->_fName."]]></name>";
        $xml .= "<label>".$this->label."</label>\n";
        $xml .= "<selected>0</selected>\n";
        foreach ( $this->_opts as $ID=>$v )
        {
            $xml .= "<dict>\n";
            $xml .= "<value>".$ID."</value>\n";
            $xml .= "<label>".$v."</label>\n";
            $xml .= "</dict>\n";
        }
        
        $xml .= "</element>\n";
        return $xml;
    }

    function _load()
    {
        $this->_opts = array(0 => $this->defValue);
        
        if ( 0>=$this->_conf['step'] || 1>= $this->_conf['num'] )
        {
            $this->_parseConf('200;10');
        }
        for ( $i=1; $i<=$this->_conf['num']; $i++ )
        {
            $sum = $i*$this->_conf['step'];
            if ( 1 == $i )
            {
                $key   = '0-'.$sum;
                $label = sprintf( m('till %d'), $sum );
            }
            elseif ( $i == $this->_conf['num'] )
            {
                $key   = $sum.'-0';
                $label = sprintf( m('from %d'), $sum );
            }
            else
            {
                $prevSum = ($i-1)*$this->_conf['step']; 
                $key     = $prevSum.'-'.$sum;
                $label   = sprintf( m('from %d till %d'), $prevSum, $sum );
            }
            $this->_opts[$key] = $label;
        }
        return true;
    }
       
    function _parseConf($conf)
    {
        if ( strstr($conf, ';') )
        {
            list($step, $num) = explode(';', $conf);
            if ( 0<$step )
            {
                $this->_conf['step'] = (int)$step;
            }
            if ( 2<$num && $num<=100 )
            {
                $this->_conf['num'] = (int)$num;
            }
        }
    }
    
}

class elIShopSearchElProp extends elIShopSearchElement
{
    function find()
    {
        $value = $this->fElement->getValue(); 
        if ( false != ($this->fElement->getValue()) )
        {
            $sql = 'DELETE FROM '.$this->tbr.' WHERE id NOT IN ('
               .'SELECT DISTINCT p2i.i_id '
               .'FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS sp, '
               .$this->tbs['tbp2i'].' AS p2i '
               .'WHERE pv.p_id=sp.p_id AND UPPER(pv.value)=UPPER("'.mysql_real_escape_string($value).'") AND p2i.pv_id=pv.id AND  p2i.pv_id=pv.id '
               .' )';
           
           $this->_db->query($sql);   
        }
    }
    
    function getUsedItemsTypes()
    {
        $ret = array();
        $sql = 'SELECT t.id, t.name AS type, p.name '
            .'FROM '.$this->tbs['tbsp'].' AS sp, '.$this->tbs['tbp'].' AS p, '.$this->tbs['tbt'].' AS t '
            .'WHERE sp.s_id='.$this->ID.' AND p.id=sp.p_id AND t.id=p.t_id';
        $this->_db->query($sql);
        while ($r = $this->_db->nextRecord())
        {
            if ( empty( $ret[$r['id']] ) )
            {
                $ret[$r['id']] = array('itype'=>$r['type'], 'props'=>$r['name']);
            }
            else
            {
                $ret[$r['id']]['props'] .= ', '.$r['name'];
            }
        }
        return $ret;
    }
    
    function toXml()
    {
        $xml  = "<element>\n";
        $xml .= "<type>select</type>\n";
        $xml .= "<name><![CDATA[".$this->_fName."]]></name>";
        $xml .= "<label>".$this->label."</label>\n";
        $xml .= "<selected>0</selected>\n";
        foreach ( $this->_opts as $ID=>$v )
        {
            $xml .= "<dict>\n";
            $xml .= "<value>".$ID."</value>\n";
            $xml .= "<label>".$v."</label>\n";
            $xml .= "</dict>\n";
        }
        
        $xml .= "</element>\n";
        return $xml;
    }
    
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
     
    function _load()
    {
        $this->_opts = array(0 => $this->defValue);
        $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
        $sql = 'SELECT pv.p_id, pv.value, p.type '
            .'FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS sp, '.$this->tbs['tbp'].' AS p  '
            .'WHERE sp.e_id='.$this->ID.' AND pv.p_id=sp.p_id AND p.id=sp.p_id GROUP BY UPPER(pv.value)';

        $this->_db->query($sql);
        
        while ( $r = $this->_db->nextRecord() )
        {
            $this->_opts[$r['value']] = EL_IS_PROP_MLIST != $r['type']
                ? $r['value']
                : preg_replace($reg, sprintf(m('from %s till %s'), "\\1", "\\2"), $r['value']);    
        }
        return 1<sizeof($this->_opts);
    }
}

?>