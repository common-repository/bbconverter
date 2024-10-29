<?php
/*
Plugin Name: bbConverter
Plugin URI: http://www.bbconverter.com/
Description: bbConverter Class
Author: Adam Ellis
Version: 1.3
Author URI: http://www.bbconverter.com/
*/

/**
 * This is a function that is purposely written to look
 * like a "new" statement.  It is basically a dynamic loader
 * that will load in the platform conversion of your choice.
 *
 * @param string $platform Name of valid platform class.
 */
function newBBConverter( $platform )
{
	$found = false;
	if( $curdir = opendir( $GLOBALS['bbc']->dir . 'bbc-convert/' ) )
	{
		while( $file = readdir( $curdir ) )
		{
			if( $file != '.' && $file != '..' && stristr( $file, 'index' ) === FALSE )
			{
				$file = preg_replace( '/.php/', '', $file );
				if( $platform == $file )
				{
					$found = true;
				}
			}
		}
		closedir( $curdir );
	}
	if( $found )
	{
		require_once( $GLOBALS['bbc']->dir . 'bbc-convert/' . $platform . '.php' );
		eval( '$obj = new ' . $platform . '();' );
		return $obj;
	}
	else
	{
		return null;
	}
}

abstract class BBConverterBase
{
	/**
	 * @var array() This is the field mapping array to process.
	 */
	protected $field_map = array();

	/**
	 * @var object This is the connection to the wordpress datbase.
	 */
	protected $wpdb;

	/**
	 * @var object This is the connection to the other platforms database.
	 */
	protected $opdb;

	/**
	 * @var int This is the max rows to process at a time.
	 */
	public $max_rows;

	/**
	 * @var array() Map of topic to forum.  It is for optimization.
	 */
	private $map_topicid_to_forumid = array();

	/**
	 * @var array() Map of from old forum ids to new forum ids.  It is for optimization.
	 */
	private $map_forumid = array();

	/**
	 * @var array() Map of from old topic ids to new topic ids.  It is for optimization.
	 */
	private $map_topicid = array();

	/**
	 * @var array() Map of from old user ids to new user ids.  It is for optimization.
	 */
	private $map_userid = array();

	/**
	 * @var str This is the charset for your wp database.
	 */
	public $charset;
	
	/**
	 * @var boolean Sync table available.
	 */
	public $sync_table = false;	

	/**
	 * @var str Sync table name.
	 */
	public $sync_table_name;	
	
	/**
	 * This is the constructor and it connects to the platform databases.
	 */
	function __construct()
	{
		$this->setupGlobals();
	}

