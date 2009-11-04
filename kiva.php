<?php
/*
Plugin Name: Kiva Widget
Plugin URI: http://urpisdream.com/2009/05/kiva-loans-wordpress-widget/
Description: Kiva widget, display my investments
Version: 3.0
Author: Marilyn Burgess
Author URI: http://urpisdream.com
*/

/*
  Copyright 2009 Urpi's Dream

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $debug;
$debug = 0;

global $kiva_cache_dir;
$kiva_cache_dir = ( defined('WP_CONTENT_DIR') ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
$kiva_cache_dir = $kiva_cache_dir . '/plugins/kivaorg-widget/cache';

global $kiva_cache_path;
$kiva_cache_path = ( defined('WP_CONTENT_URL') ) ? WP_CONTENT_URL : ABSPATH . 'wp-content';;
$kiva_cache_path = $kiva_cache_path . "/plugins/kivaorg-widget/cache";

global $kiva_api_app_id;
$kiva_api_app_id = "com.urpidream.wpwidget";

function widget_kiva_loan_init() {

	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ){
		return; 
    }

	// Print the sidebar widget
	function widget_kiva_loan($args) {
		extract($args);

		// Collect our widget's options, or define their defaults.
		$title = 'Kiva loans';
		$text = get_kiva_loans();

		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo $text;
		echo $after_widget;
	}

    function get_kiva_loans(){
        global $kiva_api_app_id;
        global $debug;

        if($debug == 1){
            echo "<b>Please pardon the temporary mess!</b><br />";
            echo "in get_kiva_loans<br />";
            flush();
        }

        $options = get_option('widget_kiva_loan');
        $limit = $options['number_of_loans'];
        if($limit <  1){
            $limit = 1;
        }
        $size = $options['image_size'];
        if($size > 200){
            $size = 200;
        }
        $username = $options['kiva_username'];

        $content = "";
        if(! $username){
            $content = "<p>No user specified!</p>";
        }else{
            $results = "";
            if(is_current_kiva_cache_available()){
                $results = get_kiva_cache();
            }
            if($debug == 1){
                echo "is_current_kiva_cache_available??  $results<br />";
                flush();
            }

            $total_loans = 0;
            if($results == ""){
                $url = "http://api.kivaws.org/v1/lenders/$username/loans.json?app_id=$kiva_api_app_id";
                if($debug == 1){
                    echo "url: $url<br />";
                    flush();
                }
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                $response = curl_exec ($ch);
                curl_close ($ch);

                if($response != ""){
                    $results = json_decode($response);

                    if($debug == 1){
                        echo "going to write results: $results <br />";
                        flush();
                    }
                    write_kiva_cache($response);
                }else{
                    $total_loans = -1;
                }
            }
            if($total_loans != -1){
                $loans_array = $results->{'loans'};
                $total_loans = sizeof($loans_array);
            }

            if($total_loans == -1){
                $content = "<p>There was an error in retriving the loans from Kiva. Please try again later</p>";
            }else if($total_loans <= 0){
                $content = "<p>User has no loans!</p>";
            }else{
                $content = "<p>I have microlent to:</p>";     
            
                // Make this randomely choose one 
                $loan_count = 0;
                $used = array_fill(0, $total_loans, 0);
                for($i = 0; $i < $total_loans; $i++){
                    if($i < $limit){
                        $j = rand(0, $total_loans-1 );
                        while ($used[$j]) {
                            $j = rand(0, $total_loans-1 );
                        }   
                        $used[$j] = 1;
                        $loan = $loans_array[$j];
                        $content .= build_html($loan, $size);
                        $loan_count++;
                    }else{
                        break;
                    }
                }                
            }
        }

        $html = "<div id='kiva_loans'>"; 
        $html .= $content;
        $html .= "<center><a href='http://kiva.org/?app_id=$kiva_api_app_id'><img src='" . get_option('siteurl') . "/wp-content/plugins/kivaorg-widget/kiva.gif' alt='Kiva.org' class='kiva_logo' /></a></center>";
        $html .= "</div>";
        return $html;
    }

    function is_current_kiva_cache_available(){
        global $kiva_cache_dir;
        global $debug;

        if($debug == 1){
            echo "in is_current_kiva_cache_available<br />";
            flush();
        }

        $cache_file = "";
        if (is_dir($kiva_cache_dir)) {
            if ($dh = opendir($kiva_cache_dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(! is_dir($file)){
                        if( preg_match("/^kiva_cache_/", $file)){
    	                     $cache_file = $file;
                        }
                    }
                }
            }
        }

        if($cache_file != ""){
	    $cache_file_original = $cache_file;
            $cache_time = preg_replace("/kiva_cache_(\d+)\.txt/", "$1", $cache_file);
            $time = time();

            if($time - $cache_time < (60)){
            #if($time - $cache_time < (60 * 60)){
                // Cache is less than an hour old
                return 1;
            }else{
                // delete the old cache
                $file = "$kiva_cache_dir/$cache_file_original";
                unlink($file);
                check_for_old_cache_files();
            }
        }
        return 0;    
    }

    function check_for_old_cache_files(){
        global $kiva_cache_dir;

        $i = 0;
        $limit = 1000;
        if (is_dir($kiva_cache_dir)) {
            if ($dh = opendir($kiva_cache_dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(! is_dir($file)){
                        if( preg_match("/^kiva_cache_/", $file)){
                            // delelte the file
                            unlink("$kiva_cache_dir/$file");
                        }
                    }
                    $i++;
                    if($i == $limit){
                        break;
                    }
                }
            }
        }
    }

    function get_kiva_cache(){
        global $kiva_cache_dir;
        global $debug;

        if($debug == 1){
            echo "in get_kiva_cache<br />";
            flush();
        }

        $cache_file = "";
        if (is_dir($kiva_cache_dir)) {
            if ($dh = opendir($kiva_cache_dir)) {
                while (($file = readdir($dh)) !== false) {
		    if(! is_dir($file)){
                        if( preg_match("/^kiva_cache_/", $file)){
                             $cache_file = $file;
                        }
                    }
                }
            }
        }

        $cache_file_name = $kiva_cache_dir . "/" . $cache_file;

        if($debug == 1){
            echo "cache_file_name: $cache_file_name<br />";
            flush();
        }

        if(filesize($cache_file_name) > 0){ 
            $fh = fopen($cache_file_name, 'r') or die("can't open file: " . $cache_file_name);
            $response = fread($fh, filesize($cache_file_name));
            fclose($fh);
        }       

        $cache_file = json_decode($response);

        return $cache_file;
    }

    function write_kiva_cache($json_response){
        global $kiva_cache_dir;
        global $debug;

        if($debug == 1){
            echo "in write_kiva_cache with content: $json_response<br />";
            flush();
        }

        if($json_response == ""){
            return;
        }

        $time = time();
        $cache_file_name = "$kiva_cache_dir/kiva_cache_$time".".txt";

        $fh = fopen($cache_file_name, 'w') or die("can't open file: " . $cache_file_name);
        fwrite($fh, $json_response);
        fclose($fh);
    }

    function build_html($loan, $size){
        global $kiva_cache_dir;
        global $kiva_cache_path;

        $html = "<link rel='stylesheet' href='". get_option('siteurl') . "/wp-content/plugins/kivaorg-widget/style.css' type='text/css' media='screen' />";
        $html .= "<div class='loan'>";
        $html .= "<h3>" . $loan->{name}. "</h3><br />";
        
        $image_path = "$kiva_cache_dir/" . $loan->{'image'}->{'id'} . ".jpg";
        $image_src = "$kiva_cache_path/" . $loan->{'image'}->{'id'} . ".jpg";
        
        if(! file_exists($image_path)){
            $url = "http://www.kiva.org/img/w200h200/" . $loan->{'image'}->{'id'} . ".jpg";

            # Get and save the image
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            $image = curl_exec ($ch);
            curl_close ($ch);

            if($image != ""){
                $fh = fopen($image_path, 'w') or die("can't open file: " . $image_path);
                fwrite($fh, $image);
                fclose($fh);
            }
        }else{
            # Check for moved images for those empty images already retrieved
            $fh = fopen($image_path, 'r');
            $image = fread($fh, filesize($image_path));
            fclose($fh);

            $matches = preg_match("/302 Found/s", $image);

            if($matches){
                $movedImage = preg_match("/href=[\"']([^\"']+)[\"']/", $image, $movedImageURL);
                if($movedImage){
                    # Get and save the moved image
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $movedImageURL[1]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    $image = curl_exec ($ch);
                    curl_close ($ch);

                    if($image != ""){
                        $fh = fopen($image_path, 'w') or die("can't open file: " . $image_path);
                        fwrite($fh, $image);
                        fclose($fh);
                    }
                }
            }
        }

        
        global $kiva_api_app_id;
        $style = "max-height:".$size."px;max-width:".$size."px;width:expression(this.width > ".$size." ? \"".$size."px\" : this.width); height:expression(this.height > ".$size." ? \"".$size."px\" : this.height);";
        $html .= "<a href='http://www.kiva.org/app.php?page=businesses&action=about&app_id=$kiva_api_app_id&id=" . $loan->{id} . "' target='_new'><img src='$image_src' alt='". $loan->{name} . "' style='".$style."' /></a><br />";
        $html .= "<table>";
        $html .= "<tr><td style='vertical-align:top'><b>Location:</b></td><td>". $loan->{location}->{country} . ", " . $loan->{location}->{town} . "</td></tr>";
        $html .= "<tr><td style='vertical-align:top'><b>Activity:</b></td><td>" . $loan->{activity} . "</td></tr>";
        $html .= "</table><br />";
        $html .= "</div>";

        return $html;
    }

	function widget_kiva_loan_control() {
		$options = get_option('widget_kiva_loan');

		if ( $_POST['kiva_loan_submit'] ) {
			$newoptions['kiva_username'] = strip_tags(stripslashes($_POST['kiva_username']));
			$newoptions['number_of_loans'] = strip_tags(stripslashes($_POST['number_of_loans']));
            $newoptions['image_size'] = strip_tags(stripslashes($_POST['image_size']));

    		if ( $options != $newoptions ) {
    			$options = $newoptions;
                //echo "updating options <br />";
    			update_option('widget_kiva_loan', $options);
    		}
        }

		$username = htmlspecialchars($options['kiva_username'], ENT_QUOTES);
		$number_of_loans = htmlspecialchars($options['number_of_loans'], ENT_QUOTES);
        $image_size = htmlspecialchars($options['image_size'], ENT_QUOTES);

// The admin control for the widget
?>
		<div>
		<label for="kiva_username" style="line-height:35px;display:block;">Kiva Lender Name: <input type="text" id="kiva_username" name="kiva_username" value="<?php echo $username ?>" /></label>
		<label for="number_of_loans" style="line-height:35px;display:block;">Number of loans (Min. 1): <input type="text" id="number_of_loans" name="number_of_loans" value="<?php echo $number_of_loans; ?>" /></label>
        <label for="image_size" style="line-height:35px;display:block;">Image size (Max. 200): <br /><input type="text" id="image_size" name="image_size" value="<?php echo $image_size; ?>" /></label>
		<input type="hidden" name="kiva_loan_submit" id="kiva_loan_submit" value="1" />
		</div>
	<?php
	}

	// This registers the widget. 
	register_sidebar_widget('Kiva loans', 'widget_kiva_loan');

	// This registers the widget control form.
	register_widget_control('Kiva loans', 'widget_kiva_loan_control');
}

// Delays plugin execution until Dynamic Sidebar has loaded first.
add_action('plugins_loaded', 'widget_kiva_loan_init');

?>
