<?php

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
    var $_db       = null;
    var $_opts     = array();
    var $_conf     = array();
    
    function elIShopSearchElement($tbs, $type)
    {
        $this->tbs  = $tbs;
        $this->type = $type;
        $this->_db  = & elSingleton::getObj('elDb');
    }
    
    function init($ID, $label, $tbr, $conf, $defValue)
    {
        $this->ID       = $ID;
        $this->_fName   = 'se['.$this->ID.']';
        $this->label    = $label;
        $this->tbr      = $tbr;
        $this->defValue = !empty($defValue) ? $defValue : m($this->defValue); 
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
    
    function save()
    {
        if ( !$this->form || !$this->form->isSubmitAndValid() )
        {
            elDebug('Class '.get_class($this).': form does not created or not submitted and valid' );
            return false;
        }
        return $this->_save( $this->form->getValue() );
    }
    
    function makeForm( $typeName )
    {
        $this->form = & elSingleton::getObj('elForm', 'se_'.$this->type, sprintf(m('New search element "%s"'), $typeName) );
        $this->form->setRenderer( elSingleton::getObj('elTplFormrenderer') );
        $this->form->add( new elText('label', m('Label')) );
        $this->form->setRequired('label');
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
                $this->fElement = & new elSelect($this->_fName, $this->label, null, array(0 => $this->eLabel) );
                $this->fElement->add( $this->_opts );
            }
        }
        return true;
    }
    
    function _parseConf( $conf ) {  }
    
    function _load()
    {
       return false; 
    }
    
    function _save( $data )
    {
        return false;
    }
 
}

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
            echo $sql.'<br>';
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
    
    function _save( $data )
    {
        $sql = 'INSERT INTO '.$this->tbs['tbse'].' (label, source, mtime) '
            .'VALUES (\''.mysql_real_escape_string($data['label']).'\', \'name\', '.time().')';
        return $this->_db->query($sql);
    }
    
}

class elIShopSearchElMnfTm extends elIShopSearchElement
{
    /**
     * Настройки класса
     * Показывать или нет страну производителя
     */
    var $_conf = array( 'mode'=>'mnf', 'useCountry' => false );
    
    function find()
    {
        if ( 0 < ($mnfID = (int)$this->fElement->getValue()) )
        {
            $mode = !empty($this->_conf['mode']) ? $this->_conf['mode'] : 'mnf';
            // ищем по производителям
            if ( 'mnf' == $mode )
            {
                $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.tm_id_id<>'.$tmID.' AND r.id=i.id';
                $this->_db->query($sql);
            } // ищем по торговым марками
            else
            {
                $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.mnf_id<>'.$mnfID.' AND r.id=i.id';
                //echo $sql.'<br>';
                $this->_db->query($sql);    
            }
            
        }
    }
    
    /***********************************************************************/
    /**                           PRIVATE                                 **/   
    /***********************************************************************/
    
    function _load()
    {
        $mode    = !empty($this->_conf['mode']) ? $this->_conf['mode'] : 'mnf';
        $mnfName =  empty($this->_conf['useCountry']) ? 'm.name ' : 'CONCAT(m.name, \', \', m.country) ';
        
        // загружаем только торговые марки
        if ( 'tm' == $mode )
        {
            $this->_opts = $this->_db->queryToArray('SELECT id, name FROM '.$this->tbs['tbtm'].' ORDER BY name', 'id', 'name');     
        } // загружаем производителей + торговые марки
        elseif ( 'mnftm' == $mode )
        {
            $sql = 'SELECT m.id AS mnfID, '.$mnfName.' AS mnf, t.id, t.name FROM '
                    .$this->tbs['tbmf'].' AS m, '.$this->tbs['tbtm'].' AS t '
                    .'WHERE t.mnf_id=m.id ORDER BY t.mnf_id, t.name';
            $this->_db->query($sql);
            while ($r = $this->_db->nextRecord())
            {
                if ( !isset($this->_opts[$r['mnfID']]) )
                {
                    $this->_opts[$r['mnfID']] = array('mnf' => $r['mnf'], 'tms'=>array() );
                }
                $this->_opts[$r['mnfID']]['tms'][$r['id']] = $r['name'];
            }  
        } // загружаем только производителей
        else
        {
            $this->_opts = $this->_db->queryToArray('SELECT id, '.$mnfName.' FROM '.$this->tbs['tbmnf'].' AS m ORDER BY name', 'id', 'name');
        }
        return !empty($this->_opts);
    }
    
