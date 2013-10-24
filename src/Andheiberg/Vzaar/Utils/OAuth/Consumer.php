<?php namespace Andheiberg\Vzaar\Utils\OAuth;

class Consumer {
	public $key;
	public $secret;

	function __construct($key, $secret, $callbackURL = NULL) {
		$this->key = $key;
		$this->secret = $secret;
		$this->callback_url = $callbackURL;
	}

	function __toString() {
		return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	}
}