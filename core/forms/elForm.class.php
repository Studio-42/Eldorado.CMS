<?php
//

elLoadMessages('Form');

if ( !defined('EL_DEFAULT_TEXT_SIZE') )
{
  define ('EL_DEFAULT_TEXT_SIZE', 50);
}
 if ( !defined('EL_DEFAULT_TA_COLS') )
 {
   define ('EL_DEFAULT_TA_COLS',   50);
 }
if ( !defined('EL_DEFAULT_TA_ROWS') )
{
  define ('EL_DEFAULT_TA_ROWS',   12);
}
if ( !defined('EL_DEFAULT_TA_SMALL_ROWS') )
{
  define ('EL_DEFAULT_TA_SMALL_ROWS',   5);
}
if ( !defined('EL_DEFAULT_EDITOR_HEIGHT') )
{
  define ('EL_DEFAULT_EDITOR_HEIGHT',   700);
}

require_once EL_DIR_CORE.'forms/elFormElement.class.php';
require_once EL_DIR_CORE.'forms/elFormInput.class.php';
require_once EL_DIR_CORE.'forms/elFormContainer.class.php';
require_once EL_DIR_CORE.'forms/elFormValidator.class.php';
require_once EL_DIR_CORE.'forms/elFormRenderer.class.php';
require_once EL_DIR_CORE.'forms/elFormSimpleRenderer.class.php';

$files = glob(EL_DIR_CORE.'forms/elements/el*.class.php');
foreach ( $files as $one )
{
  require_once $one;
}
$files = glob(EL_DIR_CORE.'forms/renderer/el*.class.php');
foreach ( $files as $one )
{
  require_once $one; //echo $one.'<br>';
}


class elForm extends elFormElement
{
  var $elEnv        = true;
  var $validator    = null;
  var $renderer     = null;
  var $inputs       = array();
  var $childs       = array();
  var $renderParams = array();
  var $jsScripts    = array();
  var $jsBaseURL    = '';
  var $_map         = array();
  var $_processed   = false;
  var $_submited;
  var $_submitedData = array();

  function __construct($name='elf', $label=null, $action=null, $method='POST', $attrs=null)
  {
    $attrs['method'] = 'POST' == strtoupper($method) ? 'POST' : 'GET';
    $attrs['action'] = $action ? $action : 'http://'.getenv('HTTP_HOST').getenv('REQUEST_URI');
    parent::__construct($name, $label, $attrs);
    $this->childs[md5(microtime())] = & new elHidden('_form_', null, $this->getName(), null, true) ;
    $this->setSubmittedData();
    if ( get_magic_quotes_gpc() || get_magic_quotes_runtime() )
    {
      $this->_submitedData = $this->_filter('stripslashes', $this->_submitedData);
    }
    $this->validator = & new elFormValidator;
    $this->elEnv = defined('EL_VER');
  }

  function elForm($name='elf', $label=null, $action=null, $method='POST', $attrs=null)
  {
    $this->__construct($name, $label, $action, $method, $attrs);
  }

  function setSubmittedData()
  {
    $this->_submitedData = 'POST' == $this->getAttr('method') ? $_POST : $_GET;
  }

  /**
   * добавляет эл-т к форме
   */
  function add( &$el, $renderParams=null )
    {
      $id = $el->getID();
      $this->_map[$el->getName()] = $id;
      $this->childs[$id] = & $el;
      $this->renderParams[$id] = $renderParams;
      $el->event('addToForm', $this);
    }

  /**
   * регистрирует ссылку элемент подкласса класса elInput в массиве inputs
   */
  function registerInput( &$el )
  {
    $id = $el->getID();
    $this->_map[$el->getName()] = $id;
    $this->inputs[$id] = &$el;
  }

  /**
   * возвращает ссылку на эл-т формы
   */
  function &get( $elname )
  {
    $id = $this->_nameMapping($elname);
    if ( isset($this->childs[$id]) )
    {
      return $this->childs[$id];
    }
    elseif( isset($this->inputs[$id]) )
    {
      return $this->inputs[$id];
    }
    return null;
  }

  /**
   * проверяет была ли послана форма
   * если да - вызвает метод _process для обновления данных в inputs и их проверки
   */
  function isSubmit()
  {
    if ( !$this->_processed )
    {
      $this->_proccess();
    }
    return $this->_submited;
  }

  /**
   * сообщает была ли форма отправлена и корректно заполнена
   */
  function isSubmitAndValid()
  {
    return $this->isSubmit() ? !$this->hasErrors() : false;
  }

  /**
   * сообщает о наличии ошибок при проверки формы
   */
  function hasErrors()
  {
    return sizeof($this->validator->errors);
  }

  function pushError( $elName, $errMsg )
  {
		elLoadMessages('Errors');
    $this->validator->errors[$this->_nameMapping($elName)] = $errMsg;
  }

	function getErrors()
	{
		elLoadMessages('Errors');
		return $this->validator->errors;
	}

  /**
   * возвращает строку со всеми сообщениями об ошибках
   */
  function errorsToString()
  {
    return implode("\n", $this->validator->errors );
  }

