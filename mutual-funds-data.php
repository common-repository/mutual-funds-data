<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/mutualfunddata
 * @since             1.0.0
 * @package           Mutual_Funds_Data
 *
 * @wordpress-plugin
 * Plugin Name:       Mutual Funds Data
 * Plugin URI:        https://mutualfundplugin.com/
 * Description:       Mutual Funds Data plugin allows you to create a comparision table of Indian Mutual Funds with latest data.
 * Version:           1.2.1
 * Author:            Mutual Fund Data
 * Author URI:        https://profiles.wordpress.org/mutualfunddata
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mutual-funds-data
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

defined('ABSPATH') or die('Permission denied!');

/* !1. HOOKS */

// 1.1
// hint: registers all our custom shortcodes on init
add_action('init', 'mfd_register_shortcodes');

// 1.2
// add groww mfd buttons in the tiny mce editor
add_action( 'init', 'mfd_tiny_mce_buttons' );

// 1.3
// load external files to public website
add_action('wp_enqueue_scripts', 'mfd_public_scripts');

// 1.4
// load external files to admin website
add_action( 'admin_enqueue_scripts', 'mfd_admin_style' );

// 1.5 
// hint: register ajax actions
add_action('wp_ajax_mfd_search_by_name', 'mfd_search_by_name');

//1.6
// register admin menu
add_action('admin_menu', 'mfd_admin_menus');

//1.7
// register plugin options
add_action('admin_init', 'mfd_register_options');

/* !2. SHORTCODES */

// 2.1
// hint: registers all our custom shortcodes
function mfd_register_shortcodes() {	
	add_shortcode('mfd', 'mfd_shortcode');	
}

// 2.2
// hint: returns a html string for the shortcode
function mfd_shortcode( $atts = [], $content = null, $tag = '') {
	    
    $grow_api_url = "https://groww.in/api/v2/web/scheme/";
    $link_prefix = "https://groww.in/mutual-funds/";
    $grow_api_url_2 = "https://mapi.groww.in/api/v2/web/scheme/portfolio/";

    $output = '';

    try{
        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$atts, CASE_LOWER);
    
        // override default attributes with user attributes
        $mfd_atts = shortcode_atts([
                                        'schemecodes' => '122639:118991',
                                        'title' => 'Best Mutual Funds'
                                    ], $atts, $tag);
        
        $scheme_codes = explode(":", $mfd_atts['schemecodes']);

        foreach($scheme_codes as $scheme_code) {
            $url = $grow_api_url.$scheme_code."/info";
            $url_2 = $grow_api_url_2.$scheme_code."/stats";
            $response = wp_remote_retrieve_body(wp_remote_get($url));          
            $response_2 = wp_remote_retrieve_body(wp_remote_get($url_2));                      
            $mf_json_data[$scheme_code]['info'] = json_decode($response, true);    
            $mf_json_data[$scheme_code]['stats'] = json_decode($response_2, true);                                        
        }               
        
        

        $options = mfd_get_current_options();
        $show_1Y = $options['1y'] === 'on' ? true : false;    
        $show_3Y = $options['3y'] === 'on' ? true : false;
        $show_5Y = $options['5y'] === 'on' ? true : false;
        $show_turnover = $options['portfolio_turnover'] === 'on' ? true : false;        
        $show_expense = $options['expense_ratio'] === 'on' ? true : false;
        $show_category = $options['category'] === 'on' ? true : false;
        $show_sub_category = $options['sub_category'] === 'on' ? true : false;
        $show_risk = $options['risk'] === 'on' ? true : false;    
    
        $output .= '
            <div class="mfd-container">
                <table class="mfd-responsive-table">
                    <caption>'.$mfd_atts['title'].'</caption>
                    <thead>
                        <tr>
                            <th scope="col">Fund Name</th>';
                            if($show_1Y){
                            $output .= '    
                            <th scope="col">1Y</th>';
                            }
                            if($show_3Y){
                            $output .= '    
                            <th scope="col">3Y</th>';
                            }
                            if($show_5Y){
                            $output .= '    
                            <th scope="col">5Y</th>';
                            }
                            if($show_expense){
                            $output .= '        
                            <th scope="col">Expense Ratio</th>';    
                            }
                            if($show_turnover){
                            $output .= '        
                            <th scope="col">Turnover Ratio</th>';    
                            }
                            if($show_category){
                            $output .= '    
                            <th scope="col">Category</th>';
                            }                                                
                            if($show_risk){
                            $output .= '    
                            <th scope="col">Risk</th>';
                            }
                        $output .= '
                        </tr>
                    </thead>
                    
                    <tbody>';
                    foreach($mf_json_data as $scheme_data) {  

                        $display_data = get_display_data($scheme_data);

                        $output .= '                                         
                        <tr>
                            <th scope="row">'.$display_data['fund_name'].'</th>';
                            if($show_1Y){
                            $output .= '    
                            <td data-title="1Y" data-type="currency">'.$display_data['one_yr_return'].'</td>';
                            }
                            if($show_3Y){
                            $output .= '    
                            <td data-title="3Y" data-type="currency">'.$display_data['three_yr_return'].'</td>';
                            }
                            if($show_5Y){
                            $output .= '    
                            <td data-title="5Y" data-type="currency">'.$display_data['five_yr_return'].'</td>';
                            }
                            if($show_expense){
                            $output .= '    
                            <td data-title="Expense Ratio" data-type="currency">'.$display_data['expense_ratio'].'</td>';
                            }                            
                            if($show_turnover){
                            $output .= '    
                            <td data-title="Turnover Ratio" data-type="currency">'.$display_data['portfolio_turnover'].'</td>';
                            }                                                        
                            if($show_category){
                            $output .= '    
                            <td data-title="Category">'.$display_data['scheme_category'];
                                if($show_sub_category){
                                    $output .= '    
                                    <br>('.$display_data['scheme_sub_category'].')';
                                }
                            $output .= '    
                            </td>';    
                            }
                            if($show_risk){
                            $output .= '    
                            <td data-title="Risk">'.$display_data['scheme_risk'].'</td>';
                            }                        
                        $output .= '        
                        </tr>';
                    }
                    $output .= '                 
                    </tbody>
                </table>
            </div>';
    }catch(Exception $e){

    }
    return $output;

}

