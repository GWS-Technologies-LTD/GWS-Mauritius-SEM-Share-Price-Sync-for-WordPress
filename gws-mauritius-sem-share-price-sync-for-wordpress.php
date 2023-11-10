<?php
/**
 * Plugin Name: GWS Mauritius SEM Share Price Sync
 * Author: GWS Technologies LTD
 * Author URI: https://www.gws-technologies.com/
 * Plugin URI: https://github.com/GWS-Technologies-LTD/GWS-Mauritius-SEM-Share-Price-Sync-for-WordPress
 * Description: Sync SEM share price from SEM XML Feed
 * Version: 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Currently plugin version.
 * Start at version 1.0.4 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (!defined('GWS_SEM_SHARE_PRICE_SYNC_VERSION')) {
    define('GWS_SEM_SHARE_PRICE_SYNC_VERSION', '1.0.4');
}

class GWS_SEM_Share_Price_Sync {

    private $table_name;
    private $version;

    function __construct(){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gws_sharepprices';
        $this->version = GWS_SEM_SHARE_PRICE_SYNC_VERSION;

        add_action('init', function(){
            if (function_exists('get_field')) {
                $this->update_db_check();
                $this->init_actions();
                $this->init_crons();
            }
        });

        add_action( 'acf/include_fields', function() {
            $this->setup_acf();
        });

        add_action('plugins_loaded', array($this,'check_for_acf'));
    }

    private function init_actions(){
        add_action( 'gws_sem_cron', array($this,'retrieve_sharepprice_and_store_to_db' ));
        add_action('wp_ajax_retrieve_sharepprice_and_store_to_db', array($this,'retrieve_sharepprice_and_store_to_db'));
        add_action('wp_ajax_nopriv_retrieve_sharepprice_and_store_to_db', array($this,'retrieve_sharepprice_and_store_to_db'));
    }

    private function init_crons(){
        if (! wp_next_scheduled ( 'gws_sem_cron' )) {
            wp_schedule_event( time(), 'hourly', 'gws_sem_cron' );
        }
    }

    private function setup_acf(){
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_options_page( array(
            'page_title' => 'GWS SEM Sync',
            'menu_slug' => 'gws-sem-sync',
            'position' => '',
            'redirect' => false,
            'capability' => 'administrator',
        ) );
    
        acf_add_local_field_group( array(
            'key' => 'group_654d0d4c9b457',
            'title' => 'GWS Sem Sync Options',
            'fields' => array(
                array(
                    'key' => 'field_654d0d4c87a77',
                    'label' => 'gws_sem_api_url',
                    'name' => 'gws_sem_api_url',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'gws-sem-sync',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ) );
        

    }

    public function check_for_acf()
        {
            if (!function_exists('get_field')) {
                // dependency not installed, show an error and bail
                add_action('admin_notices', array($this,'check_for_acf_error'));
                return;
            }

            // load the rest of your plugin stuff here
        }

    public function check_for_acf_error()
    {
        ?>
        <div class="error">
            <p>
                <?php _e('Please note this plugin requires <a href="https://www.advancedcustomfields.com/pro/" target="_blank">ACF Pro</a> to run properly', 'gws'); ?>
            </p>
        </div>
        <?php
    }

    private function update_db_check()
    {
        if (get_site_option('gws_sem_share_price_plugin_version') != $this->version) {
            $this->install();
            update_option("gws_sem_share_price_plugin_version", $this->version);
        }
    }

    private function install(){
        $this->rates_custom_table();
    }

    private function rates_custom_table(){
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
                `sem_date` DATE UNIQUE,
                `sem_time` TIME,
                `sharepprice` DECIMAL(10,2),
                `semdex` DECIMAL(10,2),
                PRIMARY KEY (`sem_date`),
                KEY `sem_date_index` (`sem_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        $this->retrieve_sharepprice_and_store_to_db();

    }

    public function retrieve_sharepprice_and_store_to_db(){
        global $wpdb;

        $date = date("Y-m-d");
        
        $query = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$date'";
        $results = $wpdb->get_results($query);
        
        $process = false;

        if(empty($results)){
            $process = true;
        }else{
            $rate = $results[0]->sharepprice;
            if(empty($rate))
                $process = true;
        }
        
        if( $process ){

                $url = get_field("gws_sem_api_url","option");
                $xml = simplexml_load_file($url);

                $share_price = $xml->quote->sharepprice;
                $sem_date = $xml->timestamp->date;
                $sem_time = $xml->timestamp->time;
                $sem_semdex = $xml->quote->semdex;

                $price = str_ireplace(",","",strip_tags($share_price->asXML()));
                $date = strip_tags($sem_date->asXML());
                $time = strip_tags($sem_time->asXML());
                $semdex = str_ireplace(",","",strip_tags($sem_semdex->asXML()));

                $dbtime = strtotime($date);
                $dbdate = date('Y-m-d',$dbtime);

                $data = array('sem_date' => $dbdate, 'sem_time' => $time, 'sharepprice' => $price ,'semdex' => $semdex);

                if($data['sharepprice'] > 0 ){
                    $wpdb->insert($this->table_name,$data);
                }
            }

        return;
    }

    public static function get_sharepprice ($user_date = ""){
        global $wpdb;

        $date = $user_date;
        if(empty($date)){
            $date = date("Y-m-d");
        }
        $date = str_replace('/' , '-' , $date);
        $formatted_date = date('Y-m-d' , strtotime($date));
        
        $query = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$formatted_date'";
        $results = $wpdb->get_results($query);
        
        if( $results ){
            $rate = $results[0]->sharepprice;

            if(empty($rate)){
                retrieve_sharepprice_and_store_to_db();
                $query = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$formatted_date'";
                $results = $wpdb->get_results($query);
                
                $rate = $results[0]->sharepprice;

                if(empty($rate)){

                    if(empty($user_date)){
                        $date = date("d-M-Y",strtotime("yesterday")); 
                        $date = str_replace('/' , '-' , $date);
                        $formatted_date = date('Y-m-d' , strtotime($date));
                        
                        $query = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$formatted_date'";
                        $results = $wpdb->get_results($query);
                        
                        $rate = $results[0]->sharepprice;
                    }
                }
            }
        }else{
            $rate = 0;
        }

        return $rate;
    }

    public static function get_sharepprice_trend($date=""){
        global $wpdb;

        if(empty($date)){
            $date = date("d-M-Y");
        }
        $date = str_replace('/' , '-' , $date);
        $formatted_date = date('d-M-Y' , strtotime($date));
        $previous_date = date('d-M-Y',(strtotime ( '-1 day' , strtotime ( $date) ) ));
        
        $query_current = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$formatted_date'";
        $results_current = $wpdb->get_results($query_current);
        $current_rate = $results_current[0]->sharepprice;

        $query_previous = "SELECT sharepprice FROM {$this->table_name} WHERE sem_date = '$previous_date'";
        $results_previous = $wpdb->get_results($query_previous);
        $previous_rate = $results_previous[0]->sharepprice;
        
        $trend = "flat";
        if($previous_rate > $current_rate){
            $trend = "down";
        }elseif($previous_rate < $current_rate){
            $trend = "up";
        }
        
        return $trend;
    }
}

new GWS_SEM_Share_Price_Sync();