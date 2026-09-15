<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// 管理画面のページ内容
function seoryco_lmi_admin_page() {
    $lmi_notice = isset( $_GET['lmi_notice'] ) ? sanitize_key( wp_unslash( $_GET['lmi_notice'] ) ) : '';
    ?>
    <div class="wrap">
        <h1>Link Map Insights</h1>
        <?php if ( 'no_data' === $lmi_notice ) : ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php esc_html_e( 'Please click "Generate" first to create the link map before downloading the CSV.', 'link-map-insights' ); ?></p>
            </div>
        <?php endif; ?>
        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" data-lmi-tab="map"><?php esc_html_e( 'Map view', 'link-map-insights' ); ?></a>
            <a href="#" class="nav-tab" data-lmi-tab="list"><?php esc_html_e( 'Link list', 'link-map-insights' ); ?></a>
        </h2>
        <div id="lmi-tab-map" class="lmi-tab-panel">
        <div id="post-type-filters-wrap" class="lmi-term-group">
            <div class="lmi-term-group-title">
                <span><?php esc_html_e( 'Post types to display', 'link-map-insights' ); ?></span>
                <span class="lmi-term-group-buttons">
                    <button type="button" class="lmi-btn-select-all" data-target=".lmi-post-type-filter"><?php esc_html_e( 'Select all', 'link-map-insights' ); ?></button>
                    <button type="button" class="lmi-btn-deselect-all" data-target=".lmi-post-type-filter"><?php esc_html_e( 'Deselect all', 'link-map-insights' ); ?></button>
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
            <input type="text" id="node-search" placeholder="<?php esc_attr_e( 'Search nodes', 'link-map-insights' ); ?>">
            <button id="search-button"><?php esc_html_e( 'Search', 'link-map-insights' ); ?></button>
            <button id="reset-search-button"><?php esc_html_e( 'Reset search', 'link-map-insights' ); ?></button>
        </div>
        <div id="zoom-controls">
            <button type="button" id="zoom-in"><?php esc_html_e( 'Zoom in', 'link-map-insights' ); ?></button>
            <button type="button" id="zoom-out"><?php esc_html_e( 'Zoom out', 'link-map-insights' ); ?></button>
            <label class="lmi-toggle-title-wrap">
                <input type="checkbox" id="toggle-title-length" name="toggle-title-length"> <?php esc_html_e( 'Shorten titles', 'link-map-insights' ); ?>
            </label>
        </div>
        <div id="network-container">
            <div id="mynetwork"></div>
            <div id="loadingBar">
                <div id="text">0%</div>
            </div>
        </div>
        <p class="lmi-action-buttons">
            <button id="generate-link-map"><?php esc_html_e( 'Generate', 'link-map-insights' ); ?></button>
            <a
                href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=seoryco_lmi_export_csv' ), 'seoryco_lmi_export_csv' ) ); ?>"
                class="button"
                id="lmi-export-csv"
            ><?php esc_html_e( 'Download CSV', 'link-map-insights' ); ?></a>
        </p>
        <h2 id="no-inbound-links-title"><?php
            /* translators: %d: number of pages. */
            printf( esc_html__( 'Pages with no inbound links: %d', 'link-map-insights' ), 0 );
        ?></h2>
        <div id="no-inbound-links-container"></div>
        <h2 id="no-outbound-links-title"><?php
            /* translators: %d: number of pages. */
            printf( esc_html__( 'Pages with no outbound links: %d', 'link-map-insights' ), 0 );
        ?></h2>
        <div id="no-outbound-links-container"></div>
        </div>
        <div id="lmi-tab-list" class="lmi-tab-panel" style="display:none;">
            <table id="lmi-link-list-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Source post', 'link-map-insights' ); ?></th>
                        <th><?php esc_html_e( 'Source post type', 'link-map-insights' ); ?></th>
                        <th><?php esc_html_e( 'Target post', 'link-map-insights' ); ?></th>
                        <th><?php esc_html_e( 'Target post type', 'link-map-insights' ); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <?php
}

// CSV エクスポート処理（uploads 等には保存せず、php://output へ直接出力）
function seoryco_lmi_handle_csv_export() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'link-map-insights' ) );
    }
    check_admin_referer( 'seoryco_lmi_export_csv' );

    $dataset = get_transient( 'seoryco_lmi_dataset' );
    if ( ! is_array( $dataset ) || empty( $dataset['raw_edges'] ) || ! is_array( $dataset['raw_edges'] ) ) {
        $redirect_url = add_query_arg( 'lmi_notice', 'no_data', admin_url( 'admin.php?page=link-map-insights' ) );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="link-map-insights-' . gmdate( 'Ymd-His' ) . '.csv"' );

    $lmi_output = fopen( 'php://output', 'w' );
    // UTF-8 BOM（Excel 等での文字化け対策）
    fwrite( $lmi_output, "\xEF\xBB\xBF" );

    fputcsv( $lmi_output, array(
        __( 'from_id', 'link-map-insights' ),
        __( 'from_title', 'link-map-insights' ),
        __( 'from_url', 'link-map-insights' ),
        __( 'from_post_type', 'link-map-insights' ),
        __( 'to_id', 'link-map-insights' ),
        __( 'to_title', 'link-map-insights' ),
        __( 'to_url', 'link-map-insights' ),
        __( 'to_post_type', 'link-map-insights' ),
    ) );

    foreach ( $dataset['raw_edges'] as $lmi_edge ) {
        $from_id = isset( $lmi_edge['from'] ) ? (int) $lmi_edge['from'] : 0;
        $to_id   = isset( $lmi_edge['to'] ) ? (int) $lmi_edge['to'] : 0;
        fputcsv( $lmi_output, array(
            $from_id,
            html_entity_decode( get_the_title( $from_id ), ENT_QUOTES, 'UTF-8' ),
            (string) get_permalink( $from_id ),
            (string) get_post_type( $from_id ),
            $to_id,
            html_entity_decode( get_the_title( $to_id ), ENT_QUOTES, 'UTF-8' ),
            (string) get_permalink( $to_id ),
            (string) get_post_type( $to_id ),
        ) );
    }

    fclose( $lmi_output );
    exit;
}
add_action( 'admin_post_seoryco_lmi_export_csv', 'seoryco_lmi_handle_csv_export' );

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
            'edit_post_base' => admin_url( 'post.php' ),
        ) )
    );
    $lmi_inline .= sprintf( 'lmiData.cached_dataset = %s;', $lmi_cached_json );
    wp_add_inline_script( 'link-map-insights-admin-js', $lmi_inline, 'before' );
    wp_enqueue_style('link-map-insights-admin-css', plugins_url('css/link-map-insights-admin.css', __FILE__), [], SEORYCO_LMI_VERSION);
}
add_action('admin_enqueue_scripts', 'seoryco_lmi_admin_enqueue_scripts');
?>