/* !3. FILTERS */
// 3.1
// add new buttons
function mfd_tiny_mce_buttons() {
    add_filter( 'mce_external_plugins', 'mfd_tiny_mce_add_buttons' );
    add_filter( 'mce_buttons', 'mfd_tiny_mce_register_buttons' );
}

function mfd_tiny_mce_add_buttons( $plugins ) {
  $plugins['groww-mfd-tinymceplugin'] = plugins_url( '/admin/js/mutual-funds-data-tinymce-plugin.js', __FILE__ );  
  return $plugins;
}

function mfd_tiny_mce_register_buttons( $buttons ) {
  array_push( $buttons, 'groww-mfd-btn' );
  return $buttons;
}

// 3.2
// admin menu
function mfd_admin_menus(){
    $top_menu_item = 'mfd_dashboard_admin_page';
    add_menu_page('','Mutual Funds Data', 'manage_options', 'mfd_dashboard_admin_page', 'mfd_dashboard_admin_page', 'dashicons-exerpt-view');
}

// 3.3
// settings link
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
    $mylinks = array(
    '<a href="' . admin_url( 'admin.php?page=mfd_dashboard_admin_page' ) . '">Settings</a>',
    );
    return array_merge( $links, $mylinks );
}

/* !4. EXTERNAL SCRIPTS */
// 4.1 
// hint: load external files into PUBLIC website (frontend)
function mfd_public_scripts(){

	// register scripts with WordPress's internal library
	wp_register_script('mutual-funds-data-js-public', plugins_url('/public/js/mutual-funds-data-public.js',__FILE__), array('jquery'),'',true);    
    // wp_register_script('cool-share-js-public', plugins_url('/public/js/cool-share-jquery-plugin.js',__FILE__), array('jquery'),'',true);
    
	wp_register_style('mutual-funds-data-css-public', plugins_url('/public/css/mutual-funds-data-public.css',__FILE__), array(), '1.1');
    // wp_register_style('cool-share-css-public', plugins_url('/public/css/cool-share-jquery-plugin.css',__FILE__), array(), '1.1');
    
	// add to que of scripts that get loaded into every page
	wp_enqueue_script('mutual-funds-data-js-public');
    // wp_enqueue_script('cool-share-js-public');        
    
	wp_enqueue_style('mutual-funds-data-css-public');
    // wp_enqueue_style('cool-share-css-public');    

}

