<?php
/*
Plugin Name: Twitter Display and Cache
Plugin URI: http://www.knowhowto.com.au/use-twitter-display-cache-plugin-wordpress-website
Description: Displays And Chaches Recent Tweets
Author: Rashed Latif
Author URI: http://www.knowhowto.com.au/rashed-latif
Version: 1.0
*/

class TT_Twitter_Widget extends WP_Widget{
	function __construct(){
	$options = array(
		'description' => 'Display and Chache Tweets',
		'name' => 'TT: Display and Cache Tweets'
		);
	parent::__construct('TT_Twitter_Widget','',$options);
	}


	public function form($instance){
		extract($instance);
		?>
	<!-- Field for Title -->
        <p>
            <label for= "<?php echo $this->get_field_id('title');?>">Title: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('title');?>"
            name="<?php echo $this->get_field_name('title');?>"
            value="<?php if(isset($title)) echo esc_attr($title);?>"  />
        </p>
	
	<!-- Field for Consumer key -->
	<p>
            <label for= "<?php echo $this->get_field_id('consumerkey');?>">Consumer Key: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('consumerkey');?>"
            name="<?php echo $this->get_field_name('consumerkey');?>"
            value="<?php if(isset($consumerkey)) echo esc_attr($consumerkey);?>"  />
        </p>
	
	<!-- Field for Consumer secret -->
	<p>
            <label for= "<?php echo $this->get_field_id('consumersecret');?>">Consumer Secret: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('consumersecret');?>"
            name="<?php echo $this->get_field_name('consumersecret');?>"
            value="<?php if(isset($consumersecret)) echo esc_attr($consumersecret);?>"  />
        </p>
	
	<!-- Field for Access Token-->
	<p>
            <label for= "<?php echo $this->get_field_id('accesstoken');?>">Access Token: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('accesstoken');?>"
            name="<?php echo $this->get_field_name('accesstoken');?>"
            value="<?php if(isset($accesstoken)) echo esc_attr($accesstoken);?>"  />
        </p>
        
	
	<!-- Field for Access Token Secret -->
	<p>
            <label for= "<?php echo $this->get_field_id('accesstokensecret');?>">Access Token Secret: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('accesstokensecret');?>"
            name="<?php echo $this->get_field_name('accesstokensecret');?>"
            value="<?php if(isset($accesstokensecret)) echo esc_attr($accesstokensecret);?>"  />
        </p>
	
	
	<!-- Field for Username -->	
        <p>
            <label for= "<?php echo $this->get_field_id('username');?>">Twitter Username: </label>
            
            <input type="text" 
            class="widefat" 
            id="<?php echo $this->get_field_id('username');?>"
            name="<?php echo $this->get_field_name('username');?>"
            value="<?php if(isset($username)) echo esc_attr($username);?>"  />
        </p>
	
	<!-- Field for Number of Tweets -->      
        <p>
            <label for= "<?php echo $this->get_field_id('tweet_count');?>">Number of Tweets to Retrieve: </label>
            
            <input type="number" 
            class="widefat" 
            style="width:40px;"
            id="<?php echo $this->get_field_id('tweet_count');?>"
            name="<?php echo $this->get_field_name('tweet_count');?>"
            min="1"
            max="10"
            value="<?php echo !empty($tweet_count) ? $tweet_count : 5; ?>"  />
        </p>


        
        <?php	
	}
	public function widget($args, $instance){
		extract($args);
		extract($instance);
				
		if (empty($title)) $title = 'Recent Tweets';
		$data = $this->twitter($tweet_count, $username, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
		
		if($data !== false && isset($data->tweets)){
			
			echo $before_widget;
				echo $before_title;
					echo $title;
				echo $after_title;
				echo '<ul><li>' . implode('</li><li>', $data->tweets) . '</li></ul>';
			echo $after_widget;
			
		}
		

	}
	
	private function twitter($tweet_count, $username, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret){
		if(empty($username)) return false;
		
		$tweets = get_transient('recent_tweets_widget');
		if(!$tweets || $tweets->username !== $username || $tweets->tweet_count !== $tweet_count){
			return $this->fetch_tweets($tweet_count, $username, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
		}
		return $tweets;
		
	}
	
	private function fetch_tweets($tweet_count, $username, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret){
	
		//session_start();
		require_once("twitteroauth/twitteroauth.php"); //Path to twitteroauth library
		 
		
		function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
		  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
		  return $connection;
		} 
		$connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
		 		
		$tweets = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=".$username."&count=".$tweet_count);
		
		
		if (isset($tweets->error)) return false;
		
		$data = new stdClass();
		$data->username = $username;
		$data->tweet_count = $tweet_count;
		$data->tweets = array();
		
		foreach($tweets as $tweet){		
		       	
			$data->tweets[] = $this->filter_tweet($tweet->text);
		}        
		
		set_transient('recent_tweets_widget', $data, 60*5);
		return $data;
	}
	
	private function filter_tweet($tweet){
		
		$tweet = preg_replace('/(http[^\s]+)/im', '<a href="$1">$1</a>', $tweet);
		$tweet = preg_replace('/@([^\s]+)/i', '<a href="http://twitter.com/$1">@$1</a>', $tweet);
		return $tweet;
	}
}

add_action('widgets_init','register_jw_twitter_widget');
function register_jw_twitter_widget(){
	register_widget('TT_Twitter_Widget');
}

?>