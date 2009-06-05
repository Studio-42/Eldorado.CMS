<?php

class elRndTemplatesEditor extends elModulerenderer
{

  function rndFilesList($tree)
  {
    $this->_setFile();

    foreach ($tree as $hash => $dir)
    {
      if (0 < $dir['level'])
      {
        $data = array(
          'name' => m($dir['name']),
          'level'=>$dir['level'],
          'descrip'=>$dir['descrip'],
          'hash'=>$hash,
          'path' => str_replace('./style', '', $dir['path'])
           );
        $this->_te->assignBlockVars('TE_TREE.TE_DIR.TE_DIR_NAME', $data, 0);
      }

      if (empty($dir['files']))
      {
        $this->_te->assignBlockVars('TE_TREE.TE_DIR.TE_DIR_NAME.TE_DIR_NOCTR', null, 3);
      }
      else
      {
        $this->_te->assignBlockVars('TE_TREE.TE_DIR.TE_DIR_FILES',
          array('name'=>$dir['name'], 'display'=>0<$dir['level']?'none': 'block', 'level'=>$dir['level']+1), 2);
        if (0 < $dir['level'])
        {
          $this->_te->assignBlockVars('TE_TREE.TE_DIR.TE_DIR_NAME.TE_DIR_CTR', array('name'=>$dir['name']), 3);
        }

        foreach ($dir['files'] as $hash=>$f)
        {
          $data = array('name' => $f['name'], 'mtime'=>$f['mtime'], 'descrip'=>$f['descrip'] );
          $this->_te->assignBlockVars('TE_TREE.TE_DIR.TE_DIR_FILES.TE_FILE', $data, 3);
          $block = 1<$f['perm']
            ? 'TE_TREE.TE_DIR.TE_DIR_FILES.TE_FILE.TE_FILE_EDIT'
            : 'TE_TREE.TE_DIR.TE_DIR_FILES.TE_FILE.TE_FILE_NOEDIT';
          $this->_te->assignBlockVars($block, array('hash'=>$hash), 4);

        }

      }


    }
  }
}


?>