// 4.2
// hint: load external files into WP Admin view
function mfd_admin_style() {            

        wp_register_script('mfd-js-admin', plugins_url('/admin/js/mutual-funds-data-admin.js',__FILE__), array('jquery'),'1.1',true);

        wp_enqueue_script('mfd-js-admin');

        wp_register_style('mutual-funds-data-css-admin', plugins_url('/admin/css/mutual-funds-data-admin.css',__FILE__), array(), '1.1');

        wp_enqueue_style('mutual-funds-data-css-admin');
}

/* !5. ACTIONS */
// 5.1
// hint: Search Mutual Fund ajax action 
function mfd_search_by_name(){        

        // setup default result data
        $result = array(
            'status' => 0,
            'message' => 'No results found for the search.',
            'error'=>'',
            'errors'=>array()
        );

        // setup our errors array
        $errors = array();        

        $output_ = '';
        $grow_search_api_url = "https://groww.in/slr/v1/search/derived/scheme?page=0&size=20&q=";

        try {
		    $search_query = urlencode($_GET['q']);
            
            if( !strlen( $search_query ) ): 
                $errors['query'] = 'Search Query cannot be empty ';
            endif;
            
            if( count($errors) ): // IF there are errors        
                // append errors to result structure for later use
                $result['error'] = 'Some fields are still required. ';
                $result['errors'] = $errors;
            
            else: // IF there are no errors, proceed...
            
                $url = $grow_search_api_url.$search_query;
                $response = wp_remote_retrieve_body(wp_remote_get($url));                
                $response_content = json_decode($response, true)['content']; 
                
                // prepare MF data
                $mf_data = [];
                foreach($response_content as $scheme){
                    $mf_scheme['scheme_code'] = $scheme['scheme_code'];
                    $mf_scheme['scheme_name'] = $scheme['scheme_name'];
                    array_push($mf_data, $mf_scheme);
                }

                $result['status']=1;
                $result['message']='Query successful';
                $result['mf_data']=$mf_data;

            endif;

        }catch(Exception $e){
            $result['error'] = 'Caught Exception'.$e->getMessage();            
        }

        // encode result as json string
        $json_result = json_encode( $result );        
        
        // return result
        die( $json_result );
        
        // stop all other processing 
        exit;        
        
}

