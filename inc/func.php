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
if ( ! function_exists( 'whol_custom_single_add_to_cart_text' ) ){
	/**
	 * 商品詳細ページの「カートに入れる」ボタン文言を、抽選購入タグの有無で切り替える。
	 *
	 * ★ 接頭辞なしの関数名（旧 woocommerce_custom_single_add_to_cart_text）はテーマ側の
	 * 同名関数と衝突し Fatal error（Cannot redeclare）の原因になっていたため whol_ を付けて改名した。
	 *
	 * ★ 商品ループ外（WooCommerce Store API 等）から呼ばれると global $post, $product が
	 * null になり HTTP 500 の原因になっていたため、フィルタの第2引数で渡ってくる $product を
	 * 関数引数として受け取る形に修正した（accepted_args を 2 に変更）。
	 * あわせて get_the_terms() の戻り値未使用の呼び出し（タグ0件時に false を返し PHP8 で
	 * TypeError になっていた）を削除し、タグ判定は部分一致を避けるため has_term() に置き換えた。
	 *
	 * @param string          $text    デフォルトのボタン文言。$product が WC_Product でない場合はそのまま返す。
	 * @param WC_Product|null $product 対象商品。フィルタの第2引数から渡ってくる。
	 * @return string ボタンに表示する文言。
	 */
	function whol_custom_single_add_to_cart_text( $text, $product = null ) {
		if ( ! $product instanceof WC_Product ) {
			return $text;
		}
		return has_term( '抽選購入', 'product_tag', $product->get_id() )
			? '抽選に申し込む'
			: 'カートに入れる';
	}
	add_filter( 'woocommerce_product_single_add_to_cart_text', 'whol_custom_single_add_to_cart_text', 10, 2 );
}