	private function setupGlobals()
	{
		global $wpdb;
		
		/** Get database connections *******************************************/
		
		$this->wpdb = $wpdb;
		$this->max_rows = $_POST['_bbc_rows'];
		$this->opdb = new wpdb( $_POST['_bbc_dbuser'], $_POST['_bbc_dbpass'], $_POST['_bbc_dbname'], $_POST['_bbc_dbserver'] );
		$this->opdb->prefix = $_POST['_bbc_dbprefix'];
		
		/**
		 * Error Reporting
		 */
		$this->wpdb->show_errors();
		$this->opdb->show_errors();
		
		/**
		 * Syncing
		 */
		$this->sync_table_name = $this->wpdb->prefix . "bbconverter_translator";
		if( $this->wpdb->get_var( "SHOW TABLES LIKE '" . $this->sync_table_name . "'" ) == $this->sync_table_name )
		{
			$this->sync_table = true;
		} 
		else
		{
			$this->sync_table = false;
		}	
			
		/**
		 * Charset
		 */
		if( empty( $this->wpdb->charset ) )
		{
 			$this->charset = "UTF8";
		}
		else
		{
			$this->charset = $this->wpdb->charset;
		}
		
		/**
		 * Default mapping.
		 */
		
		/** Forum Section ******************************************************/
		
		$this->field_map[] = array(
			'to_type' => 'forum',
			'to_fieldname' => 'post_status',
			'default' => 'publish'
		);
		$this->field_map[] = array(
			'to_type' => 'forum',
			'to_fieldname' => 'comment_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'forum',
			'to_fieldname' => 'ping_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'forum',
			'to_fieldname' => 'post_type',
			'default' => 'forum'
		);

		/** Topic Section ******************************************************/

		$this->field_map[] = array(
			'to_type' => 'topic',
			'to_fieldname' => 'post_status',
			'default' => 'publish'
		);
		$this->field_map[] = array(
			'to_type' => 'topic',
			'to_fieldname' => 'comment_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'topic',
			'to_fieldname' => 'ping_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'topic',
			'to_fieldname' => 'post_type',
			'default' => 'topic'
		);

		/** Post Section ******************************************************/

		$this->field_map[] = array(
			'to_type' => 'reply',
			'to_fieldname' => 'post_status',
			'default' => 'publish'
		);
		$this->field_map[] = array(
			'to_type' => 'reply',
			'to_fieldname' => 'comment_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'reply',
			'to_fieldname' => 'ping_status',
			'default' => 'closed'
		);
		$this->field_map[] = array(
			'to_type' => 'reply',
			'to_fieldname' => 'post_type',
			'default' => 'reply'
		);
		
		/** User Section ******************************************************/

		if( get_option( '_bbp_allow_global_access' ) )
		{
		 	$default_role = bbp_get_participant_role();
		}
		else
		{
		 	$default_role = get_option( 'default_role' );
		}	
		$this->field_map[] = array(
			'to_type' => 'user',
			'to_fieldname' => 'role',
			'default' => $default_role
		);			
	}

	/**
	 * Convert Forums
	 */
	public function convert_forums( $start = 1 )
	{
		return $this->convert_table( 'forum', $start );
	}

	/**
	 * Convert Topics / Threads
	 */
	public function convert_topics( $start = 1 )
	{
		return $this->convert_table( 'topic', $start );
	}

	/**
	 * Convert Posts
	 */
	public function convert_replies( $start = 1 )
	{
		return $this->convert_table( 'reply', $start );
	}

	/**
	 * Convert Users
	 */
	public function convert_users( $start = 1 )
	{
		return $this->convert_table( 'user', $start );
	}

	/**
	 * Convert Tags
	 */
	public function convert_tags( $start = 1 )
	{
		return $this->convert_table( 'tags', $start );
	}	
		
