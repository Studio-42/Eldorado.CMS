<?php
/**
 * Create catalog of text documents/articles
 *
 */
include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

class elModuleDocsCatalog extends elCatalogModule
{
	var $tbc        = 'el_dcat_%d_cat';
  var $tbi        = 'el_dcat_%d_item';
  var $tbi2c      = 'el_dcat_%d_i2c';
  var $_itemClass = 'elDCatalogItem';
}

?>