 /**
   * регистрирует новое правило для проверки в валидаторе
   * @param string  $name название правила
   * @param string  $type тип (now "regexp" only)
   * @param string  $rule правило (рег выражение)
   * @param string  $errMsg сообщение об ошибке для правила
   */
  function registerRule($name, $type, $rule, $errMsg)
  {
    $this->validator->setRule($name, $type, $rule, $errMsg);
  }

  /**
   * устанавливает правило для проверки элемента
   * @param string  $elname имя проверяемого эл-та
   * @param string  $rule название правила
   * @param misc    $data доп данные для правила
   * @param string  $errMsg сообщение об ошибке
   * @param bool    $required является ли элемент обязательным
   */
  function setElementRule($elname, $rule, $req=true, $data=null, $errMsg=null)
  {
    $id = $this->_nameMapping($elname);
    $this->validator->elementRule($id, $rule, $req, $data, $errMsg);
  }

  /**
   * simple wrapper for previous method
   */
  function setRequired( $elname, $rule='noempty' )
  {
    $this->setElementRule($elname, $rule); //print_r( $this->inputs);
  }

  /**
   * является ли элемент обязательным к заполнению
   */
  function isRequired($elname)
  {
    return $this->validator->isRequired($this->_nameMapping($elname));
  }

  function setJsBaseURL( $URL )
  {
    $this->jsBaseURL = $URL;
  }

  function addJsSrc($js, $type=EL_JS_CSS_SRC, $first=false)
  {
    if ( $this->elEnv )
    {
      return elAddJs($js, $type, $first);
    }
    $key = crc32($js);
    $js = array('src'=>$js, 't'=>(int)$type);

    if ( !iiset($this->jsSrcipts[$key]) )
    {
      if ( !$first )
      {
        $this->jsSrcipts[$key] = $js;
      }
      else
      {
        $this->jsSrcipts = array($key=>$js) + $this->jsSrcipts;
      }
    }
  }

  /**
   * возвращает все значения элементов формы
   */
  function getValue( $slashed=false)
  {
    $values = array(); $slashed=false;
    foreach ($this->inputs as $id=>$input)
    {
      if ( !is_null($val = $this->inputs[$id]->getValue()) )
      {
        $name = preg_replace('/(\[\])+$/', '', $this->inputs[$id]->getName());
        if ( false === ($pos = strpos($name, '[')) || false === ($pos2 = strpos($name, ']')) )
        {
          $values[$name] = $val;
        }
        else
        {
        	@$values[substr($name, 0, $pos)][substr($name, $pos+1, $pos2-$pos-1)] = $val;
        }
      }
    }
    return !$slashed ? $values : $this->_filter('addslashes', $values);
  }

  /**
   * возвращает значение эл-та с именем $elname
   */
  function getElementValue( $elname, $slashed=false )
  {
    if ( $el = & $this->get($elname) )
    {
      $value = $el->getValue();
      return !$slashed ? $value : $this->_filter('addslashes', $value);
    }
    return null;
  }

  function setRenderer( &$renderer )
  {
    $this->renderer = & $renderer;
  }

  function render()
  {
    echo $this->renderer->getHtml();
  }

  function _render()
  {
    $this->renderer->beginForm( $this->attrsToString(), $this->getLabel(),
                                $this->validator->errors, $this->jsScripts, $this->jsBaseURL );

    foreach ( $this->childs as $id=>$el )
    {
      if ( 'hidden' == $el->getAttr('type') )
      {
        $this->renderer->renderHidden($this->childs[$id]);
      }
      elseif ( isset($el->isCData) )
      {
        $this->renderer->renderCData($this->childs[$id], $this->renderParams[$id]);
      }
      else
      {
        $this->renderer->renderElement($this->childs[$id], $this->isRequired($el->getName()), $this->renderParams[$id]);
      }
    }
    $this->renderer->endForm();
  }

  function toHtml()
  {
    if ( !$this->renderer->renderComplite() )
    {
      $this->_render();
    }
    return $this->renderer->getHtml();
  }

  //===============   private methods   ========================//

  /**
   * обновляет данные элементов после submit'a формы
   */
  function _proccess()
  {
    if ( $this->_processed )
    {
      return;
    }
    $this->setSubmittedData();
    if ( isset($this->_submitedData['_form_']) && $this->_submitedData['_form_'] == $this->getName() )
    {
      $this->_submited = true;
      foreach ( $this->inputs as $id=>$input )
      {
        $this->inputs[$id]->event('submit', $this->_submitedData);
      }
      $this->validator->valid( $this->inputs );
    }
    $this->_processed = 1;
  }

  /**
   * Возвращает id эл-та по его имени
   */
  function _nameMapping($name)
  {
    return isset($this->_map[$name]) ? $this->_map[$name] : null;
  }

  function _filter( $func, $data )
  {
    if ( !is_array($data) )
    {
      return call_user_func( $func, $data );
    }
    else
    {
      $res = array();
      foreach ( $data as $k=>$v )
      {
        $res[$k] = $this->_filter($func, $data[$k]);
      }
      return $res;
    }
  }

}

?>