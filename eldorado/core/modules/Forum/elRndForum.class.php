<?php
class elRndForum extends elModuleRenderer
{
	/**
	 * шаблоны
	 *
	 * @var array  
	 **/
	var $_tpls = array(
		'forums'  => 'forums.html',
		'topics'  => 'topics.html',
		'topic'   => 'topic.html',
		'post'    => 'post.html',
		'users'   => 'users.html',
		'profile' => 'profile.html',
		'search'  => 'search.html'
		);
		
	/**
	 * ID текущего топика (если есть)
	 *
	 * @var int
	 **/
	var $_topicID  = 0;
	/**
	 * права доступа пользователя (share from elModuleForum)
	 *
	 * @var array
	 **/
	var $_acl      = array();
	
	/**
	 * профайл пользователя (share from elModuleForum)
	 *
	 * @var object
	 **/
	var $_profile  = null;
	
	/**
	 *  ID текущего форума (share from elModuleForum)
	 *
	 * @var int
	 **/
	var $_catID    = 1;
	/**
	 * флаг - разрешена ли регистрация новых пользователей (share from elModuleForum)
	 *
	 * @var bool
	 **/
	var $_regAllow = false;
	
	/**
	 * Рисует список форумов и топиков в текущем форуме
	 *
	 * @param  string  $forumName  название текущего форума
	 * @param  array   $cats       массив форумов
	 * @param  array   $topics     массив топиков
	 * @param  int     $total      кол-во страниц с топиками
	 * @param  int	   $current    номер текущей стр
	 * @return void
	 **/
	function render ($forumName, $cats, $topics, $total, $current)
	{
		$this->_rndCommon($forumName, 'forum', $total, $current);
		
		if ( ($s = sizeof($cats)) > 0 ) // если нет подфорумов - текущий форум не показываем
		{
			$this->_setFile('forums', 'FORUM_CONTENT');

			for ($i=0; $i < $s; $i++) 
			{ 
				if ($cats[$i]['level'] < 3)
				{
					if ($cats[$i]['is_read'])
					{
						$cats[$i]['ico'] = EL_BASE_URL.'/style/images/forum/forums/read.png'; 
						$cats[$i]['msg'] = m('No new messages');
					}
					else
					{
						$cats[$i]['ico'] = EL_BASE_URL.'/style/images/forum/forums/noread.png'; 
						$cats[$i]['msg'] = m('New messages');
					}
				}
				else
				{
					$cats[$i]['cssClass'] = !empty($cats[$i]['is_read']) ? 'mod-forum-level3-read' : 'mod-forum-level3-noread';
				}
				switch ($cats[$i]['level']) 
				{
					case 1:
						$this->_te->assignBlockVars('FORUM.TOP_FORUM', $cats[$i]);
						if ( $this->_admin || !empty($this->_acl['cat_edit']))
						{
							$this->_te->assignBlockVars('FORUM.TOP_FORUM.TF_ADMIN', array('id'=>$cats[$i]['id']), 2);
						}
						break;
						
					case 2:
						$this->_te->assignBlockVars('FORUM.SUBFORUM', $cats[$i], 1);
						if ( $cats[$i]['last_post_id'])
						{ 
							$this->_te->assignBlockVars('FORUM.SUBFORUM.LAST_POST', $cats[$i], 2);
						}
						if ( $this->_admin || !empty($this->_acl['cat_edit']))
						{
							$this->_te->assignBlockVars('FORUM.SUBFORUM.F_ADMIN', array('id'=>$cats[$i]['id']), 2);
						}
						break;
					
					default:
						$this->_te->assignBlockVars('FORUM.SUBFORUM.SUBFORUMS.SUBFORUM2', $cats[$i], 3);
				}
			}
		}


		if ( ($s = sizeof($topics)) >0 )
		{
			$this->_setFile('topics', 'TOPICS_CONTENT');

			if ($this->_acl('topic_lock_any') || $this->_acl('topic_sticky_any') 
			|| $this->_acl('topic_move') || $this->_acl('topic_rm'))
			{
				$this->_te->assignBlockVars('TA_HEAD');
			}
			
			for ($i=0; $i < $s; $i++) 
			{ 
				$topics[$i]['ico'] = EL_BASE_URL.'/style/images/forum/topics/';
				if ( $topics[$i]['num_replies']>=$this->_conf['hotTopicPostsNum'] )
				{
					$topics[$i]['ico'] .= $topics[$i]['is_read'] ? 'hot-read.png' : 'hot-noread.png';
				}
				else
				{
					$topics[$i]['ico'] .= $topics[$i]['is_read'] ? 'read.png' : 'noread.png';
				}
				
				$this->_te->assignBlockVars('TOPIC', $topics[$i]);
				
				if ( $topics[$i]['sticky'] || $topics[$i]['locked'] )
				{
					if ( $topics[$i]['sticky'] )
					{
						$this->_te->assignBlockVars('TOPIC.STICKED', null, 1);
					}
					if ( $topics[$i]['locked'] )
					{
						$this->_te->assignBlockVars('TOPIC.LOCKED', null, 1);
					}
				}
				else
				{
					$this->_te->assignBlockVars('TOPIC.ICO', array('ico'=>$topics[$i]['post_ico']), 1);
				}
				
				
				if (!empty($this->_acl['topic_lock_any']))
				{
					$this->_te->assignBlockVars('TOPIC.T_ADMIN.TOPIC_LOCK', array('id'=>$topics[$i]['id'], 'cat_id'=>$topics[$i]['cat_id']), 2);
				}
				if (!empty($this->_acl['topic_sticky_any']))
				{
					$this->_te->assignBlockVars('TOPIC.T_ADMIN.TOPIC_STICKY', array('id'=>$topics[$i]['id'], 'cat_id'=>$topics[$i]['cat_id']), 2);
				}
				if (!empty($this->_acl['topic_move']))
				{
					$this->_te->assignBlockVars('TOPIC.T_ADMIN.TOPIC_MOVE', array('id'=>$topics[$i]['id'], 'cat_id'=>$topics[$i]['cat_id']), 2);
				}
				if (!empty($this->_acl['topic_rm']))
				{
					$this->_te->assignBlockVars('TOPIC.T_ADMIN.TOPIC_RM', array('id'=>$topics[$i]['id'], 'cat_id'=>$topics[$i]['cat_id']), 2);
				}
			}
		}
	}

