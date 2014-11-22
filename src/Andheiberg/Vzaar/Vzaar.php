<?php namespace Andheiberg\Vzaar;

use Andheiberg\Vzaar\Utils\OAuth\Consumer as OAuthConsumer;
use Andheiberg\Vzaar\Utils\OAuth\Token as OAuthToken;
use Andheiberg\Vzaar\Utils\OAuth\Request as OAuthRequest;
use Andheiberg\Vzaar\Utils\OAuth\SignatureMethod_HMAC_SHA1 as OAuthSignatureMethod_HMAC_SHA1;
use Andheiberg\Vzaar\Utils\OAuth\Exception as OAuthException;
use Andheiberg\Vzaar\Utils\HttpRequest as HttpRequest;
use Andheiberg\Vzaar\Utils\XMLToArray;

use Andheiberg\Vzaar\Models\AccountType;
use Andheiberg\Vzaar\Models\User;
use Andheiberg\Vzaar\Models\VideoList;
use Andheiberg\Vzaar\Models\VideoDetails;
use Andheiberg\Vzaar\Models\UploadSignature;
use Andheiberg\Vzaar\Models\Profile;


class Vzaar
{
	/**
	 * The AWS S3 token to be used when communicating with AWS S3.
	 *
	 * @var string
	 */
	public $token;

	/**
	 * The secret AWS S3 key used to sign request to AWS S3.
	 *
	 * @var string
	 */
	public $secret;

	/**
	 * Indicates if videos should be optimized for flash support.
	 *
	 * @var boolean
	 */
	public $flashSupport;

	/**
	 * The url for the Vzaar API to be used.
	 *
	 * @var string
	 */
	protected $apiURL = "https://vzaar.com/";

	/**
	 * Create a new Vzaar instance.
	 *
	 * @param  string $token
	 * @param  string $secret
	 * @return void
	 */
	public function __construct($token, $secret, $flashSupport = false)
	{
		$this->token  = $token;
		$this->secret = $secret;
		$this->flashSupport = $flashSupport;
	}

	/**
	 * Get the the associated username for this Vzaar instance.
	 *
	 * @return array
	 */
	public function setAuth($token, $secret)
	{
		$this->token  = $token;
		$this->secret = $secret;

		return $this;
	}

	/**
	 * Get the the associated username for this Vzaar instance.
	 *
	 * @return array
	 */
	public function whoAmI()
	{
		$url = $this->apiURL . 'api/test/whoami.json';

		$req = $this->authenticate($url);

		$c = new HttpRequest($url);

		array_push($c->headers, $req->to_header());
		array_push($c->headers, 'User-Agent: Vzaar OAuth Client');

		$response = json_decode($c->send());
		
		// Special exceptions to work around the Vzaar API's inconsistent error responses
		$api = isset($response->vzaar_api) ? $response->vzaar_api : null;

		if (is_null($api))
		{
			$api = isset($response->{'vzaar-api'}) ? $response->{'vzaar-api'} : null;
		}
		
		if (is_null($api))
		{
			throw new OAuthException("Authentication failed with malformed response");
		}

		if (isset($api->error))
		{
			throw new OAuthException("Authentication failed with message {$api->error->type}");
		}

		return $api->test->login;
	}

	/**
	 * This API call returns the details and rights for each vzaar account
	 * type along with it's relevant metadata
	 * http://vzaar.com/api/accounts/{account}.json
	 *
	 * @param  int    $account
	 * @return Vzaar\AccountType
	 */
	public function accountDetails($account)
	{
		$req = new HttpRequest("{$this->apiURL}api/accounts/{$account}.json");

		return AccountType::fromJson($req->send());
	}

	/**
	 * This API call returns the user's public details along with it's
	 * relevant metadata
	 *
	 * @param  string $account
	 * @return Vzaar\User
	 */
	public function userDetails($user)
	{
		$req = new HttpRequest("{$this->apiURL}api/{$user}.json");

		return User::fromJson($req->send());
	}

	/**
	 * This API call returns a list of the user's active videos along with it's
	 * relevant metadata
	 * http://vzaar.com/api/vzaar/videos.xml?title=vzaar
	 *
	 * @param  string $username Note: This must be the username and not the email address
	 * @param  int    $count
	 * @return Vzaar\VideoList
	 */
	public function videoListForUser($username, $auth = false, $count = 20, $labels = null, $status = null)
	{
		$params = compact('count', 'labels', 'status');
		$url = "{$this->apiURL}api/{$username}/videos.json?" . http_build_query($params);

		$req = new HttpRequest($url);

		if ($auth)
		{
			array_push($req->headers, $this->authenticate($url, 'GET')->to_header());
		}

		return VideoList::fromJson($req->send());
	}

