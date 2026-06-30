<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lmi_get_dataset() {
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	// 管理者のみ実行可能
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Forbidden', 'link-map-insights' ) ), 403 );
		return;
	}
	if ( ! isset( $_POST['lmi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lmi_nonce'] ) ), 'lmi_get_dataset' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'link-map-insights' ) ), 403 );
		return;
	}

	$lmi_regenerate = isset( $_POST['lmi_regenerate'] ) && ( $_POST['lmi_regenerate'] === '1' || $_POST['lmi_regenerate'] === 'true' );
	if ( ! $lmi_regenerate ) {
		$cached = get_transient( 'lmi_dataset' );
		if ( is_array( $cached ) && isset( $cached['nodes'] ) && isset( $cached['edges'] ) ) {
			wp_send_json( $cached );
			return;
		}
		wp_send_json( array(
			'nodes'             => array(),
			'edges'             => array(),
			'terms_colors'       => array(),
			'post_type_labels'   => array(),
			'no_inbound_links'   => array(),
			'no_outbound_links'  => array(),
		) );
		return;
	}

	$allowed_post_types = array_values( get_post_types( array( 'public' => true ), 'names' ) );
	$allowed_post_types = array_diff( $allowed_post_types, array( 'attachment' ) );
	$allowed_post_types = array_values( $allowed_post_types );
	if ( empty( $allowed_post_types ) ) {
		$allowed_post_types = array( 'post', 'page' );
	}

	$post_types = $allowed_post_types;
	if ( isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] ) ) {
		$requested  = array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['post_types'] ) ), $allowed_post_types ) );
		$post_types = empty( $requested ) ? array() : $requested;
	}
	if ( empty( $post_types ) ) {
		wp_send_json( array(
			'nodes'             => array(),
			'edges'             => array(),
			'terms_colors'      => array(),
			'post_type_labels'  => array(),
			'no_inbound_links'  => array(),
			'no_outbound_links' => array(),
		) );
		return;
	}

	$args   = array(
		'posts_per_page' => -1,
		'post_type'      => $post_types,
		'post_status'    => 'publish',
	);
	$articles = get_posts( $args );
	$nodes    = array();
	$edges    = array();
	$terms_colors = array();
	$no_inbound_links  = array();
	$no_outbound_links = array();

	// 対象投稿タイプに紐づく全タクソノミーを収集
	$taxonomies_by_type = array();
	foreach ( $post_types as $pt ) {
		$taxonomies_by_type[ $pt ] = get_object_taxonomies( $pt, 'objects' );
	}

	$hue_start = 30;
	$hue_end   = 330;
	$hue_range = $hue_end - $hue_start;

	foreach ( $taxonomies_by_type as $post_type => $tax_objects ) {
		foreach ( $tax_objects as $tax_slug => $tax_obj ) {
			if ( empty( $tax_obj->public ) ) {
				continue;
			}
			$tax_label = ( isset( $tax_obj->labels->name ) ) ? $tax_obj->labels->name : $tax_slug;
			$root_terms = get_terms( array(
				'taxonomy'   => $tax_slug,
				'hide_empty' => false,
				'parent'     => 0,
			) );
			if ( is_wp_error( $root_terms ) ) {
				continue;
			}
			$total_root = count( $root_terms );
			$hue_step   = ( $total_root > 0 ) ? ( $hue_range / $total_root ) : $hue_range;
			foreach ( $root_terms as $idx => $term ) {
				$hue = (int) ( $hue_start + ( $idx * $hue_step ) );
				$hue = max( $hue_start, min( $hue_end, $hue ) );
				$key = $tax_slug . '_' . $term->term_id;
				if ( isset( $terms_colors[ $key ] ) ) {
					continue;
				}
				$terms_colors[ $key ] = array(
					'color'          => sprintf( 'hsl(%d, 100%%, 70%%)', $hue ),
					'name'           => $term->name,
					'taxonomy_label' => $tax_label,
				);
				if ( ! empty( $tax_obj->hierarchical ) ) {
					$child_terms = get_terms( array(
						'taxonomy'   => $tax_slug,
						'hide_empty' => false,
						'parent'     => $term->term_id,
					) );
					if ( ! is_wp_error( $child_terms ) ) {
						$parent_color = $terms_colors[ $key ]['color'];
						$child_color  = preg_replace( '/70%/', '85%', $parent_color );
						foreach ( $child_terms as $child ) {
							$ck = $tax_slug . '_' . $child->term_id;
							if ( ! isset( $terms_colors[ $ck ] ) ) {
								$terms_colors[ $ck ] = array(
									'color'          => $child_color,
									'name'           => $child->name,
									'taxonomy_label' => $tax_label,
								);
							}
						}
					}
				}
			}
		}
	}

	foreach ( $articles as $post ) {
		$post_terms = array();
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $tax_slug ) {
			$terms = get_the_terms( $post->ID, $tax_slug );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}
			foreach ( $terms as $t ) {
				$key = $tax_slug . '_' . $t->term_id;
				$post_terms[ $key ] = true;
			}
		}
		$post_term_keys = array_keys( $post_terms );

		$assigned_color = null;
		foreach ( $post_term_keys as $key ) {
			if ( isset( $terms_colors[ $key ] ) ) {
				$assigned_color = $terms_colors[ $key ]['color'];
				break;
			}
		}
		if ( ! $assigned_color ) {
			$assigned_color = sprintf( 'hsl(%d, 100%%, 85%%)', wp_rand( 0, 360 ) );
		}
		if ( $post->post_type === 'page' ) {
			$assigned_color = 'hsl(0, 0%, 0%)';
		}

		$nodes[] = array(
			'id'          => $post->ID,
			'label'       => $post->post_title,
			'full_label'  => $post->post_title,
			'short_label' => mb_strimwidth( $post->post_title, 0, 40, '...' ),
			'url'         => get_permalink( $post->ID ),
			'post_type'   => $post->post_type,
			'terms'       => $post_term_keys,
			'color'       => $assigned_color,
			'font'        => array( 'color' => $post->post_type === 'page' ? '#FFFFFF' : '#000000' ),
		);

        $content = $post->post_content;

        // 既存のURLを取得（PHP 8+: マッチなし時は $matches[1] が未定義のため配列で受ける）
        $urls = array();
        if ( preg_match_all( '/(https?:\/\/[^\s"\'<>]+)/i', $content, $matches ) && ! empty( $matches[1] ) ) {
            $urls = array_unique( $matches[1] );
        }

        // コメント内のlinkとidまたはpostIdからIDを取得（大文字・小文字を区別しない）
        $post_ids = array();
        if ( preg_match_all( '/<!--.*?(?:link).*?(?:id|postId).*?(\d+).*?-->/i', $content, $id_matches ) && ! empty( $id_matches[1] ) ) {
            $post_ids = array_unique( $id_matches[1] );
        }

        // 投稿IDからURLを取得して$urlsに追加
        foreach ($post_ids as $post_id) {
            $post_url = get_permalink($post_id);
            if ($post_url) {
                $urls[] = $post_url;
            }
        }

        // 最終的なURLリストをユニーク化
        $urls = array_unique($urls);

        $has_outbound_link = false;
        foreach ($urls as $url) {
            $linked_post_id = url_to_postid($url);
            if ($linked_post_id && ($linked_post_id !== $post->ID)) { // 自己リンクを回避
                $edges[] = [
                    'from' => $post->ID,
                    'to' => $linked_post_id,
                ];
                $has_outbound_link = true;
            }
        }

        if ( ! $has_outbound_link ) {
            $no_outbound_links[] = array(
                'id'        => $post->ID,
                'title'     => $post->post_title,
                'post_type' => $post->post_type,
                'edit_url'  => get_edit_post_link( $post->ID ),
                'view_url'  => get_permalink( $post->ID ),
            );
        }
    }

    // Find reciprocal links and consolidate them
    $consolidated_edges = [];
    $added_edges = [];
    $inbound_links_count = [];

    foreach ($edges as $edge) {
        $reverse_edge = ['from' => $edge['to'], 'to' => $edge['from']];
        if (in_array($reverse_edge, $edges) && !in_array($reverse_edge, $added_edges)) {
            // Add the consolidated edge with arrows in both directions
            $consolidated_edges[] = ['from' => $edge['from'], 'to' => $edge['to'], 'arrows' => 'to, from'];
            $added_edges[] = $edge; // Mark the edge as added
            $added_edges[] = $reverse_edge; // Mark the reverse edge as added
        } elseif (!in_array($edge, $added_edges)) {
            $consolidated_edges[] = ['from' => $edge['from'], 'to' => $edge['to'], 'arrows' => 'to'];
        }

        if (!isset($inbound_links_count[$edge['to']])) {
            $inbound_links_count[$edge['to']] = 0;
        }
        $inbound_links_count[$edge['to']]++;
    }

    // 被リンクがゼロのページを検出
    foreach ( $nodes as $node ) {
        if ( ! isset( $inbound_links_count[ $node['id'] ] ) ) {
            $no_inbound_links[] = array(
                'id'        => $node['id'],
                'title'     => $node['label'],
                'post_type' => $node['post_type'],
                'edit_url'  => get_edit_post_link( $node['id'] ),
                'view_url'  => get_permalink( $node['id'] ),
            );
        }
    }

    // 公開済み投稿に属するタームのみ残す（投稿0のターム・タクソノミーは表示しない）
    $used_term_keys = array();
    foreach ( $nodes as $node ) {
        foreach ( $node['terms'] as $key ) {
            $used_term_keys[ $key ] = true;
        }
    }
    $terms_colors = array_intersect_key( $terms_colors, $used_term_keys );

    // 投稿タイプの表示ラベル（レスポンスで使用）
    $post_type_labels = array();
    foreach ( $post_types as $pt ) {
        $obj = get_post_type_object( $pt );
        $post_type_labels[ $pt ] = ( $obj && isset( $obj->labels->name ) ) ? $obj->labels->name : $pt;
    }

	$result = array(
		'nodes'              => $nodes,
		'edges'              => $consolidated_edges,
		'terms_colors'       => $terms_colors,
		'post_type_labels'   => $post_type_labels,
		'no_inbound_links'   => $no_inbound_links,
		'no_outbound_links'  => $no_outbound_links,
	);
	set_transient( 'lmi_dataset', $result, 0 );
	wp_send_json( $result );
}

add_action( 'wp_ajax_lmi_get_dataset', 'lmi_get_dataset' );
