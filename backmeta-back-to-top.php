<?php
/*
Plugin Name: BackMeta Back to Top
Plugin URI: https://backmeta.devtheme.net/
Description: Adds a customizable “Back to Top” button with options for button color (using a color picker), Font Awesome icon selection (via dropdown), text orientation, and button shape.
Version: 1.0.0
Author: devthemenet
Author URI: https://devtheme.net
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: backmeta-back-to-top
*/

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BackMeta_Back_To_Top {

    /**
     * Constructor.
     */
    public function __construct() {
        // Front-end hooks.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_button' ) );

        // Admin hooks.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue front-end CSS, JavaScript, and conditionally Font Awesome.
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'backmeta-back-to-top-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/back-to-top.css'
        );
    
        wp_enqueue_script(
            'backmeta-back-to-top-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/back-to-top.js',
            array( 'jquery' ),
            '1.0',
            true
        );
    
        // Instead of calling an external URL, load Font Awesome locally.
        $options      = get_option( 'backmeta_btt_options', array() );
        $display_icon = isset( $options['display_icon'] ) ? $options['display_icon'] : 1;
        if ( $display_icon ) {
            wp_enqueue_style(
                'font-awesome',
                plugin_dir_url( __FILE__ ) . 'assets/vendor/font-awesome/css/all.min.css',
                array(),
                '6.4.0'
            );
        }
    }
    

    /**
     * Enqueue admin assets (color picker, custom JS) on our settings page.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin settings page.
        if ( 'settings_page_backmeta-btt-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style(
            'backmeta-btt-admin-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/back-to-top-admin.css'
        );
        wp_enqueue_script(
            'backmeta-btt-admin-script',
            plugin_dir_url( __FILE__ ) . 'assets/js/back-to-top-admin.js',
            array( 'wp-color-picker', 'jquery' ),
            '1.0',
            true
        );
    }
    

    /**
     * Render the Back to Top button in the footer.
     */
    public function render_button() {
        $options          = get_option( 'backmeta_btt_options', array() );
        $enabled          = isset( $options['enable_button'] ) ? $options['enable_button'] : 1;
        if ( ! $enabled ) {
            return;
        }
    
        // Retrieve the button text (defaulting to "Top" if not set).
        $button_text      = isset( $options['button_text'] ) ? $options['button_text'] : 'Top';
    
        // Other settings are retrieved and processed here...
        $button_color     = isset( $options['button_color'] ) ? $options['button_color'] : '#555';
        $button_icon      = isset( $options['button_icon'] ) ? $options['button_icon'] : 'fa fa-arrow-up';
        $display_icon     = isset( $options['display_icon'] ) ? $options['display_icon'] : 1;
        $display_text     = isset( $options['display_text'] ) ? $options['display_text'] : 1;
        $text_orientation = isset( $options['text_orientation'] ) ? $options['text_orientation'] : 'vertical';
        $button_shape     = isset( $options['button_shape'] ) ? $options['button_shape'] : 'rounded';
        $custom_radius    = isset( $options['custom_button_radius'] ) ? $options['custom_button_radius'] : '';
    
        // Determine border radius based on shape selection.
        if ( 'custom' === $button_shape && ! empty( $custom_radius ) ) {
            $border_radius = $custom_radius;
        } elseif ( 'round' === $button_shape ) {
            $border_radius = '50%';
        } else {
            $border_radius = '8px';
        }
        ?>
        <a href="#" id="backmeta-back-to-top" title="<?php echo esc_attr__( 'Back to top', 'backmeta-back-to-top' ); ?>"
           style="background-color: <?php echo esc_attr( $button_color ); ?>; border-radius: <?php echo esc_attr( $border_radius ); ?>;">
            <?php if ( $display_icon ) : ?>
                <span class="backmeta-icon">
                    <i class="<?php echo esc_attr( $button_icon ); ?>"></i>
                </span>
            <?php endif; ?>
            <?php if ( $display_text ) : ?>
                <span class="backmeta-text <?php echo ( 'horizontal' === $text_orientation ) ? 'horizontal-text' : 'vertical-text'; ?>">
                    <?php
                    // Escape the button text output
                    echo esc_html( $button_text );
                    ?>
                </span>
            <?php endif; ?>
        </a>
        <?php
    }    

    /*=======================================
      =           ADMIN SETTINGS            =
    =======================================*/

    /**
     * Add the settings page to the admin menu.
     */
    public function add_admin_menu() {
        add_options_page(
            'BackMeta Back to Top Settings',
            'BackMeta Back to Top',
            'manage_options',
            'backmeta-btt-settings',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Register settings, sections, and fields.
     */
    public function register_settings() {
        register_setting(
            'backmeta_btt_options_group',
            'backmeta_btt_options',
            'BackMeta_Back_To_Top::sanitize_settings'
        );        

        add_settings_section(
            'backmeta_btt_settings_section',
            'BackMeta Back to Top Settings',
            array( $this, 'settings_section_text' ),
            'backmeta-btt-settings'
        );

        // Existing settings.
        add_settings_field(
            'enable_button',
            'Enable Button',
            array( $this, 'enable_button_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'button_color',
            'Button Color',
            array( $this, 'button_color_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'display_icon',
            'Display Icon',
            array( $this, 'display_icon_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'button_icon',
            'Font Awesome Icon',
            array( $this, 'button_icon_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'display_text',
            'Display Text',
            array( $this, 'display_text_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'button_text',
            'Button Text',
            array( $this, 'button_text_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'text_orientation',
            'Text Orientation',
            array( $this, 'text_orientation_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );

        // New settings for button shape.
        add_settings_field(
            'button_shape',
            'Button Shape',
            array( $this, 'button_shape_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
        add_settings_field(
            'custom_button_radius',
            'Custom Button Border Radius',
            array( $this, 'custom_button_radius_field' ),
            'backmeta-btt-settings',
            'backmeta_btt_settings_section'
        );
    }

    /**
     * Sanitize and validate settings input.
     *
     * @param array $input The submitted options.
     * @return array Sanitized options.
     */
    public static function sanitize_settings( $input ) {
        // Ensure $input is an array.
        if ( ! is_array( $input ) ) {
            return array();
        }
    
        $new_input = array();
    
        // Boolean (checkbox) values.
        $new_input['enable_button'] = ( isset( $input['enable_button'] ) && $input['enable_button'] == 1 ) ? 1 : 0;
        $new_input['display_icon']  = ( isset( $input['display_icon'] ) && $input['display_icon'] == 1 ) ? 1 : 0;
        $new_input['display_text']  = ( isset( $input['display_text'] ) && $input['display_text'] == 1 ) ? 1 : 0;
    
        // Color field: use sanitize_hex_color().
        $new_input['button_color'] = isset( $input['button_color'] ) ? sanitize_hex_color( $input['button_color'] ) : '#555';
    
        // Icon and text: use sanitize_text_field().
        $new_input['button_icon']  = isset( $input['button_icon'] ) ? sanitize_text_field( $input['button_icon'] ) : 'fa fa-arrow-up';
        $new_input['button_text']  = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : 'Top';
    
        // Select fields with allowed values.
        $allowed_orientations = array( 'vertical', 'horizontal' );
        $new_input['text_orientation'] = ( isset( $input['text_orientation'] ) && in_array( $input['text_orientation'], $allowed_orientations ) )
            ? $input['text_orientation']
            : 'vertical';
    
        $allowed_shapes = array( 'rounded', 'round', 'custom' );
        $new_input['button_shape'] = ( isset( $input['button_shape'] ) && in_array( $input['button_shape'], $allowed_shapes ) )
            ? $input['button_shape']
            : 'rounded';
    
        // Custom border radius.
        $new_input['custom_button_radius'] = isset( $input['custom_button_radius'] ) ? sanitize_text_field( $input['custom_button_radius'] ) : '';
    
        return $new_input;
    }      

    /**
     * Output the settings section description.
     */
    public function settings_section_text() {
        echo '<p>Configure the settings for the BackMeta Back to Top plugin.</p>';
    }

    /**
     * Output the "Enable Button" field.
     */
    public function enable_button_field() {
        $options = get_option( 'backmeta_btt_options' );
        $enabled = isset( $options['enable_button'] ) ? $options['enable_button'] : 1;
        echo '<input type="checkbox" name="backmeta_btt_options[enable_button]" value="1" ' . checked( 1, $enabled, false ) . ' />';
        echo '<label> Enable the Back to Top button.</label>';
    }

    /**
     * Output the "Button Color" field using a color picker.
     */
    public function button_color_field() {
        $options = get_option( 'backmeta_btt_options' );
        // Use a default value of "#555" if none is set.
        $color   = isset( $options['button_color'] ) ? $options['button_color'] : '#555';
        // Always escape the output using esc_attr() for attributes.
        echo '<input type="text" class="color-field" name="backmeta_btt_options[button_color]" value="' . esc_attr( $color ) . '" placeholder="#555" />';
        echo '<p class="description">' . esc_html__( 'Select a button background color.', 'backmeta-back-to-top' ) . '</p>';
    }
    

    /**
     * Output the "Display Icon" field.
     */
    public function display_icon_field() {
        $options      = get_option( 'backmeta_btt_options' );
        $display_icon = isset( $options['display_icon'] ) ? $options['display_icon'] : 1;
        echo '<input type="checkbox" name="backmeta_btt_options[display_icon]" value="1" ' . checked( 1, $display_icon, false ) . ' />';
        echo '<label> Check to display the icon.</label>';
    }

    /**
     * Output the "Font Awesome Icon" field as a dropdown.
     */
    public function button_icon_field() {
        $options       = get_option( 'backmeta_btt_options' );
        $selected_icon = isset( $options['button_icon'] ) ? esc_attr( $options['button_icon'] ) : 'fa fa-arrow-up';
        // Define a list of available Font Awesome icon classes.
        $icons = array(
            'fa fa-arrow-up'      => 'Arrow Up',
            'fa fa-chevron-up'    => 'Chevron Up',
            'fa fa-angle-up'      => 'Angle Up',
            'fa fa-long-arrow-up' => 'Long Arrow Up',
            'fa fa-plane'         => 'Plane',
        );
        echo '<select name="backmeta_btt_options[button_icon]">';
        foreach ( $icons as $icon_class => $icon_name ) {
            echo '<option value="' . esc_attr( $icon_class ) . '" ' . selected( $selected_icon, $icon_class, false ) . '>' . esc_html( $icon_name ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select a Font Awesome icon.</p>';
    }

    /**
     * Output the "Display Text" field.
     */
    public function display_text_field() {
        $options      = get_option( 'backmeta_btt_options' );
        $display_text = isset( $options['display_text'] ) ? $options['display_text'] : 1;
        echo '<input type="checkbox" name="backmeta_btt_options[display_text]" value="1" ' . checked( 1, $display_text, false ) . ' />';
        echo '<label> Check to display the text.</label>';
    }

    /**
     * Output the "Button Text" field.
     */
    public function button_text_field() {
        $options = get_option( 'backmeta_btt_options' );
        // Use a default value of "Top" if not set.
        $text = isset( $options['button_text'] ) ? $options['button_text'] : 'Top';
        ?>
        <input type="text" name="backmeta_btt_options[button_text]" value="<?php echo esc_attr( $text ); ?>" />
        <p class="description"><?php echo esc_html__( 'Enter the text to display on the button.', 'backmeta-back-to-top' ); ?></p>
        <?php
    }
    

    /**
     * Output the "Text Orientation" field.
     */
    public function text_orientation_field() {
        $options     = get_option( 'backmeta_btt_options' );
        $orientation = isset( $options['text_orientation'] ) ? $options['text_orientation'] : 'vertical';
        ?>
        <label>
            <input type="radio" name="backmeta_btt_options[text_orientation]" value="vertical" <?php checked( 'vertical', $orientation ); ?> />
            Vertical
        </label>
        <label>
            <input type="radio" name="backmeta_btt_options[text_orientation]" value="horizontal" <?php checked( 'horizontal', $orientation ); ?> />
            Horizontal
        </label>
        <p class="description">Choose how the button text should be displayed.</p>
        <?php
    }

    /**
     * Output the "Button Shape" field as a dropdown.
     */
    public function button_shape_field() {
        $options       = get_option( 'backmeta_btt_options' );
        $button_shape  = isset( $options['button_shape'] ) ? esc_attr( $options['button_shape'] ) : 'rounded';
        ?>
        <select id="button_shape_field" name="backmeta_btt_options[button_shape]">
            <option value="rounded" <?php selected( $button_shape, 'rounded' ); ?>>Rounded</option>
            <option value="round" <?php selected( $button_shape, 'round' ); ?>>Round</option>
            <option value="custom" <?php selected( $button_shape, 'custom' ); ?>>Custom</option>
        </select>
        <p class="description">Select a preset button shape. Choose "Custom" to specify your own border radius.</p>
        <?php
    }

    /**
     * Output the "Custom Button Border Radius" field.
     */
    public function custom_button_radius_field() {
        $options = get_option( 'backmeta_btt_options' );
        $custom_radius = isset( $options['custom_button_radius'] ) ? $options['custom_button_radius'] : '';
        ?>
        <div class="custom-button-radius">
            <input type="text" name="backmeta_btt_options[custom_button_radius]" 
                   value="<?php echo esc_attr( $custom_radius ); ?>" 
                   placeholder="<?php echo esc_attr__( 'e.g., 12px or 50%', 'backmeta-back-to-top' ); ?>" />
            <p class="description"><?php echo esc_html__( 'Enter a custom border-radius (used only if "Custom" is selected above).', 'backmeta-back-to-top' ); ?></p>
        </div>
        <?php
    }
    

    /**
     * Render the settings page.
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>BackMeta Back to Top Settings</h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'backmeta_btt_options_group' );
                    do_settings_sections( 'backmeta-btt-settings' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin.
new BackMeta_Back_To_Top();