// 5.2
// Admin options action
function mfd_dashboard_admin_page(){

    $options = mfd_get_current_options();    
    
    $checkbox_1Y = $options['1y'] === 'on' ? 'checked' : '';    
    $checkbox_3Y = $options['3y'] === 'on' ? 'checked' : '';    
    $checkbox_5Y = $options['5y'] === 'on' ? 'checked' : '';    
    $checkbox_expense = $options['expense_ratio'] === 'on' ? 'checked' : '';    
    $checkbox_turnover = $options['portfolio_turnover'] === 'on' ? 'checked' : '';    
    $checkbox_category = $options['category'] === 'on' ? 'checked' : '';    
    $checkbox_sub_category = $options['sub_category'] === 'on' ? 'checked' : '';    
    $checkbox_risk = $options['risk'] === 'on' ? 'checked' : '';
    
    echo('<div class="wrap">                        
                        <h2>Mutual Funds Data - Settings</h2>
                        <h3>Select the fields/columns you want to include/show in the Mutual Funds Data table.</h3> 
                        <form action="options.php" method="post">');
                        settings_fields('mfd_plugin_options');        
                        @do_settings_fields('mfd_plugin_options', 'mfd_dashboard_admin_page');
                    echo('<table class="form-table">                            
                            <tbody>                                                                                            
                                <tr>
                                    <th scope="row"><label for="1y">1Y Returns</label></th>
                                    <td>
                                        <input type="checkbox" name="1y" class="" '.$checkbox_1Y.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="3y">3Y Returns</label></th>
                                    <td>
                                        <input type="checkbox" name="3y" class="" '.$checkbox_3Y.'/>                                        
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="5y">5Y Returns</label></th>
                                    <td>
                                        <input type="checkbox" name="5y" class="" '.$checkbox_5Y.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="expense_ratio">Expense Ratio</label></th>
                                    <td>
                                        <input type="checkbox" name="expense_ratio" class="" '.$checkbox_expense.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="portfolio_turnover">Turnover Ratio</label></th>
                                    <td>
                                        <input type="checkbox" name="portfolio_turnover" class="" '.$checkbox_turnover.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="category">Category</label></th>
                                    <td>
                                        <input type="checkbox" name="category" class="" '.$checkbox_category.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="sub_category">Sub Category</label></th>
                                    <td>
                                        <input type="checkbox" name="sub_category" class="" '.$checkbox_sub_category.'/>
                                    </td>
                                </tr>			

                                <tr>
                                    <th scope="row"><label for="risk">Risk</label></th>
                                    <td>
                                        <input type="checkbox" name="risk" class="" '.$checkbox_risk.'/>
                                    </td>
                                </tr>			
                                <input type="hidden" name="is_first_time" value="false"/>
                            </tbody>				
                        </table>');
                        @submit_button();
                    echo('</form>
          </div>');
}

// 5.3
// Admin get settings
function mfd_get_admin_settings(){     

        // setup default result data
        $result = array(
            'status' => 0,
            'message' => 'No results found for the settings.',
            'error'=>'',
            'errors'=>array()
        );
    
        try{
            $result['status'] = 1;
            $result['message'] = 'Results Found.';
        }catch(Exception $e){
            $result['error'] = 'Caught Exception'.$e->getMessage();            
        }
        // encode result as json string
        $json_result = json_encode( $result );        
        
        // return result
        die( $json_result );
        
        // stop all other processing 
        exit;     
}

// 5.4
// Function to run during plugin uninstall
function mfd_uninstall_plugin(){
    mfd_remove_options();
}

// 5.5
function mfd_remove_options(){
   	
	$options_removed = false;
	
	try {
	
		// get plugin option settings
		 $options = mfd_get_options_settings();
		
		// loop over all the settings
		foreach( $options['settings'] as $setting ):
			
			// unregister the setting
			unregister_setting( $options['group'], $setting );
            delete_option($setting);
		
		endforeach;
		
		// return true if everything worked
		$options_removed = true;
	
	} catch( Exception $e ) {		
		// php error		
	}
	
	// return result
	return $options_removed;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mutual-funds-data-activator.php
 */
function activate_mutual_funds_data() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mutual-funds-data-activator.php';
	Mutual_Funds_Data_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mutual-funds-data-deactivator.php
 */
function deactivate_mutual_funds_data() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mutual-funds-data-deactivator.php';
	Mutual_Funds_Data_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mutual_funds_data' );
register_deactivation_hook( __FILE__, 'deactivate_mutual_funds_data' );


/* !6. HELPERS */
// 6.1
// hint: logging helper function
if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

// 6.2
// Gets the current options and returns values in associative array
function mfd_get_current_options(){
	// setup our return variable
	$current_options = array();
	
	try {
	
		// build our current options associative array
		$current_options = array(
			'1y' => mfd_get_option('1y'),
			'3y' => mfd_get_option('3y'),
			'5y' => mfd_get_option('5y'),            
            'expense_ratio' => mfd_get_option('expense_ratio'),
            'portfolio_turnover'=> mfd_get_option('portfolio_turnover'),
			'category' => mfd_get_option('category'),
			'sub_category' => mfd_get_option('sub_category'),
            'risk' => mfd_get_option('risk')            
		);
	
	} catch( Exception $e ) {
        // php error
	}
	
	// return current options
	return $current_options;
}

// 6.3
// hint: returns the requested page option value or it's default
function mfd_get_option( $option_name ) {
	
	// setup return variable
	$option_value = '';	
		
	try {		
		// get default option values
		$defaults = mfd_get_default_options();
				
		switch( $option_name ) {			
			case '1y':				                
				$option_value = (get_option('1y')) ? get_option('1y') : $defaults['1y'];
				break;
			case '3y':				
				$option_value = (get_option('3y'))  ? get_option('3y') : $defaults['3y'];
				break;
			case '5y':				
				$option_value = (get_option('5y'))  ? get_option('5y') : $defaults['5y'];
				break;
			case 'expense_ratio':				
				$option_value = (get_option('expense_ratio'))  ? get_option('expense_ratio') : $defaults['expense_ratio'];
				break;
            case 'portfolio_turnover':
            	$option_value = (get_option('portfolio_turnover'))  ? get_option('portfolio_turnover') : $defaults['portfolio_turnover'];
				break;    
			case 'category':				
				$option_value = (get_option('category'))  ? get_option('category') : $defaults['category'];
				break;
			case 'sub_category':				
				$option_value = (get_option('sub_category'))  ? get_option('sub_category') : $defaults['sub_category'];
				break;
            case 'risk':	                
				$option_value = (get_option('risk'))  ? get_option('risk') : $defaults['risk'];
				break;    
		}
		
	} catch( Exception $e) {		
		// php error		
	}
	
	// return option value or it's default
	return $option_value;	
}

// 6.4
// hint: returns the requested page option value or it's default
function mfd_get_default_options() {
	
	$defaults = array();
	
	try {		
		// setup defaults array
        if(!get_option('is_first_time')){
            $defaults = array(
                '1y'=>'on',
                '3y'=>'on',
                '5y'=>'on',
                'expense_ratio' => 'on',
                'portfolio_turnover' => 'on',
                'category'=>'on',
                'sub_category'=>'on',
                'risk'=>'on',                
            );	
        }else{
           $defaults = array(
                '1y'=>'',
                '3y'=>'',
                '5y'=>'',
                'expense_ratio'=>'',
                'portfolio_turnover'=>'',
                'category'=>'',
                'sub_category'=>'',
                'risk'=>'',                
            );	 
        }
	} catch( Exception $e) {		
		// php error		
	}	
	// return defaults
	return $defaults;	
}

// 6.5
// hint: returns the all the option names
function mfd_get_options_settings() {	

	// setup our return data
	$settings = array( 
		'group'=>'mfd_plugin_options',
		'settings'=>array(
			'1y',
			'3y',
			'5y',
            'expense_ratio',
            'portfolio_turnover',
			'category',
			'sub_category',
            'risk',
            'is_first_time',
		),
	);
	
	// return option data
	return $settings;    
}

// 6.6
function get_display_data( $data ){
        
    $display_data = array();
    $scheme_data = $data['info'];
    $scheme_stats = $data['stats'];

    if(!empty($scheme_data['scheme_name'])){
        $scheme_name = $scheme_data['scheme_name'];     
        $scheme_url = $scheme_data['search_id'];
        $fund_name = '<a href="https://groww.in/mutual-funds/'.$scheme_url.'">'.$scheme_name.'</a>';
    }else{
        $scheme_name = 'Not Available';
    }
    $display_data['scheme_name'] = $scheme_name;
    $display_data['fund_name'] = $fund_name;

    if(!empty($scheme_data['nav'])){
        $scheme_nav = '<i class="fa fa-inr"></i>'.$scheme_data['nav'];                    
    }else{
        $scheme_nav = 'NA';
    }
    $display_data['scheme_nav'] = $scheme_nav;

    if(!empty($scheme_stats['portfolio_turnover'])){
        $turnover_ratio = $scheme_stats['portfolio_turnover'].'%';                     
    }else{
        $turnover_ratio = 'NA';
    }
    $display_data['portfolio_turnover'] = $turnover_ratio;

    if(!empty($scheme_data['expense_ratio'])){
        $expense_ratio = $scheme_data['expense_ratio'].'%';                    
    }else{
        $expense_ratio = 'NA';
    }
    $display_data['expense_ratio'] = $expense_ratio;

    if(!empty($scheme_data['category'])){
        $scheme_category = $scheme_data['category'];                    
    }else{
        $scheme_category = 'NA';
    }
    $display_data['scheme_category'] = $scheme_category;

    if(!empty($scheme_data['sub_category'])){
        $scheme_sub_category = $scheme_data['sub_category'];                    
    }else{
        $scheme_sub_category = 'NA';
    }                    
    $display_data['scheme_sub_category'] = $scheme_sub_category;                        

    $return_stats = $scheme_data['return_stats'][0];

    if(!empty($return_stats['return1y'])){
        $one_yr_return = $return_stats['return1y'].'%';                    
    }else{
        $one_yr_return = 'NA';
    }
    $display_data['one_yr_return'] = $one_yr_return;                        

    if(!empty($return_stats['return3y'])){
        $three_yr_return = $return_stats['return3y'].'%';                    
    }else{
        $three_yr_return = 'NA';
    }
    $display_data['three_yr_return'] = $three_yr_return;                        

    if(!empty($return_stats['return5y'])){
        $five_yr_return = $return_stats['return5y'].'%';                    
    }else{
        $five_yr_return = 'NA';
    }
    $display_data['five_yr_return'] = $five_yr_return;    

    if(!empty($return_stats['risk'])){
        $scheme_risk = $return_stats['risk'];                    
    }else{
        $scheme_risk = 'NA';
    }
    $display_data['scheme_risk'] = $scheme_risk;                        

    return $display_data;
}

/* !7. CUSTOM POST TYPES */

/* !8. ADMIN PAGES */

/* !9. SETTINGS */
function mfd_register_options() {

    $options = mfd_get_options_settings();
	
	// loop over settings
	foreach( $options['settings'] as $setting ):		
		register_setting($options['group'], $setting);	
	endforeach;	
}
