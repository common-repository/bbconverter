<?php
/*
Plugin Name: BBConverter for Xenforo
Plugin URI: http://www.bbconverter.com/
Description: Convert Xenforo forum software into bbpress.
Author: Adam Ellis
Version: 1.3
Author URI: http://www.bbconverter.com/
*/

/**
 * Include files.
 */
require_once( $GLOBALS['bbc']->dir . 'bbc-includes/bbc-converter-class.php' );

/**
 * Implementation of Xenforo converter.
 */
class Xenforo extends BBConverterBase
{
	function __construct()
	{
		parent::__construct();
		$this->setup_globals();
	}

	public function setup_globals()
	{
		/** Forum Section ******************************************************/

		// Forum id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'node_id',
			'to_type' => 'forum', 'to_fieldname' => '_bbc_forum_id'
		);
		
		// Forum parent id.  If no parent, than 0. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'parent_node_id',
			'to_type' => 'forum', 'to_fieldname' => '_bbc_parent_id',
			'translate_method' => 'translate_forumid'
		);
		
		// Forum title.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'title',
			'to_type' => 'forum', 'to_fieldname' => 'post_title'
		);
		
		// Forum slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'title',
			'to_type' => 'forum', 'to_fieldname' => 'post_name',
			'translate_method' => 'translate_title'
		);
		
		// Forum description.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'description',
			'to_type' => 'forum', 'to_fieldname' => 'post_content',
			'translate_method' => 'translate_null'
		);
		
		// Forum display order.  Starts from 1.
		$this->field_map[] = array(
			'from_tablename' => 'node', 'from_fieldname' => 'display_order',
			'to_type' => 'forum', 'to_fieldname' => 'menu_order'
		);
		
		// Forum date update.
		$this->field_map[] = array(
			'to_type' => 'forum', 'to_fieldname' => 'post_date',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'forum', 'to_fieldname' => 'post_date_gmt',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'forum', 'to_fieldname' => 'post_modified',
			'default' => date('Y-m-d H:i:s')
		);
		$this->field_map[] = array(
			'to_type' => 'forum', 'to_fieldname' => 'post_modified_gmt',
			'default' => date('Y-m-d H:i:s')
		);

		/** Topic Section ******************************************************/

		// Topic id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'thread_id',
			'to_type' => 'topic', 'to_fieldname' => '_bbc_topic_id'
		);
		
		// Forum id. Stored in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'node_id',
			'to_type' => 'topic', 'to_fieldname' => '_bbc_forum_id',
			'translate_method' => 'translate_forumid'
		);
				
		// Topic author.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'user_id',
			'to_type' => 'topic', 'to_fieldname' => 'post_author',
			'translate_method' => 'translate_userid'
		);
		
		// Topic title.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'title',
			'to_type' => 'topic', 'to_fieldname' => 'post_title'
		);
		
		// Topic slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'title',
			'to_type' => 'topic', 'to_fieldname' => 'post_name',
			'translate_method' => 'translate_title'
		);
		
		// Forum id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'node_id',
			'to_type' => 'topic', 'to_fieldname' => 'post_parent',
			'translate_method' => 'translate_forumid'
		);

		// Topic date update.
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'post_date',
			'to_type' => 'topic', 'to_fieldname' => 'post_date',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'post_date',
			'to_type' => 'topic', 'to_fieldname' => 'post_date_gmt',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'last_post_date',
			'to_type' => 'topic', 'to_fieldname' => 'post_modified',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'thread', 'from_fieldname' => 'last_post_date',
			'to_type' => 'topic', 'to_fieldname' => 'post_modified_gmt',
			'translate_method' => 'translate_datetime'
		);

		/** Post Section ******************************************************/

		// Post id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'post_id',
			'to_type' => 'reply', 'to_fieldname' => '_bbc_post_id'
		);
		
		// Forum id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'thread_id',
			'to_type' => 'reply', 'to_fieldname' => '_bbc_forum_id',
			'translate_method' => 'translate_topicid_to_forumid'
		);
		
		// Topic id. Stores in postmeta.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'thread_id',
			'to_type' => 'reply', 'to_fieldname' => '_bbc_topic_id',
			'translate_method' => 'translate_topicid'
		);

		// Post author.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'user_id',
			'to_type' => 'reply', 'to_fieldname' => 'post_author',
			'translate_method' => 'translate_userid'
		);
		