	/**
	 * Рисует список постов в топике
	 *
	 * @param  oblect   $topic  топик
	 * @param  array    $posts   массив постов
	 * @param  array    $attachs   массив аттачментов
	 * @param  int      $total   кол-во страниц с постами
	 * @param  int	    $current номер текущей стр	
	 * @return void
	 **/
	function rndTopic($topic, $posts, $attachs, $total, $current)
	{
		$this->_topicID = $topic->ID; 
		$this->_rndCommon($topic->subject, 'topic', $total, $current);
		
		$this->_setFile('topic', 'TOPICS_CONTENT');

		$this->_te->assignVars('maxPostImgSize', (int)$this->_conf('maxPostImgSize'));
		$allowAttach = false;
		for ($i=0, $s=sizeof($posts); $i < $s; $i++) 
		{ 
			$posts[$i]['cssRowClass'] = $i%2 ? 'mod-forum-post-row-odd' : 'mod-forum-post-row-ev';
			
			$this->_te->assignBlockVars('POST', $posts[$i]);
			if ( $posts[$i]['modified_name'] )
			{
				$data = array('uid'=>$posts[$i]['modified_uid'], 'name'=>$posts[$i]['modified_name'], 'date'=>$posts[$i]['modified_date']);
				$this->_te->assignBlockVars('POST.POST_MODIFIED', $data, 1);
			}
			$avatar = $posts[$i]['author_avatar'] ? $posts[$i]['author_avatar'] : $this->_conf['defaultAvatar'];
			if ( $avatar )//$posts[$i]['author_avatar'] )
			{
				$this->_te->assignBlockVars('POST.AUTHOR_AVATAR', array('avatar'=>$avatar), 1);
			}
			
			$data =  array('id'=>$posts[$i]['id'], 'cat_id'=>$posts[$i]['cat_id'], 'topic_id'=>$posts[$i]['topic_id']);
			$attachEdit = $this->_acl('attach') 
				&& ($this->_acl('post_edit_any') || ($this->_profile->UID == $posts[$i]['author_uid'] && $this->_acl('post_edit_own')));
			if ( $attachEdit )
			{
				$allowAttach = true;
			}
			if (!empty($attachs[$posts[$i]['id']]))
			{
				foreach ($attachs[$posts[$i]['id']] as $atID=>$at)
				{
					$at['cat_id'] = $posts[$i]['cat_id'];
					if ($at['is_img'])
					{
						$this->_te->assignBlockVars('POST.POST_ATTACHMENTS.POST_ATTACH.POST_IMG', $at, 2);
					}
					else
					{
						$this->_te->assignBlockVars('POST.POST_ATTACHMENTS.POST_ATTACH.POST_FILE', $at, 2); 
					}
					if ($attachEdit)
					{
						$data['attach_id'] = $atID;
						$this->_te->assignBlockVars('POST.POST_ATTACHMENTS.POST_ATTACH.ATTACH_RM', $data, 3); 	
					}
				}
			}
			
			
			if ( $this->_acl('post_reply') )
			{
				$this->_te->assignBlockVars('POST.POST_QUOTE', $data, 1);
			}
			if ( $this->_acl('post_edit_any') || ($this->_profile->UID == $posts[$i]['author_uid'] && $this->_acl('post_edit_own')) )
			{
				$this->_te->assignBlockVars('POST.AUTHOR_IP', array('ip'=>$posts[$i]['author_ip']), 1);
				$this->_te->assignBlockVars('POST.POST_MODIFY', $data, 1);
				if ($this->_acl('attach'))
				{
					$this->_te->assignBlockVars('POST.POST_ATTACH_FILE', $data, 1);
				}
				
			}
			if ( $i<>0 && ($this->_acl('post_rm_any') || ($this->_profile->UID == $posts[$i]['author_uid'] && $this->_acl('post_rm_own'))) )
			{
				$this->_te->assignBlockVars('POST.POST_RM', $data, 1);
			}
			if ( $i == 0 )
			{
				if ( $this->_acl('topic_lock_any') || ($this->_profile->UID == $posts[$i]['author_uid'] && $this->_acl('topic_lock_own')) )
				{
					$this->_te->assignBlockVars($topic->locked ? 'POST.TOPIC_UNLOCK' : 'POST.TOPIC_LOCK', $data, 1);
				}
				if ( $this->_acl('topic_sticky_any') || ($this->_profile->UID == $posts[$i]['author_uid'] && $this->_acl('topic_sticky_own')) )
				{
					$this->_te->assignBlockVars($topic->sticky ? 'POST.TOPIC_UNSTICK' : 'POST.TOPIC_STICK', $data, 1);
				}
			}
		}

		if ($allowAttach)
		{
			elLoadJQueryUI();
			elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
			$this->_te->assignBlockVars('POST_ATTACH_JS');
		}

	}
	
