<?php namespace Andheiberg\Vzaar\Utils\OAuth;

class SignatureMethod_PLAINTEXT extends SignatureMethod {
	public function get_name() {
		return "PLAINTEXT";
	}

	public function build_signature($request, $consumer, $token) {
		$sig = array(
			OAuthUtil::urlencode_rfc3986($consumer->secret)
		);

		if ($token) {
			array_push($sig, Util::urlencode_rfc3986($token->secret));
		} else {
			array_push($sig, '');
		}

		$raw = implode("&", $sig);
		// for debug purposes
		$request->base_string = $raw;

		return Util::urlencode_rfc3986($raw);
	}
}