<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Wp_WeatherOn_Plugin
 * @subpackage Wp_WeatherOn_Plugin/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_WeatherOn_Plugin
 * @subpackage Wp_WeatherOn_Plugin/public
 * @author     Your Name <email@example.com>
 */
class Wp_WeatherOn_Plugin_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_WeatherOn_Plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_WeatherOn_Plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp_weatheron_plugin-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_WeatherOn_Plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_WeatherOn_Plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp_weatheron_plugin-public.js', array( 'jquery' ), $this->version, false );

	}
}


/**
 * get data from api and put in the db.
 *
 * @since    1.0.0
 */

function CallAPI($method, $url, $data = false) {
  $curl = curl_init();
  switch ($method) {
  case "POST":
    curl_setopt($curl, CURLOPT_POST, 1);

    if ($data)
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    break;
  case "PUT":
    curl_setopt($curl, CURLOPT_PUT, 1);
    break;
  default:
    if ($data)
      $url = sprintf("%s?%s", $url, http_build_query($data));
  }
  // Optional Authentication:
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($curl, CURLOPT_USERPWD, "username:password");
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($curl);
  curl_close($curl);
  return $result;
}
	
function weatherCall() {
  global $wpdb;
  $key = '631c4c4741eab33701a8bebe0a9ca9aa';
  $cityID = 3435910;
  $returnCityCode = get_option( 'city_code' );

  $meta_key_temp = 'temp_'.$returnCityCode;
  $meta_key_icon = 'icon_'.$returnCityCode;

  $result = CallAPI('GET', 'api.openweathermap.org/data/2.5/weather?id='.$cityID.'&units=metric&appid='.$key);
  $json = json_decode($result, true);
  $temp = round($json['main']['temp']);
  $icon = $json['weather'][0]['icon'];

  $temp_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_temp'" );
  if ($temp_db) {
    $wpdb->update( $wpdb->postmeta, array("meta_value" => $temp), array("meta_key" => $meta_key_temp));
  } else {
    $wpdb->insert( $wpdb->postmeta, array("meta_key" => $meta_key_temp, "meta_value" => $temp));
  }
  $temp_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_temp' ");

  $icon_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_icon' ");
  if ($icon_db) {
    $wpdb->update( $wpdb->postmeta, array("meta_value" => $icon), array("meta_key" => $meta_key_icon));
  } else {
    $wpdb->insert( $wpdb->postmeta, array("meta_key" => $meta_key_icon, "meta_value" => $icon));
  }
  $icon_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_icon' ");
}
add_action( 'weather-call', 'weatherCall');

function add_cron_recurrence_interval( $schedules ) {
  $schedules['every_30_minutes'] = array(
    'interval'  => 1800,
    'display'   => __( 'Every 30 Minutes', 'textdomain' )
  );
  return $schedules;
}
add_filter( 'cron_schedules', 'add_cron_recurrence_interval' );

if ( ! wp_next_scheduled( 'weather-call' ) ) {
  wp_schedule_event( time(), 'every_30_minutes', 'weather-call' );
}

/**
 * Shortcode [weatheron].
 *
 * @since    1.0.0
 */
function weatheron_shortcode( $atts ) {
	global $wpdb;
	$returnCityCode = get_option( 'city_code' );

	$meta_key_temp = 'temp_'.$returnCityCode;
	$temp_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_temp'" );
	$meta_key_icon = 'icon_'.$returnCityCode;
	$icon_db = $wpdb->get_var("SELECT meta_value FROM wp_postmeta WHERE meta_key = '$meta_key_icon' ");
	echo '<p>Lo que dice el plugin: ' . $icon_db . '/'. $temp_db . '</p>';
}
add_shortcode( 'weatheron', 'weatheron_shortcode' );

/**
 * Item in the menu.
 *
 * @since    1.0.0
 */
add_action('admin_menu', 'test_plugin_setup_menu');

function test_plugin_setup_menu(){
    add_menu_page( 'WeatherOn', 'WeatherOn', 'manage_options', 'weatheron', 'test_init' );
}

function test_init(){
	if ( isset( $_POST['city_code'] ) ) :
		update_option( 'city_code', $_POST['city_code'] );
	endif;
	wp_enqueue_media();
	?>
    <div class="wrap">
        <h1 class="wp-heading-inline">WeatherOn <span style="font-size: 13px;">(by Martín Vera)</span></h1>
        <h2>Enter city to know weather.</h2>
        <p>Get city code from <a href="https://openweathermap.org/find?q=" target="_blank">OpenWeather</a></p>
        <form method='post'>
			<table class="form-table">
				<tr valign="top">
					<th scope="row" valign="top"><p>City code:</p></th>
					<td valign="top"><input type="text" id="city_code" name="city_code" value="<?php echo get_option( 'city_code' ); ?>" placeholder="bsas" /></td>
				</tr>
			</table>
			
           	<?php submit_button(); ?>
        </form>
    </div>
<?php
}
