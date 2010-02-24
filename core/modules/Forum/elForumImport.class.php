<?php
include_once './core/lib/elDbNestedSets.class.php';
class elForumImport 
{
	var $_db = null;
	var $error = '';
	var $_tbs = array(
		'SMF' => array(
			'user'      => '%s_members',
			'group'     => '%s_membergroups',
			'moderator' => '%s_moderators',
			'cat'       => '%s_categories',
			'board'     => '%s_boards',
			'topic'     => '%s_topics',
			'post'      => '%s_messages',
			'attach'    => '%s_attachments'
			)
		);
		
	var $_maps = array(
		'groups' => array(),
		'users'  => array(),
		'usersInGroups' => array(),
		'cats' => array(),
		'boards' => array()
		);
	
	var $_smfPostIconsMap = array(
		'xx' => 'default.png',
		'thumbup' => 'smiley-lol.png',
		'thumbdown' => 'smiley-cry.png',
		'exclamation' => 'exclamation.png',
		'question' => 'question.png',
		'lamp' => 'lamp.png',
		'smiley' => 'smiley-lol.png',
		'angry' => 'smiley-twist.png',
		'cheesy' => 'smiley-lol.png',
		'grin' => 'smiley-lol.png',
		'sad' => 'smiley-cry.png',
		'wink' =>'smiley-lol.png'
		);
	
	
	function importSMF($filename, $prefix, $url, $getAttachments=false, $maxDim=100, $minDim=35, $attachDim=100)
	{
		set_time_limit(0);
		
		$this->_db = & elSingleton::getObj('elDb');
		$elTables = $this->_db->tablesList();
		//elPrintR($elTables);
		$prefix = $prefix ? $prefix : 'smf';
		$smfTables = array();
		foreach ($this->_tbs['SMF'] as $k=>$v)
		{
			$smfTables[$k] = sprintf($v, $prefix);
		}
		//elPrintR($smfTables);
		$conf = &elSingleton::getObj('elXmlConf');
		$user = $conf->get('user', 'db');
  		$pass = $conf->get('pass', 'db');
  		$db   = $conf->get('db',   'db');
		$host = $conf->get('host', 'db');
  		$sock = $conf->get('sock', 'db');
		if ( $sock )
		{
			$host .= ':'.$sock;
		}
		$cmd = 'mysql '     
		      .' -h '.escapeshellarg($host)
	          .' -u '.escapeshellarg($user)                                         
	          .' --password='.escapeshellarg($pass)                                 
		      .' '.escapeshellarg($db)                                            
		      .' < '.escapeshellarg($filename);
		//echo $cmd;
		exec($cmd, $output, $exitCode);
		
		if ( $exitCode )
		{
			return $this->_error('Import from dump file to database failed');
		}
		//elPrintR($output);
		$tables = $this->_db->tablesList();
		
		foreach ($smfTables as $tb)
		{
			if ( !in_array($tb, $tables) )
			{
				return $this->_error('Required table %s was not found in dump', $tb);
			}
		}
		
		$rm = array_diff($tables, $elTables);
		
		// GROUPS
		$groups = $this->_db->queryToArray('SELECT ID_GROUP, groupName FROM '.$smfTables['group'].' ORDER BY ID_GROUP', 'ID_GROUP', 'groupName');
		//elPrintR($groups);
		$this->_insertGroups($groups);
		
		$sql = 'SELECT ID_MEMBER, 
				memberName AS login, 
				passwd AS pass, 
				realName AS f_name, 
				emailAddress AS email,
				ICQ AS icq_uin,
				websiteURL AS web_site,
				dateRegistered as crtime, 
				posts as forum_posts_count,
				signature,
				personalText AS personal_text,
				IF(gender=0, "", IF(gender=1, "male", "female")) AS gender,
				UNIX_TIMESTAMP(birthdate) AS birthdate,
				location,
				IF(hideEmail=1, 0, 1) AS show_email,
				showOnline AS show_online,
				ID_GROUP, 
				ID_POST_GROUP
				FROM '.$smfTables['user'].'
				WHERE is_activated=1
				ORDER BY ID_MEMBER
				';
		$users       = array();
		$usersGroups = array();
		$this->_db->query($sql);
		while ( $r = $this->_db->nextRecord() )
		{
			if (!empty($r['ID_GROUP']))
			{
				$usersGroups[$r['ID_MEMBER']][] = $this->_maps['groups'][$r['ID_GROUP']];
			}
			if (!empty($r['ID_POST_GROUP']))
			{
				$usersGroups[$r['ID_MEMBER']][] = $this->_maps['groups'][$r['ID_POST_GROUP']];
			}
			unset($r['ID_GROUP'], $r['ID_POST_GROUP']);
			$users[] = $r;
		}
		$this->_insertUsers($users);
		$this->_insertUsersInGroups($usersGroups);
		unset($groups, $users, $usersGroups);
		
		$this->_insertCats( $this->_db->queryToArray('SELECT ID_CAT, name FROM '.$smfTables['cat'].' ORDER BY catOrder'));
		
		$sql = 'SELECT ID_BOARD, 
				ID_CAT, 
				ID_PARENT, 
				name, 
				description AS descrip, 
				countPosts AS count_posts, 
				numTopics  AS num_topics,
				numPosts AS num_posts,
				ID_LAST_MSG AS last_post_id 
				FROM '.$smfTables['board'].' ORDER BY boardOrder';
		$this->_insertBoards($this->_db->queryToArray($sql));
		
		$sql = 'SELECT ID_BOARD AS cat_id, ID_MEMBER AS uid FROM '.$smfTables['moderator'];
		$this->_insertModerators( $this->_db->queryToArray($sql) );
		
		$sql = 'SELECT ID_MSG,
				ID_TOPIC AS topic_id,
				ID_BOARD AS cat_id,
				posterTime AS crtime,
				modifiedTime AS mtime,
				ID_MEMBER AS author_uid,
				posterName AS author_name,
				posterEmail AS author_email,
				posterIP AS author_ip,
				modifiedName AS modified_name,
				subject,
				body AS message,
				smileysEnabled AS smiley_enabled,
				icon AS ico
				FROM '.$smfTables['post'].'
				ORDER BY ID_MSG
				';
		$this->_insertPosts( $this->_db->queryToArray($sql) );
		
		$sql = 'SELECT ID_TOPIC, 
				ID_BOARD AS cat_id, 
				ID_FIRST_MSG AS first_post_id,
				ID_LAST_MSG AS last_post_id,
				numViews AS num_views,
				numReplies AS num_replies,
				isSticky AS sticky,
				locked
				FROM '.$smfTables['topic'].'
				ORDER BY ID_TOPIC
				';
		$this->_insertTopics( $this->_db->queryToArray($sql) );
		$this->_updateCats();
		
		if ($url)
		{
			$url = '/'==substr($url, -1, 1) ? $url : $url.'/';
			$this->_loadSMFAvatars($this->_db->queryToArray('SELECT ID_ATTACH, ID_MEMBER, filename FROM '.$smfTables['attach'].' WHERE ID_MEMBER>0'), $url, $maxDim, $minDim);
			
			$sql = 'SELECT a.ID_ATTACH, 
					p.ID_TOPIC,
					a.ID_MSG AS post_id, 
					IF(a.ID_THUMB>0, 1, 0) AS is_img, 
					a.filename, 
					a.size, 
					a.width AS img_w, 
					a.height AS img_h, 
					a.downloads 
					FROM '.$smfTables['attach'].' AS a,  '.$smfTables['post'].' AS p
					WHERE a.ID_MSG>0 AND a.filename NOT LIKE "%_thumb" AND p.ID_MSG=a.ID_MSG
					ORDER BY ID_ATTACH';

			$this->_insertAttachments( $this->_db->queryToArray($sql), $url, $getAttachments, $attachDim );
		}
		
		
		$maps = $this->_maps;
		unset($maps['users']);
		//elPrintR($maps);
		
		$this->_clean($rm);
		return true;
	}
	
	
	function _insertGroups($groups)
	{
		$sql = 'INSERT INTO el_group ( name, mtime) VALUES ("%s", '.time().')';
		foreach ($groups as $oldID => $name)
		{
			$this->_db->query( sprintf($sql, mysql_real_escape_string($name)) );
			$this->_maps['groups'][$oldID] = $this->_db->insertID();
		}
	}
	
