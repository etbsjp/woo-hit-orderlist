<?php
/*-------------------------------------------*/
/* 旧シンボル名（whol_ 改名前）の残存を検知
/*-------------------------------------------*/
/**
 * 改名前の旧グローバル記号（接頭辞なし、または `etbs_` 接頭辞のみ）が読み込まれていないか
 * 確認し、存在する場合は管理画面に警告を表示する。
 *
 * ★ グローバル記号を whol_ / WHOL_ へ改名した際、下記の固定リスト（8個）の記号は
 * 現行のプラグイン内では既にすべて whol_ / WHOL_ 接頭辞の名前へ置き換えて削除した。
 * にもかかわらずこれらの名前が定義されている場合、原因はプラグインフォルダの重複ではなく、
 * 有効テーマの functions.php に旧コード（改名前の本プラグインと同一の関数・クラス名）が
 * そのまま残っていること。このプラグインはもともとテーマへ直接書き込む形で配布していた
 * 時期があり、テーマ側の同名関数と衝突して Fatal error（Cannot redeclare）を起こしていたのは
 * その名残であるため、サイレントに無視せず管理者へ知らせる。
 *
 * ★★ task-queue #109（2026-08-23）で、実在するテーマ内の唯一の旧コピー
 * （社内で読み取り専用のまま保全した検証用ファイル）を
 * 使って実測した結果、この旧コピーは「プラグインの一部だけを含む部分コピー」だった。
 * 具体的には `Woo_Comment_Search_List` クラスや `etbs_woocommerce_tag_exists()` 関数は
 * 持たない一方、#98 で洗い出した旧記号リストには無かった `add_select2_script()`（Select2 を
 * CDN 経由で読み込む関数。無接頭辞の汎用名）を独自に持っていた。つまり「クラスの実在有無
 * だけを見れば旧コードの有無が分かる」という単純な仮定は実物と食い違う。この関数は現在の
 * プラグインでは対応物の名前が異なる（`whol_enqueue_enhanced_select()`）ため、旧コード側の
 * 単独の関数だけが残っているケースはクラスの実在チェックだけでは検出できない
 * （実測: task-queue #109 手順2の8）。
 *
 * そのため本関数は、クラス1個だけでなく実測に基づいた固定リスト（8個。クラス2個＋関数6個）
 * それぞれについて `class_exists()` / `function_exists()` で存在確認し、見つかった記号ごとに
 * `ReflectionClass` / `ReflectionFunction` の `getFileName()` / `getStartLine()` で宣言元の
 * ファイル・行番号を管理者へ提示する。ただしこれも「実測で確認できた8個」の固定リストに
 * 過ぎず、旧コードの改変・部分コピーのバリエーションを網羅的に検出できるわけではない
 * （網羅的な検出には静的解析等が必要で、本関数のスコープ外）。
 *
 * ★ `add_select2_script` は無接頭辞の汎用名であり、本プラグインと無関係のテーマ・
 * プラグインが同名の関数を偶然持っている可能性がある。この記号だけが見つかった場合に
 * 「whol の旧コードが確実にある」と断定する文言にはせず、Reflection で取得した宣言元
 * ファイルを確認するよう促す文言にしている。
 *
 * ★ 旧コードには nonce 検証のない注文メモ送信の入口（`wp_ajax_etbs_woo_sendmailhit` への
 * 旧ハンドラー登録）が含まれている場合がある。プラグイン側のコールバックが先に登録され
 * nonce 不一致で即座に処理を打ち切るため通常は旧ハンドラーへ到達しないが、これはあくまで
 * 読み込み順に依存した緩和策に過ぎない。旧コードをテーマから取り除かない限り、この入口は
 * 塞がったことにはならない。
 *
 * ★ 判定はメニュースラッグ（woo-order-list）や $_GET['page'] では行わない。スラッグは
 * 改名の前後で意図的に据え置いており、旧コードが読み込まれているかどうかの判定材料には
 * ならない。
 *
 * ★ 画面を限定せず（`admin_notices` に無条件で）表示する。旧コードは AJAX 経由でどの画面
 * からでも到達し得るため、特定の画面でだけ警告しても見落としのリスクが残るため。
 *
 * @return void
 */
function whol_legacy_symbol_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$found = whol_detect_legacy_symbols();

	if ( empty( $found ) ) {
		return;
	}

	whol_render_legacy_symbol_notice( $found );
}
add_action( 'admin_notices', 'whol_legacy_symbol_notice' );

/**
 * 改名前の旧グローバル記号の固定リストを返す。
 *
 * task-queue #98 で洗い出した旧記号名と、#109 で実測した実物の旧コピーが
 * 実際に持っていた記号名を合わせた8個（クラス2個＋関数6個）。
 *
 * @return array<int, array{type: string, name: string}> type は 'class' または 'function'。
 */
