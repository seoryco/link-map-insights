<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// ヘルプページを表示する関数
function seoryco_lmi_render_help_page() {
    ?>
    <div id="lmi-help-container">
        <h1><?php esc_html_e( 'Link Map Insights ヘルプ', 'link-map-insights' ); ?></h1>
        <p><?php esc_html_e( 'プラグインをインストールしていただき、誠にありがとうございます。内部リンク改善の手助けになれば幸いです。', 'link-map-insights' ); ?></p>
        <h2><?php esc_html_e( '基本的な使い方', 'link-map-insights' ); ?></h2>
        <p><?php esc_html_e( 'Link Map Insights は、ワンクリックでデータを取得・分析します。', 'link-map-insights' ); ?></p>
        <p><a href="https://seory.co.jp/link-map-insights/guide/" target="_blank"><?php esc_html_e( '詳細な解説はこちら', 'link-map-insights' ); ?></a></p>
        <ul>
            <li><?php esc_html_e( 'STEP 1： 「Generate」ボタンをクリック', 'link-map-insights' ); ?></li>
            <li><?php esc_html_e( 'STEP 2： 数秒でリンクマップが表示されます', 'link-map-insights' ); ?></li>
            <li><?php esc_html_e( 'STEP 3： ページ下部に「被リンクゼロのページ」「発リンクゼロのページ」が表示されます', 'link-map-insights' ); ?></li>
        </ul>
        <h2><?php esc_html_e( 'LINKS', 'link-map-insights' ); ?></h2>
        <ul>
            <li><a href="https://seory.co.jp/link-map-insights/" target="_blank"><?php esc_html_e( 'Link Map Insights 公式ページ', 'link-map-insights' ); ?></a></li>
            <li><a href="https://seory.co.jp/" target="_blank"><?php esc_html_e( 'セオリコ', 'link-map-insights' ); ?></a></li>
            <li><a href="https://x.com/seoryco" target="_blank"><?php esc_html_e( 'X (@seoryco)', 'link-map-insights' ); ?></a></li>
        </ul>
    </div>
    <?php
}

// CSSを読み込む関数
function seoryco_lmi_enqueue_help_assets($hook_suffix) {
    // フック名が 'link-map_page_link-map-insights-help' であるかを確認
    if ($hook_suffix != 'link-map_page_link-map-insights-help') {
        return;
    }
    // CSSファイルをキューに追加
    wp_enqueue_style('lmi-help-css', plugins_url('css/link-map-insights-help.css', __FILE__), [], SEORYCO_LMI_VERSION);
}
add_action('admin_enqueue_scripts', 'seoryco_lmi_enqueue_help_assets');

?>
