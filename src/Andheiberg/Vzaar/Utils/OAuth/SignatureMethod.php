<?php namespace Andheiberg\Vzaar\Utils\OAuth;

class SignatureMethod {
	public function check_signature(&$request, $consumer, $token, $signature) {
		$built = $this->build_signature($request, $consumer, $token);
		return $built == $signature;
	}
}