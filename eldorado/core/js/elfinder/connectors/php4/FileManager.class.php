<?php

namespace el;

include_once 'Optionable.class.php';
include_once 'FS.class.php';
include_once 'Session.class.php';

class FileManager extends Optionable
{
	/**
	 * Параметры объекта
	 *
	 * @var array
	 **/
	protected $_options = array(
		'root'      => './',
		'URL'       => '',
		'role'      => 'user',
		'fileUmask' => 0666,
		'dirUmask'  => 0777,
		'tplName'   => 'FILEMANAGER',
		'tmbDir'    => '.tmb',
		'tmbSize'   => 48
		
		);
		
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	protected $_mimetypes = array(
		'directory'                     => array('Directory',                      'dir.png'),
		'text/plain'                    => array('Plain text',                     'text.png'),
	    'text/x-php'                    => array('PHP source',                     'php.png'),
		'text/javascript'               => array('Javascript source',              'js.png'),
		'text/css'                      => array('CSS style sheet',                'css.png'),
	    'text/html'                     => array('HTML document',                  'html.png'),
		'text/x-c'                      => array('C source',                       'c.png'),
		'text/x-c++'                    => array('C++ source',                     'c-plus.png'),
		'text/x-shellscript'            => array('Unix shell script',              'shell.png'),		
	    'text/rtf'                      => array('Rich Text Format (RTF)',         'rtf.png'),
		'text/rtfd'                     => array('RTF with attachments (RTFD)',    'rtf.png'),
	    'text/xml'                      => array('XML document',                   'xml.png'),
		'application/xml'               => array('XML document',                   'xml.png'),
	    'application/x-gzip'            => array('GZIP archive',                   'gzip.png'),
	    'application/x-bzip2'           => array('BZIP archive',                   'bzip.png'),
	    'application/x-zip'             => array('ZIP archive',                    'zip.png'),
	    'application/zip'               => array('ZIP archive',                    'zip.png'),	
	    'application/x-rar'             => array('RAR archive',                    'rar.png'),
	    'image/jpeg'                    => array('JPEG image',                     'jpg.png'),
	    'image/gif'                     => array('GIF Image',                      'gif.png'),
	    'image/png'                     => array('PNG image',                      'png.png'),
	    'image/tiff'                    => array('TIFF image',                     'tif.png'),
	    'image/vnd.adobe.photoshop'     => array('Adobe Photoshop image',          'image.png'),	
	    'application/pdf'               => array('Portable Document Format (PDF)', 'pdf.png'),
	    'application/msword'            => array('Microsoft Word document',        'ms-word.png'),
		'application/vnd.ms-office'     => array('Microsoft Office document',      'ms-office.png'),		
		'application/vnd.ms-word'       => array('Microsoft Word document',        'ms-word.png'),	
	    'application/msexel'            => array('Microsoft Excel document',       'ms-excel.png'),
	    'application/vnd.ms-excel'      => array('Microsoft Excel document',       'ms-excel.png'),	
		'application/octet-stream'      => array('Application',                    'application.png'),
		'audio/mpeg'                    => array('MPEG audio',                     'mp3.png'),
		'video/mpeg'                    => array('MPEG video',                     'mpeg.png'),
		'video/x-msvideo'               => array('AVI video',                      'avi.png'),
		'application/x-shockwave-flash' => array('Flash application',              'flash.png'),
		'video/x-flv'                   => array('Flash video',                    'video.png')
		);
		
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	private $_fileTypes = array(
		'text'    => array('Text document', 'text.png'),
		'image'   => array('Image',         'image.png'),
		'audio'   => array('Audio',         'audio.png'),
		'video'   => array('Video',         'video.png'),
		'unknown' => array('Unknown',       'unknown.png')
		);
		
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	private $_views = array('list', 'icons-small', 'icons-big');
	/**
	 * объект класса el\TemplateEngine для отрисовки контента
	 *
	 * @var el\TemplateEngine
	 **/
	private $_te;
	/**
	 * объект класса el\ACL - определение прав доступа к директориям
	 * ACL должен быть помещен в el\Registry  и права доступа к директориям (по абсолютным путям)
	 * должны быть установлены до создания файлового менеджера.
	 * Роль передается в конструктор
	 *
	 * @var el\ACL
	 **/
	private $_acl;
	/**
	 * Флаг - доступ на чтение корневой директории разрешен
	 *
	 * @var bool
	 **/
	private $_allow = false;
	
