<?php
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
		$footer_html = '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.9.10/xlsx.full.min.js"></script>';
		$footer_html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.3/FileSaver.min.js"></script>';
		$footer_html .= <<< EOF
		<script>
		document.getElementById("dl-xlsx").addEventListener("click", function () {
		var wopts = {
			bookType: "xlsx",
			bookSST: false,
			type: "binary"
		};

		var workbook = {SheetNames: [], Sheets: {}};

		document.querySelectorAll("table.table-to-export").forEach(function (currentValue, index) {
			// sheet_to_workbook()の実装を参考に記述
			var n = currentValue.getAttribute("data-sheet-name");
			if (!n) {
			n = "Sheet" + index;
			}
			workbook.SheetNames.push(n);
			workbook.Sheets[n] = XLSX.utils.table_to_sheet(currentValue, wopts);
			workbook["Sheets"][n]["!cols"] = {$wid};
		});

		var wbout = XLSX.write(workbook, wopts);

		function s2ab(s) {
			var buf = new ArrayBuffer(s.length);
			var view = new Uint8Array(buf);
			for (var i = 0; i != s.length; ++i) {
			view[i] = s.charCodeAt(i) & 0xFF;
			}
			return buf;
		}

		saveAs(new Blob([s2ab(wbout)], {type: "application/octet-stream"}), "{$fname}");
		}, false);
		</script>
		EOF;
		return $footer_html;
	}
}

/*-------------------------------------------*/
/* CDN経由で Select2 の読み込み
/*-------------------------------------------*/
if ( ! function_exists( 'add_select2_script' ) ){
	function add_select2_script(){
		$script = <<< EOF
		<!-- Select2.css -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
		<!-- Select2本体 -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js"></script>
		<!-- Select2日本語化 -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/i18n/ja.js"></script>
		<script type="text/javascript">
		jQuery(function() {
			jQuery('#product').select2({
				language: "ja" //日本語化
			});
		})
		</script>
		EOF;
		return $script;
	}
}

/*-------------------------------------------*/
/* 送信するIDを取得してメッセージを送信
/*-------------------------------------------*/
if ( ! function_exists( 'etbs_woo_sendmailhit' ) ){
	function etbs_woo_sendmailhit(){
		list( $txt, $ids )= explode( '||', $_POST['mes'] );
		$order_note = $txt;
		$order_ids = explode( '##', $ids );
		foreach( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			$order->add_order_note( $order_note, 1 );
		}
		$msg = '送信が完了しました！';
		echo $msg;
		wp_die();
	}
	add_action( 'wp_ajax_etbs_woo_sendmailhit', 'etbs_woo_sendmailhit' );
	//add_action( 'wp_ajax_nopriv_etbs_woo_sendmailhit', 'etbs_woo_sendmailhit' );
}