<?php

if (function_exists('mb_internal_encoding'))
{
	mb_internal_encoding('UTF-8');
}


class elFinder {
	
	var $_options = array(
		'root'      => './',
		'URL'       => '',
		'role'      => 'user',
		'fileUmask' => 0666,
		'dirUmask'  => 0777,
		'tplDir'    => '',       
		'tplName'   => 'FILEMANAGER',
		'lang'      => 'en',
		'tmbDir'    => '.tmb',
		'tmbSize'   => 48,
		'mimetypes' => array(),
		'acl'       => null,
		'role'      => 'user',
		'defaults' => array(
			'read'  => true,
			'write' => false
			),
		'perms' => array()
		);
		
	var $_views      = array('list', 'ismall', 'ibig');
	var $_te         = null;
	var $_translator = null;
	var $_img        = null;
	var $_imglib     = '';
	
	var $_mimetypes = array(
		'directory'                     => 'Directory',
		'text/plain'                    => 'Plain text',
	    'text/x-php'                    => 'PHP source',
		'text/javascript'               => 'Javascript source',
		'text/css'                      => 'CSS style sheet',  
	    'text/html'                     => 'HTML document', 
		'text/x-c'                      => 'C source', 
		'text/x-c++'                    => 'C++ source', 
		'text/x-shellscript'            => 'Unix shell script',
	    'text/rtf'                      => 'Rich Text Format (RTF)',
		'text/rtfd'                     => 'RTF with attachments (RTFD)', 
	    'text/xml'                      => 'XML document', 
		'application/xml'               => 'XML document', 
		'application/x-tar'             => 'TAR archive', 
	    'application/x-gzip'            => 'GZIP archive', 
	    'application/x-bzip2'           => 'BZIP archive', 
	    'application/x-zip'             => 'ZIP archive',  
	    'application/zip'               => 'ZIP archive',  
	    'application/x-rar'             => 'RAR archive',  
	    'image/jpeg'                    => 'JPEG image',   
	    'image/gif'                     => 'GIF Image',    
	    'image/png'                     => 'PNG image',    
	    'image/tiff'                    => 'TIFF image',   
	    'image/vnd.adobe.photoshop'     => 'Adobe Photoshop image',
	    'application/pdf'               => 'Portable Document Format (PDF)',
	    'application/msword'            => 'Microsoft Word document',  
		'application/vnd.ms-office'     => 'Microsoft Office document',
		'application/vnd.ms-word'       => 'Microsoft Word document',  
	    'application/msexel'            => 'Microsoft Excel document', 
	    'application/vnd.ms-excel'      => 'Microsoft Excel document', 
		'application/octet-stream'      => 'Application', 
		'audio/mpeg'                    => 'MPEG audio',  
		'video/mpeg'                    => 'MPEG video',  
		'video/x-msvideo'               => 'AVI video',   
		'application/x-shockwave-flash' => 'Flash application', 
		'video/x-flv'                   => 'Flash video'
		);
	
	var $_m = array(
		'cd'     => '_cd',
		'tree'   => '_getTree',
		'open'   => '_open',
		'info'   => '_info',
		'url'    => '_url',
		'mkdir'  => '_mkdir',
		'rename' => '_rename',
		'edit'   => '_edit',
		'rm'     => '_rm',
		'copy'   => '_copy',
		'upload' => '_upload'
		);
	
	var $_allowed = array();
		
	function __construct($opts=array())
	{
		foreach ($this->_options as $k=>$v) 
		{
			if (isset($opts[$k])) 
			{
				$this->_options[$k] = !is_array($v) ? $opts[$k] : array_merge($v, $opts[$k]);
			}
		}
		$this->_options['root'] = realpath($this->_options['root'] ? $this->_options['root'] : './').'/';
		if (!$this->_options['tplDir']) 
		{
			$this->_options['tplDir'] = dirname(__FILE__).'/tpl/';
		}
		if ($this->_options['URL']{strlen($this->_options['URL'])-1} != '/') 
		{
			$this->_options['URL'] .= '/';
		}
		
		foreach ($this->_options['mimetypes'] as $m)
		{
			$this->_allowed[] = $this->_fileKind($m);
		}
		$this->_allowed = array_unique($this->_allowed);
		$this->_image = new elImage();
	}
		
