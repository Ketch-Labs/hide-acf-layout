<?php
/*
 * Plugin Name: Hide ACF Layout
 * Description: Hide a module in ACF flexible content on the frontend but still keep it in the backend.
 * Tags: acf, advanced custom fields, flexible content, hide layout
 * Version: 1.0
 * Author: Ketchlabs
 * Author URI: https://lukeketchen.com/
 * Text Domain: hide-acf-layout
 * Domain Path: /languages
 * License GPL2
 */

 // If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HIDE_ACF_LAYOUT_PLUGIN_NAME',               'Hide ACF Layout');
define( 'HIDE_ACF_LAYOUT_FD_FILE',                  __FILE__ );
define( 'HIDE_ACF_LAYOUT_PLUGIN_FOLDER',             plugin_dir_path( __FILE__ ));

class ACF_Hide_Layout {

	protected static $instance = null;
	protected $field_key = 'acf_hide_layout';
	protected $hidden_layouts = [];
	protected $dashboard_screen_name;

	/**
	 * A dummy magic method to prevent class from being cloned.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * A dummy magic method to prevent class from being unserialized.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Main instance.
	 * Ensures only one instance is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->file     =  __FILE__;
		$this->basename = plugin_basename( $this->file );

		$this->init_hooks();
	}

	/**
	 * Get the plugin url.
	 */
	public function get_plugin_url() {
		return plugin_dir_url( $this->file );
	}

	/**
	 * Get the plugin path.
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->file );
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		$plugin_data = get_file_data( $this->file, [ 'Version' => 'Version' ], 'plugin' );
		return $plugin_data['Version'];
	}

	/**
	 * Get field key.
	 */
	public function get_field_key() {
		return $this->field_key;
	}

	/**
	 * Get hidden layouts.
	 */
	public function get_hidden_layouts() {
		return $this->hidden_layouts;
	}

	/**
	 * Set hidden layout.
	 */
	public function set_hidden_layout( $field_key, $row ) {
		$this->hidden_layouts[ $field_key ][] = 'row-' . $row;
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'init', [$this,'init'], 0 );
		add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts'] );
		add_action( 'admin_footer', [$this,'admin_footer'] );
		add_filter( 'acf/load_value/type=flexible_content', [$this,'load_value'], 10, 3 );
		add_filter( 'acf/update_value/type=flexible_content', [$this,'update_value'], 10, 4 );
		add_action('admin_menu', [$this,'PluginMenu'] );

		add_action( 'admin_menu', array( $this, 'hide_acf_setting_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'hide_acf_setting_page_init' ) );
	}

	/**
	 * Set up menu for plugin
	 */
	public function PluginMenu()
	{
		add_submenu_page(
			'tools.php',
            HIDE_ACF_LAYOUT_PLUGIN_NAME,
            HIDE_ACF_LAYOUT_PLUGIN_NAME,
            'manage_options',
            'dropdown-option-setting',
            array( $this, 'hide_acf_setting_create_admin_page' )
        );
	}

	public function hide_acf_setting_create_admin_page() {
        $this->hide_acf_setting_options = get_option( 'hide_acf_setting_option_name' ); ?>

        <div class="wrap">
            <h2>Hide ACF Setting</h2>
            <p></p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                    settings_fields( 'hide_acf_setting_option_group' );
                    do_settings_sections( 'hide-acf-option-setting-admin' );
                    submit_button();
                ?>
            </form>
        </div>
    <?php }

	public function hide_acf_setting_page_init() {
        register_setting(
            'hide_acf_setting_option_group', // option_group
            'hide_acf_setting_option_name', // option_name
            array( $this, 'hide_acf_setting_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'hide_acf_setting_setting_section', // id
            'Settings', // title
            array( $this, 'hide_acf_setting_section_info' ), // callback
            'hide-acf-option-setting-admin' // page
        );

        add_settings_field(
            'hide_acf_settings', // id
            'Logged in status', // title
            array( $this, 'hide_acf_settings_callback' ), // callback
            'hide-acf-option-setting-admin', // page
            'hide_acf_setting_setting_section' // section
        );
    }

	public function hide_acf_setting_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['hide_acf_settings'] ) ) {
            $sanitary_values['hide_acf_settings'] = $input['hide_acf_settings'];
        }