	/**
	 * Рисует список пользовтелей в одном из 3=х вариантов 
	 * 1 - список всех пользователей
	 * 2 - список пользователей, чье имя начинается с опред буквы
	 * 3 - список модератов текущего форума
	 *
	 * @param  string  тип списка
	 * @param  misc    доп параметр
	 * @param  array   пользователи
	 * @param  array   массив первых букв имен пользователей
	 * @param  int     кол-во страниц в списке
	 * @param  int	   текущая стр
	 * @return void
	 **/
	function rndUsers($type, $param, $users, $letters, $total=0, $current=0)
	{
		$context = 'users';
		if ( 'letter' == $type )
		{
			$title = sprintf(m('Users list with names starts with "%s"'), $param);
		}
		elseif ( 'moderators' == $type )
		{
			$context = 'moderators';
			$title   = sprintf(m('Moderators on forum "%s"'), $param); 
		}
		else
		{
			$title = m('Users');
		}
		
		$this->_rndCommon($title, $context, $total, $current, $letters); 
		if ( !$users )
		{
			return $this->_te->assignVars('FORUM_CONTENT', m('There are no one user was found'));
		}

		$this->_setFile('users', 'FORUM_CONTENT');
		for ($i=0, $s=sizeof($users); $i < $s ; $i++) 
		{ 
			$users[$i]['onlineCssClass'] = $users[$i]['online'] ? 'online' : 'offline';
			$users[$i]['online'] = $users[$i]['online'] ? m('Online') : m('Offline');
			$users[$i]['cssRowClass'] = $i%2 ? 'mod-forum-users-row-odd' : 'mod-forum-users-row-ev';
			
			$this->_te->assignBlockVars('FORUM_USER', $users[$i]);
			if ( $users[$i]['web_site'])
			{
				$this->_te->assignBlockVars('FORUM_USER.USER_SITE', $users[$i], 1);
			}
			$avatar = $users[$i]['avatar'] ? $users[$i]['avatar'] : (!empty($this->_conf['defaultAvatar']) ? $this->_conf['defaultAvatar'] : '');
			if ( $avatar )
			{
				$this->_te->assignBlockVars('FORUM_USER.U_AVATAR', array('uid'=>$users[$i]['uid'], 'avatar'=>$avatar), 1);
			}
		}
	}
		