function whol_legacy_symbol_targets() {
	return array(
		array(
			'type' => 'class',
			'name' => 'Woo_Order_Search_List',
		),
		array(
			'type' => 'class',
			'name' => 'Woo_Comment_Search_List',
		),
		array(
			'type' => 'function',
			'name' => 'header_wol_css_html',
		),
		array(
			'type' => 'function',
			'name' => 'footer_wol_xlsx_html',
		),
		array(
			'type' => 'function',
			'name' => 'etbs_wol_enqueue_enhanced_select',
		),
		array(
			'type' => 'function',
			'name' => 'etbs_woo_sendmailhit',
		),
		array(
			'type' => 'function',
			'name' => 'etbs_woocommerce_tag_exists',
		),
		array(
			'type' => 'function',
			'name' => 'add_select2_script',
		),
	);
}

/**
 * 固定リストの旧グローバル記号のうち、実際に読み込まれているものを検出する。
 *
 * 見つかった記号ごとに `ReflectionClass` / `ReflectionFunction` で宣言元の
 * ファイル・行番号を取得する。ファイルパスは `ABSPATH` を除いた相対パスで返す。
 *
 * @return array<int, array{name: string, file: string, line: int|null}> 見つかった記号の一覧。
 */
function whol_detect_legacy_symbols() {
	$found = array();

	foreach ( whol_legacy_symbol_targets() as $target ) {
		if ( 'class' === $target['type'] ) {
			if ( ! class_exists( $target['name'], false ) ) {
				continue;
			}
			$reflection = new ReflectionClass( $target['name'] );
		} else {
			if ( ! function_exists( $target['name'] ) ) {
				continue;
			}
			$reflection = new ReflectionFunction( $target['name'] );
		}

		$found[] = array(
			'name' => $target['name'],
			'file' => whol_relative_path( $reflection->getFileName() ),
			'line' => $reflection->getStartLine() ?: null,
		);
	}

	return $found;
}

/**
 * 絶対パスから ABSPATH を除いた相対パスを返す。
 *
 * PHP 内蔵クラス等、`getFileName()` が false を返す場合も考慮する。
 *
 * @param string|false $absolute_path 絶対パス。
 * @return string 相対パス。取得できない場合は '(不明)'。
 */
function whol_relative_path( $absolute_path ) {
	if ( ! $absolute_path ) {
		return '(不明)';
	}

	$normalized_abspath = wp_normalize_path( ABSPATH );
	$normalized_path    = wp_normalize_path( $absolute_path );

	if ( 0 === strpos( $normalized_path, $normalized_abspath ) ) {
		return substr( $normalized_path, strlen( $normalized_abspath ) );
	}

	return $normalized_path;
}

/**
 * 旧グローバル記号の検出結果から、管理画面の警告バナーを出力する。
 *
 * @param array<int, array{name: string, file: string, line: int|null}> $found whol_detect_legacy_symbols() の戻り値。
 * @return void
 */
function whol_render_legacy_symbol_notice( array $found ) {
	$has_generic_symbol = false;
	foreach ( $found as $item ) {
		if ( 'add_select2_script' === $item['name'] ) {
			$has_generic_symbol = true;
			break;
		}
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'Woo Hit Orderlist: 旧バージョンのコードと同名のクラス・関数が現在のバージョンと同時に読み込まれています。', 'woo-hit-orderlist' ); ?>
		</p>
		<ul style="list-style: disc; margin-left: 2em;">
			<?php foreach ( $found as $item ) : ?>
				<li>
					<code><?php echo esc_html( $item['name'] ); ?></code>
					<?php if ( null !== $item['line'] ) : ?>
						&mdash;
						<?php
						printf(
							/* translators: 1: 宣言元ファイルの相対パス, 2: 宣言元の行番号 */
							esc_html__( '宣言元: %1$s %2$d 行目', 'woo-hit-orderlist' ),
							esc_html( $item['file'] ),
							(int) $item['line']
						);
						?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p>
			<?php esc_html_e( 'プラグインフォルダの重複ではなく、有効テーマの functions.php に旧コードが残っている可能性があります。', 'woo-hit-orderlist' ); ?>
		</p>
		<p>
			<?php esc_html_e( '上記の宣言元ファイルを確認してください。', 'woo-hit-orderlist' ); ?>
		</p>
		<?php if ( $has_generic_symbol ) : ?>
			<p>
				<?php esc_html_e( '「add_select2_script」は無接頭辞の汎用的な関数名のため、本プラグインと無関係のテーマ・プラグインが同名の関数を偶然持っている可能性があります。', 'woo-hit-orderlist' ); ?>
			</p>
			<p>
				<?php esc_html_e( '上記の宣言元ファイルを見て、本プラグインの旧コードかどうかを判断してください。', 'woo-hit-orderlist' ); ?>
			</p>
		<?php endif; ?>
		<p>
			<?php esc_html_e( '旧コードには nonce 検証のない注文メモ送信の入口が含まれている場合があります。', 'woo-hit-orderlist' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'テーマから旧コードを削除するまでこの経路は塞がりません。', 'woo-hit-orderlist' ); ?>
		</p>
	</div>
	<?php
}