    function _createFormElement()
    {
        if ( empty($this->fElement) )
        {
            $this->fElement = & new elExtSelect($this->_fName, $this->label, null, array(0 => $this->eLabel) );
            foreach ( $this->_opts as $ID=>$v )
            {
                if ( !is_array($v) )
                {
                    $this->fElement->opts[$ID] = $v;
                }
                else
                {
                    $this->fElement->addGroup($v['mnf'], $v['tms']);
                }
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
    
    function _makeForm($typeName)
    {
        parent::_makeForm($typeName);

        $modes = array(
                       'mnf'   => m('by manufacturer'),
                       'tm'    => m('by trade mark / display only trade marks'),
                       'mnftm' => m('by trade mark / display manufacturers and trade marks list')
                       );
        $this->form->add( new elSelect('mode', m('Search mode'), 'mnf', $modes) );
        $this->form->add( new elSelect('useCountry', m('Append country name to manufacturer name'), 0, $GLOBALS['yn']) );
    }
    
    function _save( $data )
    {
        $conf = mysql_real_escape_string($data['mode']).';'.mysql_real_escape_string($data['useCountry']);
        $sql = 'INSERT INTO '.$this->tbs['tbse'].' (label, source, conf_mnf, mtime) '
            .'VALUES (\''.mysql_real_escape_string($data['label']).'\', \'mnftm\', \''.$conf.'\', '.time().')';
        return $this->_db->query($sql);
    }
}

class elIShopSearchElTm extends elIShopSearchElement
{
    /**
     * Настройки класса
     * Если useMnf==true - торговые марки в списке в форме группируются по производителям
     * else - список будет содержать только торговые марки
     */
    var $_conf = array('useMnf' => true);
    
    function find()
    {
        if ( 0 < ($tmID = (int)$this->fElement->getValue()) )
        {
            $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE i.tm_id_id<>'.$tmID.' AND r.id=i.id';
            $this->_db->query($sql);
        }
    }
    
    function _createFormElement()
    {
        if ( empty($this->fElement) )
        {
            $this->fElement = & new elExtSelect($this->_fName, $this->label, null, array(0 => $this->eLabel) );
            foreach ( $this->_opts as $ID=>$v )
            {
                if ( !is_array($v) )
                {
                    $this->fElement->opts[$ID] = $v;
                }
                else
                {
                    $this->fElement->addGroup($v['mnf'], $v['tms']);
                }
            }
        }
        return true;
    }
    
    function _load()
    {
        if ( empty($this->_conf['useMnf']) )
        {
            $this->_opts = $this->_db->queryToArray('SELECT id, name FROM '.$this->tbs['tbtm'].' ORDER BY name', 'id', 'name');   
        }
        else
        {
            $sql = 'SELECT m.id AS mnfID, m.name AS mnf, t.id, t.name FROM '
                    .$this->tbs['tbmf'].' AS m, '.$this->tbs['tbtm'].' AS t '
                    .'WHERE t.mnf_id=m.id ORDER BY t.mnf_id, t.name';
            $this->_db->query($sql);
            while ($r = $this->_db->nextRecord())
            {
                if ( !isset($this->_opts[$r['mnfID']]) )
                {
                    $this->_opts[$r['mnfID']] = array('mnf' => $r['mnf'], 'tms'=>array() );
                }
                $this->_opts[$r['mnfID']]['tms'][$r['id']] = $r['name'];
            }
        }
        return !empty($this->_opts);
    }
    
    function _parseConf($conf)
    {
        $this->_conf['useMnf'] = !empty($conf);
    }
}

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
            //$clause = ( $max > 0 )
            //    ? 'i.price  BETWEEN '.intval($min).' AND '.intval($max)
            //    : 'i.price>='.intval($min);
            //$sql = 'SELECT r.id FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE '.$clause.' AND r.id=i.id';
            //echo $sql; elPrintR($this->_db->queryToArray($sql));
            $sql = 'DELETE r FROM '.$this->tbr.' AS r, '.$this->tbs['tbi'].' AS i WHERE '.$clause.' AND r.id=i.id';
            $this->_db->query($sql);
        }
    }

    function _makeForm($typeName)
    {
        parent::_makeForm($typeName);
        $this->form->add( new elText('step', m('Price step'), 200, array('size'=>12)) );
        $this->form->add( new elSelect('num', m('Number of steps'), 10, range(2, 20), null, false, false) );
        $this->form->setElementRule('step', 'numbers');
    }
    
