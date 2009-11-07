<?php

class elForumPost extends elDataMapping
{
	var $_tb   = 'el_forum_post';
	var $_tbc  = 'el_forum_cat';
	var $_tbt  = 'el_forum_topic';
	var $_tbat = 'el_forum_attach';
	var $_urlRegexp = '/^(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]$/i';
	
	var $ID           = 0;
	var $catID        = 0;
	var $topicID      = 0;
	var $crtime       = 0;
	var $mtime        = 0;
	var $authorUID    = 0;
	var $authorName   = '';
	var $authorEmail  = '';
	var $authorIP     = '';
	var $modifiedUID  = 0;
	var $modifiedName = '';
	var $subject      = '';
	var $message      = '';
	var $ico          = 'default.png';

	function preview($params)
	{
		$this->_makeForm($params);
		if ( !$this->_form->isSubmit() )
		{
			return false;
		}
		if ( $this->_form->hasErrors() )
		{
			return array('errors' => $this->_form->getErrors());
		}
		$this->attr( $this->_form->getValue() );
		$attrs = $this->_attrsForSave();
		return array('subject'=>$attrs['subject'], 'message'=>$attrs['message'], 'create_date'=>date(EL_DATETIME_FORMAT, $attrs['crtime']));
	}
	
	function delete()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query(sprintf('DELETE FROM %s WHERE id=%d LIMIT 1', $this->_tb, $this->ID));
		$db->query(sprintf('UPDATE %s SET num_replies=num_replies-1, last_post_id=(SELECT MAX(id) FROM %s WHERE topic_id=%d) WHERE id=%d LIMIT 1', $this->_tbt, $this->_tb, $this->topicID, $this->topicID));
		$db->query(sprintf('UPDATE %s SET num_posts=num_posts-1, last_post_id=(SELECT MAX(id) FROM %s WHERE topic_id=%d) WHERE id=%d LIMIT 1', $this->_tbc, $this->_tb, $this->topicID, $this->catID));
		$db->optimizeTable($this->_tb);
	}

	/**********************************/
	/**           PRIVATE            **/
	/**********************************/
	
	function _makeForm($params)
	{
		$title       = m('New topic');
		$this->_form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this) );
		
		if ( $this->ID ) // редактирование поста, редактор - владелец или модератор - мы не знаем
		{	
			$this->modifiedUID  = $params['user']->UID;
			$this->modifiedName = $params['user']->getName();
		}
		else // новый пост
		{
			if ( !$this->authorUID )  // гость
			{
				$this->authorIP = $_SERVER['REMOTE_ADDR'];
				$this->_form->add( new elText('author_name',  m('Name')) );
				$this->_form->add( new elText('author_email', m('E-mail')) );
				$this->_form->setRequired('author_name');
				$this->_form->setElementRule('author_email', 'email');
			}
			
			if ( $this->topicID )  //  ответ в существующую тему
			{
				$db = &elSingleton::getObj('elDb');
				$db->query(sprintf('SELECT p.subject FROM %s AS t, %s AS p WHERE t.id="%d" AND p.id=t.first_post_id', $this->_tbt, $this->_tb, $this->topicID));
				$r       = $db->nextRecord();
				$title   = sprintf( m('Reply to: %s'), $r['subject']);
				$this->subject = m('Re: ').$r['subject'];
				
				if ($params['quoteID'])  //  ответ с цитированием
				{
					$sql = 'SELECT p.message, IF(u.f_name!="", u.f_name, IFNULL(u.login, p.author_name)) AS author, '
						.'DATE_FORMAT(FROM_UNIXTIME(p.crtime), "'.EL_MYSQL_DATETIME_FORMAT.'") AS date FROM '
						.$this->_tb.' AS p LEFT JOIN el_user AS u ON (u.uid=p.author_uid) WHERE id="'.$params['quoteID'].'"';
					$db->query($sql);
					$r = $db->nextRecord(); 
					$link = 'topic/'.$this->catID.'/'.$this->topicID.'/'.$params['pageNum'].'/#'.$params['quoteID'];
					$this->message = '[quote author='.$r['author'].' link='.$link.' date='.$r['date'].']'.$r['message'].'[/quote]';
				}
			}
		}
		
		$this->_form->setLabel($title);
		$this->_form->add( new elText('subject', m('Subject'), $this->subject) );
		$ic = glob('./style/images/forum/posts/*'); 
		$icons = array();
		foreach ($ic as $i)
		{
			if (is_file($i))
			{
				$icons[] = basename($i);
			}
		}
		sort($icons);
		$opts = array();
		foreach ($icons as $ico)
		{
			$opts[$ico] = '<img src="'.EL_BASE_URL.'/style/images/forum/posts/'.$ico.'" />';
		}
		
		$rb = & new elRadioButtons('ico', m('Post icon'), $this->ico, $opts) ;
		$rb->tpl['element'] = "<label for='%s' style=\"border-right:1px solid grey;padding:0 5px;margin:0\"><input%s%s value=\"%s\" />&nbsp;%s</label> \n";
		$this->_form->add( $rb, array('noLabelCell'=>1, 'cellAttrs'=>'class="form-tb-sub"') );
		
		include_once './core/forms/elements/elBBcodeEditor.class.php';
		//elPrintR($this->message);
		$this->_form->add( new elBBcodeEditor('message', m('Message'), $this->message), array('noLabelCell'=>1, 'nolabel'=>1));
		
		
		
		
		$this->_form->setRequired('subject');
		$this->_form->setRequired('message');
		
		$attachFiles = '<div class="important" id="mod-forum-post-attach">';
		if ( !empty($params['attachs']) )
		{
			//elPrintR($params['attachs']);
			
			foreach ($params['attachs'] as $id=>$filename)
			{
				$attachFiles .= '<div name="'.$id.'">'.$filename
					.' <a href="'.EL_URL.'attach_rm/'.$this->catID.'/'.$this->topicID.'/'.$this->ID.'/'.$id.'/'.rawurlencode($filename).'"><img src="'.EL_BASE_URL.'/style/icons/user/remove.png" style="vertical-align:middle" /></a></div>';
			}
			//echo $attach;
		}
		$attachFiles .= '</div>';
		
		$attacher = & new elFormContainer('post_attach', m('Attachments'));
		$attacher->setTpl('label', '');
		$attacher->add( new elCData('atfiles', $attachFiles) );
		$attacher->add( new elFileInput('post_attach_file', m('Attach file')) );
		$attacher->add( new elSubmit('s1', '', 'add file') );
		
		//$this->_form->add($attacher);
		
		
		$rnd = & elSingleton::getObj($this->_formRndClass);
		$rnd->setButtonsNames('', '');
		$rnd->addButton( new elSubmit('save', '', m('Send')));
		$rnd->addButton( new elSubmit('preview', '', m('Preview') ));		
		$this->_form->setRenderer( $rnd );
		
		elAddJs('jquery.form.js', EL_JS_CSS_FILE);
	}
	
	function _attrsForSave()
	{
		$attrs = $this->attr(); 
		$attrs['subject'] = strip_tags($attrs['subject']);
		$attrs['message'] = strip_tags($attrs['message']);	
		// проверяем картинки
		if ( preg_match_all('/\[img\](.+?)\[\/img\]/ism', $attrs['message'], $m) )
		{
			for ($i=0, $s=sizeof($m[1]); $i<$s; $i++) 
			{
				elDebug('Check image URL: '.$m[1][$i]);
				if (!preg_match($this->_urlRegexp, $m[1][$i]))
				{
					elDebug('Not URL');
					$attrs['message'] = str_replace($m[0][$i], '', $attrs['message']);
				}
				else
				{
					elDebug('URL; Check image');
					$size = getimagesize($m[1][$i]);
					if (!$size || empty($size[2])) 
					{
						elDebug('Not image');
						$attrs['message'] = str_replace($m[0][$i], '', $attrs['message']);
					}
					elDebug('Image - OK');
				}
			}
		}
		// режем нездоровые urls
		if ( preg_match_all('/\[url\=(.+?)\](.+?)?\[\/url\]/s', $attrs['message'], $m) )
		{
			for ($i=0, $s=sizeof($m[1]); $i<$s; $i++) 
			{
				elDebug('Check URL: '.$m[1][$i]);
				if (!preg_match($this->_urlRegexp, $m[1][$i]) 
				|| strstr($m[1][0], 'document.cookie') )
				{
					elDebug('Not URL');
					$attrs['message'] = str_replace($m[0][$i], $m[2][0], $attrs['message']);
				}
			}
		}
		if (!$this->ID)
		{
			$attrs['crtime'] = time();
		}	
		else 
		{
			$attrs['mtime'] = time();			
		}
		return $attrs;
	}
	
	function _postSave($isNew)
	{
		$db = &elSingleton::getObj('elDb');
		if ( $isNew )
		{
			$newTopic = 0;
			if ( $this->topicID ) //  reply - обновляем счетчики в таблице топиков
			{
				if ( !$db->query(sprintf('UPDATE %s SET last_post_id=%d, num_replies=num_replies+1 WHERE id=%d LIMIT 1', $this->_tbt, $this->ID, $this->topicID) ) )
				{
					return false;
				}
				
			}
			else // new post - новый топик
			{
				if (!$db->query(sprintf('INSERT INTO %s (cat_id, first_post_id, last_post_id) VALUES (%d, %d, %d)', $this->_tbt, $this->catID, $this->ID, $this->ID)))
				{
					return false;
				}
				$this->topicID = $db->insertID();
				if (!$db->query(sprintf('UPDATE %s SET topic_id=%d WHERE id=%d LIMIT 1', $this->_tb, $this->topicID, $this->ID )))
				{
					return false;
				}
				$newTopic = 1;
			}
			// обновляем счетчики в таблице форумов
			return $db->query( sprintf('UPDATE %s SET last_post_id="%d", num_posts=num_posts+1, num_topics=num_topics+%d WHERE id="%d" LIMIT 1',
								$this->_tbc, $this->ID, $newTopic, $this->catID));
		}
		return true;
	}
	
	function _initMapping()
	  {
	    return array(
			'id'            => 'ID', 
			'cat_id'        => 'catID', 
			'topic_id'      => 'topicID', 
			'crtime'        => 'crtime',
			'mtime'         => 'mtime',
			'author_uid'    => 'authorUID',
			'author_name'   => 'authorName',
			'author_email'  => 'authorEmail',
			'author_ip'     => 'authorIP',
			'modified_uid'  => 'modifiedUID',
			'modified_name' => 'modifiedName',			
			'subject'       => 'subject', 
			'message'       => 'message',
			'ico'           => 'ico'
			);
	  }
}

?>