	function elFinder($opts=array()) 
	{
		$this->__construct($opts);
	}
	
	function setTE(&$te) 
	{
		$this->_te = & $te;
	}
	
	function setTranslator($tr)
	{
		$this->_translator = $tr;
	}
		
	function autorun() 
	{

		if (!$this->_isAllowed($this->_options['root'], 'read')) {
			exit('<h1 style="color:red">Access denied!</h1>');
		}
		
		$cmd = !empty($_GET['cmd']) ? trim($_GET['cmd']) : (!empty($_POST['cmd']) ? trim($_POST['cmd']) : '');
		
		if (!empty($this->_m[$cmd]) && method_exists($this, $this->_m[$cmd])) 
		{
			$this->{$this->_m[$cmd]}();
		}
		else {
			$this->_rnd();
		}
		exit();
	}
		
	/********************************************************************/
	/***                     Манипуляции с файлами                    ***/
	/********************************************************************/
		
	function _cd(){
		$target = !empty($_GET['target']) ? $_GET['target'] : '';
		$dir = $this->_find($target);
		exit($this->_rndDir($dir ? $dir : $this->_options['root']));
	}
		
	function _getTree() 
	{
		exit($this->_rndTree());
	}
		
	/**
	 * Выводит содержимое файла в браузер
	 *
	 * @return void
	 **/
	function _open()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			header('HTTP/1.x 404 Not Found'); 
			exit('File was not found. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read'))
		{
			header('HTTP/1.0 403 Acces denied');
			exit('Access denied');
		}
		if (false == ($info = elFileInfo::info($target)) || 'directory' == $info['mimetype'])
		{
			header('HTTP/1.x 404 Not Found'); 
			exit('File not found');
		}
		if (!$info['read'])
		{
			header('HTTP/1.0 403 Acces denied');
			exit('Access denied');
		}
		header("Content-Type: ".$info['mimetype']);
		header("Content-Disposition: ".(substr($info['mimetype'], 0, 5) == 'image' || substr($info['mimetype'], 0, 4) == 'text' ? 'inline' : 'attacments')."; filename=".$info['basename']);
		header("Content-Location: ".str_replace($this->_options['root'], '', $target));
		header("Content-Length: " .$info['size']);
		header("Connection:close");
		readfile($target);
		exit();
	}
	
		
	function _url() 
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			$this->_jsonError('File was not found. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read'))
		{
			$this->_jsonError('Access denied.');
		}
		if (false == ($info = elFileInfo::info($target)))
		{
			$this->_jsonError('File was not found.');
		}
