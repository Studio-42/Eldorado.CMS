<?php

class elForumTopic extends elDataMapping
{
	var $_tb         = 'el_forum_topic';
	var $_tbp        = 'el_forum_post';
	var $_tbc        = 'el_forum_cat';
	var $ID          = 0;
	var $catID       = 0;
	var $firstPostID = 0;
	var $lastPostID  = 0;	
	var $numReplies  = 0;
	var $numViews    = 0;
	var $sticky      = 0;
	var $locked      = 0;
	var $subject     = 0;
	var $authorUID   = 0;
	
	
	function fetch()
	{
		if ( $this->ID )
		{
			$db  = & elSingleton::getObj('elDb');
			$db->query(
				sprintf('SELECT t.%s, p.subject, p.author_uid FROM %s AS t, %s AS p 
						WHERE t.id=%d AND p.id=t.first_post_id LIMIT 1',
						implode(',t.', array_keys($this->attr())), $this->_tb, $this->_tbp, $this->ID )
					);
			if ( $db->numRows() )
			{
				$r = $db->nextRecord();
				$this->attr( $r );
				$this->subject   = $r['subject'];
				$this->authorUID = $r['author_uid'];
				return true;
			}
			$this->idAttr(0);
		}
	}
	
	function updateViewsCount()
	{
		$db  = & elSingleton::getObj('elDb');
		$db->query(sprintf('UPDATE %s SET num_views=num_views+1 WHERE id="%d" LIMIT 1', $this->_tb, $this->ID));
	}
	
	
	
	function sticky()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query(sprintf('UPDATE %s SET sticky=IF(sticky, 0, 1) WHERE id=%d LIMIT 1', $this->_tb, $this->ID) );
	}
	
	function lock()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query(sprintf('UPDATE %s SET locked=IF(locked, 0, 1) WHERE id=%d LIMIT 1', $this->_tb, $this->ID) );
	}
	
	function move($cats)
	{
		$this->_form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this), sprintf(m('Move topic "%s" to another forum'), $this->subject) );
		$this->_form->setRenderer( elSingleton::getObj($this->_formRndClass) );
		$this->_form->add( new elSelect('cat_id', m('Select forum'), $this->catID, $cats) );
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			$catID = (int)$data['cat_id'];
			if (empty($cats[$catID]))
			{
				$this->pushError('cat_id', m('Invalid forum selected'));
				return false;
			}
			if ( $catID <> $this->catID )
			{
				$db = & elSingleton::getObj('elDb');	
				$db->query(sprintf('UPDATE %s SET cat_id=%d WHERE id=%d LIMIT 1', $this->_tb, $catID, $this->ID));
				$db->query(sprintf('UPDATE %s SET cat_id=%d WHERE topic_id=%d ', $this->_tbp, $catID, $this->ID));
				
				$db->query( sprintf('UPDATE %s SET num_topics=num_topics-1, num_posts=num_posts-%d, 
								last_post_id=IF(num_topics>0, (SELECT MAX(id) FROM %s WHERE cat_id=%d), 0) WHERE id=%d LIMIT 1', 
								$this->_tbc, $this->numReplies+1, $this->_tbp, $this->catID, $this->catID) );
				$db->query( sprintf('UPDATE %s SET num_topics=num_topics+1, num_posts=num_posts+%d, 
								last_post_id=(SELECT MAX(id) FROM %s WHERE cat_id=%d) WHERE id=%d LIMIT 1', 
								$this->_tbc, $this->numReplies+1, $this->_tbp, $catID, $catID) );
				$this->catID = $catID;
			}
			return true;
		}
	}
	
	function delete()
	{
		$db = &elSingleton::getObj('elDb');
		$db->query( sprintf('DELETE FROM %s WHERE topic_id="%d"', $this->_tbp, $this->ID) );
		$postsNum = $db->affectedRows();
		$db->query( sprintf('DELETE FROM %s WHERE id="%d" LIMIT 1', $this->_tb, $this->ID) );
		$db->query( sprintf('UPDATE %s SET num_topics=num_topics-1, num_posts=num_posts-%d, last_post_id=(SELECT MAX(id) FROM %s WHERE cat_id="%d") WHERE id="%d" LIMIT 1', 
					$this->_tbc, $postsNum, $this->_tbp, $this->catID, $this->catID) );
		$db->optimizeTable($this->_tbp);
		$db->optimizeTable($this->_tb);
	}
	
	/**********************************/
	/**           PRIVATE            **/
	/**********************************/
	

	
	function _initMapping()
	{
	    return array(
			'id'            => 'ID', 
			'cat_id'        => 'catID',
			'first_post_id' => 'firstPostID',
			'last_post_id'  => 'lastPostID',
			'num_replies'   => 'numReplies',
			'num_views'     => 'numViews',	
			'sticky'        => 'sticky',
			'locked'        => 'locked'	
			);
  	}
	
}

?>