    function _save($data)
    {
        $step = $data['step']>0 ? (int)$data['step'] : 200;
        $num  = $data['num'] > 1 && $data['num'] <= 100 ? (int) $data['num'] : 10;
        $sql  = 'INSERT INTO '.$this->tbs['tbse'].' (label, source, conf_price, mtime) '
                .'VALUES (\''.mysql_real_escape_string($data['label']).'\', \'price\', \''.$step.';'.$num.'\', '.time().')';
        return $this->_db->query($sql);
    }
    
    function _load()
    {
        if ( 0>=$this->_conf['step'] || 0>= $this->_conf['num'] )
        {
            return false;
        }
        for ( $i=1; $i<=$this->_conf['num']; $i++ )
        {
            $sum = $i*$this->_conf['step'];
            if ( 1 == $i )
            {
                $key = '0-'.$sum;
                $label = sprintf( m('till %d'), $sum );
            }
            elseif ( $i == $this->_conf['num'] )
            {
                $key = $sum.'-0';
                $label = sprintf( m('from %d'), $sum );
            }
            else
            {
                $prevSum = ($i-1)*$this->_conf['step']; 
                $key = $prevSum.'-'.$sum;
                $label = sprintf( m('from %d till %d'), $prevSum, $sum );
            }
            $this->_opts[$key] = $label;
        }
        return !empty($this->_opts);
    }
       
    function _parseConf($conf)
    {
        if ( strstr($conf, ';') )
        {
            list($step, $num) = explode(';', $conf);
            if (0<$step && $step<100001)
            {
                $this->_conf['step'] = (int)$step;
            }
            if (2<$num && $num<101)
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
        $value = $this->fElement->getValue(); //elPrintR($value);
        if ( false != ($this->fElement->getValue()) )
        {
            $sql = 'DELETE FROM '.$this->tbr.' WHERE id NOT IN ('
               .'SELECT DISTINCT p2i.i_id '
               .'FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS sp, '
               .$this->tbs['tbp2i'].' AS p2i '
               .'WHERE pv.p_id=sp.p_id AND UPPER(pv.value)=UPPER("'.$value.'") AND p2i.pv_id=pv.id AND  p2i.pv_id=pv.id '
               .' )';
           
           //echo $sql.'<br>';
           $this->_db->query($sql);   
        }
        
        //$this->_db->query('SELECT * FROM '.$this->tbr); //echo $this->_db->numRows();

/**
        $sql = ' SELECT t.id, t.name FROM '.$this->tbs['tbt'].' AS t, '.$this->tbs['tbp'].' AS p, '
            .$this->tbs['tbsp'].' AS sp WHERE p.id=sp.p_id AND t.id=p.t_id';
elPrintR($this->_db->queryToArray($sql, null));

        $sql = 'SELECT p2i.i_id '
            .'FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS sp, '
            .$this->tbs['tbp2i'].' AS p2i '
            .'WHERE pv.p_id=sp.p_id AND UPPER(pv.value)=UPPER("'.$value.'") AND p2i.pv_id=pv.id AND  p2i.pv_id=pv.id ORDER BY p2i.i_id';
        echo 'ONE';
        elPrintR($this->_db->queryToArray($sql, null, 'i_id'));
            
        $sql = 'SELECT r.id FROM '.$this->tbr.' AS r '
            .'WHERE id NOT IN ( '.
            'SELECT p2i.i_id '
            .'FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS sp, '
            .$this->tbs['tbp2i'].' AS p2i '
            .'WHERE pv.p_id=sp.p_id AND UPPER(pv.value)=UPPER("'.$value.'") AND p2i.pv_id=pv.id AND  p2i.pv_id=pv.id '
            .' )'
            ;
         
            echo 'here ->';
                elPrintR($this->_db->queryToArray($sql, null, 'id'));
               **/ 
         //echo $this->_db->affectedRows();
    }
    
    function getUsedItemsTypes()
    {
        $ret = array();
        $sql = 'SELECT t.id, t.name AS type, p.name FROM '.$this->tbs['tbsp'].' AS sp, '.$this->tbs['tbp'].' AS p, '.$this->tbs['tbt'].' AS t '
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
    
    function _load()
    {
        $sql = 'SELECT pv.p_id,  pv.value FROM '.$this->tbs['tbpval'].' AS pv, '.$this->tbs['tbsp'].' AS p '
            .'WHERE p.s_id='.$this->ID.' AND pv.p_id=p.p_id GROUP BY UPPER(pv.value)';
        $this->_opts = $this->_db->queryToArray($sql, 'value', 'value');
        //elPrintR($this->_db->queryToArray($sql));
        return !empty($this->_opts);
    }
}
?>