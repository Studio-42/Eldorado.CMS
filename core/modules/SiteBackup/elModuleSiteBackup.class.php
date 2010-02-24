<?php
/*
* @package eldorado
* Create and restore site from backup
* Backup includes Data base dump, config file (/conf/main.conf.xml) and user uploaded files directory (/storage)
*/
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFS.class.php';
class elModuleSiteBackup extends elModule
{
  /**
  * list of backup files
  */
  var $_backups = array();
 /**
  * Flag - is backup dir exists and writable
  */
  var $_dirOK = false;
  /**
  * Disk quote for backups
  */
  var $_quote = 100;
  /**
  * Total size of backup files
  */
  var $_du = 0;
  /**
  * Requests - methods mapping (see parent class)
	*
 	* @var array
  */
  var $_mMap = array( 'create'  => array('m'=>'create',   'l'=>'Create backup',           'ico'=>'icoArc', 'g'=>'Actions'),
                      'upload'  => array('m'=>'upload',   'l'=>'Upload backup file',      'ico'=>'icoFileUpload', 'g'=>'Actions'),
                      'clean'   => array('m'=>'cleanAll', 'l'=>'Delete all backup files', 'ico'=>'icoDelete',
                                         'onClick'=>"return confirm('{m('Do You really want to delete all backups?')}');"),
                      'rm'      => array('m'=>'rm'),
                      'restore' => array('m'=>'restore')
                    );
	/**
 	 * Configuration: quote - max files size in MB, auto - auto backup period in days
 	 *
 	 * @var array
 	 */
	var $_conf = array('quote' => 100, 'auto'=>0);

	/**
	 * Name of group in conf file
	 *
	 * @var string
	 */
	var $_confID = 'backup';

  function defaultMethod()
  {
    $this->_initRenderer();
    if ( !$this->_dirOK )
    {
      return $this->_rnd->addToContent( m('Backup directory does not exists or has hot write permissions!') );
    }
    $this->_rnd->rndBackupsList( $this->_backups, $this->_quote, $this->_du );
  }

  /**
  * create new backup
  */
  function create($autoMode=false)
  {
  	if ( $this->_quote <= $this->_du )
  	{
    	return $autoMode
    		? false
    		: elThrow(E_USER_WARNING, 'Disk quote is exceeded! Remove older backup files or increase quote size.', null, EL_URL);
  	}

	$dump       = & elSingleton::getObj('elDbDump');
    $conf       = & elSingleton::getObj('elXmlConf');
    $host       = $conf->get('host', 'db');
    $db         = $conf->get('db',   'db');
    $user       = $conf->get('user', 'db');
    $pass       = $conf->get('pass', 'db');
    $dbFile     = EL_DIR_CONF.$db.'.sql';
    $backupFile = EL_DIR_BACKUP.'backup-'.time().'.tar.gz';
    
	$dump->writeDump($dbFile);

    $cmd = 'tar czfp '
            .escapeshellarg($backupFile).' '
	    	.'--exclude "./storage/.htaccess" '
            .escapeshellarg(EL_DIR_CONF.'main.conf.xml').' '
	    	.escapeshellarg($dbFile).' '
            .escapeshellarg(EL_DIR_STORAGE);

    exec( $cmd, $out, $code );
    if ( 0 < $code)
    {
    	return $autoMode
    		? false
    		: elThrow(E_USER_WARNING, 'Error creating archive. Here is info that may be usefull: "%s"', implode(',', $out), EL_URL);
    }

    if ( $autoMode )
    {
    	return true;
    }
    elMsgBox::put( m('Backup was created') );
    elLocation( EL_URL );
  }

  /**
  * upload backup file from client
  */
  function upload()
  {
    $form = & elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $form->setLabel( m('Upload backup file') );

    $f = & new elFileInput('bf', m('Backup file') );
    $f->setFileExt('tar.gz');
    $f->setFileExt('tgz');

    $form->add( $f );
    $form->setRequired('bf');

    if ( $form->isSubmitAndValid() )
    {
      if ( !$f->moveUploaded( null, EL_DIR_BACKUP, 0664) )
      {
        elThrow(E_USER_WARNING, 'Error uploading file "%s"! See more info in debug or error log.', null, EL_URL);
      }
      elMsgBox::put( sprintf( m('Backup file %s was successfully uploaded!'), $f->getFileName() ));
      elLocation( EL_URL );
    }

    $this->_initRenderer();
    $this->_rnd->addToContent( $form->toHtml() );
  }

  /**
  * delete all backup files
  */
  function cleanAll()
  {
    if ( elFS::rmdir(EL_DIR_BACKUP, true) )
    {
		elFS::mkdir(EL_DIR_BACKUP);
      	elMsgBox::put('All backup files was deleted');
    }
    elLocation(EL_URL);
  }

  /**
  * delete backup file if exists
  */
  function rm()
  {
    $hash = $this->_arg();
    if ( empty($this->_backups[$hash]) )
    {
      elThrow(E_USER_WARNING, 'File does not exists', null, EL_URL);
    }
    if ( !@unlink(EL_DIR_BACKUP.$this->_backups[$hash]['file']) )
    {
      elThrow(E_USER_WARNING, 'Could not delete file "%s"', $this->_backups[$hash]['file'], EL_URL);
    }
    elMsgBox::put( sprintf(m('Backup file %s was deleted'), $this->_backups[$hash]['file']) );
    elLocation(EL_URL);
  }