        return $sanitary_values;
    }

    public function hide_acf_setting_section_info() {

    }

    public function hide_acf_settings_callback() {
        ?>
		<select name="hide_acf_setting_option_name[hide_acf_settings]" id="hide_acf_settings">
            <?php $selected = (isset( $this->hide_acf_setting_options['hide_acf_settings'] ) && $this->hide_acf_setting_options['hide_acf_settings'] === 'hide-module') ? 'selected' : '' ; ?>
            <option value="hide-module" <?php echo $selected; ?>>Hide When Logged in</option>
            <?php $selected = (isset( $this->hide_acf_setting_options['hide_acf_settings'] ) && $this->hide_acf_setting_options['hide_acf_settings'] === 'show-module') ? 'selected' : '' ; ?>
            <option value="show-module" <?php echo $selected; ?>>Show when logged in</option>
        </select>
		<?php
    }

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		$assets_url     = $this->get_plugin_url() . 'assets/';
		$plugin_version = $this->get_version();

		wp_enqueue_style( 'hide-acf-layout', $assets_url . 'css/style.css', [], $plugin_version );
		wp_enqueue_script( 'hide-acf-layout', $assets_url . 'js/script.js', ['jquery'], $plugin_version, true );
	}

	/**
	 * Add script options
	 */
	public function admin_footer() {

		$args = [
			'hidden_layouts' => $this->get_hidden_layouts(),
			'i18n' => [
				'hide_layout' => esc_html__( 'Hide / Show Layout', 'hide-acf-layout' ),
			],
		];

		wp_localize_script( 'hide-acf-layout', 'acf_hide_layout_options', $args );
	}


	/**
	 * Remove layouts that are hidden from frontend
	 */
	public function load_value( $layouts, $post_id, $field ) {

		// bail early if no layouts
		if ( empty( $layouts ) ) {
			return $layouts;
		}

		// value must be an array
		$layouts = acf_get_array( $layouts );
		$field_key = $this->get_field_key();

		foreach ( $layouts as $row => $layout ) {

			$hide_layout_field = [
				'name' => "{$field['name']}_{$row}_{$field_key}",
				'key' => "field_{$field_key}",
			];

			$is_hidden = acf_get_value( $post_id, $hide_layout_field );

			if ( $is_hidden ) {
				// used only on admin for javascript
				$this->set_hidden_layout( $field['key'], $row );


        		$hide_acf_setting_options = get_option( 'hide_acf_setting_option_name' );
				if(isset( $hide_acf_setting_options['hide_acf_settings'] ) && $hide_acf_setting_options['hide_acf_settings'] === 'show-module'){
					// hide layout on frontend

					if ( ( ! is_admin() || wp_doing_ajax() ) && ! defined( 'DOING_CRON' ) && ! wp_is_json_request() && ! is_user_logged_in() ) {
						unset( $layouts[ $row ] );
					}

				} else {
					// hide layout on frontend
					if ( ( ! is_admin() || wp_doing_ajax() ) && ! defined( 'DOING_CRON' ) && ! wp_is_json_request() ) {
						unset( $layouts[ $row ] );
					}
				}



			}
		}

		return $layouts;
	}

	/**
	 * Update the field acf_hide_layout value
	 */
	public function update_value( $rows, $post_id, $field, $original ) {

		// return if no layouts or empty values
		if ( empty( $field['layouts'] ) || empty( $rows ) ) {
			return $rows;
		}

		unset( $rows['acfcloneindex'] );

		$rows = array_values( $rows);
		$field_key = $this->get_field_key();

		foreach ( $rows as $key => $row ) {

			// return if no layout reference
			if ( !is_array( $row ) || !isset( $row['acf_fc_layout'] ) || !isset( $row[ $field_key ] ) ) {
				continue;
			}

			$hide_layout_field = [
				'name' => "{$field['name']}_{$key}_{$field_key}",
				'key' => "field_{$field_key}",
			];

			$new_value = $row[ $field_key ];

			acf_update_value( $new_value, $post_id, $hide_layout_field );
		}

		return $rows;
	}
}

ACF_Hide_Layout::instance();
