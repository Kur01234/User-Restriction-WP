<?php
/*
 * Plugin Name: Custom User Restriction
 * Author: Kuro
 * Author URI: https://github.com/Kur01234
 * Version: 1.2
 * Description: Restrict acces to pages for All User Groups that are not selected.
 */

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );
add_action("template_redirect", "on_site_open");

$setting = get_option('user_restriction_options');
$fields_value = $setting ['user_restriction_field_amount'] - 1;


function add_action_links ( $actions ) {
$mylinks = array(
'<a href="admin.php?page=user_restriction">Einstellungen</a>'
);
$actions = array_merge( $mylinks, $actions );
return $actions;
}

add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {

	include_once 'updater.php';

	define( 'WP_GITHUB_FORCE_UPDATE', true );
	if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
		$config = array(
			'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
			'proper_folder_name' => 'User-Restriction-WP', // this is the name of the folder your plugin lives in
			'api_url' => 'https://api.github.com/repos/Kur01234/User-Restriction-WP', // the GitHub API url of your GitHub repo
			'raw_url' => 'https://raw.github.com/Kur01234/User-Restriction-WP/main', // the GitHub raw url of your GitHub repo
			'github_url' => 'https://github.com/Kur01234/User-Restriction-WP', // the GitHub url of your GitHub repo
			'zip_url' => 'https://github.com/Kur01234/User-Restriction-WP/zipball/main', // the zip url of the GitHub repo
			'sslverify' => true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
			'requires' => '3.0', // which version of WordPress does your plugin require?
			'tested' => '3.3', // which version of WordPress is your plugin tested up to?
			'readme' => 'README.md', // which file to use as the readme for the version number
			'access_token' => '', // Access private repositories by authorizing under Plugins > GitHub Updates when this example plugin is installed
		);
		new WP_GitHub_Updater($config);
	}
}

function on_site_open() {
	$user = wp_get_current_user();
	$setting = get_option('user_restriction_options');
  	global $fields_value;
  	for($i = $fields_value; $i >= 0; $i--) {
	$role_value = $setting ['user_restriction_field_role' . (String)$i];
  	$text_value = $setting ['user_restriction_field_site' . (String)$i];
  	$redirect_url = $setting ['user_restriction_field_redirect'];
  	$actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  	if ($actual_link == $text_value) {
      if ( in_array( $role_value, (array) $user->roles ) ) {
          ?><script>console.log("<?php echo "$role_value" ?>, <?php echo "$text_value" ?>");</script><?php
      }else{
		?><script>window.location.href = "<?php echo "$redirect_url" ?>";</script><?php
      } 
    }
   }
}

/**
 * custom option and settings
 */
function user_restriction_settings_init() {
	// Register a new setting for "user_restriction" page.
	register_setting( 'user_restriction', 'user_restriction_options' );

	// Register a new section in the "user_restriction" page.
    add_settings_section(
          'user_restriction_section_developers',
          __( 'Add restricted Site.', 'user_restriction' ), 'user_restriction_section_developers_callback',
          'user_restriction'
    );
  	global $fields_value;
    for($i = $fields_value; $i >= 0; $i--) {
	// Register a new field in the "user_restriction_section_developers" section, inside the "user_restriction" page.
	add_settings_field(
		'user_restriction_field_role' . (String)$i, // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'User Role', 'user_restriction' ),
		'user_restriction_field_role_cb',
		'user_restriction',
		'user_restriction_section_developers',
		array(
			'label_for'         => 'user_restriction_field_role' . (String)$i,
			'class'             => 'user_restriction_row',
          	'current_i' => $i,
			'user_restriction_custom_data' => 'custom',
		)
	);
  	
  	add_settings_field(
		'user_restriction_field_site' . (String)$i, // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Site to restrict', 'user_restriction' ),
		'user_restriction_field_site_cb',
		'user_restriction',
		'user_restriction_section_developers',
		array(
			'label_for'         => 'user_restriction_field_site' . (String)$i,
			'class'             => 'user_restriction_row',
          	'current_i' => $i,
			'user_restriction_custom_data' => 'custom',
		)
	);
    }
    add_settings_field(
		'user_restriction_field_redirect', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Site to redirect to', 'user_restriction' ),
		'user_restriction_field_redirect_cb',
		'user_restriction',
		'user_restriction_section_developers',
		array(
			'label_for'         => 'user_restriction_field_redirect',
			'class'             => 'user_restriction_row',
			'user_restriction_custom_data' => 'custom',
		)
	);
  	 add_settings_field(
		'user_restriction_field_amount', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Add Settings section', 'user_restriction' ),
		'user_restriction_field_amount_cb',
		'user_restriction',
		'user_restriction_section_developers',
		array(
			'label_for'         => 'user_restriction_field_amount',
			'class'             => 'user_restriction_row',
			'user_restriction_custom_data' => 'custom',
		)
	);
}