	/**
	 * This API call returns a list of the user's active videos along with it's
	 * relevant metadata
	 *
	 * @param  string $username Note: This must be the actual username and not the email address
	 * @param  string $title    Return only videos with title containing given string
	 * @param  int    $count    Specifies the number of videos to retrieve per page. Default is 20. Maximum is 100
	 * @param  int    $page     Specifies the page number to retrieve. Default is 1
	 * @param  string $sort     Values can be asc (least_recent) or desc (most_recent). Defaults to desc
	 * @return Vzaar\VideoList
	 */
	public function searchVideoList($username, $auth = false, $title = null, $labels = null, $count = 20, $page = 1, $sort = 'desc')
	{
		$params = compact('count', 'page', 'sort', 'labels', 'title');
		$url = "{$this->apiURL}api/{$username}/videos.json?" . http_build_query($params);

		$req = new HttpRequest($url);

		if ($auth)
		{
			array_push($req->headers, $this->authenticate($url, 'GET')->to_header());
		}

		return VideoList::fromJson($req->send());
	}

	/**
	 * vzaar uses the oEmbed open standard for allowing 3rd parties to
	 * integrated with the vzaar. You can use the vzaar video URL to easily
	 * obtain the appropriate embed code for that video
	 *
	 * @param  int     $id
	 * @return Vzaar\VideoDetails
	 */
	public function videoDetails($id, $auth = false)
	{
		$url = "{$this->apiURL}api/videos/{$id}.json";

		$req = new HttpRequest($url);

		if ($auth)
		{
			array_push($req->headers, $this->authenticate($url, 'GET')->to_header());
		}

		return VideoDetails::fromJson($req->send());
	}

	/**
	 *
	 *
	 * @param  string $path
	 * @return string GUID of the file uploaded
	 */
	public function uploadVideo($path)
	{
		$signature = $this->uploadSignature();

		$req = new HttpRequest("https://{$signature['bucket']}.s3.amazonaws.com/");

		$req->method     = 'POST';
		$req->uploadMode = true;
		$req->verbose    = false;
		$req->useSsl     = true;

		array_push($req->headers, 'User-Agent: Vzaar API Client');
		array_push($req->headers, 'x-amz-acl: ' . $signature['acl']);
		array_push($req->headers, 'Enclosure-Type: multipart/form-data');

		$s3Headers = array(
			'AWSAccessKeyId' => $signature['accesskeyid'],
			'Signature'      => $signature['signature'],
			'acl'            => $signature['acl'],
			'bucket'         => $signature['bucket'],
			'policy'         => $signature['policy'],
			'success_action_status' => 201,
			'key'            => $signature['key'],
			'file'           => "@" . $path
		);

		$reply  = $req->send($s3Headers, $path);
		$xmlObj = new XMLToArray($reply, array(), array(), true, false);
		$arrObj = $xmlObj->getArray();
		$key    = explode('/', $arrObj['PostResponse']['Key']);
		
		return $key[sizeOf($key) - 2];
	}

	/**
	 * Get a signed signature
	 *
	 * @param  string $redirectUrl
	 * @return array
	 */
	public function uploadSignature($redirectUrl = null)
	{
		$signature = $this->uploadSignatureAsXml($redirectUrl);
		$signature = UploadSignature::fromXml($signature);

		return $signature['vzaar-api'];
	}

	/**
	 * Get a signed signature as XML
	 * Redirect Url should not be URL Encoded for sign to work
	 *
	 * @param  string $redirectUrl
	 * @return XML
	 */
	public function uploadSignatureAsXml($redirectUrl = null)
	{
		$params = [
			'flash_request' => $this->flashSupport ? true : null,
			'success_action_redirect' => $redirectUrl
		];

		// remove entries having null/false
		$params = array_filter($params);

		$urlParams = '';
		foreach ($params as $key => $value) {
			$urlParams .= '&'.$key.'='.$value;
		}
		$urlParams = ltrim($urlParams, '&');

		$url = $this->apiURL . 'api/videos/signature?' . $urlParams;

		$req = $this->authenticate($url, 'GET');

		$c = new HttpRequest($url);
		$c->method = 'GET';
		array_push($c->headers, $req->to_header());
		array_push($c->headers, 'User-Agent: Vzaar OAuth Client');

		return $c->send();
	}

