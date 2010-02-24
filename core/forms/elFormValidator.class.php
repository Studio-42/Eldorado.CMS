<?php

class elFormValidator
{
  var $rules = array(
         'required'     => array('regexp', '/.*/', '"%s"'),
         'noempty'      => array('regexp', '/[^\s]+/', '"%s" can not be empty'),
         'minlength'    => array('regexp',
                                 '/^(\s|\S){%d,}$/',
                                 '"%s" cant be shorter then %d chars'),
         'maxlength'    => array('regexp',
                                 '/^(\s|\S){0,%d}$/',
                                 '"%s" cant be longer then %d chars'),
         'nozero'       => array('regexp',
                                 '/^-?[1-9][0-9]*/',
                                 '"%s" cant contains zero'),
         'password'     => array('regexp',
                                 '/[a-z0-9\-\.]{4,20}/i',
                                 '"%s" must be valid password'),
         'email'        => array('regexp',
                                 '/^[a-zA-Z0-9\._-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/',
                                 '"%s" must contain valid email address'),
         'emailorblank' => array('regexp',
                                 '/(^$)|(^[a-zA-Z0-9\._-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$)/',
                                 '"%s" must contain valid email address'),
         'alfanum_lat'  => array('regexp',
                                 '/^[a-z0-9_\-\/]+$/i',
                                 '"%s" must contain only latin alfanum chars'),
        'alfanum_lat_dot'  => array('regexp',
                                 '/^[a-z0-9_\-\.]+$/i',
                                 '"%s" must contain only latin alfanum chars'),
         'numbers'      => array('regexp',
                                 '/^\d+$/i',
                                 '"%s" must contain only numbers'),
         'http_url'     => array('regexp',
                                 '/http:\/\/[^\s]+/i',
                                 '"%s" must contain valid URL start with http://'),
         'url'          => array('regexp',
                                 '/^(http|ftp|mailto):[^\s]+\.[a-z\-~]{2,4}/i',
                                 '"%s" must contain valid URL start with http:// or ftp://'),
         'el_vdir'      => array('regexp',
                                 '/^[a-z0-9_\-]{2,25}$/',
                                 '"%s" must contains from 2 till 25 latin alfanum chars or underscore/dash'),
         'letters'      => array('regexp',
         												 '/^[^\d\s]+$/i',
         												 '"%s" must contains only letters'),
         'letters_or_space'      => array('regexp',
         												 '/^[^\d]+$/i',
         												 '"%s" must contains only letters'),
         'phone'        => array('regexp',
         												 '/^\+?[1-9]?(\s*\(?[0-9]{1,7}\)?\s*)?([0-9]{2,4}\s*\-?\s*[0-9]{2,4}\s*\-?\s*[0-9]{2,4})$/',
         												 '"%s" must contains valid phone number'),
        'hostname'      => array('regexp',
         												 '/^([a-z0-9]([-a-z0-9]*[a-z0-9])?\.)+((a[cdefgilmnoqrstuwxz]|aero|arpa)|(b[abdefghijmnorstvwyz]|biz)|(c[acdfghiklmnorsuvxyz]|cat|com|coop)|d[ejkmoz]|(e[ceghrstu]|edu)|f[ijkmor]|(g[abdefghilmnpqrstuwy]|gov)|h[kmnrtu]|(i[delmnoqrst]|info|int)|(j[emop]|jobs)|k[eghimnprwyz]|l[abcikrstuvy]|(m[acdghklmnopqrstuvwxyz]|mil|mobi|museum)|(n[acefgilopruz]|name|net)|(om|org)|(p[aefghklmnrstwy]|pro)|qa|r[eouw]|s[abcdeghijklmnortvyz]|(t[cdfghjklmnoprtvwz]|travel)|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw])$/i',
         												 '"%s" must contains valid host name'),

         'dnshost_or_ip' => array('regexp',
         												 '/^(([a-z0-9]([-a-z0-9]*[a-z0-9])?\.)+((a[cdefgilmnoqrstuwxz]|aero|arpa)|(b[abdefghijmnorstvwyz]|biz)|(c[acdfghiklmnorsuvxyz]|cat|com|coop)|d[ejkmoz]|(e[ceghrstu]|edu)|f[ijkmor]|(g[abdefghilmnpqrstuwy]|gov)|h[kmnrtu]|(i[delmnoqrst]|info|int)|(j[emop]|jobs)|k[eghimnprwyz]|l[abcikrstuvy]|(m[acdghklmnopqrstuvwxyz]|mil|mobi|museum)|(n[acefgilopruz]|name|net)|(om|org)|(p[aefghklmnrstwy]|pro)|qa|r[eouw]|s[abcdeghijklmnortvyz]|(t[cdfghjklmnoprtvwz]|travel)|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw])\.)|(\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b)$/i',
         												 '"%s" must contains valid host name ended with dot or vali IP address'),
         'ip'            => array('regexp',
         												 '/^\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b$/',
         												 '"%s" must contains vali IP address'),

         );
  var $elemRules = array();//id=>(rule, data, msg, required)
  var $errors    = array();

  function setRule($name, $type, $rule, $errMsg)
  {
    $this->rules[$name] = array($type, $rule, $errMsg);
  }

  function elementRule($id, $rule, $req, $data, $errMsg)
  {
    $this->elemRules[$id] = array($rule, $data, $errMsg, $req);//elPrintR($this->elemRules[$id]);
  }

  function isRequired($id)
  {
    return isset($this->elemRules[$id][3]) ? $this->elemRules[$id][3] : false;
  }

  function getElementRule( $id )
  {
    $rulename = isset($this->elemRules[$id]) ? $this->elemRules[$id][0] : '';
    if ( !$rulename || !isset($this->rules[$rulename])  )
    {
      return null;
    }
    $rule = $this->rules[$rulename]; //print_r($rule);
    if ( 'func' != $rule[0] )
    {
      $rule[1] = sprintf( $rule[1], $this->elemRules[$id][1]);
    }
    $rule[2] = $this->elemRules[$id][2] ? $this->elemRules[$id][2] : $rule[2];
    $rule[3] = $this->elemRules[$id][3]; //echo  $this->elemRules[$id][2];
    $rule[4] = $this->elemRules[$id][1] ? $this->elemRules[$id][1] : '';
    return $rule;
  }

  function valid( &$inputs )
  {
    foreach ( $inputs as $id=>$input )
    {
    	$rule = $this->getElementRule($id); //elPrintR($rule);
    	if ($rule)
    	{
    		$rule[2] = sprintf( m($rule[2]), $input->getLabel() ? $input->getLabel() : $input->getName(), $rule[4]);
    	}
    	if (!empty($input->events['validate']))
    	{
    		if ( $inputs[$id]->event('validate', $this->errors, $rule[3], $rule[1], $rule[2]) )
    			{
    				continue;
    			}

    	}

      if ( !$rule || !$input->validate )
      {
        continue;
      }

      if ( 'func' == $rule[0] )
      {
        if ( function_exists($rule[1]) )
        {
          $errMsg = $rule[1]($inputs[$id], $rule[2], $rule[4]);
          if ( $errMsg )
          {
            $this->errors[$id] = $errMsg;
          }
        }
      }
      else
      {
        $value = $input->getValue();
        $checkValue = !is_array($value) ? $value : sizeof($value);

        if ( !preg_match($rule[1], $checkValue) && ($this->isRequired($id) || $checkValue) )
        {
          $this->errors[$id] = $rule[2];
        }
      }
    }
  }

}

?>