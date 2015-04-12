<?php
/**
Plugin Name: WP-Farsi
Plugin URI: http://wordpress.org/extend/plugins/wp-farsi
Description: افزونه مبدل تاریخ میلادی وردپرس به خورشیدی، فارسی ساز، مبدل اعداد انگلیسی به فارسی، رفع مشکل هاست با زبان و تاریخ، سازگار با افزونه‌های مشابه.
Author: Ali.Dbg
Author URI: https://github.com/alidbg/wp-farsi
Version: 2.3.3
License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
*/

defined('ABSPATH') or exit;
define('WPFA_NUMS', get_option('wpfa_nums'));
require_once plugin_dir_path(__FILE__) . 'pdate.php';

function wpfa_activate() {
    update_option('WPLANG', 'fa_IR');
    update_option('start_of_week', '6');
    update_option('timezone_string', 'Asia/Tehran');
    if (WPFA_NUMS === false) add_option('wpfa_nums', 'on');
    $inc = ABSPATH . 'wp-admin/includes/translation-install.php';
    if (file_exists($inc)){
        require_once($inc);
        wp_download_language_pack('fa_IR'); 
    }
}

function wpfa_load_first(){
    $plugins = get_option('active_plugins');
    $path = plugin_basename(__FILE__);
    if (is_array($plugins) and $plugins[0] !== $path) {
        $key = array_search($path, $plugins);
        array_splice($plugins, $key, 1);
        array_unshift($plugins, $path);
        update_option('active_plugins', $plugins);
    }
}

function wpfa_patch_func($patch = false) {
    $file = ABSPATH . 'wp-includes/functions.php';
    if (!is_writable($file)) return;
    $src = file_get_contents($file);
    if (preg_match_all('/else\s+return\s+(date.*)[(]/', $src, $match) === 1) 
        file_put_contents($file, str_replace($match[0][0], (rtrim($match[1][0]) === "date" && $patch ? "else\n\t\treturn date_i18n(" : "else\n\t\treturn date("), $src));
}

function timestampdiv() {
?><script type='text/javascript'>
    var c = ("۰,۱,۲,۳,۴,۵,۶,۷,۸,۹,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec").split(",");
    var d = ("0,1,2,3,4,5,6,7,8,9,فرو,ارد,خرد,تیر,مرد,شهر,مهر,آبا,آذر,دی,بهم,اسف").split(",");
    jQuery(document).ready(function(){
    jQuery("#timestampdiv,.timestamp-wrap,.inline-edit-date,.jj,.mm,.aa,.hh,.mn,.ss").html(function(a,b){
    jQuery.each(c,function(a,c){b=b.replace(new RegExp(c,'g'),d[a])});return b});
    jQuery("#mm option[value='"+jQuery('#hidden_mm').val()+"']").attr("selected","selected")});
</script><?php 
}

function numbers_fa( $string ) {
    static $en_nums = array('0','1','2','3','4','5','6','7','8','9');
    static $fa_nums = array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹');
    return str_replace($en_nums, $fa_nums, $string);
}

function dreg_jsfa() {
    wp_deregister_script('ztjalali_reg_admin_js');
    wp_deregister_script('ztjalali_reg_date_js');
    wp_deregister_script('wpp_admin');
}

function wpfa_date_i18n( $g, $f, $t ) {
    $d = wpfa_date($f, intval($t));
    return WPFA_NUMS === "on" ? numbers_fa($d) : $d;
}

function wpfa_nums() {
    register_setting('general', 'wpfa_nums', 'esc_attr');
    add_settings_field('wpfa_nums', '<label for="wpfa_nums">ساختار اعداد</label>', create_function('', '
        echo \'<label><input type="checkbox" name="wpfa_nums" ' . (WPFA_NUMS === "on" ? "checked" : "") . '/> 
        <span>فارسی ۰۱۲۳۴۵۶۷۸۹</span></label>\';'), 'general');
}

function wpfa_apply_filters() {
    ini_set('default_charset', 'UTF-8');
    ini_set('date.timezone', 'UTC');
    if (extension_loaded('mbstring')) {
        mb_internal_encoding('UTF-8');
        mb_language('neutral');
        mb_http_output('UTF-8');
    }
    foreach (array(
        'date_i18n', 'get_post_time', 'get_comment_date', 'get_comment_time', 'get_the_date', 'the_date', 'get_the_time', 'the_time',
        'get_the_modified_date', 'the_modified_date', 'get_the_modified_time', 'the_modified_time', 'get_post_modified_time', 'number_format_i18n'
    ) as $i) remove_all_filters($i);
    add_filter('date_i18n', 'wpfa_date_i18n', 10, 3);
    if (WPFA_NUMS === "on") 
        add_filter('number_format_i18n', 'numbers_fa');
}

function wpfa_init() {
    global $wp_locale;
    $wp_locale->number_format['thousands_sep'] = ",";
    $wp_locale->number_format['decimal_point'] = ".";
    if (isset($_POST['aa'], $_POST['mm'], $_POST['jj']))
        list($_POST['aa'], $_POST['mm'], $_POST['jj']) = jalali2gregorian(zeroise(intval($_POST['aa']), 4), zeroise(intval($_POST['mm']), 2), zeroise(intval($_POST['jj']), 2));
    wpfa_load_first();
    if (numbers_fa(mysql2date("Y", "2015", 0)) !== "۱۳۹۴") 
        wpfa_patch_func(true);
}

wpfa_apply_filters();
add_action('init', 'wpfa_init');
add_action('admin_init', 'wpfa_nums');
add_action('admin_footer', 'timestampdiv');
add_action('wp_print_scripts', 'dreg_jsfa', 900);
add_action('wp_loaded', 'wpfa_apply_filters', 900);
register_activation_hook(__FILE__, 'wpfa_activate');
register_deactivation_hook(__FILE__, 'wpfa_patch_func');