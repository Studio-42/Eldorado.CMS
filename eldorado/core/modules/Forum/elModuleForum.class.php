<?php

class elModuleForum extends elModule
{
	var $_user = null;
	var $_gids = array(-1);
	
	var $_tbc   = 'el_forum_cat';
	var $_tbt   = 'el_forum_topic';
	var $_tbp   = 'el_forum_post';
	var $_tbm   = 'el_forum_moderator';
	var $_tblrf = 'el_forum_log_read_forum';
	var $_tblrt = 'el_forum_log_read_topic';
	var $_tbrb  = 'el_forum_rbac';
	var $_tbrl  = 'el_forum_role';
	var $_tbrla = 'el_forum_role_action'; 
	var $_tbfc  = 'el_forum_floodcontrol';
	var $_tbat  = 'el_forum_attach';
	
	var $_path         = array();
	var $_defaultRoles = array(-1=>2, 0=>4);
	var $_adminRole    = 9;
	var $_catID        = 1;
	var $_cats         = array();
	var $_admin        = false;
	var $_acl          = array();
	var $_mMap = array(
		'topic'         => array('m' => 'readTopic'),
		'post'          => array('m' => 'post'),
		'view_attach'   => array('m' => 'viewAttach'),
		'attach_rm'     => array('m' => 'attachRm'),
		'attach_upload' => array('m' => 'attachUpload'),
		'post_rm'       => array('m' => 'postRm'),
		'topic_rm'      => array('m' => 'topicRm'),
		'sticky'        => array('m' => 'sticky'),
		'lock'          => array('m' => 'lock'),
		'topic_move'    => array('m' => 'topicMove'),
		'users'         => array('m' => 'usersList'),
		'users_by_name' => array('m' => 'usersListByName'),
		'moderators'    => array('m' => 'moderators'),
		'profile'       => array('m' => 'viewProfile'),
		'avatar'        => array('m' => 'avatarUpload'),
		'avatar_rm'     => array('m' => 'avatarRm'),
		'profile_edit'  => array('m' => 'profileEdit'),
		'reg'           => array('m' => 'profileNew'),
		'passwd'        => array('m' => 'passwd'),
		'rules'         => array('m' => 'rules')
		);
		
	var $_conf = array(
		'topicsNum'           => 25,
		'postsNum'            => 25,
		'hotTopicPostsNum'    => 25,
		'maxPostImgSize'      => 500,
		'attachImgDimensions' => 100,
		'usersNum'            => 30,
		'defaultAvatar'       => 'avatar.png',
		'avatarMaxFileSize'   => 15,
		'avatarMaxDimension'  => 100,
		'avatarMiniDimension' => 40,
		'notifyModerators'    => 7,
		'notifyAdmins'        => 1,
		'postTimeout'         => 5,
		'bbcodes'             => array('text'=>'text', 'alignment'=>'alignment', 'elements' => 'elements'),
		'smiley'              => 1,
	 	'autolinks'           => 0,
		'maxAttachments'      => 5,
		'maxAttachSize'       => 128
		);
		
	var $_sharedRndMembers = array( '_acl', '_profile', '_catID', '_regAllow');	
		
	/**
	 * Список форумов и топиков внутри текущего форума
	 *
	 * @return void
	 **/
	function defaultMethod()
	{ 
		$forums = $this->_db->queryToArray(
			sprintf(
				'SELECT ch.id, ch.level-IF(p.level, p.level-1, p.level) AS level, ch.name, ch.descrip, 
				ch.num_topics, ch.num_posts, ch.last_post_id, ch._right-ch._left-1 AS has_childs, 
				IF(LENGTH(post.subject)>25, CONCAT(LEFT(post.subject, 25), "..."), post.subject) AS subject, post.author_name AS author, 
				DATE_FORMAT( FROM_UNIXTIME(post.crtime), "%s") AS date, 
				IF(u.f_name!="", CONCAT(u.f_name, " ", u.l_name), IFNULL(u.login, post.author_name)) AS author_name, post.author_uid, 
				IF(ch.last_post_id>0,  IF(post.crtime<rf.lvt, 1, 0), 1) AS is_read, post.topic_id 
				FROM %s AS p, %s AS ch 
				LEFT JOIN %s AS post ON (post.id=ch.last_post_id) 
				LEFT JOIN el_user AS u ON (u.uid=post.author_uid) 
				LEFT JOIN %s AS rf ON (rf.uid="%d" AND rf.cat_id=ch.id) 
				WHERE p.id="%d" AND ch.id IN(%s)  AND ch._left BETWEEN p._left AND p._right 
				ORDER BY ch._left', 
				EL_MYSQL_DATETIME_FORMAT, $this->_tbc, $this->_tbc, $this->_tbp, $this->_tblrf, 
				$this->_profile->UID, $this->_catID, implode(',', array_keys($this->_cats))
				)
			);		

		$total   = ceil($forums[0]['num_topics']/$this->_conf('topicsNum'));  
		$curpage = !empty($this->_args[1]) && $this->_args[1]>1 && $this->_args[1]<=$total ? (int)$this->_args[1] : 1; 
		$offset  = ($curpage-1)*$this->_conf('topicsNum'); 
				
		$posts = $this->_db->queryToArray(
			sprintf(
				'SELECT t.id, t.cat_id, t.last_post_id, t.num_replies, t.num_views, t.sticky, t.locked, fp.subject, fp.ico AS post_ico, 
				fpu.uid AS author_uid, lpu.uid AS last_post_uid,
				IF(fpu.f_name!="", CONCAT(fpu.f_name, " ", fpu.l_name), IFNULL(fpu.login, fp.author_name)) AS author, 
				IF(lpu.f_name!="", CONCAT(lpu.f_name, " ", lpu.l_name), IFNULL(lpu.login, lp.author_name))  AS last_post_author, 
				IF(tlog.post_id >=t.last_post_id, 1, 0)  AS is_read,
				DATE_FORMAT(FROM_UNIXTIME(fp.crtime), "%s") AS first_post_date,
				DATE_FORMAT(FROM_UNIXTIME(lp.crtime), "%s") AS last_post_date 
				FROM %s AS t LEFT JOIN %s AS tlog ON (tlog.uid="%d" AND tlog.t_id=t.id),  
				%s AS fp LEFT JOIN el_user AS fpu ON (fpu.uid=fp.author_uid), 
				%s AS lp LEFT JOIN el_user AS lpu ON (lpu.uid=lp.author_uid) 
				WHERE t.cat_id=%d AND fp.id=t.first_post_id AND lp.id=t.last_post_id 
				ORDER BY t.sticky DESC, lp.crtime DESC 
				LIMIT %d, %d',
				EL_MYSQL_DATETIME_FORMAT, EL_MYSQL_DATETIME_FORMAT, $this->_tbt, $this->_tblrt, $this->_profile->UID, 
				$this->_tbp, $this->_tbp, $this->_catID, $offset, $this->_conf('topicsNum')
				)
			);
		$forumName = $forums[0]['name'];
		
		array_shift($forums);

		$this->_initRenderer();
		$this->_rnd->render($forumName, $forums, $posts, $total, $curpage);
		$this->_profile->logForumRead( $this->_catID );
	}
	