  /**
  * restore site from backup
  */
  	function restore()
  	{
    	$hash = $this->_arg();
    	if ( empty($this->_backups[$hash]) )
	    {
		    elThrow(E_USER_WARNING, 'File does not exists');
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
	    }

		$ark = &elSingleton::getObj('elArk'); 
	    $tmpDir = './tmp/b';
		
		include_once EL_DIR_CORE.'lib/elFS.class.php';
		if (!is_dir($tmpDir) && !elFS::mkdir($tmpDir, 0777)) 
		{
			elThrow(E_USER_WARNING, m('Could not create directory %s'), $tmpDir);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		if (!is_writable($tmpDir))
		{
			elThrow(E_USER_WARNING, m('Directory %s is not writable'), $tmpDir);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		if ( !$ark->extract('./backup/'.$this->_backups[$hash]['file'], $tmpDir) )
	    {
		    elThrow(E_USER_WARNING, $ark->getError(), null);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
	    }
	
		if ( !is_dir($tmpDir.'/conf') || !is_dir($tmpDir.'/storage'))
	    {
			elFS::rmdir($tmpDir);
	     	elThrow(E_USER_WARNING, 'Invalid backup file %s', $this->_backups[$hash]['file']);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
	    }
	
		$rm = elFS::find($tmpDir, '/^((ftp)|(\.ht.+)|(\.DS_Store)|(\.git.*))/i');
		foreach ($rm as $one)
		{
			if (is_dir($one))
			{
				elFS::rmdir($one);
			}
			else
			{
				@unlink($one);
			}
		}

		if (!elFS::move($tmpDir.DIRECTORY_SEPARATOR.'storage', EL_DIR))
		{
			elThrow(E_USER_WARNING, 'Could not copy %s to %s!', array($tmpDir.DIRECTORY_SEPARATOR.'storage', EL_DIR_STORAGE));
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		if (!elFS::move($tmpDir.DIRECTORY_SEPARATOR.'conf', EL_DIR))
		{
			elThrow(E_USER_WARNING, 'Could not copy %s to %s!', array($tmpDir.DIRECTORY_SEPARATOR.'conf', EL_DIR_CONF));
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		elFS::rmdir($tmpDir);
		
		$dump   = & elSingleton::getObj('elDbDump');
		$conf   = & elSingleton::getObj('elXmlConf');
	    $host   = $conf->get('host', 'db');
	    $db     = $conf->get('db',   'db');
	    $user   = $conf->get('user', 'db');
	    $pass   = $conf->get('pass', 'db');
	    $dbFile = EL_DIR_CONF.$db.'.sql';
		if (!is_file($dbFile) || !is_readable($dbFile))
		{
			elThrow(E_USER_WARNING, 'File %s does not exists', $dbFile);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		
		if (!$dump->restore($dbFile))
		{
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
			elThrow(E_USER_WARNING, 'Unable to complite restore from backup', EL_URL);
		}
		
		$msg = sprintf( m('Site was restored from file %s by date %s'),	$this->_backups[$hash]['file'], $this->_backups[$hash]['date'] ) ;
	    elMsgBox::put( $msg );
	    elLocation(EL_URL);
  }

  /**
  * check for backup directory and create if no exists
  * read list of existing backups
  * check for disk usage
  */
  function _onInit()
  {
    if ( !is_dir(EL_DIR_BACKUP) && !@mkdir(EL_DIR_BACKUP) )
    {
      $this->_mMap = array();
      return elThrow(E_USER_ERROR, 'Could not create directory %s', EL_DIR_BACKUP);
    }

    if ( !is_writable(EL_DIR_BACKUP) && !chmod(EL_DIR_BACKUP, 0755) )
    {
      $this->_mMap = array();
      return elThrow(E_USER_ERROR, 'Directory %s is not writable', EL_DIR_BACKUP);
    }
    $quote = (int)$this->_conf('quote');
    if ( $quote > 0 )
    {
    	$this->_quote = $quote;
    }
    $this->_dirOK = true;
    $list         = glob(EL_DIR_BACKUP.'backup-*.tar.gz');

    foreach ($list as $one )
    {
      $size                   = round( filesize($one)/1048576, 2);
      $this->_du             += $size;
      $hash                   = md5($one);
      $this->_backups[$hash]  = array('file' => basename($one),
                                      'hash' => $hash,
                                      'date' => date(EL_DATETIME_FORMAT, filemtime($one)),
                                      'size' => $size );
    }
    if ( $this->_du >= $this->_quote )
    {
      unset( $this->_mMap['create'] );
    }
  }

  function &_makeConfForm()
  {
  	$form = & parent::_makeConfForm();
  	$q = array(10, 50, 100, 150, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 2000 );
  	$c = array(m('No'), 1=>m('Daily'), 7=>m('Weekly'), 14=>m('Every two weeks'), 30=>m('Monthly') );
  	$form->add( new elSelect('quote', m('Backup disk quote (MB)'), (int)$this->_conf('quote'), $q, null, false, false));
  	$form->add( new elSelect('auto',  m('Create backup automaticaly'), $this->_conf('auto'), $c ));
  	return $form;
  }
}

?>