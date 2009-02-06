<?php
/*
Plugin Name: Kiva Widget
Plugin URI: http://kiva.com
Description: Kiva widget, display my investments
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

        $options = get_option('widget_kiva_loan');
        $limit = $options['number_of_loans'];
        $size = $options['image_size'];
        $username = $options['kiva_username'];

        $content = "";
        if(! $username){
            $content = "<p>No user specified!</p>";
        }else{
            $url = "http://api.kivaws.org/v1/lenders/$username/loans.json";
    
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec ($ch);
            curl_close ($ch);
            $results = json_decode($response);
    
            $loans_array = $results->{'loans'};
            $total_loans = sizeof($loans_array);

            if($total_loans == 0){
                $content = "<p>User has no loans!</p>";
            }else{
                $content = "<p>I have microlent to:</p>";     
            
                // Make this randomely choose one 
                $loan_count = 0;
                $used = array_fill(0, $total_loans-1, 0);
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
        $html .= "<center><a href='http://kiva.org/'><img src='http://images.kiva.org/images/logoLeafy3.gif' alt='Kiva.org' class='kiva_logo' /></a></center>";
        $html .= "</div>";
        return $html;
    }

    function build_html($loan, $size){

        $html = "<link rel='stylesheet' href='". get_option('siteurl') . "/wp-content/plugins/kiva-widget/style.css' type='text/css' media='screen' />";
        $html .= "<div class='loan'>";
        $html .= "<h3>" . $loan->{name}. "</h3><br />";
        $img_source = "http://www.kiva.org/img/$size/" . $loan->{'image'}->{'id'} . ".jpg";
        $html .= "<a href='http://www.kiva.org/app.php?page=businesses&action=about&id=" . $loan->{id} . "' target='_new'><img src='$img_source' alt='". $loan->{name} . "' /></a><br />";
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
                echo "updating options <br />";
    			update_option('widget_kiva_loan', $options);
    		}
        }

		$username = htmlspecialchars($options['kiva_username'], ENT_QUOTES);
		$number_of_loans = htmlspecialchars($options['number_of_loans'], ENT_QUOTES);
        $image_size = htmlspecialchars($options['image_size'], ENT_QUOTES);

// The admin control for the widget
?>
		<div>
		<label for="kiva_username" style="line-height:35px;display:block;">Kiva Username: <input type="text" id="kiva_username" name="kiva_username" value="<?php echo $username ?>" /></label>
		<label for="number_of_loans" style="line-height:35px;display:block;">Number of loans: <input type="text" id="number_of_loans" name="number_of_loans" value="<?php echo $number_of_loans; ?>" /></label>
        <label for="image_size" style="line-height:35px;display:block;">Image size: <br /><input type="text" id="image_size" name="image_size" value="<?php echo $image_size; ?>" /></label>
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