	function rndProfile($profile, $forums)
	{
		elLoadMessages('UserProfile');
		$this->_setFile('profile', 'FORUM_CONTENT');
		$this->_rndCommon('', 'profile'); 
		$profile['gender'] = '' == $profile['gender'] ? m('Undefined') : m(ucfirst($profile['gender']));
		$avatar = $profile['avatar'] ? $profile['avatar'] : (!empty($this->_conf['defaultAvatar']) ? $this->_conf['defaultAvatar'] : '');
		if ( $avatar )
		{
			$this->_te->assignBlockVars('PROFILE_AVATAR', array('avatar'=>$avatar));
		}
		if ( $this->_profile->UID == $profile['uid'] || $this->_acl('profile_edit_any') )
		{
			$data = array('uid'=>$profile['uid']);
			$this->_te->assignBlockVars('PROFILE_MOD_DATE', array('mod_date', $profile['mod_date']));
			$this->_te->assignBlockVars('PROFILE_ACTIONS.PROFILE_EDIT', $data, 1);
			$this->_te->assignBlockVars('PROFILE_ACTIONS.PASSWD', $data, 1);
			$this->_te->assignBlockVars('PROFILE_ACTIONS.UPL_AVATAR', $data, 1);
			elLoadJQueryUI();
			elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
			$this->_te->assignBlockVars('UPLOAD_AVATAR_JS');
			$this->_te->assignVars('avatar_type_msg', m('Image must be in jpg, gif or png format'));
			$this->_te->assignVars('avatar_size_msg', sprintf(m('Image file size must be less or equal then %d Kb'), $this->_conf['avatarMaxFileSize']));
			$this->_te->assignVars('avatar_dim_msg', sprintf(m('Image width add height must be les or equal %s pixels'), $this->_conf['avatarMaxDimension']));
			
			if ($this->_acl('profile_edit_any'))
			{
				$this->_te->assignBlockVars('PROFILE_ACTIONS.USER_RM', $data, 1);
				$this->_te->assignBlockVars('PROFILE_ACTIONS.SET_MODERATOR', $data, 1);
			}
			if ( $profile['avatar'] )
			{
				$this->_te->assignBlockVars('PROFILE_ACTIONS.RM_AVATAR', $data, 2);
			}
			
		}
		if ( $this->_profile->UID )
		{
			$this->_te->assignBlockVars('PROFILE_ACTIONS.SEND_PM', array('uid'=>$profile['uid']), 2);
		}
		
		if ( $profile['online'] )
		{
			$profile['status']         = m('Online');
			$profile['cssStatusClass'] = 'status-online';
		}
		else
		{
			$profile['status']         = m('Offline');
			$profile['cssStatusClass'] = 'status-offline';
		}
		if ( $forums )
		{
			$this->_te->assignBlockVars('MODERATOR_FORUMS', array('forums' => !empty($forums[1]) ? m('All forums') : implode(', ', $forums)) );
		}

		$this->_te->assignVars( $profile );
	}
	
	function rndPost($form)
	{
		$this->_setFile('post');
		$this->_te->assignVars('POST_FORM', $form);
	}

	function rndSearchResult($results, $next, $form)
	{
		$this->_setFile('search');
		$this->_te->assignVars(array('form' => $form));
		
		foreach ($results as $r)
		{
			// elPrintR($r);
			$r['crtime'] = date(EL_DATETIME_FORMAT, $r['crtime']);
			$this->_te->assignBlockVars('RESULT.FOUND', $r, 1);
		}
		
		if ($next > 0)
			$this->_te->assignBlockVars('RESULT.NEXT', array('next' => $next));
		
		if (is_array($results) and (sizeof($results) == 0))
			$this->_te->assignBlockVars('NOT_FOUND');
	}
	
	/**
	 * Перегруженый родительский метод
	 * Вставляет контент в страницу
	 *
	 * @param  string  $str  текст для вставки
	 * @param  bool    $popup когда содержимое открывается в новом окне ведет себя как родитель
	 * @return void
	 **/
	function addToContent($str, $popup=false)
  	{
		if ( $popup )
		{
			return parent::addToContent($str);
		}
		$this->_rndCommon('', 'undefined');
		$this->_te->assignVars('FORUM_CONTENT', $str, true);
	}
	
