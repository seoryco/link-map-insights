<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
Plugin Name: Link Map Insights
Plugin URI: https://seory.co.jp
Description: ワンクリックで内部リンクマップを作成できます。
Author: seoryco
Version: 1.1.0
Author URI: https://seory.co.jp
License: GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: link-map-insights
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.9.1
Requires PHP: 7.4
*/

// プラグインのバージョンを定義
define( 'LMI_VERSION', '1.1.0' );

function lmi_add_admin_and_help_menu() {
    // メインメニュー「Link Map」を追加
    add_menu_page(
        'Link Map Insights',
        'Link Map',
        'manage_options',
        'link-map-insights',
        'lmi_admin_page',
        'dashicons-location',
        20
    );

    // サブメニュー「ヘルプ」を追加
    add_submenu_page(
        'link-map-insights',  // 親メニューのスラッグ
        'ヘルプ',             // サブメニューのタイトル
        'ヘルプ',             // サブメニューのタイトル
        'manage_options',     // 権限
        'link-map-insights-help', // サブメニューのスラッグ
        'lmi_render_help_page'    // コールバック関数
    );
}
add_action('admin_menu', 'lmi_add_admin_and_help_menu');


// Include core functionality
require_once plugin_dir_path(__FILE__) . 'link-map-insights-core.php';

// Include admin functionality
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'link-map-insights-admin.php';
    require_once plugin_dir_path(__FILE__) . 'link-map-insights-help.php';
}