	/**
	 * Список постов топика
	 *
	 * @return void
	 **/
	function readTopic()
	{
		$topic = & $this->_topic((int)$this->_arg(1));
		
		$this->_path[]  = array('url'=>'topic/'.$this->_catID.'/'.$topic->ID, 'name'=>$topic->subject);
		
		$postsPerPage   = $this->_conf('postsNum');
		$total          = ceil(($topic->numReplies+1)/$postsPerPage);  
		$curpage        = 1;
		if (!empty($this->_args[2]) )
		{
			$curpage = 'last' == $this->_args[2] ? $total : ($this->_args[2]>1 && $this->_args[2] <= $total ? $this->_args[2] : 1);
		}
		
		$bbcode = & $this->_bbcode();
		$posts  = array();
		$this->_db->query(
			sprintf(
				'SELECT p.id, p.topic_id, p.cat_id, p.ico, p.subject, p.message, p.author_uid, p.author_ip, p.modified_uid, 
				u.avatar AS author_avatar, u.forum_posts_count AS author_posts, 
				IF(u.f_name!="", CONCAT(u.f_name, " ", u.l_name), IFNULL(u.login, p.author_name)) AS author_name, 
				DATE_FORMAT(FROM_UNIXTIME(u.crtime), "%s") AS author_reg_date, 
				IF(um.f_name!="", CONCAT(um.f_name, " ", um.l_name), IFNULL(um.login, p.modified_name)) AS modified_name, 
				DATE_FORMAT(FROM_UNIXTIME(p.crtime), "%s") AS create_date, 
				IF(p.mtime, DATE_FORMAT(FROM_UNIXTIME(p.mtime), "%s"), "") AS modified_date 
				FROM %s AS p 
					LEFT JOIN el_user AS u ON(u.uid=p.author_uid) 
					LEFT JOIN el_user AS um ON(um.uid=p.modified_uid) 
				WHERE p.topic_id=%d ORDER BY p.crtime LIMIT %d, %d',
				EL_MYSQL_DATE_FORMAT, EL_MYSQL_DATETIME_FORMAT, EL_MYSQL_DATETIME_FORMAT, $this->_tbp, $topic->ID, ($curpage-1)*$postsPerPage, $postsPerPage
				)
			);
		$postsIDs = array();
		$attachs = array();
		while ($r = $this->_db->nextRecord())
		{
			$r['message'] = nl2br($bbcode->parse($r['message']));
			$posts[]      = $r;
			$postsIDs[]   = $r['id'];
		}
		
		if ($this->_acl('attach_view'))
		{
			
			$sql = 'SELECT id, post_id, CONCAT(post_id, "_", filename) AS filename, size, is_img, img_w, img_h, downloads FROM '.$this->_tbat.' 
				WHERE post_id IN ('.implode(',', $postsIDs).')';
			$this->_db->query($sql);

			while ($r = $this->_db->nextRecord())
			{
				if (empty($attachs[$r['post_id']]))
				{
					$attachs[$r['post_id']] = array();
				}
				$attachs[$r['post_id']][$r['id']] = $r;
			}
		}
		
		
		$this->_initRenderer();
		$this->_rnd->rndTopic($topic, $posts, $attachs, $total, $curpage);
		
		$topic->updateViewsCount();
		if ( $this->_profile->UID  )
		{
			$this->_profile->logTopicRead($topic->ID, $posts[sizeof($posts)-1]['id']);
		} 
	}
	
	function viewAttach()
	{
		if (!$this->_acl('attach_view'))
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Not enough permissions to view post attachments', EL_URL);
		}
		
		$at  = $this->_db->queryToArray(
			sprintf('SELECT CONCAT(post_id, "_", filename) AS filename, is_img, size FROM %s WHERE id=%d  AND post_id=%d', $this->_tbat, (int)$this->_arg(2), (int)$this->_arg(1))
			);

		if (empty($at))
		{
			elThrow(E_USER_WARNING, 'Requested file does not exists', null, EL_URL);
		}
		$filename = $at['0']['filename'];
		$filepath = EL_DIR.'storage/attachments/'.$filename;

		if ( false == ($file = file_get_contents($filepath)))
		{
			elThrow(E_USER_WARNING, 'Could not read file %s', $filename, EL_URL);
		}

