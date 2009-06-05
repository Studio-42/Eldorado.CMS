<?php

class elCaptcha extends elFormInput
{
    var $_code = '';
    var $events  = array(
                        'addToForm' => 'onAddToForm', 
                        'submit'    => 'onSubmit', 
                        'validate'  => 'selfValidate');
    var $_validResult = 0;

    function onAddToForm( &$form )
    {
        $form->registerInput($this);
        if ( empty($_SESSION['captchas'][$this->_id]) || strlen($_SESSION['captchas'][$this->_id]) != 5 )
        {
            $this->_generateCode();
        }
        else
        {
            $this->_code = $_SESSION['captchas'][$this->_id];
        }
        $form->setRequired( $this->getName() );
    }
    
    function getValue()
    {
        return $this->_validResult;
    }
    
    function _generateCode()
    {
        $this->_code = substr(md5(uniqid('')),-9,5);    
        $_SESSION['captchas'][$this->_id] = $this->_code; 
    }
    
    function selfValidate(&$errors, $args)
    {
        $input = $this->getAttr('value');
        $code  = $this->_code;
        $this->_generateCode();
        if ( $input != $code )
        {
            $this->_validResult     = 0;
            $errors[$this->getID()] = m('You entered invalid code');
        }
        $this->_validResult = 1;
        return (bool)$this->_validResult;
    }
    
    
    function toHtml()
    {
        $html = '<table cellspacing="0"><tr>';
        $html .= '<td style="vertical-align:middle;border:none"><img src="'.EL_BASE_URL.'/__capt__/'.$this->getID().'/" /></td>';
        $html .= '<td style="vertical-align:middle;border:none"><input type="text" size="12" name="'.$this->getName().'" /></td>';
        $html .= '</tr></table>';
        return $html;
    }
}

?>