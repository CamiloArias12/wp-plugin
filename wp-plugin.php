<?php
/**
 * Plugin Name: Safe iFrame Handler
 * Plugin URI: https://example.com/plugins/safe-iframe-handler
 * Description: Safely embed external content using iframes with shortcode support
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Juan Camilo Arias
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: safe-iframe-handler
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

define('SIH_VERSION', '1.0.0');
define('SIH_PLUGIN_DIR', plugin_dir_path(__FILE__));

class SafeIframeHandler {

    public function __construct() {
        add_action('init', array($this, 'init'));

        add_shortcode('safe_iframe', array($this, 'iframe_shortcode'));

        add_action('admin_menu', array($this, 'add_admin_menu'));

        add_action('admin_init', array($this, 'register_settings'));
    }

    public function init() {
        add_filter('wp_kses_allowed_html', array($this, 'allow_iframe_tags'), 10, 2);
    }

    public function allow_iframe_tags($tags, $context) {
        if ($context === 'post') {
            $tags['iframe'] = array(
                'src'             => true,
                'height'          => true,
                'width'           => true,
                'frameborder'     => true,
                'allowfullscreen' => true,
                'loading'         => true,
                'title'           => true,
                'style'           => true,
                'class'           => true
            );
        }
        return $tags;
    }

    public function iframe_shortcode($atts) {
        $allowed_domains = $this->get_allowed_domains();

        $default_atts = array(
            'src'     => '',
            'width'   => '100%',
            'height'  => '450',
            'class'   => 'safe-iframe',
            'title'   => ''
        );

        $atts = shortcode_atts($default_atts, $atts, 'safe_iframe');

        $url = esc_url($atts['src']);
        $domain = parse_url($url, PHP_URL_HOST);

        // Check if domain is allowed
        if (!in_array($domain, $allowed_domains)) {
            return sprintf(
                '<div class="iframe-error">%s</div>',
                esc_html__('Domain not allowed for iframe embedding.', 'safe-iframe-handler')
            );
        }

        $iframe = sprintf(
            '<iframe src="%s" width="%s" height="%s" class="%s" title="%s" frameborder="0" loading="lazy" allowfullscreen></iframe>',
            esc_url($url),
            esc_attr($atts['width']),
            esc_attr($atts['height']),
            esc_attr($atts['class']),
            esc_attr($atts['title'])
        );

        return sprintf(
            '<div class="iframe-container" style="position: relative; overflow: hidden; width: 100%%; padding-top: 56.25%%;">
                <div style="position: absolute; top: 0; left: 0; bottom: 0; right: 0;">%s</div>
            </div>',
            $iframe
        );
    }

    public function add_admin_menu() {
        add_options_page(
            __('Safe iFrame Settings', 'safe-iframe-handler'),
            __('Safe iFrame', 'safe-iframe-handler'),
            'manage_options',
            'safe-iframe-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('safe_iframe_settings', 'safe_iframe_allowed_domains');

        add_settings_section(
            'safe_iframe_main_section',
            __('Allowed Domains', 'safe-iframe-handler'),
            array($this, 'settings_section_callback'),
            'safe-iframe-settings'
        );

        add_settings_field(
            'allowed_domains',
            __('Domains', 'safe-iframe-handler'),
            array($this, 'domains_field_callback'),
            'safe-iframe-settings',
            'safe_iframe_main_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . esc_html__('Enter the domains that are allowed to be embedded in iframes (one per line)', 'safe-iframe-handler') . '</p>';
    }

    public function domains_field_callback() {
        $domains = get_option('safe_iframe_allowed_domains', '');
        printf(
            '<textarea name="safe_iframe_allowed_domains" rows="10" cols="50" class="large-text">%s</textarea>',
            esc_textarea($domains)
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('safe_iframe_settings');
                do_settings_sections('safe-iframe-settings');
                submit_button();
                ?>
            </form>

            <h2><?php esc_html_e('Shortcode Usage', 'safe-iframe-handler'); ?></h2>
            <p><code>[safe_iframe src="https://example.com" width="100%" height="500" title="Example iframe"]</code></p>
        </div>
        <?php
    }

    private function get_allowed_domains() {
        $domains_string = get_option('safe_iframe_allowed_domains', '');
        $domains = array_map('trim', explode("\n", $domains_string));
        return array_filter($domains);
    }
}

// Initialize plugin
new SafeIframeHandler();