	/**
	 * Convert Table
	 * 
	 * @param string to type
	 * @param int Start row
	 */
	public function convert_table( $to_type, $start )
	{
		if( $this->wpdb->get_var( "SHOW TABLES LIKE '" . $this->sync_table_name . "'" ) == $this->sync_table_name )
		{
			$this->sync_table = true;
		} 
		else
		{
			$this->sync_table = false;
		}
		$has_insert = false;
		$from_tablename = '';
		$field_list = $from_tables = $tablefield_array = array();		
		switch( $to_type )
		{
			case 'user': 
				$tablename = $this->wpdb->users; 
				break;
			case 'tags':
				$tablename = '';
				break;
			default: 
				$tablename = $this->wpdb->posts;
		}
		if( $tablename )
		{
			$tablefield_array = $this->get_fields( $tablename );
		}
		foreach( $this->field_map as $item )
		{
			if( $item['to_type'] == $to_type && !is_null( $item['from_tablename'] ) )
			{
				if( $from_tablename != '' )
				{
					if( !in_array( $item['from_tablename'], $from_tables ) && in_array( $item['join_tablename'], $from_tables ) )
					{
						$from_tablename .= ' ' . $item['join_type'] . ' JOIN ' . $this->opdb->prefix . $item['from_tablename'] . ' AS ' . $item['from_tablename'] . ' ' . $item['join_expression'];
					}
				}
				else
				{
					$from_tablename = $item['from_tablename'] . ' AS ' . $item['from_tablename'];
				}	
				if( $item['from_expression'] )
				{
					if( stripos( $from_tablename, "WHERE" ) === FALSE )
					{
						$from_tablename .= ' ' . $item['from_expression'];
					}
					else 
					{
						$from_tablename .= ' ' . str_replace( "WHERE", "AND", $item['from_expression'] );
					}
				}	
				$from_tables[] = $item['from_tablename'];
				$field_list[] = 'convert(' . $item['from_tablename'] . '.' . $item['from_fieldname'] . ' USING "' . $this->charset . '") AS ' . $item['from_fieldname'];
			}
		}
		if( $from_tablename != '' )
		{
			$forum_array = $this->opdb->get_results( 'SELECT ' . implode( ',', $field_list ) . ' FROM ' . $this->opdb->prefix . $from_tablename . ' LIMIT ' . $start . ', ' . $this->max_rows, ARRAY_A );
			if( $forum_array )
			{
				foreach( $forum_array as $forum )
				{
					$insert_post = $insert_postmeta = $insert_data = array();
					foreach( $this->field_map as $row )
					{
						if( $row['to_type'] == $to_type && !is_null( $row['to_fieldname'] ) )
						{
							if( in_array( $row['to_fieldname'], $tablefield_array ) )
							{
								if( isset( $row['default'] ) ) //Allows us to set default fields.
								{
									$insert_post[$row['to_fieldname']] = $row['default'];
								}
								elseif( isset( $row['translate_method'] ) ) //Translates a field from the old forum.
								{
									if( $row['translate_method'] == 'translate_userid' && $_POST['_bbc_convert_users'] == 0 )
									{
										$insert_post[$row['to_fieldname']] = $forum[$row['from_fieldname']];
									}
									else
									{
										$insert_post[$row['to_fieldname']] = call_user_func_array( array( $this, $row['translate_method'] ), array( $forum[$row['from_fieldname']], $forum ) );
									}
								}
								else //Just maps the field from the old forum.
								{
									$insert_post[$row['to_fieldname']] = $forum[$row['from_fieldname']];
								}
							}
							elseif( $row['to_fieldname'] != '' )
							{
								if( isset( $row['default'] ) ) //Allows us to set default fields.
								{
									$insert_postmeta[$row['to_fieldname']] = $row['default'];
								}
								elseif( isset( $row['translate_method'] ) ) //Translates a field from the old forum.
								{
									if( $row['translate_method'] == 'translate_userid' && $_POST['_bbc_convert_users'] == 0 )
									{
										$insert_postmeta[$row['to_fieldname']] = $forum[$row['from_fieldname']];
									}
									else
									{
										$insert_postmeta[$row['to_fieldname']] = call_user_func_array( array( $this, $row['translate_method'] ), array( $forum[$row['from_fieldname']], $forum ) );
									}									
								}
								else //Just maps the field from the old forum.
								{
									$insert_postmeta[$row['to_fieldname']] = $forum[$row['from_fieldname']];
								}
							}
						}
					}
					if( count( $insert_post ) > 0 || ( $to_type == 'tags' && count( $insert_postmeta ) > 0 ) )
					{
						switch( $to_type )
						{
							case 'user': 
								if( username_exists( $insert_post['user_login'] ) ) 
								{
									$insert_post['user_login'] = 'imported_' . $insert_post['user_login'];
								} 
								if( email_exists( $insert_post['user_email'] ) ) 
								{
									$insert_post['user_email'] = 'imported_' . $insert_post['user_email'];
								} 	
								$post_id = wp_insert_user( $insert_post );
								if( is_numeric( $post_id ) )
								{
									foreach( $insert_postmeta as $key => $value )
									{
										//add_user_meta( $post_id, $key, $value, true );	
										if( substr( $key, -3 ) == "_id" && $this->sync_table === true ) 
										{
											$this->wpdb->insert( $this->sync_table_name, array( 'value_type' => 'user', 'value_id' => $post_id, 'meta_key' => $key, 'meta_value' => $value ) );
										}
										else 
										{
											add_user_meta( $post_id, $key, $value, true );	
										}
									}	
								}									
								break;
							case 'tags': 
								wp_set_object_terms( $insert_postmeta['objectid'], $insert_postmeta['name'], 'topic-tag', true );
								break;								
							default: 
								$post_id = wp_insert_post( $insert_post );
								if( is_numeric( $post_id ) )
								{		
									foreach( $insert_postmeta as $key => $value )
									{
										//add_post_meta( $post_id, $key, $value, true );
										if( substr( $key, -3 ) == "_id" && $this->sync_table === true ) 
										{
											$this->wpdb->insert( $this->sync_table_name, array( 'value_type' => 'post', 'value_id' => $post_id, 'meta_key' => $key, 'meta_value' => $value ) );
										}
										else
										{
											add_post_meta( $post_id, $key, $value, true );
										}
									}	
								}									
						}						
						$has_insert = true;
					}
				}
			}
		}
		return !$has_insert;
	}
	