	function _insertUsers($users)
	{
		$conf = &elSingleton::getObj('elXmlConf');
		$defaultGID = $conf->get('defaultGID', 'auth');
		foreach ($users as $u)
		{
			$oldID = $u['ID_MEMBER'];
			unset($u['ID_MEMBER']);
			$u = array_map('mysql_real_escape_string', $u);
			
			$sql = 'SELECT uid, login FROM el_user WHERE login=\''.$u['login'].'\' OR email=\''.$u['email'].'\'';
			$this->_db->query($sql);
			if ($this->_db->numRows())
			{
				$r = $this->_db->nextRecord();
				//echo 'Found '.$oldID.'->'.$r['uid'].' '.$r['login'].'<br>';
				unset($u['login'], $u['email']);
				$u['l_name'] = $u['s_name'] = '';
				$sql = 'UPDATE el_user SET ';
				foreach ($u as $k=>$v)
				{
					$sql .= $k.'=\''.$v.'\', ';
				}
				$this->_db->query( substr($sql, 0, -2).' WHERE uid='.$r['uid'] );
				$this->_maps['users'][$oldID] = $r['uid'];
			}
			else
			{
				$sql = 'INSERT INTO el_user ('.implode(', ', array_keys($u)).') VALUES ("'.implode('","', $u).'")';	
				$this->_db->query($sql);
				$UID = 	$this->_db->insertID();
				$this->_maps['users'][$oldID] = $UID;	
				if ($defaultGID)
				{
					$this->_db->query('REPLACE INTO el_user_in_group SET user_id=\''.$UID.'\', group_id=\''.$defaultGID.'\'');
				}	
			}
		}
	}
	
