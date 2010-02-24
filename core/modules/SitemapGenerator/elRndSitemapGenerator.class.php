<?php

class elRndSitemapGenerator extends elModuleRenderer 
{
  function rndSitemap($file, $size, $date, $numLinks )
  {
    $this->_setFile();
    $vars = array('file'     => str_replace('./', '', $file),
                  'size'     => $size,
                  'date'     => $date,
                  'numLinks' => $numLinks);
    $this->_te->assignVars($vars);
  }

 }

?>