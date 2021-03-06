<?php
/*
Plugin Name: My Eventish Events
Description: Get your events from www.eventish.com
Author: Alex Vanyan
Version: 1.1
Author URI: http://cs16.us
*/

if(!function_exists('get_my_eventish_name')):
function get_my_eventish_name($args = '')
{
    $path = dirname(__FILE__);
    $path = str_replace('\\', '/', $path);
    $path = explode('/', $path);
    return end($path);
}
endif;

if(!function_exists('my_eventish_events')):
function my_eventish_events($args = '')
{
    $params = array(
                    'limit'          => 5
                    );
    
    if($args) {
        parse_str($args, $opts);
        foreach($opts as $opt => $val) {
            $params[$opt] = $val;
        }
    }
    
    my_eventish_events_widget();
}
endif;

if(!function_exists('my_eventish_css')):
function my_eventish_css() {
    wp_enqueue_style('myeventish-front-css', WP_PLUGIN_URL . '/' . get_my_eventish_name() . '/stylesheet.css');
}
add_action('wp_print_styles', 'my_eventish_css');
endif;

if(!function_exists('my_eventish_events_widget')):
function my_eventish_events_widget() {
    $opts = get_option('my_eventish_opts');
    $title = $opts['title'] ? $opts['title'] : "Upcoming Events";
    $apiKey = $opts['api_key'] ? $opts['api_key'] : "";
    $limit = (int) $opts['limit'] ? (int) $opts['limit'] : 0;
    
    if(!$apiKey) {
        echo "No API key is configured. Please proceed to admin panel widgets to enter a valid API key.";
    } else {
        $my_events = my_eventish_curl_download("http://www.eventish.com/api/events/my?apikey=" . $apiKey . "&startnum=0&limit=" . $limit);
        $my_events = json_decode($my_events, 1);
        if(array_key_exists("ERROR", $my_events)) {
            echo "\r\n<div class=\"my_eventish_errors\">\r\n";
            show_my_eventish_errors($my_events["ERROR"]);
            echo "</div>";
        } else {
        	echo <<<HTML
            <li class="widget-container my-eventish-events-widget">
                <div class="my-eventish-title-box">
                    <div class="my-eventish-box-top-left"></div>
                    <span class="widget-title">$title</span>
                    <div class="my-eventish-box-top-right"></div>
                </div>
                <div class="widget-content">
                    <div class="my-eventish-box-middle">
                        <div class="my-eventish-box-content">
                        <div class="my-eventish-box-left"></div>
                        <div class="my-eventish-box-right"></div>
HTML;

            echo "
                <style type=\"text/css\">
                    .my-eventish-events-widget .widget-content {
                        height: " . (int) ((122 * (count($my_events) ? count($my_events) : 1)) + 80) . "px;
                    }
                </style>
            ";

            foreach($my_events as $event) {
                echo "
                            <div class=\"my-eventish-single-event\">
                                <div class=\"my-eventish-event-title\">
                                    <a class=\"my-eventish-event-title-link\" target=\"_blank\" href=\"http://www.eventish.com/events/show/{$event['ID']}\">{$event['EventName']}</a>
                                </div>
                                <div class=\"my-eventish-calendar\">
                                    <span class=\"my-eventish-calendar-day\">
                                    " . date("d", $event['StartDate']) . "
                                    </span>
                                    <span class=\"my-eventish-calendar-month\">
                                    " . strtoupper(date("M", $event['StartDate'])) . "
                                    </span>
                                </div>
                                <div class=\"my-eventish-event-venue\">
                                    {$event['Address']},
                                    <br />
                                    {$event['City']}, {$event['StateCode']} {$event['ZipCode']}
                                </div>
                                <span class=\"my-eventish-clear\"></span>
                                <div class=\"my-eventish-event-timer\">
                                    <div class=\"my-eventish-starttime-title\">
                                    StartTime:
                                    </div>
                                    <div class=\"my-eventish-starttime\">
                                    at " . date("H:i A") . "
                                    </div>
                                </div>
                            </div>
                            <span class=\"my-eventish-single-events-separator\"></span>
                            ";
            }

            echo <<<HTML
                            <div class="my-eventish-events-powered-by"></div>
                        </div>
                    </div>
                    <div class="my-eventish-box-footer">
                        <div class="my-eventish-box-bottom-left"></div>
                        <div class="my-eventish-box-bottom"></div>
                        <div class="my-eventish-box-bottom-right"></div>
                    </div>
                </div>
            </li>
HTML;
        }
    }
}
endif;

if(!function_exists('init_my_eventish_events_widget')):
function init_my_eventish_events_widget(){
	register_sidebar_widget("My Eventish Events", "my_eventish_events_widget");     
}
add_action("plugins_loaded", "init_my_eventish_events_widget");
endif;

if(!function_exists('my_eventish_events_init')):
function my_eventish_events_init() {
    
    $fields = array(
                    'api_key' => 'api_key',
                    'limit'   => 'events_limit',
                    'title'   => 'title'
                    );
    
    if($_POST) {
        $save_opts = array();
        
        foreach($fields as $fkey => $field) {
            $save_opts[$fkey] = $_POST[$field];
        }
        
        if(get_option('my_eventish_opts')) {
            update_option('my_eventish_opts', $save_opts);
        } else {
            add_option('my_eventish_opts', $save_opts, null, 'yes');
        }
    }
    
    $opts = get_option('my_eventish_opts', array());
    
    echo "Title: <input type=\"text\" name=\"title\" value=\"{$opts['title']}\" /><br /><br />";
    
    echo "API Key: <input type=\"text\" name=\"api_key\" value=\"{$opts['api_key']}\" /><br /><br />";
    
    if(!$opts['api_key']) {
        echo "Please enter an API key...<br /><br />";
    } else {
        $my_events = my_eventish_curl_download("http://www.eventish.com/api/events/my?apikey=" . $opts['api_key'] . "&startnum=0&limit=" . $opts['limit']);
        $my_events = json_decode($my_events, 1);
        if(array_key_exists("ERROR", $my_events)) {
            show_my_eventish_errors($my_events["ERROR"]);
            echo "<br /><br />";
        }
    }
    
    echo "Events Limit: <input type=\"text\" name=\"events_limit\" value=\"{$opts['limit']}\" size=\"1\" /><br /><br />";
}
register_widget_control('My Eventish Events', 'my_eventish_events_init');
endif;

if(!function_exists('show_my_eventish_errors')):
function show_my_eventish_errors($errors) {
    foreach($errors as $error_key => $error) {
        echo "\t<span class=\"my_eventish_error\"><strong>error #" . ($error_key + 1) . "</strong>: " . $error["message"] . "</span>\r\n";
    }
}
endif;

if(!function_exists('my_eventish_curl_download')):
function my_eventish_curl_download($url){
    
    if (!function_exists('curl_init')){
        die(' - cURL extension is required to use this widget - ');
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    curl_close($ch);
 
    return $output;
}
endif;