	function _insertUsersInGroups($data)
	{
		foreach ($data as $uid=>$gids)
		{
			foreach ($gids as $gid)
			{
				$this->_db->query('REPLACE INTO el_user_in_group SET user_id=\''.$uid.'\', group_id=\''.$gid.'\'');
			}
		}
	}
	
	function _loadSMFAvatars($avs, $url, $maxDim, $miniDim)
	{
		$dir = './storage/avatars';
		if ( !is_dir($dir) && !mkdir($dir) )
		{
			elThrow(E_USER_ERROR, 'Could not create directory %s', $dir);
			return elThrow(E_USER_ERROR, 'Could not save users avatars');
		}
		$dir .= '/';
		$exts = array(1=>'.gif', '.jpg', '.png');
		$im = & elSingleton::getObj('elImage');
		foreach ($avs as $av)
		{
			$img = file_get_contents($url.'index.php?action=dlattach;attach='.$av['ID_ATTACH'].';type=avatar');
			if ($img && !empty($this->_maps['users'][$av['ID_MEMBER']]))
			{
				//elPrintR($av);
				$filename = md5($this->_maps['users'][$av['ID_MEMBER']]);
				$fp = fopen($dir.$filename, 'w');
				if ( $fp )
				{
					fwrite($fp, $img);
					fclose($fp);
					
					$nfo = getimagesize($dir.$filename);
					if (!empty($nfo[2]) && $nfo[2]>0 && $nfo[2]<=3)
					{
						$newName = $filename.'.'.$exts[$nfo[2]];
						copy($dir.$filename, $dir.$newName);
						
						if ($nfo[0]>$maxDim || $nfo[1]>$maxDim)
						{
							$im->resize($dir.$newName, $maxDim, $maxDim);
						}
						$im->copyResized($dir.$newName, $dir.'mini-'.$newName, $miniDim, $miniDim);
						$UID = $this->_maps['users'][$av['ID_MEMBER']];
						$this->_db->query('UPDATE el_user SET avatar=\''.$newName.'\' WHERE uid='.$UID);
					}
					unlink($dir.$filename);
				}
			}
		}
	}
	
