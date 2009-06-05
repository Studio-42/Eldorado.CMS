<?php

class elICartRnd extends elModuleRenderer
{
    var $_te        = null;
    var $_conf      = array();
    var $_dir       = '';
    var $_tpls      = array(
		'icart'    => 'icart.html',
        'delivery' => 'delivery.html',
        'summary'  => 'summary.html',
		'letter'   => 'letter.html'
		);
    var $_pricePrec = null;
    var $currInfo   = array(
                            'currency'     => 'USD',
                            'currencySign' => '$',
                            'currencyName' => 'US dollars',
                            'decPoint'     => '.',
                            'thousandsSep' => ','  
                            );

    /**
     * получает мсасив-конфигурацию корзины
     *
     **/
    function setConf( $conf )
    {
        $this->_conf = $conf;
    }
    /**
     * Устанавливает массив-кол-во шагов в корзине,
     * текущий шаг и макимально допустимый для клиента шаг
     **/
    function setSteps($steps, $curStepID, $maxStepID)
    {
        $this->_steps     = $steps;
        $this->_curStepID = $curStepID;
        $this->_maxStepID = $maxStepID; 
    }
    
    /**
     * получает настройки валюты для сайта
     **/
    function setCurrencyInfo( $cNfo )
    {
        $this->currInfo = $cNfo;   
    }
    
    
    /**
     * Рисует товары в корзине
     **/
    function rndICart( $items )
    {
        $this->_rndCommon();
        $this->_setFile('icart', 'ICART_CONTENT');
        $amount = 0;
        foreach ( $items as $i )
        {
			if (!$i['display_code'])
			{
				$i['code'] = '';
			}
            $amount += $i['sum'];
            $i['price'] = $this->_formatPrice($i['price']);
            $i['sum']   = $this->_formatPrice($i['sum']);
            $props = $i['props'];
            unset($i['props']);
            $this->_te->assignBlockVars('ICART_ITEM', $i);
            if ( !empty($props) )
            {
                foreach ( $props as $p )
                {
                    $data = array('name'=>$p[0], 'value'=>$p[1]);
                    $this->_te->assignBlockVars('ICART_ITEM.IC_IPROPS.IC_IPROP', $data, 2);
                }
            }
        }
        $this->_te->assignVars('amount', $this->_formatPrice($amount) );
    }
    
    /**
     * Рисует форму доставки-оплаты
     **/
    function rndDelivery()
    {
        $this->_rndCommon();
        $this->_setFile('delivery', 'ICART_CONTENT');
    }
    
    /**
     * Рисует форму - инфа о покупателе
     **/
    function rndAddressForm( $formHtml )
    {
        $this->_rndCommon();
        $this->_te->assignVars( 'ICART_CONTENT', $formHtml );
    }
    
    /**
     *  Рисует сводную таблицу заказа
     **/
    function rndSummary($items, $delivery, $amount, $addr)
    {
        $this->_rndCommon();
        $this->_te->assignVars('ICART_CONTENT', $this->getSummaryRnd($items, $delivery, $amount, $addr) );
    }
    
    /**
     * Возвращает содержимое сводной таблицы заказа
     * для отрисовки или отправке по e-mail
     **/
    function getSummaryRnd($items, $delivery, $amount, $addr, $rnd=true, $orderID=0)
    {

		if ($rnd)
		{
			$this->_setFile('summary', 'SUMMARY');
			$this->_te->assignBlockVars('ICART_BUTTON');
		}
		else
		{
			$this->_setFile('letter', 'SUMMARY', true);
			$this->_te->assignVars('date', date(EL_DATETIME_FORMAT));
			$this->_te->assignVars('order_id', $orderID);
		}
        //elPrintR($items);
        $this->_te->assignVars('currencySign', $this->currInfo['currencySign']);
        foreach ( $items as $i )
        {
			if ($rnd && !$i['display_code'])
			{
				$i['code'] = '';
			}
            $i['price'] = $this->_formatPrice($i['price']);
            $i['sum']   = $this->_formatPrice($i['sum']);
            $this->_te->assignBlockVars('ICART_ITEM', $i);
            if ( !empty($i['props']) )
            {
                foreach ( $i['props'] as $p )
                {
                    $data = array('name'=>$p[0], 'value'=>$p[1]);
                    $this->_te->assignBlockVars('ICART_ITEM.IC_IPROPS.IC_IPROP', array('name'=>$p[0], 'value'=>$p[1]), 2);
                }
            }
        }
        $this->_te->assignVars('amount', $this->_formatPrice($amount)); 
        
        if ( !empty($delivery) )
        {
            $this->_te->assignBlockVars('ICART_DELIVERY');
        }
        
        foreach ($addr as $field)
        {
            //$this->_te->assignBlockVars('ICART_ADDR', $field);
        }
        $this->_te->assignBlockFromArray('ICART_ADDR', $addr);
        $this->_te->parse('SUMMARY', null, false, true);
        return $this->_te->getVar('SUMMARY');
    }
    
    /*********************************************************/
    /**                      PRIVATE                        **/
    /*********************************************************/    
    /**
     * Загружает общий шаблон для корзины
     * Отрисовывает меню (шаги) корзины 
     **/
    function _rndCommon()
    {
        $this->_setFile();
        $this->_te->assignVars('currencySign', $this->currInfo['currencySign']);
        foreach ( $this->_steps as $ID=>$step )
        {
            if ( $ID<=$this->_maxStepID )
            {
                $link     = '<a href="'.EL_URL.'__icart__/'.$ID.'/">'.$step.'</a>';
                if ($ID == $this->_curStepID)
                {
                    $cssClass = 'iCartCurStep';
                    $this->_te->assignVars('iCartStepTitle', $step);
                }
                else
                {
                    $cssClass ='iCartStep';    
                }
                
            }
            else
            {
                $link     = $step;
                $cssClass = 'iCartStepDisable';
            }
            $this->_te->assignVars('stepID', $this->_curStepID);
            $this->_te->assignBlockVars('ICART_STEP', array('stepID'=>$ID, 'link'=>$link, 'cssClass'=>$cssClass) );
        }
    }

    /**
     * Возвращает цену отформатированную в соответствии и настройками
     **/
    function _formatPrice( $pr )
    {
        if ( null === $this->_pricePrec )
        {
            $this->_pricePrec = $this->_conf('priceIsInt') ? 0 : 2;
        }
        return 0 < $pr
            ? number_format(round($pr, $this->_pricePrec), $this->_pricePrec, $this->currInfo['decPoint'], $this->currInfo['thousandsSep'])
            : '';
    }
}

?>