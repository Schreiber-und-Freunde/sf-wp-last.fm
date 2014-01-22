<?
/*
Plugin Name: WP last.fm
Description: last.fm API Integration for Wordpress
Version: 0.1
Author: Schreiber & Freunde GmbH
Author URI: http://www.schreiber-freunde.de
*/

class SfWpLastfm
{
	// singleton instance
	private static $instance;

	private $url;
	private $result;
	private $is_ready = true;

	public static function instance() {
		if ( isset( self::$instance ) )
			return self::$instance;

		self::$instance = new SfWpLastfm;
		return self::$instance;
	}

	function __construct() {
		add_action( 'init', array(&$this, 'init'));
		add_action( 'admin_menu', array( &$this, 'add_pages' ), 30 );		
	}

	function init() {

		if( isset($_REQUEST['sfwp_lastfm_action']) ) {
			if( $_REQUEST['sfwp_lastfm_action'] == 'save_options' ) {
				$this->save_options();
			}
		}

		$api_key = get_option('lastfm_api_key');
		
		if( $api_key === false ) {
			$this->is_ready = false;
			add_action('admin_notices', array( &$this, 'admin_notice_missing_account_data'));
			return;
		}

		$this->url = 'http://ws.audioscrobbler.com/2.0/?api_key=' . $api_key . '&format=json&method=';

		if( isset($_REQUEST['sfwp_lastfm_action']) ) {
			if( $_REQUEST['sfwp_lastfm_action'] == 'test' ) {
				$this->test();
			}
		}
	}

	function admin_notice_missing_account_data() {
		echo '<div class="error"><p>' . __('WP last.fm: Please go to the options page and fill in your account details.', 'sf_wp_lastfm') . '</p></div>';
	}

	function add_pages() {
		add_options_page( 'last.fm', 'last.fm', 'manage_options', 'sfwp_lastfm_options', array( &$this, 'page_options'));
	}

	function save_options() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'sfwp_lastfm_action_save_options' ) ) {
			wp_die( __('Nonce check failed', 'sf_wp_lastfm') );
			return;
		}
		
		if( isset($_REQUEST['lastfm_api_key']) ) {
			update_option('lastfm_api_key', trim($_REQUEST['lastfm_api_key']) );
		}
	}

	function page_options() {
		?>
		<div class="wrap">
			<h2><? _e('Settings', 'sf_wp_lastfm'); ?> › <? _e('last.fm', 'sf_wp_lastfm') ?></h2>
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<input type="hidden" name="sfwp_lastfm_action" value="save_options" />
				<input type="hidden" name="_wpnonce" value="<? echo wp_create_nonce( 'sfwp_lastfm_action_save_options' ) ?>" />
				<table class="form-table">
					<tr>
						<th><label for="lastfm_api_key"><? _e('API Key', 'sf_wp_lastfm') ?></label></th>
						<td><input name="lastfm_api_key" id="lastfm_api_key" type="text" value="<? echo get_option('lastfm_api_key') ?>" /></td>
					</tr>
				</table>
				<p class="submit"><input type="submit" value="<? _e('Save Settings', 'sf_wp_lastfm') ?>" class="button-primary" /></p>
			</form>
			<h3><? _e('Test', 'sf_wp_lastfm') ?></h3>
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<input type="hidden" name="sfwp_lastfm_action" value="test" />
				<input type="hidden" name="_wpnonce" value="<? echo wp_create_nonce( 'sfwp_lastfm_action_test' ) ?>" />
				<p class="submit"><input type="submit" value="<? _e('Test Settings', 'sf_wp_lastfm') ?>" class="button-primary" /></p>
			</form>
			<? if( isset($this->result) ) : ?>
			<h3><? _e('Test Result', 'sf_wp_lastfm') ?></h3>
			<? echo '<pre>' . print_r( $this->result, true) . '</pre>'; ?>
			<? endif; ?>
		</div>
		<?
	}

	private function test() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'sfwp_lastfm_action_test' ) ) {
			wp_die( __('Nonce check failed', 'sf_wp_lastfm') );
			return;
		}

		if( !$this->is_ready ) {
			return false;
		}

		$this->result = lastfm_get_recent_tracks('schrbr');
	}

	public function do_request($method, $data = false) {

		if( !$this->is_ready ) {
			return false;
		}
		
		$curl = curl_init();

		$url = $this->url . $method;

		if ($data) {
			$url = sprintf("%s&%s", $url, http_build_query($data));
		}

		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

		return curl_exec($curl);
	}
}
$sf_wp_lastfm = SfWpLastfm::instance();
function lastfm_get_recent_tracks($user) {
	return json_decode( SfWpLastfm::instance()->do_request( 'user.getrecenttracks', array( 'user' => $user ) ) );
}

function lastfm_get_user_info($user) {
	$user_info = json_decode( SfWpLastfm::instance()->do_request( 'user.getInfo', array( 'user' => $user ) ) );
	return $user_info->user;
}

function lastfm_get_play_count($user) {
	$user_info = lastfm_get_user_info( $user );
	return $user_info->playcount;
}
?>