	/**
	 * Конструктор
	 *
	 * @return void
	 **/
	public function __construct(array $options=array())
	{
		parent::__construct($options);
		$this->_optionsRO = true;
		$this->_options['root'] = realpath($this->_options['root']);
		if (!$this->_options['root'])
		{
			$this->_options['root'] = realpath('./');
		}
		$this->_acl = Registry::get('acl');
		$this->_allow = $this->_acl->isAllowed($this->_options['role'], $this->_options['root'], 'read');
	}

	/**
	 * Устанавливает объект-рендерер 
	 *
	 * @param  el\TemplateEngine  $te
	 * @return void
	 **/
	public function setTE(TemplateEngine $te)
	{
		$this->_te = $te;
	}

	
	function autorun() 
	{
		if (!$this->_allow) // нет прав на чтение корневой директории
		{
			exit('<h1 style="color:red">Access denied!</h1>');
		}
		
		$cmd = !empty($_GET['cmd']) ? trim($_GET['cmd']) : (!empty($_POST['cmd']) ? trim($_POST['cmd']) : '');
		
		switch ($cmd) 
		{
			case 'cd':
				exit($this->_rndDir($this->_find(!empty($_GET['target']) ? $_GET['target'] : '') ?: $this->_options['root']));
				break;
				
			case 'open':
				$this->_open();
				break;
				
			case 'tree':
				exit($this->_rndTree());
				break;
				
			case 'info':
				exit($this->_info());
				break;
					
			case 'mkdir':
				$this->_mkdir();
				break;

			case 'rename':
				$this->_rename();
				break;
				
			case 'copy':
				$this->_copy();
				break;
				
			case 'rm':
				$this->_rm();
				break;
				
			case 'upload':
				$this->_upload();
				break;
				
			default:
				exit($this->_rnd());
		}
	}
	
	/********************************************************************/
	/***                методы - отрисовщики контента                 ***/
	/********************************************************************/	

	/**
	 * Возвращает распарсеный шаблон файлового менеджера
	 *
	 * @return string
	 **/
	public function _rnd()
	{
		
		$this->_te();
		$this->_te->load($this->_options['tplName'], $this->_tplPath('default.html'));
		$this->_rndTree();
		$this->_rndDir($this->_options['root']);
		return $this->_te->getParsed($this->_options['tplName']);
	}

	/**
	 * Возвращает распарсеный шаблон дерева директорий
	 *
	 * @return string
	 **/
	private function _rndTree()
	{
		$tree = $this->_tree();
		$this->_te();
		$this->_te->load('TREE', $this->_tplPath('tree.html'));
		$this->_te->vars('tree', $this->_tree2htmllist($tree['dirs']));
		$this->_te->tplVars('TREE', 'key', $tree['hash']);
		return $this->_te->getParsed('TREE');
	}