	function _rndCommon($head, $context='forum', $total=1, $current=1, $letters=null, $moderators=false)
	{
		$this->_setFile();
		
		if ( $this->_acl('profile_view') )
		{
			$this->_te->assignBlockVars('USERS_LINK');
		}
		if (!$this->_profile->UID)
		{
			
			$this->_te->assignBlockVars('FORUM_LOGIN');
			if ($this->_regAllow)
			{
				elLoadMessages('Auth');
				$this->_te->assignBlockVars('FORUM_LOGIN.FORUM_REG', null, 1);
			}
		}
		else
		{
			$this->_te->assignBlockVars('FORUM_CUR_USER', array('user_name'=>$this->_profile->getName(), 'uid'=>$this->_profile->UID) );
			if ( $this->_profile->avatar )
			{
				$this->_te->assignBlockVars('FORUM_CUR_USER.MINI_AVATAR', 
						array('avatar'=>$this->_profile->avatar, 'dim'=>$this->_conf['avatarMiniDimension'], 'uid'=>$this->_profile->UID), 1);
			}
		}
		// заголовок
		if ( 'users' == $context || 'moderators' == $context )
		{   // название списка пользователей
			$this->_te->assignBlockVars('FORUM_HEAD', array('id'=>('moderators' == $context ? 'moderators/'.$this->_catID : 'users'), 'name'=>$head));	
			for ($i=0, $s=sizeof($letters); $i<$s; $i++)
			{
				$this->_te->assignBlockVars('FORUM_TOP_PANEL.ULETTERS.ULETTER', array('letter'=>$letters[$i]), 2);
			}
		}
		elseif ( 'topic' == $context )
		{   // название топика
			$this->_te->assignBlockVars('FORUM_HEAD', array('id'=>'topic/'.$this->_catID.'/'.$this->_topicID, 'name'=>$head));
		}
		elseif (1 <> $this->_catID )
		{   // название форума, кроме форума верхнего уровня
			$this->_te->assignBlockVars('FORUM_HEAD', array('id'=>$this->_catID, 'name'=>$head));			
		}
		
		$this->_te->assignVars('forum_current_cat_id', $this->_catID);
		$this->_te->assignVars('forum_current_page', $current);
		$data = array('cat_id'=>$this->_catID, 'topic_id'=>$this->_topicID);
		if (('forum' == $context || 'topic' == $context) && $this->_acl('post_new'))
		{
			$this->_te->assignBlockVars('FORUM_TOP_PANEL.BUTTON_POST_NEW', $data, 1);
			$this->_te->assignBlockVars('FORUM_BOT_PANEL.BUTTON_POST_NEW', $data, 1);
		}
		if ('topic' == $context && $this->_acl('post_reply'))
		{
			$this->_te->assignBlockVars('FORUM_TOP_PANEL.BUTTON_POST_REPLY', $data, 1); 
			$this->_te->assignBlockVars('FORUM_BOT_PANEL.BUTTON_POST_REPLY', $data, 1);
		}
		if ('topic' == $context && $this->_acl('view'))
		{
			$this->_te->assignBlockVars('FORUM_TOP_PANEL.TOPIC_SEARCH', $data, 1); 
			// $this->_te->assignBlockVars('FORUM_BOT_PANEL.TOPIC_SEARCH', $data, 1);
		}		
		
		if ($total>1)
		{
			$this->_te->assignBlockVars('FORUM_TOP_PANEL', null, 1); 
			$this->_te->assignBlockVars('FORUM_BOT_PANEL', null, 1);
			$this->_te->assignVars('forum_current_page', $current);
			$this->_te->setFile('PAGER', 'common/pager.html');
			if ('topic' == $context)
			{
				$url = EL_URL.'topic/'.$this->_catID.'/'.$this->_topicID.'/';
			}
			elseif ('users' == $context)
			{
				$url = EL_URL.'users/';
			}
			else
			{
				$url = EL_URL.$this->_catID.'/';
			}

			if ( $current > 1 )
			{
				$this->_te->assignBlockVars('PAGER.PREV', array('url' => $url, 'num'=>$current-1 ));
			}
			for ( $i=1; $i<=$total; $i++ )
			{
				$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num'=>$i, 'url'=>$url));
			}
			if ( $current < $total )
			{
				$this->_te->assignBlockVars('PAGER.NEXT', array('url'=>$url, 'num'=>$current+1 ));
			}
			$this->_te->parse('PAGER');
		}
	}

	function _acl($param)
	{
		return isset($this->_acl[$param]) ? $this->_acl[$param] : false;
	}


}

?>