/*
		// Topic title.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'title',
			'to_type' => 'reply', 'to_fieldname' => 'post_title'
		);
		
		// Topic slug. Clean name.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'title',
			'to_type' => 'reply', 'to_fieldname' => 'post_name',
			'translate_method' => 'translate_title'
		);
*/		
		
		// Post content.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'message',
			'to_type' => 'reply', 'to_fieldname' => 'post_content',
			'translate_method' => 'translate_html'
		);
		
		// Topic id.  If no parent, than 0.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'thread_id',
			'to_type' => 'reply', 'to_fieldname' => 'post_parent',
			'translate_method' => 'translate_topicid'
		);

		// Topic date update.
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'post_date',
			'to_type' => 'reply', 'to_fieldname' => 'post_date',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'post_date',
			'to_type' => 'reply', 'to_fieldname' => 'post_date_gmt',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'post_date',
			'to_type' => 'reply', 'to_fieldname' => 'post_modified',
			'translate_method' => 'translate_datetime'
		);
		$this->field_map[] = array(
			'from_tablename' => 'post', 'from_fieldname' => 'post_date',
			'to_type' => 'reply', 'to_fieldname' => 'post_modified_gmt',
			'translate_method' => 'translate_datetime'
		);

		/** User Section ******************************************************/

		// Store old User id. Stores in usermeta.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'user_id',
			'to_type' => 'user', 'to_fieldname' => '_bbc_user_id'
		);
		
/*	
data field format (XenForo_Authentication_Core):	
a:3:{s:4:"hash";s:64:"70975f029b8fc03c73d364c7d733de66a37c19f1dda19216fcb451ec44fca11e";s:4:"salt";s:64:"26fcda8a9f58c39316318338e57f746443e0fa3488b843b61233305b161c7964";s:8:"hashFunc";s:6:"sha256";}
		// User password.
		$this->field_map[] = array(
			'from_tablename' => 'user_authenticate', 'from_fieldname' => 'data',
			'to_type' => 'user', 'to_fieldname' => '_bbp_converter_password'
		);
				
		// Store old User password. Stores in usermeta serialized with salt.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'password',
			'to_type' => 'user', 'to_fieldname' => '_bbc_password',
			'translate_method' => 'translate_savepass'
		);

		// Store old User Salt. This is only used for the SELECT row info for the above password save
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'salt',
			'to_type' => 'user', 'to_fieldname' => ''
		);
*/	
		
		// User password verify class. Stores in usermeta for verifying password.
		$this->field_map[] = array(
			'to_type' => 'user', 'to_fieldname' => '_bbc_class',
			'default' => 'Xenforo'
		);
		
		// User name.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'username',
			'to_type' => 'user', 'to_fieldname' => 'user_login'
		);
				
		// User email.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'email',
			'to_type' => 'user', 'to_fieldname' => 'user_email'
		);
		
/*		
 * Table user_profile
		// User homepage.
		$this->field_map[] = array(
			'from_tablename' => 'user_profile', 'from_fieldname' => 'homepage',
			'to_type' => 'user', 'to_fieldname' => 'user_url'
		);
*/	
			
/*
 * Table user_identity
		// User aim.
		$this->field_map[] = array(
			'from_tablename' => 'user_identity', 'from_fieldname' => 'aim',
			'to_type' => 'user', 'to_fieldname' => 'aim'
		);
		
		// User yahoo.
		$this->field_map[] = array(
			'from_tablename' => 'user_identity', 'from_fieldname' => 'yahoo',
			'to_type' => 'user', 'to_fieldname' => 'yim'
		);
*/
		
		// User registered.
		$this->field_map[] = array(
			'from_tablename' => 'user', 'from_fieldname' => 'register_date',
			'to_type' => 'user', 'to_fieldname' => 'user_registered',
			'translate_method' => 'translate_datetime'
		);
		
		
	}

	/**
	 * This method allows us to indicates what is or is not converted for each
	 * converter.
	 */
	public function info()
	{
		return '';
	}

	/**
	 * This method is to save the salt and password together.  That
	 * way when we authenticate it we can get it out of the database
	 * as one value. Array values are auto sanitized by wordpress.
	 */
	public function translate_savepass( $field, $row )
	{
		$pass_array = array( 'hash' => $field, 'salt' => $row['salt'] );
		return $pass_array;
	}

	/**
	 * This method is to take the pass out of the database and compare
	 * to a pass the user has typed in.
	 */
	public function authenticate_pass( $password, $serialized_pass )
	{
		$pass_array = unserialize( $serialized_pass );
		switch( $pass_array['hashFunc'] )
		{
			case 'sha256':
				return ( $pass_array['hash'] == hash( 'sha256', hash( 'sha256', $password ) . $pass_array['salt'] ) );
			case 'sha1':
				return ( $pass_array['hash'] == sha1( sha1( $password ) . $pass_array['salt'] ) );
		}
	}
}
?>