		if ($at[0]['is_img'])
		{
			$this->_initRenderer();
			$this->_rnd->addToContent('<img src="'.EL_BASE_URL.'/storage/attachments/'.$filename.'" />', true);
		}
		else
		{
			header("Content-Type: ".elGetMimeContentType($filepath));
			header("Content-Disposition: attachment; filename=".$filename );
			header("Content-Location: ".EL_DIR.'storage/attachments/');
			header("Content-Length: " .filesize($filepath));
			header("Connection:close");
			echo $file;
			$this->_db->query( sprintf('UPDATE %s SET downloads=downloads+1 WHERE id=%d', $this->_tbat, (int)$this->_arg(2)));
			exit;
		}
	}
	/**
	 * Новый пост или редактирование существующего
	 *
	 * @return void
	 **/
	function post()
	{
		set_time_limit(0);
		$postID  = (int)$this->_arg(2); 
		$pageNum = (int)$this->_arg(3); 
		$quoteID = (int)$this->_arg(4); 
		$topic   = & $this->_topic((int)$this->_arg(1), false);
		$post    = & $this->_post($postID, $topic->ID, false);  //elPrintR($post);

		// форум закрыт для постов
		if (!$this->_cats[$this->_catID]['allow_posts'])
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Posts creation and editing not allowed in this forum for all users', EL_URL.$this->_catID);
		}

		// проверяем права доступа
		if ( !$this->_admin )
		{
			if ( $topic->locked && !$this->_acl('post_edit_any') )
			{
				elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'This topic closed! Only moderators can post here', EL_URL.$this->_catID);
			}
			elseif ( !$post->topicID && !$this->_acl('post_new') )
			{
				elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'You could not create new topic in this forum', EL_URL.$this->_catID);
			}
			elseif ( $post->topicID && !$this->_acl('post_reply') )
			{
				elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'You could not reply in topics in this forum', EL_URL.$this->_catID);
			}
			elseif ($post->ID && !($this->_profile->UID==$post->authorUID && $this->_acl('post_edit_own') ) && !$this->_acl('post_edit_any') )
			{
				elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Not enough permissions', EL_URL.$this->_catID);
			}
		}

		$bbcode = & $this->_bbcode();

		// preview via ajax
		if ( (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') 
		|| $this->_args[sizeof($this->_args)-1] == 'prevew')
		{
			$preview = $post->preview( array('user'=>$this->_profile, 'quoteID'=>$quoteID, 'pageNum'=>$pageNum) );
			if ( !$preview )
			{
				echo '{ error: "'.m('Invalid data').'"}';
				exit();
			}
			if (!empty($preview['errors']))
			{
				$resp = '{ error: "'.addslashes( implode('<br />', $preview['errors']) ).'" }';
				echo $resp;
				exit();
			}
			$resp = '{ ';
			$resp .= 'subject: "'.addslashes($preview['subject']).'", ';
			$msg = str_replace("\r", '', $bbcode->parse( $preview['message']));
			$resp .= 'message: "'.addslashes(str_replace("\n", '<br />', $msg)).'", ';
			$resp .= 'create_date: "'.addslashes($preview['create_date']).'", ';
			
			$profile = $this->_profile->brief();
			$resp .= 'author_name: "'.addslashes($profile['name']).'", ';
			$resp .= 'reg_date: "'.addslashes($profile['reg_date']).'", ';
			$resp .= 'posts: "'.addslashes($profile['posts']).'", ';
			$resp .= 'avatar: "'.($profile['avatar'] ? addslashes(EL_BASE_URL.'/storage/avatars/'.$profile['avatar']) : '').'", ';
			$resp = substr($resp, 0, -2).' }';
			echo $resp;
			exit();
		}

		// флуд ?
		$timeout = $this->_conf('postTimeout');
		$lpt     = (int)$this->_profile->sessionData('last_post_time');
		$r       = $this->_db->queryToArray(sprintf('SELECT ts FROM %s WHERE ip="%s" LIMIT 1', $this->_tbfc, $_SERVER['REMOTE_ADDR']), null, 'ts');
		$IPlpt   = !empty($r[0]) ? $r[0] : 0;
		if ( ($lpt && time() - $lpt <= $timeout) || ($IPlpt && time() - $IPlpt <= $timeout) )
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Too resent sending posts! Flood is bad!', EL_URL.$this->_catID);
		}
		
		$attachs = array();
		if ($postID)
		{
			$sql = 'SELECT id, filename FROM '.$this->_tbat.' WHERE post_id='.$postID;
			$attachs = $this->_db->queryToArray($sql, 'id', 'filename');
			//elPrintR($atachs);
		}

		if ( $post->editAndSave( array('user'=>$this->_profile, 'quoteID'=>$quoteID, 'pageNum'=>$pageNum, 'attachs'=>$attachs) ) )
		{
			// сохраняем время последнего поста  c IP
			$this->_db->query( sprintf('REPLACE INTO %s (ip, ts) VALUES ("%s", %d)', $this->_tbfc, $_SERVER['REMOTE_ADDR'], time()) );
			
			// сохраняем время последнего поста пользователя
			$this->_profile->sessionData('last_post_time', time());

			// новый пост - обновляем счетчик постов юзера
			if ( $this->_profile->UID && !$postID && $this->_cats[$this->_catID]['count_posts'] )
			{
				$this->_profile->updatePostsCount();
			}
			elLocation(EL_URL.'topic/'.$this->_catID.'/'.$post->topicID.'/'.($postID ? ($pageNum>1 ? $pageNum.'/' : '') : 'last/').'#'.$post->ID);
		}
		
		// если ответ в существующий топик - добавить имя топика в путь
		if ($topic->ID)
		{
			$this->_path[] = array('url'=>'topic/'.$this->_catID.'/'.$topic->ID, 'name'=>$topic->subject);
		}
		$this->_initRenderer();
		$this->_rnd->rndPost($post->formToHtml());
	}

	function _prepareAttach()
	{
		
		$catID   = (int)$this->_arg();
		$topicID = (int)$this->_arg(1);
		$postID  = (int)$this->_arg(2);
		
		if ( empty($this->_cats[$catID]) )
		{
			$this->_json(array(), 'Requested forum does not exists');
		}
		if (!$this->_cats[$catID]['allow_posts'])
		{
			$this->_json(array(), 'Posts creation and editing not allowed in this forum for all users');
		}
		
		$topic     = & $this->_topic(0, false); 
		$topic->ID = $topicID;
		if (!$topicID || !$topic->fetch() || $catID<>$topic->catID )
		{
			$this->_json(array(), 'Requested topic does not exists');
		}
		
		$post     = & $this->_post(0, $topic->ID, false); 
		$post->ID = $postID;
		if (!$postID || !$post->fetch() || $topic->ID<>$post->topicID )
		{
			$this->_json(array(), 'Requested post does not exists');
		}
		return array($catID, $topic, $post);
	}

	function attachUpload()
	{
		elLoadMessages('Errors');
		$data = array('error'=>'', 'result'=>'', 'debug'=>'');
		
		if (empty($_FILES['attach']))
		{
			$this->_json($data, 'File was not uploaded');
		}
		if ( ceil($_FILES['attach']['size']/1024) > $this->_conf('maxAttachSize'))
		{
			$this->_json($data, sprintf(m('Image file size must be less or equal then %d Kb'), $this->_conf('maxAttachSize')));
		}
		if ( '.' == $_FILES['attach']['name']{0} )
        {
			$this->_json($data, 'Error uploading file! File name does not allowed.');
        }
		$dir = './storage/attachments';
		if ( (!is_dir($dir) && !mkdir($dir)) || !is_writable($dir) )
		{
			$this->_json($data, sprintf(m('Directory %s does not exists or not writable'), $dir));
		}
		$dir .= '/';
		
		list($catID, $topic, $post) = $this->_prepareAttach();
		$data['debug'] = ' file: '.$_FILES['attach']['name'].'; size: '.$_FILES['attach']['size'].' catID='.$catID.' topicID='.$topic->ID.' postID='.$post->ID;
		
		$this->_db->query( sprintf('SELECT id FROM %s WHERE post_id=%d', $this->_tbat, $post->ID) );
		if ( $this->_db->numRows() >= $this->_conf('maxAttachments') )
		{
			return $this->_json($data, 'This post already has allowed maximum of attachments');
		}
		
		if ( !$this->_admin )
		{
			if ( $topic->locked && !$this->_acl('post_edit_any') )
			{
				$this->_json($data, 'This topic closed! Only moderators can post here');
			}
			elseif (!($this->_profile->UID==$post->authorUID && $this->_acl('post_edit_own') ) && !$this->_acl('post_edit_any') && !$this->_acl('attach') )
			{
				$this->_json($data, 'Acess denied');
			}
		}
		
		$filename = $post->ID.'_'.$_FILES['attach']['name'];
		if ( !move_uploaded_file($_FILES['attach']['tmp_name'], $dir.$filename) )
		{
			$this->_json($data, sprintf(m('File %s upload failed'), $_FILES['attach']['name']));
		}
		$is_img = $w = $h = 0;
		$nfo = getimagesize($dir.$filename);
		if ( !empty($nfo[2]) && $nfo[2]>0 && $nfo[2]<=3 )
		{
			$is_img = 1;
			$w      = $nfo[0];
			$h      = $nfo[1];
			$dim    = $this->_conf('attachImgDimensions');
			if ( $nfo[0]>$dim || $nfo[1]>$dim )
			{
				$im     = & elSingleton::getObj('elImage');
				$im->tmb($dir.$filename, $dir.'mini-'.$filename, $dim, $dim);
			}
			else
			{
				copy($dir.$filename, $dir.'mini-'.$filename);
			}
			$data['debug'] .= ' w: '.$w.' h: '.$h;
		}

		$sql = sprintf('INSERT INTO %s (post_id, filename, size, is_img, img_w, img_h) VALUES (%d, "%s", %d, %d, %d, %d)', 
			$this->_tbat, $post->ID, mysql_real_escape_string($_FILES['attach']['name']), ceil( $_FILES['attach']['size']/1024 ), $is_img, $w, $h );
		$this->_db->query( $sql );
	
		$data['result'] = sprintf(m('File %s was attached to post'), $_FILES['attach']['name']);
		
		$this->_json($data);
	}

	function _json($data, $error='')
	{
		$data['error'] = m($error);
		$json = '';
		foreach ($data as $k=>$v)
		{
			$json .= '"'.$k.'" : "'.$v.'", ';
		}
		echo '{ '.substr($json, 0, -2).' }';
		exit();
	}

	function attachRm()
	{
		elLoadMessages('Errors');
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') 
		{
			
		}
		list($catID, $topic, $post) = $this->_prepareAttach();
		$attachID  = (int)$this->_arg(3);
		
		$attach = $this->_db->queryToArray(sprintf('SELECT id, post_id, filename FROM %s WHERE id=%d LIMIT 1', $this->_tbat, $attachID));
		if ( empty($attach[0]) || $attach[0]['post_id']<>$post->ID )
		{
			$this->_json($data, 'Requested file does not exists');
		}
		
		unlink('./storage/attachments/'.$post->ID.'_'.$attach[0]['filename']);
		$this->_db->query( sprintf('DELETE FROM %s WHERE id=%d', $this->_tbat, $attachID) );
		$this->_db->optimizeTable($this->_tbat);
		
		$this->_json($data, sprintf(m('File %s removed'), $attach[0]['filename']));
	}

	/**
	 * Удаляет пост (кроме первого в топике), если у пользователя есть права
	 *
	 * @return void
	 **/
	function postRm()
	{
		$topic   = & $this->_topic((int)$this->_arg(1));
		$post    = & $this->_post((int)$this->_arg(2), $topic->ID);
		$pageNum = (int)$this->_arg(3); 		

		if (!$this->_admin 
		&& !$this->_acl('post_rm_any') && !($this->_profile->UID==$post->authorUID && $this->_acl('post_rm_own') ) )
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Not enough permissions', EL_URL.'topic/'.$this->_catID.'/'.$topic->ID.'/');
		}
		if ( $post->ID == $topic->firstPostID )
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'First post in topic could not be deleted', EL_URL.'topic/'.$this->_catID.'/'.$topic->ID);
		}
		$post->delete();
		elMsgBox::put( sprintf( m('Post %s by %s was deleted'), $post->subject, $post->authorName) );
		elLocation(EL_URL.'topic/'.$this->_catID.'/'.$topic->ID.'/'.$pageNum.'/');
	}
	
	/**
	 * Прикрепляет/открепляет топик
	 *
	 * @return void
	 **/
	function sticky()
	{
		$topic   = & $this->_topic((int)$this->_arg(1));
		$pageNum = (int)$this->_arg(2);
		$return  = 'topic' == $this->_arg(3) ? 'topic/'.$this->_catID.'/'.$topic->ID : $this->_catID.'/'.($pageNum>1 ? $pageNum : ''); 
		if (!$this->_admin
		&& !$this->_acl('topic_sticky_any') && !($this->_profile->UID==$topic->authorUID && $this->_acl('topic_sticky_own') ) )
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Not enough permissions to stick topic', EL_URL.$return);
		}
		$topic->sticky();
		elLocation(EL_URL.$return);
	}
	
	/**
	 * Lock/unlock топик
	 *
	 * @return void
	 **/
	function lock()
	{
		$topic   = & $this->_topic((int)$this->_arg(1));
		$pageNum = (int)$this->_arg(2);
		$return  = 'topic' == $this->_arg(3) ? 'topic/'.$this->_catID.'/'.$topic->ID : $this->_catID.'/'.($pageNum>1 ? $pageNum : ''); 
		if (!$this->_admin 
		&& !$this->_acl('topic_lock_any') && !($this->_profile->UID==$topic->authorUID && $this->_acl('topic_lock_own') ) )
		{
			
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Not enough permissions to lock topic', EL_URL.$return);
		}
		$topic->lock();
		elLocation(EL_URL.$return);
	}
	
	/**
	 * Удаляет топик
	 *
	 * @return void
	 **/
	function topicRm()
	{
		if (!$this->_admin && !$this->_acl('topic_rm'))
		{
			elPrintR($this->_acl);
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Only moderators can delete topics', EL_URL.$this->_catID);
		}
		$topic = & $this->_topic((int)$this->_arg(1));
		$topic->delete();
		elMsgBox::put( sprintf( m('Topic "%s" was deleted'), $topic->subject) );
		elLocation(EL_URL.$this->_catID.'/');
	}
	
	/**
	 * Перемещает топик в другой форум
	 *
	 * @return void
	 **/
	function topicMove()
	{
		if (!$this->_admin && !$this->_acl('topic_move'))
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', 'Only moderators can move topics', EL_URL.$this->_catID);
		}
		$pageNum = (int)$this->_arg(2);
		$topic   = & $this->_topic((int)$this->_arg(1));
		$sql     = 'SELECT id, CONCAT( "|", REPEAT(" - ", level), name) AS name FROM '.$this->_tbc.' ORDER BY _left';
		if ($topic->move($this->_db->queryToArray($sql, 'id', 'name')))
		{
			elMsgBox::put( sprintf( m('Topic "%s" was moved'), $topic->subject) );
			elLocation(EL_URL.$this->_catID.'/'.($pageNum>1 ? $pageNum : ''));
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($topic->formToHtml());
	}
	
	/**
	 * Список всех пользователей или пользователей, чье имя начинается с опред буквы или модераторов текущего форума
	 *
	 * @param  string  $type  флаг, какой список создавать (all, letter, moderators)
	 * @return void
	 **/
	function usersList($type="all")
	{
		if (!$this->_admin && !$this->_acl('profile_view'))
		{
			elThrow(E_USER_WARNING, 'Access denied', null, EL_URL);
		}

		$profile = & $this->_profile(0, false);
		$param   = null;
		if ('letter' == $type)
		{
			$param = mysql_real_escape_string(rawurldecode(strip_tags(trim($this->_arg()))));
			if ( strlen($param) == 0 || strlen($param) > 3 )
			{
				elThrow(E_USER_WARNING, 'Invalid search parameter', null, EL_URL);
			}
			list($profiles, $current, $total) = $profile->collection('letter', $param);
			$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		}
		elseif ('moderators' == $type)
		{
			$cat   = & $this->_cat($this->_catID);
			$param = $cat->name;
			$ids   = array();
			foreach ($this->_path as $o)
			{
				$ids[] = (int)$o['url'];
			}
			list($profiles, $current, $total) = $profile->collection('moderators', $ids);
			$this->_path[] = array('url'=>'moderators/'.$this->_catID.'/', 'name'=>m('Moderators'));
		}
		else
		{
			list($profiles, $current, $total) = $profile->collection('all', null, (int)$this->_arg(), $this->_conf('usersNum'));
			$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		}
		
		$this->_initRenderer();
		$this->_rnd->rndUsers($type, $param, $profiles, $profile->letters(), $total, $current);
	}
	
	/**
	 * Список пользователей, чье имя начинается с опред буквы
	 *
	 * @return void
	 **/
	function usersListByName()
	{
		return $this->usersList('letter');
	}

	/**
	 * Список модераторов текущего форума
	 *
	 * @return void
	 **/
	function moderators()
	{
		return $this->usersList('moderators');
	}

	/**
	 * Просмотр профайла пользователя
	 *
	 * @return void
	 **/
	function viewProfile()
	{
		if ( !$this->_admin && !$this->_acl('profile_view') )
		{
			elThrow(E_USER_WARNING, 'Access denied', null, EL_URL);
		}
		$profile = & $this->_profile( (int)$this->_arg(0) );
		$this->_initRenderer();
		$this->_rnd->rndProfile($profile->brief(), $profile->isModerator());
		$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		$this->_path[] = array('url'=>'profile/'.$profile->UID, 'name'=>m('User profile').' '.$profile->getName());
	}
	
	/**
	 * Загрузка аватара через ajax
	 *
	 * @return void
	 **/
	function avatarUpload()
	{
		//  доступ не через ajax
		if ( empty($_POST['au']) )
		{
			elLocation(EL_URL);
		}
		$profile = & $this->_profile(0, false);
		$profile->idAttr( (int)$this->_arg() );
		elLoadMessages('Errors');
		if ( !$profile->fetch() )
		{
			$error = m('Requested user does not exits');
		}
		elseif ( $profile->UID<>$this->_profile->UID && !$this->_acl('post_edit_any') )
		{
			$error = m('Acess denied');
		}
		else
		{
			list($error, $filename, $filesize, $dim) 
				= $profile->avatarUpload($this->_conf('avatarMaxFileSize'), $this->_conf('avatarMaxDimension'), $this->_conf('avatarMiniDimension'));
		}
		
		$msg = $error
			? '{err: "'.$error.'"}'
			: '{ msg: "'.m('Avatar was upload successfully!').'<br />'.m('File size').': '.$filesize.' Kb<br />'.m('Dimensions').': '.$dim.'",
				img: "'.EL_BASE_URL.'/storage/avatars/'.$filename.'"}';
		echo $msg;
		exit();
	}
	
	/**
	 * Удаление аватара
	 *
	 * @return void
	 **/
	function avatarRm()
	{
		$profile = & $this->_profile((int)$this->_arg());
		
		if ( $profile->UID<>$this->_profile->UID && !$this->_acl('profile_edit_any') )
		{
			elThrow(E_USER_WARNING, m('Acess denied'), null, EL_URL.'profile/'.$profile->UID);
		}
		if ( !$profile->avatar )
		{
			elThrow(E_USER_WARNING, m('Requested avatar does not exists'), null, EL_URL.'profile/'.$profile->UID);
		}
		$profile->avatarRm();
		elMsgBox::put( sprintf(m('Avatar was removed')) );
		elLocation(EL_URL.'profile/'.$profile->UID);
	}
	
	/**
	 * Смена пароля.
	 * Если модератор меняет пароль пользователю - возможно уведомление поьзователя о новом пароле по email
	 *
	 * @return void
	 **/
	function passwd()
	{
		$profile = & $this->_profile((int)$this->_arg());
		if ( $profile->UID<>$this->_profile->UID && !$this->_acl('profile_edit_any') && !$this->_admin )
		{
			elThrow(E_USER_WARNING, 'Acess denied', null, EL_URL.'profile/'.$profile->UID);
		}
		
		if ( $profile->passwd($this->_profile->UID==$profile->UID) )
		{
			elMsgBox::put( sprintf(m('Password for user "%s" was changed'), $profile->getName()));
			elLocation(EL_URL.'profile/'.$profile->UID);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $profile->formToHtml() );
		$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		$this->_path[] = array('url'=>'profile/'.$profile->UID, 'name'=>m('User profile').' '.$profile->getName());
	}
	
	/**
	 * Регистрация нового пользователя
	 *
	 * @return void
	 **/
	function profileNew()
	{
		if ($this->_profile->UID)
		{
			elThrow(E_USER_WARNING, 'You need to log out before register as new user', null, EL_URL);
		}
		$profile = & $this->_profile(0, false);
		
		if ( !$this->_profile->sessionData('rules_agree') )
		{
			elLoadMessages('Auth');
			$form        = & elSingleton::getObj('elForm');
			$form->label = m('New user registration');
			$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
			$form->add( new elCData('r', nl2br($this->_rules())) );
			$form->add( new elCheckBox('agree', '<strong>'.m('I agree with forum rules').'</strong>', 1));
			
			if ( $form->isSubmit() )
			{
				$val = $form->getElementValue('agree');
				if (!$form->getElementValue('agree'))
				{
					elThrow(E_USER_WARNING, 'You can not register as forum user, if you does not agree with forum rules', null, EL_URL);
				}
				$this->_profile->sessionData('rules_agree', 1);
				elLocation(EL_URL.'reg/');
			}
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		}
		else
		{
			if ( $profile->editAndSave( array($this->_conf('notifyAdmins'), $this->_conf('notifyModerators'), $this->pageID) ) )
			{
				$this->_profile->sessionData('rules_agree', '');
				elMsgBox::put( m('Registration complite! Password was sent on Your e-mail address') );
				elLocation(EL_URL);
			}
			$this->_initRenderer();
			$this->_rnd->addToContent($profile->formToHtml());
		}
	}
	
	/**
	 * Редактирование профайла
	 *
	 * @return void
	 **/
	function profileEdit()
	{
		$profile =  $this->_profile((int)$this->_arg());
		if ( $profile->UID<>$this->_profile->UID && !$this->_acl('profile_edit_any') && !$this->_admin )
		{
			elThrow(E_USER_WARNING, 'Acess denied', null, EL_URL.'profile/'.$profile->UID);
		}
		
		if ($profile->editAndSave())
		{
			elMsgBox::put( sprintf(m('Profile for user %s was updated'), $profile->getName()) );
			elLocation(EL_URL.'profile/'.$profile->UID);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $profile->formToHtml() );
		$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		$this->_path[] = array('url'=>'profile/'.$profile->UID, 'name'=>m('User profile').' '.$profile->getName());
	}
	
	
	
	
	/**
	 * Показывает правила форума на текущем языке
	 *
	 * @return void
	 **/
	function rules()
	{
		$this->_initRenderer(); 
		$this->_rnd->addToContent( nl2br($this->_rules()) );
	}
	
	/**********************************/
	/**           PRIVATE            **/
	/**********************************/

	/**
	 * Возвращает объект-профайл пользователя
	 *
	 * @return object
	 **/
	function &_profile($ID, $onlyExists=true)
	{
		include_once './core/modules/Forum/elForumProfile.class.php';
		$profile = & new elForumProfile( array('uid'=>$ID) );
		if (!empty($this->_conf['defaultAvatar']))
		{
			$profile->defaultAvatar = $this->_conf['defaultAvatar'];
		}
		if ( !$profile->fetch() && ($ID || $onlyExists) )
		{
			elThrow(E_USER_WARNING, 'Requested user does not exits', null, EL_URL.$this->_catID);
		}
		return $profile;
	}

	/**
	 * Возвращает объект-форум
	 *
	 * @return object
	 **/
	function &_cat($ID, $onlyExists=true)
	{
		include_once './core/modules/Forum/elForumCategory.class.php';
		$cat = & new elForumCategory( array('id'=>$ID) );
		if ( !$cat->fetch() && ($ID || $onlyExists) )
		{
			elThrow(E_USER_WARNING, 'Requested forum does not exists', null, EL_URL.$this->_catID);
		}
		return $cat;
	}

	/**
	 * Возвращает объект-тему
	 *
	 * @return object
	 **/
	function &_topic($ID, $onlyExists=true)
	{
		include_once './core/modules/Forum/elForumTopic.class.php';
		$topic = & new elForumTopic( array('id'=>$ID) );
		if ( (!$topic->fetch() || $this->_catID<>$topic->catID) && ($ID || $onlyExists) )
		{
			elThrow(E_USER_WARNING, 'Requested topic does not exists', null, EL_URL.$this->_catID);
		}
		return $topic;
	}
	
	/**
	 * Возвращает объект-сообщение
	 *
	 * @return object
	 **/
	function &_post($ID, $topicID, $onlyExists=true)
	{
		include_once './core/modules/Forum/elForumPost.class.php';
		$post = & new elForumPost( 
			array(
				'id'           => $ID, 
				'cat_id'       => $this->_catID, 
				'topic_id'     => $topicID, 
				'author_uid'   => $this->_profile->UID,
				'author_name'  => $this->_profile->getName(),
				'author_email' => $this->_profile->email,
				'author_ip'    => $_SERVER['REMOTE_ADDR']
				) );
		if ((!$post->fetch() || $post->topicID<>$topicID) && ($ID || $onlyExists))
		{
			elThrow(E_USER_WARNING, 'Requested post does not exists', null, EL_URL.$this->_catID);
		}
		return $post;
	}

	function &_bbcode()
	{
		$bbcode = & elSingleton::getObj('elBBCode');
		$bbcode->init( $this->_conf('bbcodes'), $this->_conf('smiley'), $this->_conf('autolinks') );
		return $bbcode;
	}

	/**
	 * Возвращает дерево форумов с ролями всех групп или только групп к которым принадлежит пользователь
	 *
	 * @param  array  группы пользователя или null если нужны роли всех групп
	 * @return array
	 **/
	function _rbacList( $gids = null)
	{
		
		if (!$gids)
		{
			$roles = array( $this->_adminRole ); //$this->_defaultRoles;
		}
		else
		{
			$roles = array();
			foreach ( $gids as $gid )
			{
				if (isset($this->_defaultRoles[$gid]))
				{
					$roles[$gid] = $this->_defaultRoles[$gid];
				}
			}
		}
		
		$cats = array( array('roles'=>$roles) );
		if ($gids)
		{
			$sql = sprintf(
					'SELECT ch.id, ch.name, ch.allow_posts, ch.count_posts, r.gid, r.rid, p.id AS pid, ch.level  
					FROM %s AS ch 
					LEFT JOIN %s AS r ON (r.cat_id=ch.id %s ) 
					LEFT JOIN %s AS p ON (ch._left BETWEEN p._left AND p._right AND p.level=ch.level-1) 
					ORDER BY ch._left ', $this->_tbc, $this->_tbrb, 'AND r.gid IN('.implode(',', $gids).') ', $this->_tbc);
		}
		else
		{
			$sql = sprintf(
					'SELECT ch.id, ch.name, ch.allow_posts, ch.count_posts, 0 AS gid, %d AS rid, p.id AS pid, ch.level  
					FROM %s AS ch 
					LEFT JOIN %s AS p ON (ch._left BETWEEN p._left AND p._right AND p.level=ch.level-1) 
					ORDER BY ch._left ', $this->_adminRole, $this->_tbc, $this->_tbc);
			
		}

		$this->_db->query($sql);
			
		while ($r = $this->_db->nextRecord())
		{
			if (empty($cats[$r['id']]))
			{
				$cats[$r['id']] = array(
					'id'          => $r['id'], 
					'name'        => $r['name'], 
					'allow_posts' => $r['allow_posts'],
					'count_posts' => $r['count_posts'],
					'pid'         => (int)$r['pid'], 
					'roles'       => array(), 
					'level'       => $r['level']);
			}
			if ($r['gid']!='')
			{
				$cats[$r['id']]['roles'][$r['gid']] = $r['rid'];
			}
		}
		
		foreach ($cats as $id=>$cat)
		{
			if ( $id >0 )
			{
				if ( empty($cats[$cat['pid']]) )
				{
					unset($cats[$id]);
				}
				elseif ( empty($cat['roles']))
				{
					$cats[$id]['roles'] = $cats[$cat['pid']]['roles'];
				}
				else
				{
					$cats[$id]['roles'] = $cats[$id]['roles'] + $cats[$cat['pid']]['roles'];
				}
			}
			if (isset($cats[$id]) && !array_sum($cats[$id]['roles']))
			{
				unset($cats[$id]);
			}
			
		}
		unset($cats[0]); 
		return $cats;
	}
	
	/**
	 * Возвращает текст правил форума на текущем или англиском языке или сообщение об их отсутствии
	 *
	 * @return string
	 **/
	function _rules()
	{
		if ( file_exists('./core/locale/'.EL_LOCALE.'/elForumRules.html') )
		{
			return file_get_contents('./core/locale/'.EL_LOCALE.'/elForumRules.html');
		}
		elseif (file_exists('./core/locale/en_US.UTF-8/elForumRules.html'))
		{
			return file_get_contents('./core/locale/en_US.UTF-8/elForumRules.html');
		}
		else
		{
			elLoadMessages('Errors');
			return m('Sorry, but probably site admin forget to write this rules.');
		}
	}
	
	/**
	 * Инициализация объекта
	 *
	 * @return void
	 **/
	function _onInit()
	{
		$defaultForumActions = array('rules', 'users', 'profile', 'reg', 'passwd', 'profile_edit', 'profile_rm', 
			'avatar', 'avatar_rm', 'moderator', 'attach_upload', 'attach_rm');
		$this->_catID = in_array($this->_mh, $defaultForumActions) || empty($this->_args[0]) || $this->_args[0]<=0
			? 1
			: (int)$this->_args[0];
		
		$this->_db       = &elSingleton::getObj('elDb');
		$ats             = &elSingleton::getObj('elATS');
		$user            = & $ats->getUser();
		$this->_regAllow = $ats->isUserRegAllow();
		
		if ( $user->UID )
		{
			$this->_gids  = array_merge($this->_gids, array(0), $user->getGroups());
			$this->_admin = $this->_aMode>EL_READ;
			$this->_profile = & $this->_profile($user->UID);
		}
		else
		{
			$this->_profile = & $this->_profile(0, false);
		}

		$this->_cats = $this->_rbacList( $this->_admin ? null : $this->_gids );


		if ( empty($this->_cats[$this->_catID]['roles']) && !$this->_admin )
		{
			elThrow(E_USER_WARNING, 'Requested forum does not exists', null, $this->_catID<>1 ? EL_URL : EL_BASE_URL );
		}
		elseif ( $this->_profile->UID && !$this->_admin )
		{
			$this->_db->query(
				sprintf('SELECT m.rid, p.id FROM %s AS m, %s AS ch, %s AS p 
						WHERE ch.id="%d" AND ch._left BETWEEN p._left AND p._right AND m.uid="%d" 
						AND m.cat_id=p.id ORDER BY p._left DESC', 
						$this->_tbm, $this->_tbc, $this->_tbc, $this->_catID, $this->_profile->UID) 
						);
			if ( $this->_db->numRows() )
			{
				$r = $this->_db->nextRecord();
				array_push($this->_cats[$this->_catID]['roles'], $r['rid']);
			}
			$this->_cats[$this->_catID]['roles'] = array_unique($this->_cats[$this->_catID]['roles']);
		}
		
		$sql = !$this->_admin 
			? sprintf('SELECT action, 1 AS allow FROM %s WHERE rid IN (%s) GROUP BY action', $this->_tbrla, implode(',', $this->_cats[$this->_catID]['roles']))
			: sprintf('SELECT action, 1 AS allow FROM %s WHERE rid=%d GROUP BY action', $this->_tbrla, $this->_adminRole);
			;
		$this->_acl = $this->_db->queryToArray(	$sql, 'action', 'allow'	);
//elPrintR($this->_acl);
		if ( !$this->_acl('view') )
		{
			if (!$this->_admin)
			{
				elThrow(E_USER_WARNING, 'Requested forum does not exists', null, $this->_catID<>1 ? EL_URL : EL_BASE_URL );
			}
			else
			{
				elThrow(E_USER_WARNING, 'Invalid forum permissions configuration! Probably, incorrect data in database.');
			}
			
		}

		if ( !$this->_cats[$this->_catID]['allow_posts'] )
		{
			$this->_acl['post_new'] = $this->_acl['post_reply'] = 0;
		}

		$catID = $this->_catID;
		$i = 0;
		do {
			$this->_path[] = array('url'=>$this->_cats[$catID]['id'], 'name'=>$this->_cats[$catID]['name']);
			$catID = $this->_cats[$catID]['pid'];
		} while ( $catID>0 && $i++<15 );
		$this->_path = array_reverse($this->_path);

		foreach ($this->_mMap as $k=>$v)
		{
			if ('conf'<>$k && 'conf_nav'<>$k)
			{
				$this->_mMap[$k]['apUrl'] = $this->_catID;
			}
		}
	}	
	
	function _onBeforeStop()
	{
		for ($i=1, $s=sizeof($this->_path); $i < $s; $i++) 
		{ 
			elAppendToPagePath($this->_path[$i]);
		}
	}
	
	function _acl($param)
	{
		return isset($this->_acl[$param]) ? $this->_acl[$param] : false;
	}
	

	
}

?>