	/**
	 * Возвращает распарсеный шаблон текущей директории
	 *
	 * @return string
	 **/
	private function _rndDir($path)
	{
		$view = !empty($_GET['view']) && in_array($_GET['view'], $this->_views) ? $_GET['view'] : $this->_views[0];
		$this->_te();
		$filesNum  = 0;
		$filesSize = 0;
		if ('list' == $view)
		{
			$tpl        = 'list.html';
			$iconsDir   = 'small';
			$cnt        = 0;
			$translator = Registry::get('translator')
				->loadMessages('common')
				->loadMessages('filemanager')
				->loadMessages('filetypes');
		}
		else
		{
			$tpl      = 'icons.html';
			$iconsDir = 'icons-small' == $view ? 'small' : 'big';
			$wrap     = 'icons-small' == $view ? 12  : 19;
		}
		$this->_te->load('CONTENT', $this->_tplPath($tpl));
		$this->_te->tplVars('CONTENT', 'view', $view);
		$cwd = FileInfo::info($path);// elPrintR($cwd);
		$p   = str_replace($this->_options['root'], '', $path);
		$info = array(
			'current'   => $cwd['hash'],
			'path'      => $p ?: '/',
			'filesNum'  => 0,
			'filesSize' => 0,
			'write'     => $cwd['write'] && $this->_acl->isAllowed($this->_options['role'], $path, 'write')
			);
			
		if (!$cwd['read'])
		{
			$this->_te->iterateBlock('CONTENT.ACCESS_DENIED');
			$this->_te->vars('info', json_encode($info));
			return $this->_te->getParsed('CONTENT');
		}
		$content = FS::lsall($path); 

		foreach ($content as $item)
		{
			if ('directory' != $item['mimetype'] || $this->_acl->isAllowed($this->_options['role'], $item['path'], 'read'))
			{
				$item['icon'] = $this->_fileIcon($item, $iconsDir);
				$item['class'] = !$item['read'] ? 'ui-state-disabled' : '';
				if ('directory' != $item['mimetype'])
				{
					$item['class'] .= ' file';
					$info['filesNum']++;
					$info['filesSize'] += $item['size'];
				}

				if ('list' == $view)
				{
					$type = $this->_fileType($item['mimetype']);
					$item['rowClass'] = ($cnt++%2) ? 'odd' : '';
					$item['kind']  = $translator->translate($type[0]);
					$item['mdate'] = date('F d Y H:i:s', $item['mtime']);
				}
				else
				{
					$item['name'] = $this->_wrapFileName($item['basename'], $wrap);
				}
				$this->_te->iterateBlock('CONTENT.FILE', $item);
			}
		}
		$info['filesSize'] = FileInfo::formatSize($info['filesSize']);
		$this->_te->vars('info', json_encode($info));
		return $this->_te->getParsed('CONTENT');
	}
	
	/********************************************************************/
	/***                     Манипуляции с файлами                    ***/
	/********************************************************************/
	
