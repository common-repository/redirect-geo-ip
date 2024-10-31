<?php
/*
  * Plugin Name: Redirect via Geo IP
  * Plugin URI: 
  * Description: Redirect via Geo IP plugin automatically determines visitors locations and redirects them to the correct store
  * Author: Sunarc
  * Author URI: https://www.suncartstore.com/
  * Version: 1.0
 */

if (!defined("ABSPATH"))
      exit;

 if (!defined("RGISUNARC_PLUGIN_DIR_PATH"))
  define("RGISUNARC_PLUGIN_DIR_PATH", plugin_dir_path(__FILE__));

 if (!defined("RGISUNARC_PLUGIN_URL"))
  define("RGISUNARC_PLUGIN_URL", plugins_url('redirect-geo-ip'));  


/*
* Create a country code table on plugin activation.
*/
function rgisunarc_activation_hook() {
  include RGISUNARC_PLUGIN_DIR_PATH.'/INC/country_tbl.php';
}
register_activation_hook(__FILE__, "rgisunarc_activation_hook");



function rgisunarc_deactivation_hook() {
   
}
register_deactivation_hook(__FILE__, "rgisunarc_deactivation_hook");


/*
* Drop country code table on plugin uninstallation.
*/
function rgisunarc_uninstall_hook() {
  global $wpdb;
  $drop = $wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."rgisunarc_countries`");
}
register_uninstall_hook(__FILE__,"rgisunarc_uninstall_hook");


/*
* Get setting values.
*/
$rgisunarc_options = get_option('rgisunarc_settings_sunarc');


/*
* Create setting page and add settting fields.
*/
function rgisunarc_options_page() {

  global $rgisunarc_options;

  ob_start(); ?>
  <div class="wrap">
    <h2><?php _e('Redirect via Geo IP', 'rgisunarc'); ?></h2>
    
    <form method="post" action="<?php echo admin_url('options.php'); ?>">
    
      <?php settings_fields('rgisunarc_settings_group'); ?>
    
      <table class="form-table" role="presentation">

        <tbody>
          <tr>
            <th scope="row"><label for="enable"><?php _e('Enable', 'rgisunarc'); ?></label></th>
            <td>
              <select name="rgisunarc_settings_sunarc[geo_enable]" id="geo_enable" style="width: 100%;">
                <option value="no" <?php selected( $rgisunarc_options['geo_enable'], 'no' ); ?>>No</option>
                <option value="yes" <?php selected( $rgisunarc_options['geo_enable'], 'yes' ); ?>>Yes</option></select>
              </td>
          </tr>
          <tr>
            <th scope="row"><label for="visitor_country"><?php _e('Visitor Country', 'rgisunarc'); ?></label></th>
            <td>
              <select name="rgisunarc_settings_sunarc[visitor_country][]" id="visitor_country" multiple style="width: 100%;">
                <option value=""><?php _e('Select a country / region…', 'rgisunarc'); ?></option>
                <?php
                global $wpdb;
                $rows =  $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."rgisunarc_countries` ORDER BY countryName ASC" , ARRAY_A);
                foreach($rows as $row){ 
                $selected = '';
                if(!empty($rgisunarc_options['visitor_country'])){
                  $selected = (in_array($row['countryCode'], $rgisunarc_options['visitor_country'])) ? 'selected' : '';
                }
                 ?>
                  <option value="<?php echo $row['countryCode']; ?>" <?php echo $selected; ?>><?php echo $row['countryName']; ?></option>
                  <?php
                }
                ?>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="redirect"><?php _e('Redirect to URL', 'rgisunarc'); ?></label></th>
            <td>
              <input name="rgisunarc_settings_sunarc[redirect]" type="text" id="redirect" value="<?php echo @$rgisunarc_options['redirect']; ?>" class="regular-text" style="width: 44%;">
            </td>
          </tr>
        </tbody>
      </table>


      <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Options', 'rgisunarc'); ?>" />
      </p>
    
    </form>
    
  </div>
  <?php
  echo ob_get_clean();
}


/*
* Add admin menu.
*/
function girsunarc_add_options_link() {
  add_options_page('Redirect via Geo IP', 'Redirect via Geo IP', 'manage_options', 'redirect-geo-ip', 'rgisunarc_options_page');
}
add_action('admin_menu', 'girsunarc_add_options_link');


/*
* Register settings.
*/
function girsunarc_register_settings() {
  // Creates settings in the options table
  register_setting('rgisunarc_settings_group', 'rgisunarc_settings_sunarc');
}
add_action('admin_init', 'girsunarc_register_settings');




/*
* Get visitor's IP
*/
function girsunarc_getVisIpAddr() { 
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) { 
      return $_SERVER['HTTP_CLIENT_IP']; 
  } 
  else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
      return $_SERVER['HTTP_X_FORWARDED_FOR']; 
  } 
  else { 
      return $_SERVER['REMOTE_ADDR']; 
  } 
} 


/*
* Check IP address with country code after load woocommerce
*/
function girsunarc_woocommerce_loaded(){
  if(!is_admin()){
    global $rgisunarc_options;
    if($rgisunarc_options['geo_enable'] == 'yes'){
      // Store the IP address 
      $visitor_ip = girsunarc_getVisIpAddr();
        
      if( !empty($visitor_ip ) && !empty($rgisunarc_options['visitor_country']) && !empty($rgisunarc_options['redirect']) ){

        $geoData        = WC_Geolocation::geolocate_ip($visitor_ip);
        $countryCode    = $geoData['country'];

        if (in_array($countryCode, $rgisunarc_options['visitor_country'])) {
          include_once ABSPATH.'/wp-includes/pluggable.php';
          wp_redirect($rgisunarc_options['redirect']);
          exit();
        }

      }
    }
  }

}
add_action( 'woocommerce_init', 'girsunarc_woocommerce_loaded'  );