	/**
	 * Delete a video
	 *
	 * @param  string $id
	 * @return string
	 */
	public function deleteVideo($id)
	{
		$url = $this->apiURL . "api/videos/" . $id . ".xml";

		$req = $this->authenticate($url, 'DELETE');

		$data = '<?xml version="1.0" encoding="UTF-8"?><vzaar-api><_method>delete</_method></vzaar-api>';

		$c = new HttpRequest($url);
		$c->method = 'DELETE';
		array_push($c->headers, $req->to_header());
		array_push($c->headers, 'User-Agent: Vzaar OAuth Client');
		array_push($c->headers, 'Connection: close');
		array_push($c->headers, 'Content-Type: application/xml');

		return $c->send($data);
	}

	/**
	 * Edit a videos meta-data
	 *
	 * @param  int    $id
	 * @param  string $title
	 * @param  string $description
	 * @param  bool   $private
	 * @param  string $seoUrl
	 * @return string
	 */
	public function editVideo($id, $title, $description, $private = false, $seoUrl = null)
	{
		$url = $this->apiURL . "api/videos/" . $id . ".xml";

		$req = $this->authenticate($url, 'POST');

		$data  = '<?xml version="1.0" encoding="UTF-8"?><vzaar-api><_method>put</_method><video>';
		$data .= '<title>' . $title . '</title>';
		$data .= '<description>' . $description . '</description>';
		
		if ($private)
		{
			$data .= '<private>' . $private . '</private>';
		}
		if ($seoUrl)
		{
			$data .= '<seo_url>' . $seoUrl . '</seo_url>';
		}
		
		$data .= '</video></vzaar-api>';

		$c = new HttpRequest($url);
		$c->method = 'POST';
		array_push($c->headers, $req->to_header());
		array_push($c->headers, 'User-Agent: Vzaar OAuth Client');
		array_push($c->headers, 'Connection: close');
		array_push($c->headers, 'Content-Type: application/xml');

		return $c->send($data);
	}

	/**
	 * This API call tells the vzaar system to process a newly uploaded video. This will encode it if necessary and
	 * then provide a vzaar video idea back.
	 * http://developer.vzaar.com/docs/version_1.0/uploading/process
	 *
	 * @param string $guid
	 * @param string $title
	 * @param string $description
	 * @param string $labels
	 * @param Vzaar\Profile $profile Specifies the size for the video to be encoded in. If not specified, this will use the vzaar default
	 * @param bool   $transcoding    If True forces vzaar to transcode the video, false makes vzaar use the original source file (available only for mp4 and flv files)
	 * @param string $replace        Specifies the video ID of an existing video that you wish to replace with the new video.
	 * @return string
	 */
	public function processVideo($guid, $title, $description, $labels, $profile = Profile::Medium, $transcoding = false, $replace = false)
	{
		$url = $this->apiURL . "api/videos";

		$replace = $replace ? '<replace_id>' . $replace . '</replace_id>' : '';

		$req = $this->authenticate($url, 'POST');

		$data = '<vzaar-api>
		    <video>' . $replace . '
			<guid>' . $guid . '</guid>
		        <title>' . $title . '</title>
		        <description>' . $description . '</description>
		        <labels>' . $labels . '</labels>
	        	<profile>' . $profile . '</profile>';
		if ($transcoding) $data .= '<transcoding>true</transcoding>';
		$data .= '</video> </vzaar-api>';

		$c = new HttpRequest($url);
		$c->verbose = false;
		$c->method = "POST";
		array_push($c->headers, $req->to_header());
		array_push($c->headers, 'User-Agent: Vzaar OAuth Client');
		array_push($c->headers, 'Connection: close');
		array_push($c->headers, 'Content-Type: application/xml');

		$apireply = new XMLToArray($c->send($data));
		return $apireply->_data[0]["vzaar-api"]["video"];
	}

	/**
	 * Setup a request for authentication
	 *
	 * @param  string $url
	 * @param  string $method
	 * @return object
	 */
	public function authenticate($url, $method = 'GET')
	{
		$consumer = new OAuthConsumer('', '');
		$token = new OAuthToken($this->secret, $this->token);
		$req = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $url);
		$req->set_parameter('oauth_signature_method', 'HMAC-SHA1');
		$req->set_parameter('oauth_signature', $req->build_signature(new OAuthSignatureMethod_HMAC_SHA1, $consumer, $token));
		
		return $req;
	}

	/**
	 * Get the endpoint for a specific user.
	 *
	 * @param  int    $user
	 * @return json
	 */
	public function endpointForUser($user)
	{
		return $this->apiURL . 'users/' . $user . '.json';
	}

	/**
	 * Get the endpoint for a specific users videos.
	 *
	 * @param  int    $user
	 * @param  int    $count
	 * @return json
	 */
	public function endpointForVideos($user, $count = 1)
	{
		return "{$this->apiURL}api/{$user}/videos.json?count={$count}";
	}

}
