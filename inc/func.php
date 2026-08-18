<?php
/*-------------------------------------------*/
/* HPOS（高性能注文ストレージ）互換の宣言
/*-------------------------------------------*/
if ( ! function_exists( 'whol_declare_hpos_compat' ) ) {
	function whol_declare_hpos_compat() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WHOL_PLUGIN_FILE, true );
		}
	}
	add_action( 'before_woocommerce_init', 'whol_declare_hpos_compat' );
}

/*-------------------------------------------*/
/* タクソノミーが存在しない場合に追加
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woocommerce_tag_exists' ) ){
    function etbs_woocommerce_tag_exists($tag_name, $tag_slug) {
        // タグが存在するか確認
        $term = get_term_by('name', $tag_name, 'product_tag');
        if (!$term) {
            // タグが存在しない場合に追加
            wp_insert_term(
                $tag_name, // タグ名
                'product_tag', // タクソノミー
                array(
                    'description' => '登録商品が多い場合はタグを追加すると、絞り込みし易くなります。',
                    'slug' => $tag_slug, // スラッグ
                )
            );
        }
    }
}

/*-------------------------------------------*/
/* 抽選購入のカテゴリーの場合、カートに追加ボタンを変更する
/*-------------------------------------------*/
if ( ! function_exists( 'woocommerce_custom_single_add_to_cart_text' ) ){
	function woocommerce_custom_single_add_to_cart_text() {
		global $post, $product;
		sizeof( get_the_terms( $post->ID, 'product_tag' ) );
		$str = htmlspecialchars( $product->get_tags() );
		$chkeck = '抽選購入';
		if ( strpos( $str, $chkeck ) === false ) {
			return __( 'カートに入れる', 'woocommerce' ); 
		} else {
			return __( '抽選に申し込む', 'woocommerce' );
		}
	}
	add_filter( 'woocommerce_product_single_add_to_cart_text', 'woocommerce_custom_single_add_to_cart_text' );
}

if ( ! function_exists( 'woocommerce_custom_product_add_to_cart_text' ) ){
	function woocommerce_custom_product_add_to_cart_text() {
		global $post, $product;
		sizeof( get_the_terms( $post->ID, 'product_tag' ) );
		$str = htmlspecialchars( $product->get_tags() );
		$chkeck = '抽選購入';
		if ( strpos( $str, $chkeck ) === false ) {
			return __( 'カートに入れる', 'woocommerce' ); 
		} else {
			return __( '抽選に申し込む', 'woocommerce' );
		}
	}
	add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_custom_product_add_to_cart_text' );
}

/*-------------------------------------------*/
/* ヘッダーCSS出力
/*-------------------------------------------*/
if ( ! function_exists( 'header_wol_css_html' ) ){
	function header_wol_css_html() {
		$header_html = <<< EOF
		<style type="text/css">
		.order-list-table{
		border-collapse: separate;
		border-spacing: 0px;
		border-top: 1px solid #ccc;
		border-left: 1px solid #ccc;
		}
		.order-list-table th{
		padding: 4px;
		text-align: left;
		vertical-align: top;
		color: #444;
		background-color: #ccc;
		border-top: 1px solid #fff;
		border-left: 1px solid #fff;
		border-right: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		}
		.order-list-table td{
		padding: 4px;
		background-color: #fafafa;
		border-right: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		}
		.order-list-table tr:nth-child(even) td{
		background-color: #f0f0f3;
		}
		.ml10 {margin-left: 10px!important;}
		</style>
		EOF;
		return $header_html;
	}
}

/*-------------------------------------------*/
/* エクセル出力
/*-------------------------------------------*/
if ( ! function_exists( 'footer_wol_xlsx_html' ) ){
	function footer_wol_xlsx_html( $wid, $fname ) {
		// SheetJS は同梱（外部CDNは使わない）。XLSX.writeFile() が保存まで行うため FileSaver は不要。
		wp_enqueue_script(
			'whol-xlsx',
			plugins_url( 'js/xlsx.full.min.js', __FILE__ ),
			array(),
			'0.20.3',
			true
		);

		$inline = 'document.addEventListener("DOMContentLoaded", function () {'
			. 'var btn = document.getElementById("dl-xlsx");'
			. 'if ( ! btn ) { return; }'
			. 'btn.addEventListener("click", function () {'
			. 'var wopts = { bookType: "xlsx", bookSST: false, type: "binary" };'
			. 'var workbook = { SheetNames: [], Sheets: {} };'
			. 'document.querySelectorAll("table.table-to-export").forEach(function (currentValue, index) {'
			. 'var n = currentValue.getAttribute("data-sheet-name") || ("Sheet" + index);'
			. 'workbook.SheetNames.push(n);'
			. 'workbook.Sheets[n] = XLSX.utils.table_to_sheet(currentValue, wopts);'
			. 'workbook["Sheets"][n]["!cols"] = ' . $wid . ';'
			. '});'
			. 'XLSX.writeFile(workbook, ' . wp_json_encode( $fname ) . ');'
			. '}, false);'
			. '});';

		wp_add_inline_script( 'whol-xlsx', $inline );

		return '';
	}
}

/*-------------------------------------------*/
/* Select2 は WooCommerce 同梱のものを使う（外部CDNは使わない）
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_wol_enqueue_enhanced_select' ) ){
	function etbs_wol_enqueue_enhanced_select(){
		// wc-enhanced-select は selectWoo に依存し、日本語文言も WooCommerce の翻訳から供給される。
		// 対象の <select> に .wc-enhanced-select を付けておけば、読み込み時に自動で初期化される。
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}
}

/*-------------------------------------------*/
/* 送信するIDを取得してメッセージを送信
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_sendmailhit' ) ){
	function etbs_woo_sendmailhit(){
		check_ajax_referer( 'etbs_woo_sendmailhit', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( '権限がありません。', '', array( 'response' => 403 ) );
		}

		$order_note = isset( $_POST['mes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mes'] ) ) : '';
		$ids_raw    = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';

		if ( '' === $order_note || '' === $ids_raw ) {
			wp_die( '送信内容が不正です。', '', array( 'response' => 400 ) );
		}

		$order_ids = array_unique( array_filter( array_map( 'absint', explode( '##', $ids_raw ) ) ) );

		$sent = 0;
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$order->add_order_note( $order_note, 1 );
			$sent++;
		}

		echo esc_html( sprintf( '送信が完了しました！（%d件）', $sent ) );
		wp_die();
	}
	add_action( 'wp_ajax_etbs_woo_sendmailhit', 'etbs_woo_sendmailhit' );
	//add_action( 'wp_ajax_nopriv_etbs_woo_sendmailhit', 'etbs_woo_sendmailhit' );
}
