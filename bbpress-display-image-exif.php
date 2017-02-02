<?php

/**
 * Plugin Name: bbPress Display Image Exif
 * Plugin URI: https://github.com/BenjaminMedia/bbpress-display-image-exif
 * Description: Display exif data for the images attached in posts and comments
 * Version:     0.0.2
 * Author:      Jonas Kjærgaard
 * Domain Path: /languages/
 * License:     GPL
 */

namespace bbPress\DisplayImageExif;

// Do not access this file directly
if (!defined('ABSPATH')) {
    exit;
}
// Handle autoload so we can use namespaces
spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
        require_once(__DIR__ . DIRECTORY_SEPARATOR . Plugin::CLASS_DIR . DIRECTORY_SEPARATOR . $className . '.php');
    }
});
class Plugin
{
    /**
     * Text domain for translators
     */
    const TEXT_DOMAIN = 'bbpress-display-image-exif';
    const CLASS_DIR = 'src';
    /**
     * @var object Instance of this class.
     */
    private static $instance;
    public $settings;
    /**
     * @var string Filename of this class.
     */
    public $file;
    /**
     * @var string Basename of this class.
     */
    public $basename;
    /**
     * @var string Plugins directory for this plugin.
     */
    public $plugin_dir;
    /**
     * @var string Plugins url for this plugin.
     */
    public $plugin_url;

    public $settingsLabel = '';
    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);
        $this->settingsLabel = 'Display Image Exif';
        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');
    }
    private function boostrap() {
        add_action( 'bbp_theme_after_topic_content', array($this, 'displayImageExif') );
        add_action( 'bbp_theme_after_reply_content', array($this, 'displayImageExif') );

        add_action( 'wp_enqueue_scripts', array($this, 'includeCss') );
    }

    public function includeCss() {
        wp_enqueue_style('bbpImagesCss', plugins_url('style.css',__FILE__ ));
    }

    private static function fixCharacters($text){
        //convert e.g. 'smu00e5' to 'små'
        $text = preg_replace('/u([\da-fA-F]{4})/', '&#x\1;', $text);
        return $text;
    }

    public function displayImageExif(){
        $post_id = get_the_ID();

        global $wpdb;
        $rows = $wpdb->get_results("select meta_value from wp_postmeta where meta_key='_bbp_files' and post_id=" . $post_id);

        $rows = $rows[0]->meta_value;
        $rows = json_decode($rows);

        if(sizeof($rows) > 0) {
            $output = "";
            foreach ($rows as $row) {
                $output .= '
<div class="mdForumAttachment">
<div class="mdImg">
<a rel="lightbox" href="' . $row->path . '"><img src="' . $row->path . '?w=124&h=124&fit=crop" alt="" title="" width="124" height="124" /></a></div>
<div class="mdTxt">
<p>' . self::fixCharacters($row->title) . '</p>
</div>
</div>';
                $exif = '';
                if ($row->fnumber) {
                    $exif .= ' | <strong>Aperture:</strong>&nbsp;f/' . $row->fnumber;
                }
                if ($row->camera) {
                    $exif .= ' | <strong>Camera:</strong>&nbsp;' . $row->camera;
                }
                if ($row->exp_comp) {
                    $exif .= ' | <strong>Exposure compensation:</strong>&nbsp;' . $row->exp_comp;
                }
                if ($row->exp_time) {
                    $exif .= ' | <strong>Exposure time:</strong>&nbsp;' . self::format_exp_time($row->exp_time);
                }
                if ($row->focal_length) {
                    $exif .= ' | <strong>Focal length:</strong>&nbsp;' . $row->focal_length . ' mm';
                }
                if ($row->iso) {
                    $exif .= ' | <strong>ISO:</strong>&nbsp;' . $row->iso;
                }
                if ($row->white_balance) {
                    $exif .= ' | <strong>White balance:</strong>&nbsp;' . $row->white_balance;
                }
                if ($exif) {
                    $output .= '<span class="mdLabel">exif</span><span class="exif">' . $exif . '</span>';
                }
            }
            echo $output;
        }
    }

    /**
     * Return the exposure time formatted
     * E.g.:
     * 2 seconds => "2
     * 0.25 second => 1/4
     */
    private function format_exp_time($time){
        if($time<1){
            $tmp = (1/$time);
            $tmp = round($tmp * 1000) / 1000;   //make sure time=0.0666667 is turning into 1/15 and not 1/14.999999
            return '1/' . $tmp;
        }

        return '"' . $time;
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            global $bbp_rma;
            $bbp_rma = self::$instance;
            self::$instance->boostrap();
            /**
             * Run after the plugin has been loaded.
             */
            do_action('bbpress-display-image-exif_loaded');
        }
        return self::$instance;
    }
}
/**
 * @return Plugin $instance returns an instance of the plugin
 */
function instance()
{
    return Plugin::instance();
}
add_action('plugins_loaded', __NAMESPACE__ . '\instance', 0);