<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// 管理画面のページ内容
function seoryco_lmi_admin_page() {
    ?>
    <div class="wrap">
        <h1>Link Map Insights</h1>
        <div id="post-type-filters-wrap" class="lmi-term-group">
            <div class="lmi-term-group-title">
                <span><?php esc_html_e( '表示する投稿タイプ', 'link-map-insights' ); ?></span>
                <span class="lmi-term-group-buttons">
                    <button type="button" class="lmi-btn-select-all" data-target=".lmi-post-type-filter"><?php esc_html_e( '全選択', 'link-map-insights' ); ?></button>
                    <button type="button" class="lmi-btn-deselect-all" data-target=".lmi-post-type-filter"><?php esc_html_e( '全解除', 'link-map-insights' ); ?></button>
                </span>
            </div>
            <div class="lmi-term-group-inner" id="post-type-filters">
                <?php
                $lmi_post_types = get_post_types( array( 'public' => true ), 'objects' );
                $lmi_post_types = array_diff_key( $lmi_post_types, array( 'attachment' => true ) );
                foreach ( $lmi_post_types as $pt => $obj ) {
                    printf(
                        '<label><input type="checkbox" class="lmi-post-type-filter" name="post_type[]" value="%s" checked> %s</label> ',
                        esc_attr( $pt ),
                        esc_html( $obj->labels->name )
                    );
                }
                ?>
            </div>
        </div>
        <div id="term-filters"></div>
        <div id="search-area">
            <input type="text" id="node-search" placeholder="<?php esc_attr_e( 'ノード検索', 'link-map-insights' ); ?>">
            <button id="search-button"><?php esc_html_e( '検索', 'link-map-insights' ); ?></button>
            <button id="reset-search-button"><?php esc_html_e( '検索リセット', 'link-map-insights' ); ?></button>
        </div>
        <div id="zoom-controls">
            <button type="button" id="zoom-in"><?php esc_html_e( '拡大', 'link-map-insights' ); ?></button>
            <button type="button" id="zoom-out"><?php esc_html_e( '縮小', 'link-map-insights' ); ?></button>
            <label class="lmi-toggle-title-wrap">
                <input type="checkbox" id="toggle-title-length" name="toggle-title-length"> <?php esc_html_e( 'タイトルを省略する', 'link-map-insights' ); ?>
            </label>
        </div>
        <div id="network-container">
            <div id="mynetwork"></div>
            <div id="loadingBar">
                <div id="text">0%</div>
            </div>
        </div>
        <button id="generate-link-map"><?php esc_html_e( 'Generate', 'link-map-insights' ); ?></button>
        <h2 id="no-inbound-links-title"><?php
            /* translators: %d: number of pages. */
            printf( esc_html__( '被リンクゼロのページ：%d件', 'link-map-insights' ), 0 );
        ?></h2>
        <div id="no-inbound-links-container"></div>
        <h2 id="no-outbound-links-title"><?php
            /* translators: %d: number of pages. */
            printf( esc_html__( '発リンクゼロのページ：%d件', 'link-map-insights' ), 0 );
        ?></h2>
        <div id="no-outbound-links-container"></div>
    </div>
    <?php
}

// 必要なスクリプトとスタイルの読み込み
function seoryco_lmi_admin_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_link-map-insights') {
        return;
    }
    wp_enqueue_script( 'vis-js', plugins_url( 'js/lib/vis-network.min.js', __FILE__ ), array(), '9.1.13', true );
    wp_enqueue_script( 'link-map-insights-admin-js', plugins_url( 'js/link-map-insights-admin.js', __FILE__ ), array( 'jquery', 'vis-js', 'wp-i18n' ), SEORYCO_LMI_VERSION, true );
    wp_set_script_translations( 'link-map-insights-admin-js', 'link-map-insights', plugin_dir_path( __FILE__ ) . 'languages' );
    $lmi_cached = get_transient( 'seoryco_lmi_dataset' );
    $lmi_cached_json = 'null';
    if ( is_array( $lmi_cached ) && isset( $lmi_cached['nodes'] ) && isset( $lmi_cached['edges'] ) && ! empty( $lmi_cached['nodes'] ) ) {
        $lmi_cached_json = wp_json_encode( $lmi_cached );
    }
    $lmi_inline = sprintf(
        'var lmiData = %s;',
        wp_json_encode( array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'seoryco_lmi_get_dataset' ),
        ) )
    );
    $lmi_inline .= sprintf( 'lmiData.cached_dataset = %s;', $lmi_cached_json );
    wp_add_inline_script( 'link-map-insights-admin-js', $lmi_inline, 'before' );
    wp_enqueue_style('link-map-insights-admin-css', plugins_url('css/link-map-insights-admin.css', __FILE__), [], SEORYCO_LMI_VERSION);
}
add_action('admin_enqueue_scripts', 'seoryco_lmi_admin_enqueue_scripts');
?>
