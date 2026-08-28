<?php
/**
 * Plugin Name:       Woo Hit Orderlist
 * Description:       特定の商品を注文したユーザーにランダムでメッセージを送信可能なプラグインです。抽選・当選メールなどを注文メモで送信することを想定しています。登録商品が多い場合は”抽選購入”のタグを追加すると、絞り込みし易くなります。
 * Version:           1.2.1
 * Requires Plugins:  woocommerce
 * Requires PHP:      7.4
 * Author: ETBS (DAI)
 * Author URI: https://etbs.jp
 * Plugin URI: https://etbs.jp/product/woo-hit-orderlist/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-hit-orderlist
 */

define( 'WHOL_PLUGIN_FILE', __FILE__ );

require_once( dirname( __FILE__ ) . '/inc/func.php' );
require_once( dirname( __FILE__ ) . '/inc/woo-orderlist.php' );
require_once( dirname( __FILE__ ) . '/inc/commentsearch.php' );
require_once( dirname( __FILE__ ) . '/inc/legacy-symbol-notice.php' );

/*-------------------------------------------*/
/* プラグインのアップデートチェック
/*-------------------------------------------*/
require 'inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$whol_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/etbsjp/woo-hit-orderlist/',
	__FILE__,
	'woo-order-list'
);
$whol_update_checker->setBranch( 'dist' );

/*-------------------------------------------*/
/* プラグインを有効化したときに実行
/*-------------------------------------------*/
function whol_plugin_activate() {
	// WooCommerceが有効化されているか確認
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		whol_ensure_product_tag('抽選購入', 'lottery');
	}
}
register_activation_hook(__FILE__ , 'whol_plugin_activate');