	function _insertCats($cats)
	{
		
		$tree = & new elDbNestedSets('el_forum_cat');
		foreach ($cats as $cat)
		{
			$oldID = $cat['ID_CAT'];
			unset($cat['ID_CAT']);
			$this->_maps['cats'][$oldID] = $tree->insert(1, $cat);
		}
	}
	
	function _insertBoards($boards)
	{
		$tree = & new elDbNestedSets('el_forum_cat');
		foreach ($boards as $b)
		{
			$oldID = $b['ID_BOARD'];
			$parentID = !empty($b['ID_PARENT']) ? $this->_maps['boards'][$b['ID_PARENT']] : $this->_maps['cats'][$b['ID_CAT']];
			unset($b['ID_BOARD'], $b['ID_CAT'], $b['ID_PARENT']);
			$this->_maps['boards'][$oldID] = $tree->insert($parentID, $b);
		}
	}

	function _insertModerators($moders)
	{
		$sql = 'REPLACE INTO el_forum_moderator SET cat_id=%d, uid=%d, rid=8';
		foreach ($moders as $m)
		{
			$m['cat_id'] = $this->_maps['boards'][$m['cat_id']];
			$m['uid']    = $this->_maps['users'][$m['uid']];
			$this->_db->query( sprintf($sql, $m['cat_id'], $m['uid']) );
		}
	}

	function _insertTopics($topics)
	{
		$this->_db->query('TRUNCATE el_forum_topic');
		$sql = 'INSERT INTO el_forum_topic (id, cat_id, first_post_id, last_post_id, num_views, num_replies, sticky, locked) VALUES (%d, %d, %d, %d, %d, %d, %d, %d)';
		$i = 0;
		foreach($topics as $t)
		{
			$oldID = $t['ID_TOPIC'];
			$this->_db->query( sprintf($sql, ++$i, $this->_maps['boards'][$t['cat_id']], $this->_maps['posts'][$t['first_post_id']], $this->_maps['posts'][$t['last_post_id']],  $t['num_views'], $t['num_replies'], $t['sticky'], $t['locked']) );
			$this->_maps['topics'][$oldID] = $i;
			$this->_db->query('UPDATE el_forum_post SET topic_id='.$i.' WHERE topic_id='.$oldID);
		}
		// foreach($topics as $t)
		// {
		// 	$oldID = $t['ID_TOPIC'];
		// 	$t['cat_id'] = $this->_maps['boards'][$t['cat_id']];
		// 	$this->_db->query( sprintf($sql, $t['cat_id'], $t['num_views'], $t['num_replies'], $t['sticky'], $t['locked']) );
		// 	$this->_maps['topics'][$oldID] = $this->_db->insertID();
		// }
	}
	
	function _insertPosts($posts)
	{
		$this->_db->query('TRUNCATE el_forum_post');
		$sql = 'INSERT INTO el_forum_post (topic_id, cat_id, crtime, mtime, author_uid, author_name, author_email, author_ip, modified_name, subject, message, smiley_enabled, ico ) 
		VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s", "%s", "%s", "%s", %d, "%s")';
		foreach ($posts as $p)
		{
			$oldID = $p['ID_MSG'];
			unset($p['ID_MSG']);
			$p['cat_id']     = $this->_maps['boards'][$p['cat_id']];
			$p['author_uid'] = $this->_maps['users'][$p['author_uid']];
			$p['ico']        = $this->_smfPostIconsMap[$p['ico']];
			$p['subject']    = mysql_real_escape_string($p['subject']);
			if ( preg_match_all('/\[quote(\s+author=([^\]\=]+))?(\s+link=topic=([^\]\=]+))?(\s+date=([^\]\=]+))?\]/ism', $p['message'], $m))
			{
				if (!empty($m[6]))
				{
					for($i=0, $s=sizeof($m[6]); $i<$s; $i++)
					{
						$author       = !empty($m[2][$i]) ? ' author='.$m[2][$i] : '';
						$date         = $m[6][$i]>0 ? ' date='.date(EL_DATETIME_FORMAT, $m[6][$i]) : '';
						$p['message'] = str_replace($m[0][$i], '[quote'.$author.$date.']', $p['message']);
					}
				}
			}
			$p['message'] = mysql_real_escape_string(str_replace('<br />', "\n", $p['message']));	
			$this->_db->query( vsprintf($sql, $p) );
			$this->_maps['posts'][$oldID] = $this->_db->insertID();
		}
	}
	
