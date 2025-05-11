<?php

class Woo_Order_Search_List {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10, 2 );
	}

	public static function add_menu() {
		$page_title = '注文検索';
		$menu_title = '注文検索';
		$capability = 'edit_pages';
		$menu_slug  = 'woo-order-list';
		$function   = array( __CLASS__, 'list_page' );
		add_submenu_page( 'woocommerce', $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public static function list_page() {
		if(isset($_GET['d1'])) { 
			$day1 = $_GET['d1']; 
		} else {
			$day1 = date("Y-m-d", strtotime("-7 day"));
		}

		if(isset($_GET['d2'])) { 
			$day2 = $_GET['d2']; 
		} else {
			$day2 = date("Y-m-d", strtotime("-1 day"));
		}

		if(isset($_GET['tag'])) { 
			$tag = $_GET['tag']; 
		} else {
			$tag = 'lottery'; // 抽選購入
		}

		if(isset($_GET['product'])) {
			$product = $_GET['product'];
		} else {
			$product = 'none';
		} 

		if(isset($_GET['status'])) {
			$status = $_GET['status'];
		} else {
			$status = 'exclude';
		} 

		echo add_select2_script();
		echo header_wol_css_html();

		$tag_html    = '<option value="lottery" ' . selected( $tag, 'lottery', false ) . '>抽選購入</option><option value="all" ' . selected( $tag, 'all', false ) . '>全て</option>';
		$status_html = '<option value="exclude" ' . selected( $status, 'exclude', false ) . '>キャンセル、失敗、返金を除外</option><option value="all" ' . selected( $status, 'all', false ) . '>全て</option>';

		if ( $tag == 'all' ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'title',
			);
		} else {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'title',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_tag',
						'field' => 'slug',
						'terms' => array($tag)
					)
				)
			);
		}
		$woo_product_posts = get_posts( $args );
		$product_html = '<option value="none">商品を選択してください</option>';
		foreach ( $woo_product_posts as $woo_product_post ) {
			$product_id = $woo_product_post->ID;
			$product_name = $woo_product_post->post_title;
			$product_html .= '<option value="' . $product_id . '" ' . selected( $product, $product_id, false ) . '>' . $product_name . '</option>';
		}

		$ajaxurl = admin_url( 'admin-ajax.php');
		$body_html1 = <<< EOF
		<script>
		function btn1Click(num){
			var d1;
			d1 = document.getElementById("day1").value;
			var d2;
			d2 = document.getElementById("day2").value;
			var tag;
			tag = document.getElementById("tag").value;
			var product;
			if(num == 99){
				product = 'none';
			} else {
				product = document.getElementById("product").value;
			}
			var status;
			status = document.getElementById("status").value;
			window.open("admin.php?page=woo-order-list&d1=" + d1 + "&d2=" + d2 + "&tag=" + tag + "&product=" + product + "&status=" + status + "","_parent");
		}
		jQuery(function(){
			jQuery('#tag').change(function(){
				btn1Click(99);
			});
		});

		function randchk(){
			// ページ内のすべてのチェックボックスを取得
			const checkboxes = document.querySelectorAll('input[type="checkbox"]');
			// チェックボックスの数を取得
			const totalCheckboxes = checkboxes.length;
			// ランダムにチェックを入れる数を決定
			const randomCount = document.getElementById("winners").value;
			if(randomCount == 0){
				alert("当選人数を入力してください");
				return false;
			}
			// チェックボックスのインデックスをランダムに選択
			const randomIndexes = new Set();
			while (randomIndexes.size < randomCount) {
				const randomIndex = Math.floor(Math.random() * totalCheckboxes);
				randomIndexes.add(randomIndex);
			}
			// 選択されたインデックスのチェックボックスにチェックを入れる
			randomIndexes.forEach(index => {
				checkboxes[index].checked = true;
			});
		}
		function pagechkclear(){
			const checkboxes = document.querySelectorAll('input[type="checkbox"]');
			checkboxes.forEach(checkbox => {
				checkbox.checked = false;
			});
		}

		function sendmailhit(){
			const send_msg = document.getElementById("send_msg");
			if(send_msg.value.trim() === ""){
				alert("送信メッセージを入力してください");
				return false;
			} else {
				const userResponse = confirm('この内容でチェックの入っている注文者にメールを送信します');
				if (userResponse) {
					woo_sendmailhit();
					pagechkclear();
				} else {
					return false;
				}
			}
		}

		function woo_sendmailhit(){
			var num = 0;
			jQuery('input[type="checkbox"]').each(function() {
				if (jQuery(this).is(':checked')) {
					const id = jQuery(this).attr('id');
					const match = id.match(/lottery\[(\d+)\]/);
					if (match) {
						const number = match[1];
						if(num == 0){
							num = number;
						} else {
							num = num +  "##"  + number;
						}
					}
				}
			});
			if( num == 0 ){
				alert("チェックされていません");
				return false;
			} else {
				const send_msg = document.getElementById("send_msg").value;
				jQuery.ajax({
					type: "POST",
					url: "{$ajaxurl}",
					data: {
						"action": "etbs_woo_sendmailhit",
						"mes"   : send_msg + "||" + num,
					},
					success: function( response ){
						alert( response );
					}
				});
				return false;
				alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
			}
		}

		</script>
		<h1>注文検索</h1>
		<p>商品タグ: <select name="tag" id="tag" class="ml10">{$tag_html}</select>&emsp;
		商品名:&nbsp; <select name="product" id="product" class="ml10">{$product_html}</select>&emsp;
		ステータス: <select name="status" id="status" class="ml10">{$status_html}</select></p>
		<p>検索開始日: <input type="date" name="day1" id="day1" value="{$day1}">&emsp;検索終了日: <input type="date" name="day2" id="day2" value="{$day2}">&emsp;
		<input type="button" class="button button-primary ml10" value="検索" onclick="btn1Click();">&emsp;<button type="button" class="button button-primary" id="dl-xlsx">Download XLSX</button></p>
		EOF;
		echo $body_html1;

		$table1 = Woo_Order_Search_List::my_table($day1, $day2, $product, $status);
		echo $table1;

		$body_html2 = <<< EOF
		<p>当選人数<input type="number" name="winners" id="winners" class="ml10">&emsp;
		<input type="button" class="button button-primary ml10" value="ランダムチェック" onclick="randchk()">&emsp;
		<input type="button" class="button button-primary" value="チェッククリア" onclick="pagechkclear()"></p>
		<p>送信メッセージ: <br><textarea name="send_msg" id="send_msg" cols="100" rows="20"></textarea><br>
		<input type="button" class="button button-primary" value="メール送信" onclick="sendmailhit()"></p>
		EOF;
		echo $body_html2;

		$wid = '[{ wpx : 2 },{ wpx : 50 },{ wpx : 144 },{ wpx : 121 },{ wpx : 121 },{ wpx : 144 },{ wpx : 144 },{ wpx : 72 }]';
		$fname = '注文検索結果一覧.xlsx';
		echo footer_wol_xlsx_html( $wid, $fname );

	}

	public static function my_table($day1, $day2, $product, $status) {
		$body_table ='<div id="T_del" class="woo-order-list">';
		$body_table .='<table class="order-list-table table-to-export" data-sheet-name="注文検索結果一覧">';
		$body_table .= <<< EOF
		<thead>
			<tr>
				<th></th>
				<th>注文番号</th>
				<th>商品名</th>
				<th>受注日</th>
				<th>受注者名</th>
				<th>受注者メール</th>
				<th>更新日</th>
				<th>注文ステータス</th>
			</tr>
		</thead>
		EOF;

		global $wpdb;
		if ( $status == 'exclude' ) {
			$query = "SELECT TableD.order_id, TableB.last_name, TableA.first_name, TableC.email, TableD.product_id, TableE.product_name, TableF.order_date, TableF.order_modified, TableF.status FROM 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'first_name' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_first_name') TableA INNER JOIN 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'last_name' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_last_name') TableB
				ON TableA.post_id = TableB.post_id INNER JOIN 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'email' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_email') TableC 
				ON TableA.post_id = TableC.post_id INNER JOIN
				(SELECT {$wpdb->prefix}wc_order_product_lookup.order_id, {$wpdb->prefix}wc_order_product_lookup.product_id FROM {$wpdb->prefix}wc_order_product_lookup WHERE {$wpdb->prefix}wc_order_product_lookup.product_id = %s) TableD
				ON TableA.post_id = TableD.order_id INNER JOIN
				(SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_title AS 'product_name' FROM {$wpdb->prefix}posts) TableE
				ON TableD.product_id = TableE.ID INNER JOIN
				(SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_date AS 'order_date', {$wpdb->prefix}posts.post_modified AS 'order_modified', {$wpdb->prefix}posts.post_status AS 'status' FROM {$wpdb->prefix}posts WHERE {$wpdb->prefix}posts.post_type = 'shop_order' AND {$wpdb->prefix}posts.post_status NOT IN ('wc-cancelled', 'wc-failed', 'wc-refunded') AND {$wpdb->prefix}posts.post_date BETWEEN %s AND %s) TableF
				ON TableA.post_id = TableF.ID
				ORDER BY TableD.order_id ASC;";
		} else {
			$query = "SELECT TableD.order_id, TableB.last_name, TableA.first_name, TableC.email, TableD.product_id, TableE.product_name, TableF.order_date, TableF.order_modified, TableF.status FROM 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'first_name' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_first_name') TableA INNER JOIN 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'last_name' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_last_name') TableB
				ON TableA.post_id = TableB.post_id INNER JOIN 
				(SELECT {$wpdb->prefix}postmeta.post_id, {$wpdb->prefix}postmeta.meta_value AS 'email' FROM {$wpdb->prefix}postmeta WHERE {$wpdb->prefix}postmeta.meta_key = '_billing_email') TableC 
				ON TableA.post_id = TableC.post_id INNER JOIN
				(SELECT {$wpdb->prefix}wc_order_product_lookup.order_id, {$wpdb->prefix}wc_order_product_lookup.product_id FROM {$wpdb->prefix}wc_order_product_lookup WHERE {$wpdb->prefix}wc_order_product_lookup.product_id = %s) TableD
				ON TableA.post_id = TableD.order_id INNER JOIN
				(SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_title AS 'product_name' FROM {$wpdb->prefix}posts) TableE
				ON TableD.product_id = TableE.ID INNER JOIN
				(SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_date AS 'order_date', {$wpdb->prefix}posts.post_modified AS 'order_modified', {$wpdb->prefix}posts.post_status AS 'status' FROM {$wpdb->prefix}posts WHERE {$wpdb->prefix}posts.post_type = 'shop_order' AND {$wpdb->prefix}posts.post_date BETWEEN %s AND %s) TableF
				ON TableA.post_id = TableF.ID
				ORDER BY TableD.order_id ASC;";
		}
		$lists = $wpdb->get_results(
			$wpdb->prepare($query, $product, $day1, $day2), 'ARRAY_A' );

		foreach ( $lists as $list ){
			$body_table .= '<tr><td><input type="checkbox" id="lottery[' . $list['order_id'] . ']" name="lottery[' . $list['order_id'] . ']" /></td>';
			$body_table .= '<td><a href="' . admin_url() . 'post.php?post=' . $list['order_id'] . '&action=edit">' . $list['order_id'] . '</a></td>';
			$body_table .= '<td>' . $list['product_name'] . '</td>';
			$body_table .= '<td>' . $list['order_date'] . '</td>';
			$body_table .= '<td>' . $list['first_name'] . ' ' . $list['last_name'] . '</td>';
			$body_table .= '<td>' . $list['email'] . '</td>';
			$body_table .= '<td>' . $list['order_modified'] . '</td>';
			$body_table .= '<td>' . Woo_Order_Search_List::status_jpn( $list['status'] ) . '</td></tr>';
		}

		$body_table .='</table></div>';
		return $body_table;
	}

	public static function status_jpn($key) {
		$status = array(
			'wc-pending' => '保留中',
			'wc-processing' => '処理中',
			'wc-on-hold' => '保留中',
			'wc-completed' => '完了',
			'wc-cancelled' => 'キャンセル',
			'wc-refunded' => '返金済み',
			'wc-failed' => '失敗'
		);
		return $status[$key];
	}

}
Woo_Order_Search_List::init();