	public function convert_forum_parents( $start )
	{
		$has_update = false;
		if( $this->sync_table )
		{
			$forum_array = $this->wpdb->get_results( 'SELECT value_id, meta_value FROM ' . $this->sync_table_name .
				' WHERE meta_key = "_bbc_parent_id" AND meta_value > 0 LIMIT ' . $start . ', ' . $this->max_rows );
		}
		else 
		{
			$forum_array = $this->wpdb->get_results( 'SELECT post_id AS value_id, meta_value FROM ' . $this->wpdb->postmeta .
				' WHERE meta_key = "_bbc_parent_id" AND meta_value > 0 LIMIT ' . $start . ', ' . $this->max_rows );
		}
		foreach( $forum_array as $row )
		{
			$parent_id = $this->translate_forumid( $row->meta_value );
			$this->wpdb->query( 'UPDATE ' . $this->wpdb->posts . ' SET post_parent = "' .
				$parent_id . '" WHERE ID = "' . $row->value_id . '" LIMIT 1' );
			$has_update = true;
		}
		return !$has_update;
	}

	/**
	 * This method deletes data from the wp database.
	 */
	public function clean( $start )
	{
		$has_delete = false;

		/** Delete bbconverter topics/forums/posts ****************************/
		
		if( $this->sync_table === true ) 
		{
			$bbconverter = $this->wpdb->get_results( 'SELECT value_id FROM ' . $this->sync_table_name . ' INNER JOIN ' . $this->wpdb->posts . ' ON(value_id = ID) WHERE meta_key LIKE "_bbc_%" AND value_type = "post" GROUP BY value_id ORDER BY value_id DESC LIMIT ' . $this->max_rows, ARRAY_A );
		}
		else
		{
			$bbconverter = $this->wpdb->get_results( 'SELECT post_id AS value_id FROM ' . $this->wpdb->postmeta . ' WHERE meta_key LIKE "_bbc_%" GROUP BY post_id ORDER BY post_id DESC LIMIT ' . $this->max_rows, ARRAY_A );
		}
		
		if( $bbconverter )
		{
			foreach( $bbconverter as $value )
			{
				wp_delete_post( $value['value_id'], true );
			}
			$has_delete = true;
		}
		
		/** Delete bbconverter users ******************************************/
		
		if( $this->sync_table === true ) 
		{
			$bbconverter = $this->wpdb->get_results( 'SELECT value_id FROM ' . $this->sync_table_name . ' INNER JOIN ' . $this->wpdb->users . ' ON(value_id = ID) WHERE meta_key = "_bbc_user_id" AND value_type = "user" LIMIT ' . $this->max_rows, ARRAY_A );
		}
		else 
		{
			$bbconverter = $this->wpdb->get_results( 'SELECT user_id AS value_id FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = "_bbc_user_id" LIMIT ' . $this->max_rows, ARRAY_A );
		}
		
		if( $bbconverter )
		{
			foreach( $bbconverter as $value )
			{			
				wp_delete_user( $value['value_id'] );
			}
			$has_delete = true;
		}
		
		return !$has_delete;
	}

