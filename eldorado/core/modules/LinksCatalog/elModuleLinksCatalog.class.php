<?php

include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

class elModulelinksCatalog extends elCatalogModule
{
  var $tbc        = 'el_lcat_%d_cat';
  var $tbi        = 'el_lcat_%d_item';
  var $tbi2c      = 'el_lcat_%d_i2c';
  var $_itemClass = 'elLCatalogItem';

}


?>