//		$url = $this->_options['URL'].substr($target, strlen($this->_options['root'])+1);	
		$url = $this->_path2url($target);
		exit(elJSON::encode(array('url' => $url)));
	}
		
	/**
	 * Информация о файле/директории
	 *
	 * @return void
	 **/
	function _info()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			exit('<div class="el-dialogform-error">'.$this->_translate('File was not found. Invalid parameters.').'</div>');
		}
		if (!$this->_isAllowed($current, 'read'))
		{
			exit('<div class="el-dialogform-error">'.$this->_translate('Access denied.').'</div>');
		}
		if (false == ($info = elFileInfo::info($target)))
		{
			exit('<div class="el-dialogform-error">'.$this->_translate('File was not found.').'</div>');
		}
		$currentInfo = elFileInfo::info($current);
		$read  = $info['read']  && $this->_isAllowed($info['path'], 'read')  ? 'read' : '';
		if ($info['mimetype'] == 'directory')
		{
			$write = $info['write'] && $this->_isAllowed($info['path'], 'write') ? 'write' : '';
		}
		else
		{
			$write = $info['write'] && $currentInfo['write'] && $this->_isAllowed($currentInfo['path'], 'write') ? 'write' : '';
		}
		
		$html = '<table>';
		$html .= '<tr><td>'.$this->_translate('File name').'</td><td>'.$info['basename'].'</td></tr>';
		$html .= '<tr><td>'.$this->_translate('Kind').'</td><td>'.$this->_fileKind($info['mimetype']).'</td></tr>';
		$html .= '<tr><td>'.$this->_translate('Size').'</td><td>'.$info['hsize'].'</td></tr>';
		$html .= '<tr><td>'.$this->_translate('Modified').'</td><td>'.date('F d Y H:i:s', $info['mtime']).'</td></tr>';
		$html .= '<tr><td>'.$this->_translate('Last opened').'</td><td>'.date('F d Y H:i:s', $info['atime']).'</td></tr>';
		$html .= '<tr><td>'.$this->_translate('Access').'</td><td>'.($this->_translate($read).' '.$this->_translate($write)).'</td></tr>';

		if (substr($info['mimetype'], 0, 5) == 'image' && false != ($s = getimagesize($target)))
		{
			$html .= '<tr><td>'.$this->_translate('Dimensions').'</td><td>'.($s[0].'px x '.$s[1].'px').'</td></tr>';
		}
		if (substr($info['mimetype'], 0, 4) == 'text')
		{
			$html .= '<tr><td>'.$this->_translate('Charset').'</td><td>'.$info['charset'].'</td></tr>';
		}
		$html .= '</table>';
		exit($html);
	}
	
	
	/**
	 * Создание директории
	 *
	 * @return void
	 **/
	function _mkdir()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])))
		{
			$this->_jsonError('Unable to create directory. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read')
		||  !$this->_isAllowed($current, 'write'))
		{
			$this->_jsonError('Access denied.');
		}
		if (empty($_GET['dirname']) || false == ($dirname = $this->_checkName($_GET['dirname'])))
		{
			$this->_jsonError('Invalid directory name');
		}
		$path = $current.DIRECTORY_SEPARATOR.$dirname;
		if (file_exists($path))
		{
			$this->_jsonError('Directory or file with the same name already exists');
		}
		if (!@mkdir($path, $this->_options['dirUmask']))
		{
			$this->_jsonError('Unable to create directory %s', array($dirname));
		}
		@chmod($path, $this->_options['dirUmask']);
		$this->_jsonMessage('Directory %s was created', $dirname);
	}
	
	
	/**
	 * Переименования файла/директории в  текущей диреkтории
	 *
	 * @return void
	 **/
	function _rename()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			$this->_jsonError('Unable to rename. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read')
		||  !$this->_isAllowed($current, 'write')
		||  (is_dir($target) && !$this->_isAllowed($target, 'write'))
		||  !is_writable($target))
		{
			$this->_jsonError('Access denied.');
		}
		if (empty($_GET['newname']) || false == ($newname = $this->_checkName($_GET['newname'])))
		{
			$this->_jsonError('Invalid name');
		}
		if ($newname == basename($target))
		{
			$this->_jsonError('New name is equal to old one.');
		}
		$newTarget = $current.DIRECTORY_SEPARATOR.$newname;
		if (file_exists($newTarget))
		{
			$this->_jsonError('File or directory with the same name already exists');	
		}
		
		if (!@rename($target, $newTarget))
		{
			$this->_jsonError('Rename %s to %s failed', array(basename($target), $newTarget));
		}
		if (elFileInfo::isWebImage($newTarget))
		{
			$tmbDir = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
			if (elFS::mkdir($tmbDir))
			{
				$oldTmb = $tmbDir.DIRECTORY_SEPARATOR.basename($target);
				$newTmb = $tmbDir.DIRECTORY_SEPARATOR.basename($newTarget);
				if (file_exists($oldTmb))
				{
					@rename($oldTmb, $newTmb);
				}
			}
		}
		$this->_jsonMessage('%s was renamed to %s', array(basename($target), $newname));
	}
	
	
	function _edit() {
		if (empty($_POST['current']) || false == ($current = $this->_find($_POST['current'])) 
		||  empty($_POST['target'])  || false == ($target  = $this->_find(trim($_POST['target']), $current)) )
		{
			header('HTTP/1.x 404 Not Found'); 
			exit('File was not found. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read'))
		{
			header('HTTP/1.0 403 Acces denied');
			exit('Access denied');
		}
		if (false == ($info = elFileInfo::info($target)) || 'directory' == $info['mimetype'])
		{
			header('HTTP/1.x 404 Not Found'); 
			exit('File not found');
		}
		if (!$info['read'])
		{
			header('HTTP/1.0 403 Acces denied');
			exit('Access denied');
		}
		
		$data = trim($_POST['content']);
		if (false!= ($fp = fopen($target, 'wb')))
		{
			fwrite($fp, $data);
			fclose($fp);
			exit(elJSON::encode( array('message' => $this->_translate('File was saved')) ));
		} else {
			exit(elJSON::encode( array('error' => $this->_translate('Unable to save file')) ));
		}
	}
	
	/**
	 * Копирует/перемещает файлы/директории
	 *
	 * @return void
	 **/
	function _copy()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current']))
		||  empty($_GET['source']) || false == ($source = $this->_find($_GET['source'])))
		{
			$this->_jsonError('Unable to paste files. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read')
		||  !$this->_isAllowed($source,  'read')
		||  !$this->_isAllowed($current, 'write'))
		{
			$this->_jsonError('Access denied.');
		}
		if (empty($_GET['files']))
		{
			$this->_jsonError('Unable to paste files. No one file was copied.');
		}
		$rm = !empty($_GET['move']) && 'true' == $_GET['move'];
		
		
		for ($i=0, $s=sizeof($_GET['files']); $i < $s; $i++) 
		{ 
			if (empty($_GET['files'][$i]) || false ==($src = $this->_find($_GET['files'][$i], $source)))
			{
				$this->_jsonError('File does not exists');
			}
			if ($rm && !$this->_isAllowed($source,  'write')) 
			{
				$this->_jsonError('Access denied');
			}
			$name    = basename($src);
			$isImage = elFileInfo::isWebImage($src);
			if (!elFS::copy($src, $current))
			{
				$this->_jsonError('Unable to copy %s', $name);
			}
			if ($rm && !elFS::rm($src))
			{
				$this->_jsonError('Unable to delete %s', $name);
			}
			
			if ($isImage)
			{
				$dir       = dirname($src).DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
				$tmb       = $dir.DIRECTORY_SEPARATOR.$name;
				$targetDir = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
				$targetTmb = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'].DIRECTORY_SEPARATOR.$name;
				if (file_exists($tmb) && elFS::mkdir($targetDir) && elFS::copy($tmb, $targetDir.DIRECTORY_SEPARATOR.$name))
				{
					$rm && @unlink($tmb);
				}
			}
		}
		$this->_jsonMessage('Paste complite');
	}
	
	/**
	 * Удадение файла/директории
	 *
	 * @return void
	 **/
	function _rm()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current']))
		||  empty($_GET['target']) || !is_array($_GET['target']))
		{
			$this->_jsonError('Unable to delete. Invalid parameters.');
		} 
		if (!$this->_isAllowed($current, 'read')
		||  !$this->_isAllowed($current, 'write')
		)
		{
			$this->_jsonError('Access denied.');
		}
		foreach ($_GET['target'] as $hash)
		{
			if (false == ($target = $this->_find($hash, $current)))
			{
				$this->_jsonError('File not found.');
			}
			if ((is_dir($target) && !$this->_isAllowed($target, 'write'))
			|| !is_writable($target))
			{
				$this->_jsonError('Access denied.');
			}
			$isImage = elFileInfo::isWebImage($target);
			if (!elFS::rm($target))
			{
				$this->_jsonError('Unable to delete %s', basename($target));
			}
			if ($isImage)
			{
				$tmb = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'].DIRECTORY_SEPARATOR.basename($target);
				file_exists($tmb) && unlink($tmb);
			}
			
		}
		$this->_jsonMessage('Files removed');
	} 
	
	/**
	 * Загрузка файлов
	 *
	 * @return void
	 **/
	function _upload()
	{
		if (empty($_POST['current']) || false == ($current = $this->_find($_POST['current'])))
		{
			$this->_jsonError('Unable to upload files. Invalid parameters.');
		}
		if (!$this->_isAllowed($current, 'read')
		||  !$this->_isAllowed($current, 'write'))
		{
			$this->_jsonError('Access denied.');
		}
		if (empty($_FILES['fm-file']))
		{
			$this->_jsonError('Select at least one file to upload');
		}
		$total = count($_FILES['fm-file']['name']);
		$ok = $failed = array();

		for ($i=0; $i < $total; $i++) 
		{ 
			if (!empty($_FILES['fm-file']['name'][$i]))
			{
				if ($_FILES['fm-file']['error'][$i] > 0)
				{
					switch ($_FILES['fm-file']['error'][$i]) {
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$error = 'File exceeds the maximum allowed filesize';
							break;
						case UPLOAD_ERR_PARTIAL:
							$error = 'File was only partially uploaded';
							break;
						case UPLOAD_ERR_NO_FILE:
							$error = 'No file was uploaded';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$error = 'Missing a temporary folder';
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$error = 'Failed to write file to disk';
							break;
						case UPLOAD_ERR_EXTENSION:
							$error = 'Not allowed file type';
					}
					$failed[] = $_FILES['fm-file']['name'][$i].' : '.($error);
				}
				elseif (!$this->_checkName($_FILES['fm-file']['name'][$i]))
				{
					$failed[] = $_FILES['fm-file']['name'][$i].' : '.$this->_translate('Invalid file name');
				}
				elseif (!$this->_checkMime($_FILES['fm-file']['tmp_name'][$i]))
				{
					$failed[] = $_FILES['fm-file']['name'][$i].' : '.$this->_translate('Not allowed file type');
				}
				else
				{
					$target = $current.DIRECTORY_SEPARATOR.$_FILES['fm-file']['name'][$i];
					if (move_uploaded_file($_FILES['fm-file']['tmp_name'][$i], $target))
					{
						@chmod($target, $this->_options['dirUmask']);
						$ok[] = $_FILES['fm-file']['name'][$i];
					}
					else
					{
						$failed[] = $_FILES['fm-file']['name'][$i].' : '.('Unable to save uploaded file');
					}
				}
			}
		}
		if (!sizeof($ok))
		{
			exit(elJSON::encode( array('error' => ('Files upload failed'), 'failed' => $failed) ));
		}

		$message = !($failed) 
			? $this->_translate('Files successfully uploaded')
			: $this->_translate( sprintf('Following files was uploaded: %s', implode(', ', $ok)));
		
		exit(elJSON::encode( array('message' => $message, 'failed' => $failed) ));
	}
	
	/********************************************************************/
	/***                методы - отрисовщики контента                 ***/
	/********************************************************************/	

	/**
	 * Возвращает распарсеный шаблон файлового менеджера
	 *
	 * @return string
	 **/	
	function _rnd() 
	{
		$this->_te();
		$this->_te->setFile($this->_options['tplName'], $this->_options['tplDir'].DIRECTORY_SEPARATOR.'default.html');
		
		$this->_rndTree();
		$this->_rndDir($this->_options['root']);
		$this->_te->parse($this->_options['tplName'], null, false, true, true);
		echo $this->_te->getVar($this->_options['tplName']);
	}
		
	
	/**
	 * Возвращает распарсеный шаблон дерева директорий
	 *
	 * @return string
	 **/
	function _rndTree()
	{
		$tree = $this->_tree();
		$this->_te();
		$this->_te->setFile('FM_TREE', $this->_options['tplDir'].DIRECTORY_SEPARATOR.'tree.html');
		$this->_te->assignVars('tree', $this->_tree2htmllist($tree['dirs']));
		$this->_te->assignVars('fm_root_hash', $tree['hash']);
		$this->_te->assignVars('home', basename($this->_options['root']));
		$this->_te->parse('FM_TREE');
		return $this->_te->getVar('FM_TREE');
	}
	
	
	/**
	 * Возвращает распарсеный шаблон текущей директории
	 *
	 * @return string
	 **/
	function _rndDir($path)
	{
		$view = !empty($_GET['view']) && in_array($_GET['view'], $this->_views) ? $_GET['view'] : $this->_views[0];
		$this->_te();
		$filesNum  = 0;
		$filesSize = 0;
		$cnt        = 0;
		$wrap       = 'ismall' == $view ? 12  : 17;
		$this->_te->setFile('FM_CONTENT', $this->_options['tplDir'].DIRECTORY_SEPARATOR.('list' == $view ? 'list.html' : 'icons.html'));
		$this->_te->assignVars(array(
			'Name'   => $this->_translate('Name'),
			'Access' => $this->_translate('Access'),
			'Date modified' => $this->_translate('Date modified'),
			'Size' => $this->_translate('Size'),
			'Kind' => $this->_translate('Kind'),
			'Back' => $this->_translate('Back'),
			'Reload' => $this->_translate('Reload'),
			'Select file' => $this->_translate('Select file'),
			'Open' => $this->_translate('Open'),
			'Rename' => $this->_translate('Rename'),
			'Edit text document' => $this->_translate('Edit text document'),
			'Delete' => $this->_translate('Delete'),
			'Get info' => $this->_translate('Get info'),
			'Create directory' => $this->_translate('Create directory'),
			'Upload files' => $this->_translate('Upload files'),
			'Copy' => $this->_translate('Copy'),
			'Cut' => $this->_translate('Cut'),
			'Paste' => $this->_translate('Paste'),
			'View as big icons' => $this->_translate('View as big icons'),
			'View as small icons' => $this->_translate('View as small icons'),
			'View as list' => $this->_translate('View as list'),
			'Toggle view of actions results dialogs' => $this->_translate('Toggle view of actions results dialogs'),
			'Help'  => $this->_translate('Help'),
			'Files' => $this->_translate('Files')
			));
		$this->_te->assignVars('view', $view);
		$cwd = elFileInfo::info($path);
		$p   = str_replace($this->_options['root'], '', $path);
		$info = array(
			'key'         => $cwd['hash'],
			'path'        => $p ? $p: '/',
			'filesNum'    => 0,
			'filesSize'   => 0,
			'write'       => $cwd['write'] && $this->_isAllowed($path, 'write'),
			'view'        => $view,
			'postMaxSize' => ini_get('post_max_size'),
			'allowed'     => implode(', ', $this->_allowed)
			);
		// echo $path.' '.$this->_isAllowed($path, 'write').'<br>';
		if (!$cwd['read'])
		{
			$this->_te->assignVars('info', elJSON::encode($info));
			$this->_te->parse('FM_CONTENT');
			return $this->_te->getVar('FM_CONTENT');
		}
		$content = elFS::lsall($path); 
		foreach ($content as $item)
		{
			if ('directory' != $item['mimetype'] || $this->_isAllowed($item['path'], 'read'))
			{
				if ($item['mimetype'] == 'directory') {
					$item['icon_class'] = 'directory';
				} else {
					$item['icon_class'] = $this->_cssClass($item['mimetype']);
				}
				$read  = $item['read']  ? 'read' : '';
				$item['disabled'] = !$read ? 'disabled' : '';
				
				
				
				if ('directory' != $item['mimetype']) 
				{
					$info['filesNum']++;
					$info['filesSize'] += $item['size'];
					$write = $item['write'] && $info['write'] ? 'write' : '';
				}
				else
				{
					$write = $item['write'] && $this->_isAllowed($item['path'], 'write') ? 'write' : '';
				}
				
				$item['type'] = ('directory' == $item['mimetype'] ? 'dir' : ($item['icon_class'] == 'image' ? 'image' : 'file'))
					.'-'.($read ? 'r' :'').($write ? 'w' :'');
				if (false !== strpos($item['mimetype'], 'text') && $item['mimetype'] != 'text/rtf' && $item['mimetype'] != 'text/rtfd') {
					$item['type'] .= ' text';
				}
					
				if ('list' == $view)
				{
					$item['rowClass'] = ($cnt++%2) ? 'odd' : '';
					$item['kind']     = $this->_fileKind($item['mimetype']);
					$item['mdate']    = date('M d Y H:i:s', $item['mtime']);
					$item['access']   = $this->_translate($read.' '.$write);
				}
				else
				{
					if ($view == 'ibig' 
					&&( $item['mimetype'] == 'image/jpeg' || $item['mimetype'] == 'image/gif' || $item['mimetype'] == 'image/png')) 
					{
						if (false != ($tmb = $this->_tmb($item)))
						{
							$item['style'] = ' style="background:url(\''.$this->_path2url($tmb).'\') center center no-repeat;"';
						}
					}
					$item['name'] = $this->_wrapFileName($item['basename'], $wrap);
				}
				$this->_te->assignBlockVars('FILE', $item);
			}
		}
		$info['filesSize'] = elFileInfo::formatSize($info['filesSize']);
		$this->_te->assignVars('info', elJSON::encode($info));
		$this->_te->parse('FM_CONTENT', null, false, true);
		return $this->_te->getVar('FM_CONTENT');
	}
	
	function _fileKind($mtype) 
	{
		if (isset($this->_mimetypes[$mtype])) 
		{
			return $this->_translate($this->_mimetypes[$mtype]); 
		}
		switch ($mtype)
		{
			case 'image': return $this->_translate('Image');
			case 'text':  return $this->_translate('Text document');
			case 'audio': return $this->_translate('Audio file');
			case 'video': return $this->_translate('Video file');
			default:      return $this->_translate('Unknown');
		}
	}
	
	/**
	 * Возвращает url иконки или превью
	 *
	 * @param  array  $file - массив, возвращаемый el\FileInfo::info()
	 * @param  string $dir  имя директории с иконками (small | big)
	 * @return string
	 **/
	function _cssClass($mime)
	{
		if ($mime == 'directory') {
			return 'directory';
		}
		$parts = explode('/', $mime);
		if ('application' == $parts[0] || 'text' == $parts[0]) {
			
			if ('application' == $parts[0] && preg_match('/(zip|rar|tar|7z|lhs)/', $parts[1])) {
				return 'archive';
			}
			return str_replace('.', '-', $parts[1]);
		}
		return $parts[0];
	}

	function _tmb($img) 
	{
		$dir = dirname($img['path']).DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
		if (!elFS::mkdir($dir))
		{
			return false;
		}
		
		$tmb = $dir.DIRECTORY_SEPARATOR.$img['basename'];
		if (file_exists($tmb))
		{
			return $tmb;
		}
		
		return $this->_image->tmb($img['path'], $tmb, $this->_options['tmbSize'], $this->_options['tmbSize'], true);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function _path2url($path)
	{
		$path = str_replace($this->_options['root'], '', $path);
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		return $this->_options['URL'].$path;
	}
	
	/**
	 * Обрезает имя файла до $wrap символов, если оно длинее
	 *
	 * @param  string  $name  имя файла
	 * @param  int     $wrap  кол-во символов
	 * @return string
	 **/
	function _wrapFileName($name, $wrap)
	{
		return function_exists('mb_substr') 
			? (mb_strlen($name) > $wrap ? mb_substr($name, 0, intval($wrap-3)).'...'.mb_substr($name, -3) : $name)
			: (strlen($name) > $wrap ? substr($name, 0, intval($wrap-3)).'...'.substr($name, -3) : $name);
	}
	
	/**
	 * Ищет директорию или файл по хэшу
	 *
	 * @param  string  $hash  хэш
	 * @param  string  $path  текщая директория для поиска файла
	 * @return string
	 **/
	function _find($hash, $path='')
	{
		$tree = $this->_tree();
		if ($tree['hash'] == $hash)
		{
			return $tree['path'];
		}
		if (false != ($result = $this->_findInTree($hash, $tree)))
		{
			return $result;
		}
		if ($path && false != ($ls = elFS::ls($path, EL_FS_ONLY_FILES, true, true)))
		{
			return isset($ls[$hash]) ? $path.DIRECTORY_SEPARATOR.$ls[$hash] : false;
		}
	}
	
	/**
	 * Ищет в дереве директорию по ее хэшу
	 * Возвращает путь или false.
	 *
	 * @param  string  $hash  хэш искомой директории
	 * @param  array   $dir   дерево директорий
	 * @return string
	 **/
	function _findInTree($hash, $dir)
	{
		if ($dir['hash'] == $hash)
		{
			return $dir['path'];
		}
		foreach ($dir['dirs'] as $_dir)
		{
			if (false != ($path = $this->_findInTree($hash, $_dir)))
			{
				return $path;
			}
		}
	}
	
	/**
	 * Возвращает представление дерева директорий в виде html списка
	 *
	 * @param  array  $dir
	 * @return string
	 **/
	function _tree2htmllist($tree)
	{
		$html = "<ul>\n";
		foreach ($tree as $dirname=>$value)
		{
			$html .= '<li><a key="'.$value['hash'].'" href="#">'.$dirname.'</a>';
			if (!empty($value['dirs']))
			{
				$html .= $this->_tree2htmllist($value['dirs']);
			}
			$html .= "</li>\n";
		}
		$html .= "</ul>\n";
		return $html;
	}
	
	/**
	 * Проверяет имя файла/директории на недопустимые символы
	 * Возвращает имя или FALSE
	 *
	 * @param  string  $name  имя
	 * @return string
	 **/
	function _checkName($name)
	{
		$name = trim($name);
		return false === strpos($name, '\\') && preg_match('/^[^\/@\!%"\']+$/i', $name) ? $name : false;
	}
	
	function _checkMime($path) 
	{
		if ($this->_options['mimetypes']) 
		{
			$fileMime = elFileInfo::mimetype($path);
			foreach ($this->_options['mimetypes'] as $mime) 
			{
				if (0 === strpos($fileMime, $mime))
				{
					return true;
				}
			}
			return false;
		}
		return true;
	}
	
	function _te() {
		if (!$this->_te) {
			$this->_te = new elTE();
		}
	}
	
	/**
	 * Возвращает массив директорий, к которым разрешен доступ на чтение
	 *
	 * @return array
	 **/
	function _tree()
	{
		return elFS::tree($this->_options['root'], 
						$this->_options['acl'], 
						$this->_options['role'], 
						$this->_options['perms'], 
						$this->_options['defaults']['read']);
	}	
		
	function _isAllowed($f, $act) 
	{
		if ($this->_options['acl']) {
			return $this->_options['acl']->isAllowed($this->_options['role'], $f, $act);
		} elseif (isset($this->_options['perms'][$f][$act])) {
			return $this->_options['perms'][$f][$act];
		}
		
		return isset($this->_options['defaults'][$act]) ? $this->_options['defaults'][$act] : false;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function _jsonError($tpl, $args=array())
	{
		exit(elJSON::encode(array('error' => vsprintf($this->_translate($tpl), $args))));
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function _jsonMessage($tpl, $args=array())
	{
		$args = !empty($args) && !is_array($args) ? array($args) : $args;
		$msg  = !$args 
			? $this->_translate($tpl) 
			: vsprintf( $this->_translate($tpl), $args);
		exit(elJSON::encode(array('message' => $msg)));
	}
	
	function _translate($msg) {
		return m($msg);
		// return $this->_translator ? $this->_translator->translate($this->_options['lang'], $msg) : $msg;
	}
	
}




?>