	/**
	 * This method deletes passwords from the wp database.
	 * 
	 * @param int Start row
	 */
	public function clean_passwords( $start )
	{
		$has_delete = false;

		/** Delete bbconverter passwords **************************************/

		$bbconverter = $this->wpdb->get_results( 'SELECT user_id, meta_value FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = "_bbc_password" LIMIT ' . $start . ', ' . $this->max_rows, ARRAY_A );
		if( $bbconverter )
		{
			foreach( $bbconverter as $value )
			{
				if( is_serialized( $value['meta_value'] ) )
				{
					$this->wpdb->query( 'UPDATE ' . $this->wpdb->users . ' ' .
						'SET user_pass = "" ' .
						'WHERE ID = "' . $value['user_id'] . '"' );	
				}	
				else 
				{
					$this->wpdb->query( 'UPDATE ' . $this->wpdb->users . ' ' .
						'SET user_pass = "' . $value['meta_value'] . '" ' .
						'WHERE ID = "' . $value['user_id'] . '"' );	
					
					$this->wpdb->query( 'DELETE FROM ' . $this->wpdb->usermeta . ' WHERE meta_key LIKE "_bbc_password" AND user_id = "' . $value['user_id'] . '"' );
				}		
			}
			$has_delete = true;
		}
		return !$has_delete;
	}
	
	/**
	 * This method implements the authentication for the different forums.
	 *
	 * @param string Unencoded password.
	 */
	abstract protected function authenticate_pass( $password, $hash );
		
	/**
	 * Info
	 */
	abstract protected function info();

	/**
	 * This method grabs appropriet fields from the table specified
	 * 
	 * @param string The table name to grab fields from
	 */
	private function get_fields( $tablename )
	{
		$rval = array();
		$field_array = $this->wpdb->get_results( 'DESCRIBE ' . $tablename, ARRAY_A );
		foreach( $field_array as $field )
		{
			$rval[] = $field['Field'];
		}
		if( $tablename == $this->wpdb->users )
		{
			$rval[] = 'role';
			$rval[] = 'yim';
			$rval[] = 'aim';
			$rval[] = 'jabber';
		}
		return $rval;
	}

	public function translate_pass( $username, $password )
	{
		$user = $this->wpdb->get_row( 'SELECT * FROM ' . $this->wpdb->users . ' WHERE user_login = "' . $username . '" AND user_pass = "" LIMIT 1' );
		if( $user )
		{
			$usermeta = $this->wpdb->get_row( 'SELECT * FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = "_bbc_password" AND user_id = "' . $user->ID . '" LIMIT 1' );
			if( $usermeta )
			{
				if( $this->authenticate_pass( $password, $usermeta->meta_value ) )
				{
					$this->wpdb->query( 'UPDATE ' . $this->wpdb->users . ' ' .
						'SET user_pass = "' . wp_hash_password( $password ) . '" ' .
						'WHERE ID = "' . $user->ID . '"' );
					$this->wpdb->query( 'DELETE FROM ' . $this->wpdb->usermeta . ' WHERE meta_key LIKE "%_bbc_%" AND user_id = "' . $user->ID . '"' );
				}
			}
		}
	}

	private function translate_forumid( $field )
	{
		if( !isset( $this->map_forumid[$field] ) ) //This is a mini cache system to reduce database calls.
		{
			if( $this->sync_table )
			{
				$row = $this->wpdb->get_row( 'SELECT value_id, meta_value FROM ' . $this->sync_table_name .
					' WHERE meta_key = "_bbc_forum_id" AND meta_value = "' . $field . 
					'" LIMIT 1' );
			}
			else 
			{
				$row = $this->wpdb->get_row( 'SELECT post_id AS value_id FROM ' . $this->wpdb->postmeta .
					' WHERE meta_key = "_bbc_forum_id" AND meta_value = "' . $field .
					'" LIMIT 1' );
			}			
			if( !is_null( $row ) )
			{
				$this->map_forumid[$field] = $row->value_id;
			}
			else
			{
				$this->map_forumid[$field] = 0;
			}
		}
		return $this->map_forumid[$field];
	}

