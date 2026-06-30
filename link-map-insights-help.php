<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// ヘルプページを表示する関数
function lmi_render_help_page() {
    ?>
    <div id="lmi-help-container">
        <h1>Link Map Insights ヘルプ</h1>
        <p>プラグインをインストールしていただき、誠にありがとうございます。内部リンク改善の手助けになれば幸いです。</p>
        <h2>基本的な使い方</h2>
        <p>Link Map Insights は、ワンクリックでデータを取得・分析します。</p>
        <p><a href="https://seory.co.jp/link-map-insights/guide/" target="_blank">詳細な解説はこちら</a></p>
        <ul>
            <li>STEP 1： 「Generate」ボタンをクリック</li>
            <li>STEP 2： 数秒でリンクマップが表示されます</li>
            <li>STEP 3： ページ下部に「被リンクゼロのページ」「発リンクゼロのページ」が表示されます</li>
        </ul>
        <h2>LINKS</h2>
        <ul>
            <li><a href="https://seory.co.jp/link-map-insights/" target="_blank">Link Map Insights 公式ページ</a></li>
            <li><a href="https://seory.co.jp/" target="_blank">セオリコ</a></li>
            <li><a href="https://x.com/seoryco" target="_blank">X (@seoryco)</a></li>
        </ul>
    </div>
    <?php
}

// CSSを読み込む関数
function lmi_enqueue_help_assets($hook_suffix) {
    // フック名が 'link-map_page_link-map-insights-help' であるかを確認
    if ($hook_suffix != 'link-map_page_link-map-insights-help') {
        return;
    }
    // CSSファイルをキューに追加
    wp_enqueue_style('lmi-help-css', plugins_url('css/link-map-insights-help.css', __FILE__), [], LMI_VERSION);
}
add_action('admin_enqueue_scripts', 'lmi_enqueue_help_assets');

?>