	function _updateCats()
	{
		$cats = $this->_db->queryToArray('SELECT id, last_post_id FROM el_forum_cat WHERE last_post_id>0', 'id', 'last_post_id');
		foreach ($cats as $id=>$lpID)
		{
			$this->_db->query('UPDATE el_forum_cat SET last_post_id='.$this->_maps['posts'][$lpID].' WHERE id='.$id.' LIMIT 1');
		}
	}
	
	
	function _insertAttachments( $attachs, $url, $download, $attachDim )
	{
		$dir = './storage/attachments';
		if ( !is_dir($dir) && !mkdir($dir) )
		{
			elThrow(E_USER_ERROR, 'Could not create directory %s', $dir);
			return elThrow(E_USER_ERROR, 'Could not save forum attachments');
		}
		$dir .= '/';
		$im = & elSingleton::getObj('elImage');
		$this->_db->query('TRUNCATE el_forum_attach');
		$sql = 'INSERT INTO el_forum_attach (post_id, is_img, filename, size, img_w, img_h, downloads) VALUES (%d, %d, "%s", %d, %d, %d, %d)';
		foreach ($attachs as $at)
		{
			$fileurl = $url.'index.php?action=dlattach;topic='.$at['ID_TOPIC'].'.0;attach='.$at['ID_ATTACH'].($at['is_img'] ? ';image' : '');
			
			unset($at['ID_ATTACH'], $at['ID_TOPIC']);
			$at['size']    = ceil($at['size']/1024);
			$at['post_id'] = $this->_maps['posts'][$at['post_id']];
			$this->_db->query( vsprintf($sql, $at) );
			
			if ( $download && false != ($file = file_get_contents($fileurl)) )
			{
				$filename = $at['post_id'].'_'.$at['filename'];
				$fp = fopen($dir.$filename, 'w');
				if ( $fp )
				{
					fwrite($fp, $file);
					fclose($fp);
					if ($at['is_img'])
					{
						$im->tmb($dir.$filename, $dir.'mini-'.$filename, $attachDim, $attachDim);
					}
				}
			}
		}
	}
	
	function _clean($tbs)
	{
		$this->_db->query('DROP TABLE `'.implode('`, `', $tbs).'`');	
		return;
		$this->_db->query('TRUNCATE `el_group`');
		$this->_db->query("INSERT INTO `el_group` VALUES (1,'root',8,1122463302),(2,'guests',0,1122464829)");
		
		$this->_db->query('TRUNCATE `el_user_in_group`');
		
		$this->_db->query('TRUNCATE `el_user`');
		$this->_db->query("INSERT INTO `el_user` (`uid`, `login`, `pass`, `f_name`, `s_name`, `l_name`, `email`, `phone`, `fax`, `company`, `postal_code`, `address`, `icq_uin`, `web_site`, `crtime`, `mtime`, `atime`, `visits`, `auto_login`, `forum_posts_count`, `avatar`, `signature`, `personal_text`, `location`, `birthdate`, `gender`, `show_email`, `show_online`) VALUES(1, 'root', '".md5('sora')."', '', '', '', '', '', '', NULL, '', '', '', '', 1146696062, 1225363884, 1240345280, 77, '0', 0, '', '', '', '', 0, '', 0, 1)");
		$this->_db->query('TRUNCATE `el_forum_cat`');
		$this->_db->query("INSERT INTO `el_forum_cat` (id, _left, _right, level, name) VALUES (1, 1, 2, 0, 'Forum');");
		$this->_db->query('TRUNCATE `el_forum_topic`');
		$this->_db->query('TRUNCATE `el_forum_post`');
	}
	
	function _error($msg, $arg=null)
	{
		elLoadMessages('Errors');
		$this->error = sprintf(m($msg), $arg);
		return false;
	}
}



?>