if ( ! function_exists( 'whol_custom_product_add_to_cart_text' ) ){
	/**
	 * 商品一覧（アーカイブ／ショートコード等）の「カートに入れる」ボタン文言を、抽選購入タグの有無で切り替える。
	 *
	 * ★ 接頭辞なしの関数名（旧 woocommerce_custom_product_add_to_cart_text）はテーマ側の
	 * 同名関数と衝突し Fatal error（Cannot redeclare）の原因になっていたため whol_ を付けて改名した。
	 *
	 * ★ 商品ループ外（WooCommerce Store API 等）から呼ばれると global $post, $product が
	 * null になり HTTP 500 の原因になっていたため、フィルタの第2引数で渡ってくる $product を
	 * 関数引数として受け取る形に修正した（accepted_args を 2 に変更）。
	 * あわせて get_the_terms() の戻り値未使用の呼び出し（タグ0件時に false を返し PHP8 で
	 * TypeError になっていた）を削除し、タグ判定は部分一致を避けるため has_term() に置き換えた。
	 *
	 * @param string          $text    デフォルトのボタン文言。$product が WC_Product でない場合はそのまま返す。
	 * @param WC_Product|null $product 対象商品。フィルタの第2引数から渡ってくる。
	 * @return string ボタンに表示する文言。
	 */
	function whol_custom_product_add_to_cart_text( $text, $product = null ) {
		if ( ! $product instanceof WC_Product ) {
			return $text;
		}
		return has_term( '抽選購入', 'product_tag', $product->get_id() )
			? '抽選に申し込む'
			: 'カートに入れる';
	}
	add_filter( 'woocommerce_product_add_to_cart_text', 'whol_custom_product_add_to_cart_text', 10, 2 );
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
	/**
	 * チェックされた注文へ注文メモ（顧客宛メッセージ）を一括送信する admin-ajax ハンドラー。
	 *
	 * ★ ヘッダの `Requires Plugins: woocommerce` は導入時（有効化時）の守りにすぎない。
	 * WooCommerce が有効化後に停止された場合はこの関数が実行時に到達し、ガードが無いと
	 * wc_get_order() が未定義関数となり Fatal error（HTTP 500）になる。それを防ぐためのガード。
	 *
	 * @return void 送信結果を echo し wp_die() で応答する。
	 */
	function etbs_woo_sendmailhit(){
		check_ajax_referer( 'etbs_woo_sendmailhit', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( '権限がありません。', '', array( 'response' => 403 ) );
		}

		// WooCommerce が無効化されていた場合、wc_get_order() は未定義関数で Fatal error になる。
		// ヘッダの Requires Plugins は有効化時にしか働かないため、実行時にも改めて確認する。
		if ( ! function_exists( 'wc_get_order' ) ) {
			wp_die( 'WooCommerce が無効化されているため実行できません。', '', array( 'response' => 500 ) );
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
/*-------------------------------------------*/
/* サポート導線（ダッシュボードウィジェット）
/*-------------------------------------------*/
if ( ! function_exists( 'whol_add_dashboard_widget' ) ) {
	function whol_add_dashboard_widget() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) { return; }
		wp_add_dashboard_widget(
			'whol_dashboard_widget',
			'Woo Hit Orderlist',
			'whol_render_dashboard_widget'
		);
	}
	add_action( 'wp_dashboard_setup', 'whol_add_dashboard_widget' );
}

if ( ! function_exists( 'whol_render_dashboard_widget' ) ) {
	function whol_render_dashboard_widget() {
		$list_url = admin_url( 'admin.php?page=woo-order-list' );
		$memo_url = admin_url( 'admin.php?page=woo-comment-list' );
		?>
		<p>特定の商品を注文した方の中から抽選で当選者を選び、注文メモ経由でメッセージを送信できます。抽選販売の当選通知を想定しています。</p>

		<strong>使い方</strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li><strong>WooCommerce &gt; 注文検索</strong>で、商品と期間を指定して対象の注文を絞り込みます。</li>
			<li>「当選人数」を入力して<strong>ランダムチェック</strong>を押すと、その人数だけ無作為に選ばれます。</li>
			<li>送信メッセージを入力して<strong>メール送信</strong>を押すと、チェックした注文に注文メモが追加されます。</li>
			<li>送信済みの内容は<strong>WooCommerce &gt; 注文メモ検索</strong>で後から確認できます。</li>
		</ul>

		<strong>注意事項</strong>
		<ul style="margin:6px 0 12px 1.2em;list-style:disc;">
			<li>送信されるのは<strong>「顧客へのメモ」</strong>です。<strong>そのまま注文者にメールが届きます。</strong>文面を確認してから送信してください。</li>
			<li>送信は取り消せません。ランダムチェックの結果は送信前に必ず確認してください。</li>
			<li>商品に<strong>「抽選購入」タグ</strong>を付けると検索で絞り込みやすくなり、商品ページのボタンが「抽選に申し込む」に変わります。</li>
			<li>同じ商品が1つの注文の中で複数明細に分かれていても、<strong>1注文1行</strong>として扱われます（当選確率が偏らないようにするため）。</li>
		</ul>

		<strong>サポート</strong>
		<p style="margin:6px 0 12px;">有償サポートやカスタマイズは<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener">こちらのページ</a>からお問い合わせください。開発の継続は<a href="https://etbs.jp/product/donate/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener">ご支援</a>で応援いただけます。</p>

		<a href="<?php echo esc_url( $list_url ); ?>" class="button button-primary">注文検索を開く</a>
		<a href="<?php echo esc_url( $memo_url ); ?>" class="button">注文メモ検索を開く</a>
		<?php
	}
}

/*-------------------------------------------*/
/* サポート導線（プラグイン一覧の行）
/*-------------------------------------------*/
if ( ! function_exists( 'whol_plugin_row_meta' ) ) {
	function whol_plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WHOL_PLUGIN_FILE ) !== $file ) { return $links; }
		$links[] = '<a href="https://etbs.jp/product/donate/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
			. esc_html__( '開発を支援', 'woo-hit-orderlist' ) . '</a>';
		$links[] = '<a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener noreferrer">'
			. esc_html__( '開発のご依頼', 'woo-hit-orderlist' ) . '</a>';
		return $links;
	}
	add_filter( 'plugin_row_meta', 'whol_plugin_row_meta', 10, 2 );
}

/*-------------------------------------------*/
/* サポート導線（専用画面のフッター）
/*-------------------------------------------*/
if ( ! function_exists( 'whol_admin_footer_text' ) ) {
	function whol_admin_footer_text( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$targets = array( 'woocommerce_page_woo-order-list', 'woocommerce_page_woo-comment-list' );
		if ( ! $screen || ! in_array( $screen->id, $targets, true ) ) { return $text; }
		return 'Woo Hit Orderlistが役に立ったら <a href="https://etbs.jp/product/donate/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener noreferrer">開発を支援</a>、カスタマイズは <a href="https://etbs.jp/product-category/wordpress-tools/?utm_source=woo-hit-orderlist&utm_medium=plugin" target="_blank" rel="noopener noreferrer">開発のご依頼</a> からどうぞ。';
	}
	add_filter( 'admin_footer_text', 'whol_admin_footer_text' );
}
