<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function etbs_woohitorderlist_uninstall() {
	//delete_option('woohitorderlist');
}

etbs_woohitorderlist_uninstall();

?>