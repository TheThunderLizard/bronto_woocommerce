<?php

class Bronto_EmailSignUp_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(false, $name='Bronto: Email Sign Up', $widget_options=array(
          'description' => 'Allow people to subscribe to a Bronto email list.'
        ));
    }
	
    function widget($args, $instance) {

        extract($args);
        $bronto_settings = get_option('bronto_settings');
        $list_id = $instance['list_id'];
		
        if (empty($list_id)) {
          return;
        }

        $title = $instance['title'];
        $description = $instance['description'];
        $button_text = $instance['button_text'];

        if (!empty($button_text)) {
          $button_text = 'Subscribe';
        }

        echo $before_widget;

        if (trim($title) != '') {
            echo $before_title . $title . $after_title;
        }

        echo '  <input type="hidden" name="bronto_list" value="' . $list_id . '">' . "\n";

        if(!empty($description)) {
          echo '  <p>' . $description . '</p>' . "\n";
        }

        echo '<div class="bronto_field_group" id="subscriber_form">' . "\n";
        echo '  <label for="bro_email" style="display:none;">' . $title .'</label>' . "\n";
        echo '  <input type="email" value="" name="email" id="email" placeholder="Your email" />' . "\n";
        echo '  <button type="submit" class="bronto_submit_button" id="submit">' . $button_text . '</button>' . "\n";
        echo '</div>' . "\n";
        echo '<div class="bronto_messages">' . "\n";
        echo '  <div class="success_message" style="display:none;"></div>' . "\n";
        echo '  <div class="error_message" style="display:none;"></div>' . "\n";
        echo '</div>' . "\n";
        ?>
        
        <script type="text/javascript">
        
			var selectors = [".bronto_submit_button"];

			function validateEmail(email) {
			  var re = /^(([^<>()\[\]\.,;:\s@"]+(\.[^<>()\[\]\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			  return re.test(String(email).toLowerCase());
			}

			function runListeners(brSelector){
			  var brSubBtn = document.querySelectorAll(brSelector); 
			  for (var i = 0; i < brSubBtn.length; i++) { 
				brSubBtn[i].addEventListener("click", function(){
				  var _email= ""; 
				  for (var j = 0; j < document.querySelectorAll("input[type='email']").length; j++) { 
					if (document.querySelectorAll("input[type='email']")[j].value) {
					  _email = document.querySelectorAll("input[type='email']")[j].value; 
					  if (validateEmail(_email)){
						var _bod = document.getElementsByTagName('body')[0]; 
						var _pixel = document.createElement("img"); 
						var _url = "//app.bronto.com/public/?q=direct_add&fn=Public_DirectAddForm&createCookie=1"; 
						_pixel.src = _url + "&id=<?php echo $bronto_settings['bronto_direct_add_id'] ?>&email=" + _email + "&list1=<?php echo $list_id ?>"; 
						_bod.appendChild(_pixel); 
						document.querySelectorAll('.success_message')[j].innerText ="YOU HAVE BEEN SUBSCRIBED"; 
						document.querySelectorAll('.success_message')[j].style.display="block";
						document.querySelectorAll('.success_message')[j].style.color="#0ebb1c";
						document.querySelectorAll('.bronto_field_group')[j].style.display="none";
						break;
					  } else {
						document.querySelectorAll('.error_message')[j].innerText="INVALID EMAIL ADDRESS";
						document.querySelectorAll('.error_message')[j].style.display="block";
						document.querySelectorAll('.success_message')[j].style.color="#bb0e1d";
						break;
					  }
					}
				  }
				})
			  }
			}

			for (var i = 0; i < selectors.length; i++) { 
			  runListeners(selectors[i]);
			};
		
		</script>
		<?php


        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        return array_merge($old_instance, $new_instance);
    }

    function form($instance) {
        $instance = wp_parse_args($instance, array('title' => '', 'list_id' => '', 'description' => '', 'button_text' => ''));
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" /></label></p>
        <label for="<?php echo $this->get_field_id('description'); ?>"><?php _e('List Description:'); ?></label>
        <textarea class="widefat" rows="3" cols="20" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>"><?php echo $instance['description']; ?></textarea>
        <p><label for="<?php echo $this->get_field_id('button_text'); ?>"><?php _e('Button Text:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('button_text'); ?>" name="<?php echo $this->get_field_name('button_text'); ?>" type="text" value="<?php echo $instance['button_text']; ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('list_id'); ?>"><?php _e('List ID:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('list_id'); ?>" name="<?php echo $this->get_field_name('list_id'); ?>" type="text" value="<?php echo $instance['list_id']; ?>" /></label></p>
        <?php
    }

}

?>
