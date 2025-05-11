<?php
/**
 * Plugin Name:       Woo Hit Orderlist
 * Description:       特定の商品を注文したユーザーにランダムでメッセージを送信可能なプラグインです。抽選・当選メールなどを注文メモで送信することを想定しています。登録商品が多い場合は”抽選購入”のタグを追加すると、絞り込みし易くなります。
 * Version:           0.1.1
 * Requires at least: 6.7
 * Requires PHP:      8.3
 * Author: DAI
 * Author URI: https://etbs.jp
 * Plugin URI: https://etbs.jp/product/woo-hit-orderlist/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-hit-orderlist
 */

require_once( dirname( __FILE__ ) . '/inc/func.php' );
require_once( dirname( __FILE__ ) . '/inc/woo-orderlist.php' );
require_once( dirname( __FILE__ ) . '/inc/commentsearch.php' );

/*-------------------------------------------*/
/* プラグインのアップデートチェック
/*-------------------------------------------*/
require 'inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/etbsjp/woo-hit-orderlist/',
	__FILE__,
	'woo-order-list'
);
$myUpdateChecker->setBranch( 'dist' );

/*-------------------------------------------*/
/* プラグインを有効化したときに実行
/*-------------------------------------------*/
if ( ! function_exists( 'woohitorderlist_plugin_activate' ) ){
	function woohitorderlist_plugin_activate() {
		// WooCommerceが有効化されているか確認
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			etbs_woocommerce_tag_exists('抽選購入', 'lottery');
		}
	}
	register_activation_hook(__FILE__ , 'woohitorderlist_plugin_activate');
}