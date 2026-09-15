<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
Plugin Name: Link Map Insights
Plugin URI: https://seory.co.jp/link-map-insights/
Description: Visualize your site's internal link structure in one click.
Author: seoryco
Version: 1.3.0
Author URI: https://seory.co.jp
License: GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: link-map-insights
Domain Path: /languages
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
*/

// プラグインのバージョンを定義
define( 'SEORYCO_LMI_VERSION', '1.3.0' );

function seoryco_lmi_add_admin_and_help_menu() {
    // メインメニュー「Link Map」を追加
    add_menu_page(
        __( 'Link Map Insights', 'link-map-insights' ),
        __( 'Link Map', 'link-map-insights' ),
        'manage_options',
        'link-map-insights',
        'seoryco_lmi_admin_page',
        'dashicons-location',
        100
    );

    // サブメニュー「ヘルプ」を追加
    add_submenu_page(
        'link-map-insights',  // 親メニューのスラッグ
        __( 'Help', 'link-map-insights' ), // サブメニューのタイトル
        __( 'Help', 'link-map-insights' ), // サブメニューのタイトル
        'manage_options',     // 権限
        'link-map-insights-help', // サブメニューのスラッグ
        'seoryco_lmi_render_help_page'    // コールバック関数
    );
}
add_action('admin_menu', 'seoryco_lmi_add_admin_and_help_menu');


// Include core functionality
require_once plugin_dir_path(__FILE__) . 'link-map-insights-core.php';

// Include admin functionality
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'link-map-insights-admin.php';
    require_once plugin_dir_path(__FILE__) . 'link-map-insights-help.php';
}
