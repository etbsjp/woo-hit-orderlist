<?php

class Woo_Comment_Search_List {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10, 2 );
	}

	public static function add_menu() {
		$page_title = '注文メモ検索';
		$menu_title = '注文メモ検索';
		$capability = 'edit_pages';
		$menu_slug  = 'woo-comment-list';
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

		if(isset($_GET['keyword'])) {
			$keyword = $_GET['keyword']; 
			if( $keyword == 'none') {
				$keyword = '';
			}
		} else {
			$keyword = '';
		}

		echo header_wol_css_html();

		$ajaxurl = admin_url( 'admin-ajax.php');
		$body_html = <<< EOF
		<script>
		function btn1Click(){
			var d1;
			d1 = document.getElementById("day1").value;
			var d2;
			d2 = document.getElementById("day2").value;
			var keyword;
			keyword = document.getElementById("keyword").value;
			if (keyword == '') {
				keyword = 'none';
			}
			window.open("admin.php?page=woo-comment-list&d1=" + d1 + "&d2=" + d2 + "&keyword=" + keyword + "","_parent");
		}
		</script>
		<h1>注文メモ検索</h1>
		<p>検索開始日: <input type="date" name="day1" id="day1" value="{$day1}">&emsp;検索終了日: <input type="date" name="day2" id="day2" value="{$day2}">&emsp;
		検索キーワード: <input type="text" name="keyword" id="keyword" value="{$keyword}">&emsp;
		<input type="button" class="button button-primary ml10" value="検索" onclick="btn1Click();">&emsp;<button type="button" class="button button-primary" id="dl-xlsx">Download XLSX</button></p>
		EOF;
		echo $body_html;

		$table1 = Woo_Comment_Search_List::my_table($day1, $day2, $keyword);
		echo $table1;

		$wid = '[{ wpx : 72 },{ wpx : 50 },{ wpx : 144 },{ wpx : 216 }]';
		$fname = '注文メモ検索一覧.xlsx';
		echo footer_wol_xlsx_html( $wid, $fname );

	}

	public static function my_table($day1, $day2, $keyword) {
		$body_table ='<div id="T_del" class="woo-order-list">';
		$body_table .='<table class="order-list-table table-to-export" data-sheet-name="注文メモ検索一覧">';
		$body_table .= <<< EOF
		<thead>
			<tr>
				<th>コメントID</th>
				<th>注文番号</th>
				<th>コメント日時</th>
				<th>コメント内容</th>
			</tr>
		</thead>
		EOF;

		global $wpdb;
		if ( $keyword == '' ) {
			$query = "SELECT comment_ID, comment_post_ID, comment_date, comment_content FROM $wpdb->comments 
				WHERE comment_type = %s AND comment_date BETWEEN %s AND %s";
		} else {
			$query = "SELECT comment_ID, comment_post_ID, comment_date, comment_content FROM $wpdb->comments 
				WHERE comment_type = %s AND comment_date BETWEEN %s AND %s AND comment_content LIKE '%{$keyword}%'";
		}
		$lists = $wpdb->get_results(
			$wpdb->prepare( $query, 'order_note', $day1, $day2 ), 'ARRAY_A' );
		
		foreach ( $lists as $list ){
			$body_table .= '<tr><td>' . $list['comment_ID'] . '</td>';
			$body_table .= '<td><a href="' . admin_url() . 'post.php?post=' . $list['comment_post_ID'] . '&action=edit">' . $list['comment_post_ID'] . '</a></td>';
			$body_table .= '<td>' . $list['comment_date'] . '</td>';
			$body_table .= '<td>' . $list['comment_content'] . '</td>';
			$body_table .= '</tr>';
		}

		$body_table .='</table></div>';
		return $body_table;
	}

}
Woo_Comment_Search_List::init();