/**
 * Register our user_restriction_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'user_restriction_settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function user_restriction_section_developers_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Change user role required to access site.', 'user_restriction' ); ?></p>
	<?php
}

/**
 * Pill field callbakc function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function user_restriction_field_role_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'user_restriction_options' );
  	$role = $options ['user_restriction_field_role' . $args['current_i']];
  	?>
	<p><?php echo $role ?></p>
	<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['user_restriction_custom_data'] ); ?>"
			name="user_restriction_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
      	<?php wp_dropdown_roles(); ?>
 		<option value="no role" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'no role', false ) ) : ( '' ); ?>>
			<?php esc_html_e( 'no role', 'user_restriction' ); ?>
		</option>
	</select>
	<p class="description">
		<?php esc_html_e( 'Change User Role.', 'user_restriction' ); ?>
	</p>
	<?php
} 

function user_restriction_field_site_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'user_restriction_options' );
  	$text_value = $options ['user_restriction_field_site' . $args['current_i']];
	?>
	<input type="text" value="<?php echo "$text_value" ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['user_restriction_custom_data'] ); ?>"
			name="user_restriction_options[<?php echo esc_attr( $args['label_for'] ); ?>]"/>
	<p class="description">
		<?php esc_html_e( 'Change Restricted Site.', 'user_restriction' ); ?>
	</p>
	<?php
}

function user_restriction_field_redirect_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'user_restriction_options' );
  	$text_value = $options ['user_restriction_field_redirect'];
	?>
	<input type="text" value="<?php echo "$text_value" ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['user_restriction_custom_data'] ); ?>"
			name="user_restriction_options[<?php echo esc_attr( $args['label_for'] ); ?>]"/>
	<p class="description">
		<?php esc_html_e( 'Change Redirect Url.', 'user_restriction' ); ?>
	</p>
	<?php
}

function user_restriction_field_amount_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'user_restriction_options' );
  	global $fields_value; 
	?>
	<input type="number" value="<?php echo $fields_value + 1 ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['user_restriction_custom_data'] ); ?>"
			name="user_restriction_options[<?php echo esc_attr( $args['label_for'] ); ?>]"/>
	<p class="description">
		<?php esc_html_e( 'Add Section.', 'user_restriction' ); ?>
	</p>
	<?php
}


/**
 * Add the top level menu page.
 */
function user_restriction_options_page() {
	add_menu_page(
		'User Restriction',
		'User Restriction Options',
		'manage_options',
		'user_restriction',
		'user_restriction_options_page_html'
	);
}


/**
 * Register our user_restriction_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'user_restriction_options_page' );


/**
 * Top level menu callback function
 */
function user_restriction_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'user_restriction_messages', 'user_restriction_message', __( 'Settings Saved', 'user_restriction' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'user_restriction_messages' ); 
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "user_restriction"
			settings_fields( 'user_restriction' );
			// output setting sections and their fields
			// (sections are registered for "user_restriction", each field is registered to a specific section)
			do_settings_sections( 'user_restriction' );
			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
?>
