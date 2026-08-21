<?php
/*-------------------------------------------*/
/* 旧シンボル名（whol_ 改名前）の残存を検知
/*-------------------------------------------*/
/**
 * 改名前の旧クラス名 `Woo_Order_Search_List`（接頭辞なし）が読み込まれていないか確認し、
 * 存在する場合は管理画面に警告を表示する。
 *
 * ★ グローバル記号を whol_ / WHOL_ へ改名した際、旧クラス名 `Woo_Order_Search_List` は
 * `WHOL_Order_Search_List` へ置き換えて削除した。にもかかわらずこの名前のクラスが
 * 定義されている場合、原因はプラグインフォルダの重複ではなく、有効テーマの functions.php に
 * 旧コード（改名前の本プラグインと同一の関数・クラス名）がそのまま残っていること。
 * このプラグインはもともとテーマへ直接書き込む形で配布していた時期があり、テーマ側の
 * 同名関数と衝突して Fatal error（Cannot redeclare）を起こしていたのはその名残であるため、
 * サイレントに無視せず管理者へ知らせる。
 *
 * ★ 旧コードには nonce 検証のない注文メモ送信の入口（`wp_ajax_etbs_woo_sendmailhit` への
 * 旧ハンドラー登録）が含まれている場合がある。プラグイン側のコールバックが先に登録され
 * nonce 不一致で即座に処理を打ち切るため通常は旧ハンドラーへ到達しないが、これはあくまで
 * 読み込み順に依存した緩和策に過ぎない。旧コードをテーマから取り除かない限り、この入口は
 * 塞がったことにはならない。
 *
 * ★ 判定はメニュースラッグ（woo-order-list）や $_GET['page'] では行わない。スラッグは
 * 改名の前後で意図的に据え置いており、旧コードが読み込まれているかどうかの判定材料には
 * ならない。クラスの実在有無だけが、旧コードが読み込まれているかを直接示す。
 *
 * @return void
 */
function whol_legacy_symbol_notice() {
	if ( ! class_exists( 'Woo_Order_Search_List' ) ) {
		return;
	}
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'Woo Hit Orderlist: 旧バージョンのコード（Woo_Order_Search_List）が現在のバージョンと同時に読み込まれています。',
				'woo-hit-orderlist'
			);
			?>
		</p>
		<p>
			<?php
			esc_html_e(
				'プラグインフォルダの重複ではなく、有効テーマの functions.php に旧コードが残っている可能性があります。',
				'woo-hit-orderlist'
			);
			?>
		</p>
		<p>
			<?php
			esc_html_e(
				'旧コードには nonce 検証のない注文メモ送信の入口が含まれている場合があります。テーマから旧コードを削除するまでこの経路は塞がりません。',
				'woo-hit-orderlist'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'whol_legacy_symbol_notice' );
