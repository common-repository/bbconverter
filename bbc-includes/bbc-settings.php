<?php
/*
Plugin Name: bbConverter
Plugin URI: http://www.bbconverter.com/
Description: bbConverter Admin Settings
Author: Adam Ellis
Version: 1.3
Author URI: http://www.bbconverter.com/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Start Main Section ********************************************************/

/**
 * Main settings section description for the settings page
 */
function bbc_admin_setting_callback_main_section() 
{
?>

	<p><?php _e( 'Main settings for the bbConverter plugin', 'bbconverter' ); ?></p>

<?php
}

/**
 * Edit Platform setting field
 */
function bbc_admin_setting_callback_platform() 
{	
	if( $curdir = opendir( $GLOBALS['bbc']->dir . 'bbc-convert/' ) )
	{
		while( $file = readdir( $curdir ) )
		{
			if( $file != '.' && $file != '..' && stristr( $file, 'index' ) === FALSE )
			{
				$file = preg_replace( '/.php/', '', $file );
				$platform_options .= '<option value="' . $file . '">' . $file . '</option>';
			}
		} 
		closedir($curdir);
	}	
?>
	
	<select name="_bbc_platform" id="_bbc_platform" /><?php echo $platform_options ?></select>
	<label for="_bbc_platform"><?php _e( 'BB Platform', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Database Server setting field
 */
function bbc_admin_setting_callback_dbserver() 
{
?>
	
	<input name="_bbc_dbserver" type="text" id="_bbc_dbserver" value="<?php if( get_option('_bbc_dbserver') ){ echo get_option('_bbc_dbserver'); }else{ echo 'localhost'; } ?>" class="medium-text" />
	<label for="_bbc_dbserver"><?php _e( 'Database Server IP', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Database Server Port setting field
 */
function bbc_admin_setting_callback_dbport() 
{
?>
	
	<input name="_bbc_dbport" type="text" id="_bbc_dbport" value="<?php if( get_option('_bbc_dbport') ){ echo get_option('_bbc_dbport'); }else{ echo '3306'; } ?>" class="small-text" />
	<label for="_bbc_dbport"><?php _e( 'Database Server Port', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Database User setting field
 */
function bbc_admin_setting_callback_dbuser() 
{
?>
	
	<input name="_bbc_dbuser" type="text" id="_bbc_dbuser" value="<?php echo get_option('_bbc_dbuser'); ?>" class="medium-text" />
	<label for="_bbc_dbuser"><?php _e( 'Database Server User', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Database Pass setting field
 */
function bbc_admin_setting_callback_dbpass() 
{
?>
	
	<input name="_bbc_dbpass" type="text" id="_bbc_dbpass" value="<?php echo get_option('_bbc_dbpass'); ?>" class="medium-text" />
	<label for="_bbc_dbpass"><?php _e( 'Database Server Pass', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Database Name setting field
 */
function bbc_admin_setting_callback_dbname() 
{
?>
	
	<input name="_bbc_dbname" type="text" id="_bbc_dbname" value="<?php echo get_option('_bbc_dbname'); ?>" class="medium-text" />
	<label for="_bbc_dbname"><?php _e( 'Database Name', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Table Prefix setting field
 */
function bbc_admin_setting_callback_dbprefix() 
{
?>
	
	<input name="_bbc_dbprefix" type="text" id="_bbc_dbprefix" value="<?php echo get_option('_bbc_dbprefix'); ?>" class="medium-text" />
	<label for="_bbc_dbprefix"><?php _e( 'Table Prefix', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Rows Limit setting field
 */
function bbc_admin_setting_callback_rows() 
{
?>
	
	<input name="_bbc_rows" type="text" id="_bbc_rows" value="<?php if( get_option('_bbc_rows') ){ echo get_option('_bbc_rows'); }else{ echo '100'; } ?>" class="small-text" />
	<label for="_bbc_rows"><?php _e( 'How many rows to process at a time (You may adjust this at any time by changing the value without pressing anything)', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Delay Time setting field
 */
function bbc_admin_setting_callback_delay_time() 
{
?>
	
	<input name="_bbc_delay_time" type="text" id="_bbc_delay_time" value="<?php if( get_option('_bbc_delay_time') ){ echo get_option('_bbc_delay_time'); }else{ echo '1'; } ?>" class="small-text" />
	<label for="_bbc_delay_time"><?php _e( 'Time delay between batch converting in seconds (You may adjust this at any time by changing the value without pressing anything)', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Clean setting field
 */
function bbc_admin_setting_callback_clean() 
{
?>
	<input id="_bbc_clean" name="_bbc_clean" type="checkbox" id="_bbc_clean" value="1" <?php checked( get_option( '_bbc_clean', false ) ); ?> />
	<label for="_bbc_clean"><?php _e( 'Clean out bbconverters converted data to start a fresh conversion', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Restart setting field
 */
function bbc_admin_setting_callback_restart() 
{
?>
	<input id="_bbc_restart" name="_bbc_restart" type="checkbox" id="_bbc_restart" value="1" <?php checked( get_option( '_bbc_restart', false ) ); ?> />
	<label for="_bbc_restart"><?php _e( 'Restart the conversion process', 'bbconverter' ); ?></label>

<?php
}

/**
 * Edit Convert Users setting field
 */
function bbc_admin_setting_callback_convert_users() 
{
?>
	<input id="_bbc_convert_users" name="_bbc_convert_users" type="checkbox" id="_bbc_convert_users" value="1" <?php if( get_option('_bbc_convert_users') ){ checked( get_option( '_bbc_convert_users', false ) ); }else{ checked( 1 ); } ?> />
	<label for="_bbc_convert_users"><?php _e( 'Convert users or not', 'bbconverter' ); ?></label>

<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @uses screen_icon() To display the screen icon
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function bbc_admin_settings() 
{	
?>

	<div class="wrap">

		<?php screen_icon(); ?>

		<h2><?php _e( 'bbConverter Settings', 'bbconverter' ) ?></h2>

		<div class="bbc-warning">
			<h3>Warning!</h3>
			Remember to always backup your database before proceeding. It is important to note before continuing that converting is not an exact science. Many factors can affect the final outcome and small oddities are to be expected following the conversion. Our technicians will be happy to assist you in resolving these issues, but you should be aware that you may need to allow for extra downtime following the conversion while these issues are addressed. 
		</div>
			
		<form action="#" method="post" id="bbc-settings">

			<?php settings_fields( 'bbconverter' ); ?>

			<?php do_settings_sections( 'bbconverter' ); ?>

			<p class="submit">
				<input type="button" name="submit" class="button-primary" id="bbc-start" value="<?php _e( 'Start', 'bbconverter' ); ?>" onclick="bbconverter_start()" />
				<input type="button" name="submit" class="button-primary" id="bbc-stop" value="<?php _e( 'Stop', 'bbconverter' ); ?>" onclick="bbconverter_stop()" />
				<img id="bbc-progress" src="<?php echo $GLOBALS['bbc']->url . 'spinner.gif'; ?>">
			</p>
			
			<div class="bbc-updated" id="bbc-message">
			
			</div>
			
			<div class="bbc-note">
				<h3>Note:</h3>
				For NON bbpress 1x conversions:<br/>
				It is not possible to convert passwords during the conversion process. You must leave the conversion plugin active to convert passwords as people log in. It will validate using their old password and convert to wordpress encryption.
			</div>
			
			<div class="bbc-help">
				<h3>Need Assistance?</h3>
				If you need assistance with the converting, or need other plugins converted, we have a conversion service available. To find out more, <a href="http://www.bbconverter.com" target="_blank">see this page</a>.
			</div>
			
		</form>
		
	</div>

<?php

}
?>