	private function translate_topicid( $field )
	{
		if( !isset( $this->map_topicid[$field] ) ) //This is a mini cache system to reduce database calls.
		{
			if( $this->sync_table )
			{
				$row = $this->wpdb->get_row( 'SELECT value_id, meta_value FROM ' . $this->sync_table_name .
					' WHERE meta_key = "_bbc_topic_id" AND meta_value = "' . $field . 
					'" LIMIT 1' );
			}
			else 
			{
				$row = $this->wpdb->get_row( 'SELECT post_id AS value_id FROM ' . $this->wpdb->postmeta .
					' WHERE meta_key = "_bbc_topic_id" AND meta_value = "' . $field .
					'" LIMIT 1' );
			}					
			if( !is_null( $row ) )
			{
				$this->map_topicid[$field] = $row->value_id;
			}
			else
			{
				$this->map_topicid[$field] = 0;
			}
		}
		return $this->map_topicid[$field];
	}

	private function translate_userid( $field )
	{
		if( !isset( $this->map_userid[$field] ) ) //This is a mini cache system to reduce database calls.
		{
			if( $this->sync_table )
			{
				$row = $this->wpdb->get_row( 'SELECT value_id, meta_value FROM ' . $this->sync_table_name .
					' WHERE meta_key = "_bbc_user_id" AND meta_value = "' . $field . 
					'" LIMIT 1' );
			}
			else 
			{
				$row = $this->wpdb->get_row( 'SELECT user_id AS value_id FROM ' . $this->wpdb->usermeta .
					' WHERE meta_key = "_bbc_user_id" AND meta_value = "' . $field .
					'" LIMIT 1' );
			}				
			if( !is_null( $row ) )
			{
				$this->map_userid[$field] = $row->value_id;
			}
			else
			{
				if( $_POST['_bbc_convert_users'] == 1 )
				{
					$this->map_userid[$field] = 0;
				}
				else 
				{
					$this->map_userid[$field] = $field;
				}
			}
		}
		return $this->map_userid[$field];
	}

	private function translate_topicid_to_forumid( $field )
	{
		$topicid = $this->translate_topicid( $field );
		if( $topicid == 0 )
		{
			$this->map_topicid_to_forumid[$topicid] = 0;
		}
		else if( !isset( $this->map_topicid_to_forumid[$topicid] ) ) //This is a mini cache system to reduce database calls.
		{
			$row = $this->wpdb->get_row(
				'SELECT post_parent FROM ' .
				$this->wpdb->posts . ' WHERE ID = "' . $topicid . '" LIMIT 1' );
			if( !is_null( $row ) )
			{
				$this->map_topicid_to_forumid[$topicid] = $row->post_parent;
			}
			else
			{
				$this->map_topicid_to_forumid[$topicid] = 0;
			}
		}
		return $this->map_topicid_to_forumid[$topicid];
	}

	protected function translate_title( $field )
	{
		return sanitize_title_with_dashes( $field );
	}
		
	protected function translate_negative( $field )
	{
		if( $field < 0 )
		{
			return 0;
		}
		else
		{
			return $field;
		}
	}

	protected function translate_html( $field )
	{
		require_once( $GLOBALS['bbc']->dir . 'bbc-includes/bbc-parser.php' );
		$bbcode = BBCode::getInstance();
		return $bbcode->Parse( $field );
	}

	protected function translate_null( $field )
	{
		if( is_null( $field ) )
		{
			return '';
		}
		else
		{
			return $field;
		}
	}

	protected function translate_datetime( $field )
	{
		if( is_numeric( $field ) )
		{
			return date( 'Y-m-d H:i:s', $field );
		}
		else
		{
			return date( 'Y-m-d H:i:s', strtotime( $field ) );
		}
	}
}
?>