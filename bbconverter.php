<?php
/*
Plugin Name: bbConverter
Plugin URI: http://www.bbconverter.com/
Description: bbConverter is a system that allows you to convert from many different forum software into bbpress.
Author: Adam Ellis
Version: 1.3
Author URI: http://www.bbconverter.com/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'bbConverter' ) ) :
/**
 * Main bbConverter Class
 */
class bbConverter 
{
	/** Version ***************************************************************/

	/**
	 * @public string bbConverter version
	 */
	public $version = '1.2';
	
	/** Paths *****************************************************************/

	/**
	 * @public string Basename of the bbConverter directory
	 */
	public $basename = '';

	/**
	 * @public string Absolute path to the bbConverter directory
	 */
	public $dir = '';

	/** URLs ******************************************************************/

	/**
	 * @public string URL to the bbConverter directory
	 */
	public $url = '';
		
	/** Errors ****************************************************************/

	/**
	 * @public WP_Error Used to log and display errors
	 */
	public $errors = array();	
	
	/** Functions *************************************************************/

	/**
	 * The main bbConverter loader (PHP4 compat)
	 * @uses bbConverter::__construct() Setup the globals needed
	 */
	public function bbConverter() 
	{
		$this->__construct();
	}

	/**
	 * The main bbConverter loader
	 * @uses bbConverter::setup_globals() Setup the globals needed
	 * @uses bbConverter::includes() Include the required files
	 * @uses bbConverter::setup_actions() Setup the actions
	 */
	public function __construct() 
	{
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Component global variables
	 * @access private
	 *
	 * @uses plugin_dir_path() To generate bbConverter path
	 * @uses plugin_dir_url() To generate bbConverter url
	 */
	private function setup_globals() 
	{
		/** Paths *************************************************************/

		// bbConverter root directory
		$this->file    	= __FILE__;
		$this->basename = plugin_basename( $this->file );
		$this->dir 		= plugin_dir_path( $this->file );
		$this->url 		= plugin_dir_url ( $this->file );

		// Errors
		$this->errors	= new WP_Error();
	}
	
	/**
	 * Include required files
	 * @access private
	 */
	private function includes() 
	{
		/** Core **************************************************************/
		
		require_once( $this->dir . 'bbc-includes/bbc-settings.php' ); // Settings Functions 
		require_once( $this->dir . 'bbc-includes/bbc-converter-class.php' ); // Converter Functions 
	}
		
	/**
	 * Setup the default actions
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() 
	{
		/** General Actions ***************************************************/
		
		// Attach the bbConverter admin settings action to the WordPress admin init action.
		add_action( 'admin_init', array( $this, 'register_admin_settings' ) );
		
		// Attach the bbConverter admin menu action to the WordPress admin menu action.
		add_action( 'admin_menu', array( $this, 'menu' ) );	

		// Attach to the login process to aid in converting passwords to wordpress.
		add_action( 'login_form_login', array( $this, 'convert_pass' ) );	

		// Attach to the admin head with our ajax requests cycle and css
		add_action( 'admin_head', array( $this, 'action_javascript' ) );
		
		// Attach to the admin ajax request to process cycles
		add_action( 'wp_ajax_bbconverter_process', array( $this, 'bbconverter_process_callback' ) );
		
		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );	
	}	

	/**
	 * Admin Menu 
	 */
	public function menu()
	{
		add_options_page( __( 'Converter', 'bbconverter' ), __( 'Converter', 'bbconverter' ), 'manage_options', 'bbconverter', 'bbc_admin_settings' );
	}
	
	/**
	 * Add Settings link to plugins area
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public function add_settings_link( $links, $file ) 
	{
		global $bbc;

		if ( plugin_basename( $bbc->file ) == $file ) 
		{
			$settings_link = '<a href="' . add_query_arg( array( 'page' => 'bbconverter' ), admin_url( 'options-general.php' ) ) . '">' . __( 'Settings', 'bbconverter' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
	
	/**
	 * Register the settings
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 */
	public function register_admin_settings() 
	{

		/** Main Section ******************************************************/

		// Add the main section
		add_settings_section( 'bbc_main', __( 'Main Settings', 'bbconverter' ), 'bbc_admin_setting_callback_main_section', 'bbconverter' );

		// System Select
		add_settings_field( '_bbc_platform', __( 'Select Platform', 'bbconverter' ), 'bbc_admin_setting_callback_platform', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_platform', 'sanitize_title' );

		// Database Server
		add_settings_field( '_bbc_dbserver', __( 'Database Server', 'bbconverter' ), 'bbc_admin_setting_callback_dbserver', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbserver', 'sanitize_title' );

		// Database Server Port
		add_settings_field( '_bbc_dbport', __( 'Database Port', 'bbconverter' ), 'bbc_admin_setting_callback_dbport', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbport', 'sanitize_title' );
	 	
		// Database User
		add_settings_field( '_bbc_dbuser', __( 'Database User', 'bbconverter' ), 'bbc_admin_setting_callback_dbuser', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbuser', 'sanitize_title' );
	 		 	
		// Database Pass
		add_settings_field( '_bbc_dbpass', __( 'Database Pass', 'bbconverter' ), 'bbc_admin_setting_callback_dbpass', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbpass', 'sanitize_title' );
	 		
		// Database Name
		add_settings_field( '_bbc_dbname', __( 'Database Name', 'bbconverter' ), 'bbc_admin_setting_callback_dbname', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbname', 'sanitize_title' );	
		
		// Database Prefix
		add_settings_field( '_bbc_dbprefix', __( 'Table Prefix', 'bbconverter' ), 'bbc_admin_setting_callback_dbprefix', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_dbprefix', 'sanitize_title' );	
		
	 	// Rows Limit
		add_settings_field( '_bbc_rows', __( 'Rows Limit', 'bbconverter' ), 'bbc_admin_setting_callback_rows', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_rows', 'intval' );		

	 	// Delay Time
		add_settings_field( '_bbc_delay_time', __( 'Delay Time', 'bbconverter' ), 'bbc_admin_setting_callback_delay_time', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_delay_time', 'intval' );	
	 		 	
	 	// Clean
		add_settings_field( '_bbc_clean', __( 'Clean', 'bbconverter' ), 'bbc_admin_setting_callback_clean', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_clean', 'intval' );		 

	 	// Restart
		add_settings_field( '_bbc_restart', __( 'Restart', 'bbconverter' ), 'bbc_admin_setting_callback_restart', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_restart', 'intval' );	

	 	// Convert Users ?
		add_settings_field( '_bbc_convert_users', __( 'Convert Users?', 'bbconverter' ), 'bbc_admin_setting_callback_convert_users', 'bbconverter', 'bbc_main' );
	 	register_setting( 'bbconverter', '_bbc_convert_users', 'intval' );	
	}
	
	/**
	 * Converter Action
	 */
	public function action_javascript() 
	{
	?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		
			div.bbc-updated, div.bbc-warning, div.bbc-help, div.bbc-note
			{
			    border-radius: 3px 3px 3px 3px;
			    border-style: solid;
			    border-width: 1px;
			    margin: 5px 15px 2px;
			    padding: 10px 10px 10px 30px;
			}
			
			div.bbc-updated
			{
				height:150px;
				overflow:auto;		
				display:none;
			    background-color: #FFFFE0;
			    border-color: #E6DB55;		    
			}
			
			div.bbc-updated p 
			{
			    margin: 0.5em 0;
			    padding: 2px;
			}	
				
			div.bbc-warning
			{
			    background-color: #F0C1CB;
			    border-color: #92394D;
			}
							
			div.bbc-help
			{
			    background-color: #CCFFCC;
			    border-color: #008800;
			}
	
			div.bbc-note
			{
			    background-color: #FFFFE0;
			    border-color: #E6DB55;		    
			}
								
			#bbc-stop
			{	
				display:none;	    
			}	
			
			#bbc-progress
			{	
				display:none;	    
			}	
						
		/*]]>*/
		</style>
		
		<script language="javascript">
	
			var bbconverter_is_running = false;
			var bbconverter_run_timer;
			var bbconverter_delay_time = 0;
			
			function bbconverter_grab_data()
			{
				var values = {};
				jQuery.each(jQuery('#bbc-settings').serializeArray(), function(i, field) {
				    values[field.name] = field.value;
				});
				if( values['_bbc_restart'] )
				{
					jQuery('#_bbc_restart').removeAttr("checked");
				}
				if( values['_bbc_delay_time'] )
				{
					bbconverter_delay_time = values['_bbc_delay_time'] * 1000;
				}
				values['action'] = 'bbconverter_process';
				return values;
			}
			
			function bbconverter_start()
			{
				if( !bbconverter_is_running )
				{
					bbconverter_is_running = true;
					jQuery('#bbc-start').hide();
					jQuery('#bbc-stop').show();
					jQuery('#bbc-progress').show();
					bbconverter_log("Starting Conversion...");
					jQuery.post(ajaxurl, bbconverter_grab_data(), function(response) {
						var response_length = response.length - 1;
						response = response.substring(0,response_length);
						bbconverter_success(response);
					});
				}
			}
			
			function bbconverter_run()
			{
				jQuery.post(ajaxurl, bbconverter_grab_data(), function(response) {
					var response_length = response.length - 1;
					response = response.substring(0,response_length);
					bbconverter_success(response);
				});
			}
					
			function bbconverter_stop()
			{
				bbconverter_is_running = false;
			}
			
			function bbconverter_success(response)
			{
				bbconverter_log(response);
				if(response == 'Conversion Complete' || response.indexOf('error') > -1)
				{
					bbconverter_log('<b>Please don\'t forget to update your counters <a href="tools.php?page=bbp-recount">HERE</a></b>');
					jQuery('#bbc-start').show();
					jQuery('#bbc-stop').hide();
					jQuery('#bbc-progress').hide();
					clearTimeout( bbconverter_run_timer );
				}
				else if( bbconverter_is_running ) //keep going
				{
					jQuery('#bbc-progress').show();
					clearTimeout( bbconverter_run_timer );
					bbconverter_run_timer = setTimeout( 'bbconverter_run()', bbconverter_delay_time );
				}
				else
				{
					jQuery('#bbc-start').show();
					jQuery('#bbc-stop').hide();
					jQuery('#bbc-progress').hide();
					clearTimeout( bbconverter_run_timer );
				}
			}
			
			function bbconverter_log(text)
			{
				if(jQuery('#bbc-message').css('display') == 'none') 
				{
					jQuery('#bbc-message').show();
				}
				if( text )
				{
					jQuery('#bbc-message').prepend('<p><strong>' + text + '</strong></p>');
				}
			}
			
		</script>
		
	<?php
	}	
	
	public function bbconverter_process_callback() 
	{
		global $wpdb; // this is how you get access to the database
		
		if( !ini_get('safe_mode') )
		{
			set_time_limit( 0 );
			ini_set( 'memory_limit', '256M' );
			ignore_user_abort( true );
		} 
		
		//Save step and count so that it can be restarted.
		if( !get_option('_bbc_step') || $_POST['_bbc_restart'] == 1 )
		{
			update_option( '_bbc_step', 1 );
			update_option( '_bbc_start', 0 );
		}
		$bbconverter_step = get_option('_bbc_step');
		$min = $bbconverter_start = get_option('_bbc_start');
		$max = $min + $_POST['_bbc_rows'] - 1;
		
		//Include the appropriate converter.
		$converter = newBBConverter( $_POST['_bbc_platform'] );
		
		if( $bbconverter_step == 1 )
		{
			// STEP 1. Clean all tables.
			if( $_POST['_bbc_clean'] == 1 )
			{
				$is_done = $converter->clean( $bbconverter_start );
				if( $is_done )
				{
					update_option( '_bbc_step', $bbconverter_step + 1 );
					update_option( '_bbc_start', 0 );
					$this->sync_table();
					
					if( !$bbconverter_start )
					{
						echo 'No data to clean';	
					}						
				}
				else
				{
					update_option( '_bbc_start', $max + 1 );
					echo 'Delete previous converted data (' . $min . ' - ' . $max . ')';
				}
			}
			else 
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				echo 'Not Cleaning Data';	
			}			
		}
		elseif( $bbconverter_step == 2 )
		{
			if( $_POST['_bbc_convert_users'] == 1 )
			{
				// STEP 2. Convert users.
				$is_done = $converter->convert_users( $bbconverter_start );
				if( $is_done )
				{
					update_option( '_bbc_step', $bbconverter_step + 1 );
					update_option( '_bbc_start', 0 );
					if( !$bbconverter_start )
					{
						echo 'No users to convert';		
					}						
				}
				else
				{
					update_option( '_bbc_start', $max + 1 );
					echo 'Convert users (' . $min . ' - ' . $max . ')';
				}
			}
			else 
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				echo 'Not Converting users Selected';		
			}
		}
		elseif( $bbconverter_step == 3 )
		{
			if( $_POST['_bbc_convert_users'] == 1 )
			{	
				// STEP 3. Clean passwords.
				$is_done = $converter->clean_passwords( $bbconverter_start );
				if( $is_done )
				{				
					update_option( '_bbc_step', $bbconverter_step + 1 );
					update_option( '_bbc_start', 0 );
					if( !$bbconverter_start )
					{
						echo 'No passwords to clear';		
					}						
				}
				else
				{
					update_option( '_bbc_start', $max + 1 );
					echo 'Delete users wordpress default passwords (' . $min . ' - ' . $max . ')';
				}
			}
			else 
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				echo 'Not clearing default passwords';		
			}	
		}
		elseif( $bbconverter_step == 4 )
		{
			// STEP 4. Convert forums.
			$is_done = $converter->convert_forums( $bbconverter_start );
			if( $is_done )
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				if( !$bbconverter_start )
				{
					echo 'No forums to convert';		
				}					
			}
			else
			{
				update_option( '_bbc_start', $max + 1 );
				echo 'Convert forums (' . $min . ' - ' . $max . ')';
			}
		}
		elseif( $bbconverter_step == 5 )
		{
			// STEP 5. Convert forum parents.
			$is_done = $converter->convert_forum_parents( $bbconverter_start );
			if( $is_done )
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				if( !$bbconverter_start )
				{
					echo 'No forums to convert parents';		
				}					
			}
			else
			{
				update_option( '_bbc_start', $max + 1 );
				echo 'Convert forum parents (' . $min . ' - ' . $max . ')';
			}
		}
		elseif( $bbconverter_step == 6 )
		{
			// STEP 6. Convert topics.
			$is_done = $converter->convert_topics( $bbconverter_start );
			if( $is_done )
			{
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				if( !$bbconverter_start )
				{
					echo 'No topics to convert';		
				}					
			}
			else
			{
				update_option( '_bbc_start', $max + 1 );
				echo 'Convert topics (' . $min . ' - ' . $max . ')';
			}
		}
		elseif( $bbconverter_step == 7 )
		{
			// STEP 7. Convert tags.
			$is_done = $converter->convert_tags( $bbconverter_start );
			if( $is_done )
			{ 
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				if( !$bbconverter_start )
				{
					echo 'No tags to convert';		
				}					
			}
			else
			{
				update_option( '_bbc_start', $max + 1 );
				echo 'Convert tags (' . $min . ' - ' . $max . ')';
			}
		}			
		elseif( $bbconverter_step == 8 )
		{
			// STEP 8. Convert posts.
			$is_done = $converter->convert_replies( $bbconverter_start );
			if( $is_done )
			{ 
				update_option( '_bbc_step', $bbconverter_step + 1 );
				update_option( '_bbc_start', 0 );
				if( !$bbconverter_start )
				{
					echo 'No posts to convert';		
				}					
			}
			else
			{
				update_option( '_bbc_start', $max + 1 );
				echo 'Convert posts (' . $min . ' - ' . $max . ')';
			}
		}	
		else
		{
			delete_option( '_bbc_step' );
			delete_option( '_bbc_start' );	
			echo 'Conversion Complete';
		}
	}	
	
	/**
	 * Convert passwords from previous forum to wordpress.
	 */
	public function convert_pass()
	{
		global $wpdb;
		
		$username = $_POST['log'];
		if( $username != '' )
		{
			$row = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->users .
				' INNER JOIN ' . $wpdb->usermeta . ' ON user_id = ID ' .
				' WHERE meta_key = "_bbc_class" AND user_login = "' . $username . '" LIMIT 1' );
			if( $row )
			{
				$converter = newBBConverter( $row->meta_value );
				$converter->translate_pass( $username, $_POST['pwd'] );
			}
		}
	}
	
	/**
	 * Create Tables for fast syncing
	 */
    public function sync_table( $drop = false )
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "bbconverter_translator";
        if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) 
		{
			 $wpdb->query("DROP TABLE $table_name");
		}
    	if( !$drop ) 
		{
			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	
			if( !empty( $wpdb->charset ) )
			{
	 			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if( !empty( $wpdb->collate ) )
			{
	       		$charset_collate .= " COLLATE $wpdb->collate";
			}
			
	    	//------ Translator -----------------------------------------------
	      	$sql = "CREATE TABLE " . $table_name . " (
	        	meta_id mediumint(8) unsigned not null auto_increment,
	        	value_type varchar(25) null,
	     		value_id bigint(20) unsigned not null default '0',
	     		meta_key varchar(25) null,
	        	meta_value varchar(25) null,
	         	PRIMARY KEY  (meta_id),
	            KEY value_id (value_id),
	            KEY meta_join (meta_key, meta_value)
	       	) $charset_collate;";
	   		dbDelta($sql);
		}
    }	
}

// Instantiate
$GLOBALS['bbc'] = new bbConverter();

endif; // class_exists check	
?>
