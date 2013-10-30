<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_meteodegrees extends sensor {
	# http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20geo.places%20where%20text%3D%22clichy%22&format=xml
	public $city_code = "55863454";
	public $unit = "c";
	public $url = "http://weather.yahooapis.com/forecastrss?w=%d&u=%s";
		
	function __construct($actionName, $flow_id) {
		// flow_id=8
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
		$this->url			= sprintf("http://weather.yahooapis.com/forecastrss?w=%d&u=%s", $this->city_code, $this->unit);
	}
	
    public function getCurrent() {
    	$bf = new BlogFeed($this->url);

    	$data[$this->actionName] = array(
			'city'		=> $bf->getCity(0),
			'city_code'	=> $this->city_code,
			'country'	=> $bf->getCountry(0),
			'link'		=> $bf->getLink(0),
			'unit'		=> $this->unit,
			'url'		=> $this->url,
			'temp'		=> $bf->getTemp(0),
			'text'		=> $bf->getText(0),
			'ts'		=> $bf->getTs(0)
    	);
		return $data;
    }
}


/* ************************************************ */


class BlogPost {
	var $date;
	var $ts;
	var $link;
	var $title;
	var $text;
	var $temp;
	var $city;
	var $country;
}

class BlogFeed {
	var $posts = array();

	function BlogFeed($file_or_url) {
		if(!preg_match('/^http:/i', $file_or_url))
			return false;
		else
			$feed_uri = $file_or_url;

		$feed = file_get_contents($feed_uri);
		$x = new SimpleXmlElement($feed);

		if(count($x) == 0)
			return false;

		foreach($x->channel->item as $item) {
			$post = new BlogPost();
			$post->date = (string) $item->pubDate;
			$post->ts = strtotime($item->pubDate);
			$post->link = (string) $item->link;
			$post->title = (string) $item->title;
			//$post->text = (string) $item->description;

			$namespaces = $item->getNameSpaces(true);
			$yweather = $item->children($namespaces['yweather']);
			$post->temp		= (string) $yweather->condition->attributes()->temp[0];
			$post->text		= (string) $yweather->condition->attributes()->text[0];

			$this->posts[] = $post;
		}
		$channel		= $x->channel->children($namespaces['yweather']);
		$post->city		= (string) $channel->location->attributes()->city[0];
		$post->country	= (string) $channel->location->attributes()->country[0];
		$post->link		= (string) $x->channel->link;
		$this->posts[]	= $post;
	}

	function getTs($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->ts;
	}

	function getTemp($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->temp;
	}

	function getText($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->text;
	}

	function getCity($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->city;
	}

	function getCountry($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->country;
	}

	function getLink($item_id=0) {
		$my_post = $this->posts[$item_id];
		return $my_post->link;
	}

	function getPosts() {
		return $this->posts;
	}
}

?>