	/**
	 * Выводит содержимое файла в браузер
	 *
	 * @return void
	 **/
	private function _open()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			header('HTTP/1.x 404 Not Found'); 
			exit(Error::message('File was not found. Invalid parameters.'));
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read'))
		{
			header('HTTP/1.0 403 Acces denied');
			exit(Error::message('Access denied'));
		}
		if (false == ($info = FileInfo::info($target)) || 'directory' == $info['mimetype'])
		{
			header('HTTP/1.x 404 Not Found'); 
			exit(Error::message('File not found'));
		}
		if (!$info['read'])
		{
			header('HTTP/1.0 403 Acces denied');
			exit(Error::message('Access denied'));
		}
		header("Content-Type: ".$info['mimetype']);
		header("Content-Disposition: ".(substr($info['mimetype'], 0, 5) == 'image' || substr($info['mimetype'], 0, 4) == 'text' ? 'inline' : 'attacments')."; filename=".$info['basename']);
		header("Content-Location: ".str_replace($this->_options['root'], '', $target));
		header("Content-Length: " .$info['size']);
		header("Connection:close");
		readfile($target);
		exit();
	}
	
	/**
	 * Информация о файле/директории
	 *
	 * @return void
	 **/
	private function _info()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			$this->_jsonError('File was not found. Invalid parameters.');
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read'))
		{
			$this->_jsonError('Access denied.');
		}
		if (false == ($info = FileInfo::info($target)))
		{
			$this->_jsonError('File was not found.');
		}
		
		$translator = Registry::get('translator')
			->loadMessages('filemanager')
			->loadMessages('common')
			->loadMessages('filetypes');
		$type = $this->_fileType($info['mimetype']);
		$result = array(
			array($translator->translate('File name'),   $info['basename']),
			array($translator->translate('Kind'),        $translator->translate($type[0])),
			array($translator->translate('Size'),        $info['hsize']),
			array($translator->translate('Modified'),    date('F d Y H:i:s', $info['mtime'])),
			array($translator->translate('Last opened'), date('F d Y H:i:s', $info['atime'])),
			array($translator->translate('Readable'),    $translator->translate($info['read'] ? 'yes' : 'no') ),
			array($translator->translate('Writable'),    $translator->translate($info['write'] ? 'yes' : 'no') ),
			);
		
		if (substr($info['mimetype'], 0, 5) == 'image' && false != ($s = getimagesize($target)))
		{
			$result[] = array($translator->translate('Dimensions'),  $s[0].'px x '.$s[1].'px');
		}
		if (substr($info['mimetype'], 0, 4) == 'text')
		{
			$result[] = array($translator->translate('Charset'),     $info['charset']);
		}
		exit(json_encode($result));
	}
		
	/**
	 * Создание директории
	 *
	 * @return void
	 **/
	private function _mkdir()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])))
		{
			$this->_jsonError('Unable to create directory. Invalid parameters.');
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $current, 'write'))
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
	 * Переименования файла/директории в  текущей диретории
	 *
	 * @return void
	 **/
	private function _rename()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current'])) 
		||  empty($_GET['target'])  || false == ($target  = $this->_find(trim($_GET['target']), $current)) )
		{
			$this->_jsonError('Unable to rename. Invalid parameters.');
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $current, 'write')
		||  (is_dir($target) && !$this->_acl->isAllowed($this->_options['role'], $target, 'write'))
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
		if (FileInfo::isWebImage($newTarget))
		{
			$tmbDir = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
			if (FS::mkdir($tmbDir))
			{
				$oldTmb = $tmbDir.DIRECTORY_SEPARATOR.basename($target);
				$newTmb = $tmbDir.DIRECTORY_SEPARATOR.basename($newTarget);
				if (file_exists($oldTmb))
				{
					@rename($oldTmb, $newTmb);
				}
				else
				{
					$this->_createThumbnail($newTarget);
				}
			}
		}
		$this->_jsonMessage('%s was renamed to %s', array(basename($target), $newname));
	}
	
	
	/**
	 * Копирует/перемещает файлы/директории
	 *
	 * @return void
	 **/
	private function _copy()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current']))
		||  empty($_GET['source']) || false == ($source = $this->_find($_GET['source'])))
		{
			$this->_jsonError('Unable to paste files. Invalid parameters.');
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $source,  'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $current, 'write'))
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
			$name    = basename($src);
			$isImage = FileInfo::isWebImage($src);
			if (!FS::copy($src, $current))
			{
				$this->_jsonError('Unable to copy %s', $name);
			}
			if ($rm && !FS::rm($src))
			{
				$this->_jsonError('Unable to delete %s', $name);
			}
			
			if ($isImage)
			{
				$dir       = dirname($src).DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
				$tmb       = $dir.DIRECTORY_SEPARATOR.$name;
				$targetDir = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
				$targetTmb = $current.DIRECTORY_SEPARATOR.$this->_options['tmbDir'].DIRECTORY_SEPARATOR.$name;
				if (file_exists($tmb) && FS::mkdir($targetDir) && FS::copy($tmb, $targetDir.DIRECTORY_SEPARATOR.$name))
				{
					$rm && @unlink($tmb);
				}
				else
				{
					$this->_createThumbnail($current.DIRECTORY_SEPARATOR.$name);
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
	private function _rm()
	{
		if (empty($_GET['current']) || false == ($current = $this->_find($_GET['current']))
		||  empty($_GET['target']) || !is_array($_GET['target']))
		{
			$this->_jsonError('Unable to delete. Invalid parameters.');
		} 
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $current, 'write'))
		{
			$this->_jsonError('Access denied.');
		}
		foreach ($_GET['target'] as $hash)
		{
			if (false == ($target = $this->_find($hash, $current)))
			{
				$this->_jsonError('File not found.');
			}
			if ((is_dir($target) && !$this->_acl->isAllowed($this->_options['role'], $target, 'write'))
			|| !is_writable($target))
			{
				$this->_jsonError('Access denied.');
			}
			$isImage = FileInfo::isWebImage($target);
			if (!FS::rm($target))
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
	private function _upload()
	{
		if (empty($_POST['current']) || false == ($current = $this->_find($_POST['current'])))
		{
			$this->_jsonError('Unable to upload files. Invalid parameters.');
		}
		if (!$this->_acl->isAllowed($this->_options['role'], $current, 'read')
		||  !$this->_acl->isAllowed($this->_options['role'], $current, 'write'))
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
					$failed[] = $_FILES['fm-file']['name'][$i].' : '.Error::message($error);
				}
				elseif (!$this->_checkName($_FILES['fm-file']['name'][$i]))
				{
					$failed[] = $_FILES['fm-file']['name'][$i].' : '.Error::message('Invalid file name');
				}
				else
				{
					$target = $current.DIRECTORY_SEPARATOR.$_FILES['fm-file']['name'][$i];
					if (move_uploaded_file($_FILES['fm-file']['tmp_name'][$i], $target))
					{
						@chmod($target, $this->_options['dirUmask']);
						$ok[] = $_FILES['fm-file']['name'][$i];
						
						if (FileInfo::iswebImage($target))
						{
							$this->_createThumbnail($target);
						}
					}
					else
					{
						$failed[] = $_FILES['fm-file']['name'][$i].' : '.Error::message('Unable to save uploaded file');
					}
				}
			}
		}
		if (!sizeof($ok))
		{
			exit(json_encode( array('error' => Error::message('Files upload failed'), 'failed' => $failed) ));
		}
		$translator = Registry::get('translator')->loadMessages('filemanager');
		$message = !($failed) 
			? $translator->translate('Files successfully uploaded')
			: $translate->formatTranslate('Following files was uploaded: %s', implode(', ', $ok));
		
		exit(json_encode( array('message' => $message, 'failed' => $failed) ));
	}
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	private function _createThumbnail($img)
	{
		$dir = dirname($img).DIRECTORY_SEPARATOR.$this->_options['tmbDir'];
		if (FS::mkdir($dir))
		{
			!class_exists('\el\Image') && Loader::LoadClass('\el\Image');
			Image::createThumbnail($img, $dir.DIRECTORY_SEPARATOR.basename($img), $this->_options['tmbSize'], $this->_options['tmbSize'], 'crop');
		}
	}
	
	/********************************************************************/
	/***                     Вспомогательные методы                   ***/
	/********************************************************************/	
	
	/**
	 * Создает TemplateEngine, если еще не создан
	 *
	 * @return void
	 **/
	private function _te()
	{
		if (!$this->_te)
		{
			include_once 'TemplateEngine.class.php';
			$this->_te = new TemplateEngine(array());
		}
	}
	
	/**
	 * Возвращает путь к файлу шаблона
	 *
	 * @param  string  $tpl  имя файла шаблона
	 * @return string
	 **/
	private function _tplPath($tpl)
	{
		return EL_PATH_STYLE.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'FileManager'.DIRECTORY_SEPARATOR.$tpl;
	}
	
	/**
	 * Возвращает массив директорий, к которым разрешен доступ на чтение
	 *
	 * @return array
	 **/
	private function _tree()
	{
		return FS::tree($this->_options['root'], $this->_acl, $this->_options['role']);
	}
	
	/**
	 * Возвращает url иконки или превью
	 *
	 * @param  array  $file - массив, возвращаемый el\FileInfo::info()
	 * @param  string $dir  имя директории с иконками (small | big)
	 * @return string
	 **/
	private function _fileIcon($file, $dir)
	{
		$type = $this->_fileType($file['mimetype']);
		$icon = EL_URL_STYLE.'icons/filemanager/'.$dir.'/'.$type[1];
		if ($dir == 'big' && substr($file['mimetype'], 0, 5) == 'image')
		{
			$tmb = dirname($file['path']).DIRECTORY_SEPARATOR.$this->_options['tmbDir'].DIRECTORY_SEPARATOR.$file['basename'];
			if (file_exists($tmb))
			{
				$icon = $this->_path2url($tmb);
			}
		}
		return $icon;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	private function _fileType($mimetype)
	{
		if (isset($this->_mimetypes[$mimetype]))
		{
			return $this->_mimetypes[$mimetype];
		}
		$type = substr($mimetype, 0, strpos($mimetype, '/'));
		if (isset($this->_fileTypes[$type]))
		{
			return $this->_fileTypes[$type];
		}
		return array($mimetype, $this->_fileTypes['unknown'][1]);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	private function _path2url($path)
	{
		$path = str_replace($this->_options['root'], '', $path);
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		return $this->_options['URL'].'/'.$path;
	}
	
	/**
	 * Обрезает имя файла до $wrap символов, если оно длинее
	 *
	 * @param  string  $name  имя файла
	 * @param  int     $wrap  кол-во символов
	 * @return string
	 **/
	private function _wrapFileName($name, $wrap)
	{
		return strlen($name) > $wrap ? substr($name, 0, intval($wrap-3)).'...'.substr($name, -3) : $name;
	}
	
	/**
	 * Проверяет имя файла/директории на недопустимые символы
	 * Возвращает имя или FALSE
	 *
	 * @param  string  $name  имя
	 * @return string
	 **/
	private function _checkName($name)
	{
		$name = trim($name);
		return false === strpos($name, '\\') && preg_match('/^[a-z0-9_][^\/@\!%"\']*$/i', $name) ? $name : false;
	}
	
	/**
	 * Ищет директорию или файл по хэшу
	 *
	 * @param  string  $hash  хэш
	 * @param  string  $path  текщая директория для поиска файла
	 * @return string
	 **/
	private function _find($hash, $path='')
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
		if ($path && false != ($ls = FS::ls($path, FS::ONLY_FILES, true, true)))
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
	private function _findInTree($hash, $dir)
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
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	private function _jsonError($tpl, $args=array())
	{
		exit(json_encode(array('error' => Error::message($tpl, $args))));
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	private function _jsonMessage($tpl, $args=array())
	{
		$translator = Registry::get('translator');
		$args = !empty($args) && !is_array($args) ? array($args) : $args;
		$msg  = !$args 
			? $translator->translate($tpl, 'filemanager') 
			: $translator->formatTranslate($tpl, $args, 'filemanager');
		exit(json_encode(array('message' => $msg)));
	}
	
	/**
	 * Возвращает представление дерева директорий в виде html списка
	 *
	 * @param  array  $dir
	 * @return string
	 **/
	private function _tree2htmllist($tree)
	{
		$html = "\n<ul class='el-tree'>\n";
		foreach ($tree as $dirname=>$value)
		{
			$html .= '<li><span class="ui-icon ui-icon-folder-collapsed" style="float:left"></span><a key="'.$value['hash'].'" href="#">'.$dirname.'</a>';
			if (!empty($value['dirs']))
			{
				$html .= $this->_tree2htmllist($value['dirs']);
			}
			$html .= "</li>\n";
		}
		$html .= "</ul>\n";
		return $html;
	}
	
}

?>