<?php
/** 
 * HTTP CURL Class.
 * @package classes.service
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 * @date 2011/02/14 16:53:50
 * @version  1.4 $
 */



/** httpResponse */
require_once  CLASS_DIR.'/http/response.php';

/**
 * HTTP CURL 
 * @category   classes.http
 * @package    .http
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 */
class HttpCurl{
	/**
	 * HTTP request methods
	 */
	const HTTP_METHOD_GET = 'GET';
	const HTTP_METHOD_POST = 'POST';
	const HTTP_METHOD_PUT = 'PUT';
	const HTTP_METHOD_HEAD = 'HEAD';
	const HTTP_METHOD_DELETE = 'DELETE';
	const HTTP_METHOD_TRACE = 'TRACE';
	const HTTP_METHOD_OPTIONS = 'OPTIONS';
	
	/**
	 * Options for cURL. Defaults to preferred (constant) options.
	 */
	protected $_curlOptions = array (CURLOPT_HEADER => 1, CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Curl/ Litb  1.4", CURLOPT_TIMEOUT=>TICKET_TIMEOUT,CURLOPT_CONNECTTIMEOUT => TICKET_TIMEOUT, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_RETURNTRANSFER => true, CURLOPT_FORBID_REUSE => true );
	/**
	 * 
	 * request Headers
	 * @var array
	 */
	protected $_requestHeaders = array ();
	/**
	 * request cookies
	 */
	protected $_requestCookies = array();	
	/**
	 * Constructor method. 
	 */
	public function __construct() {
	
	}
	
	public function reset() {
		return $this;
	}
	
	/**
	 * Set cURL options.
	 */
	public function setCurlOption($name, $value) {
		$this->_curlOptions [$name] = $value;
	}
	
	/**
	 * set a username and password to access a protected resource
	 * Only "Basic" authentication scheme is supported yet
	 * @param username string - identifier
	 * @param password string - clear password
	 **/
	public function setCredentials($username, $password) {
		$this->addHeader ( 'Authorization', 'Basic ' . base64_encode ( $username . ':' . $password ) );
	}
	
	/**
	 * define a set of HTTP headers to be sent to the server
	 * header names are lowercased to avoid duplicated headers
	 * @param headers hash array containing the headers as headerName => headerValue pairs
	 **/
	function setHeaders($headers) {
		if (is_array ( $headers )) {
			foreach ( $headers as $name => $value ) {
				$this->addHeader ( $name, $value );
			}
		}
	}
	
	/**
	 * addHeader
	 * set a unique request header
	 * @param headerName the header name
	 * @param headerValue the header value, ( unencoded)
	 **/
	public function addHeader($headerName, $headerValue = null) {
		$lower_name = strtolower ( $headerName );
		
		// Check if $name needs to be split
		if ($headerValue === null && (strpos ( $headerName, ':' ) > 0)) {
			list ( $headerName, $headerValue ) = explode ( ':', $headerName, 2 );
		}
		
		// Make sure the name is valid
		if (! preg_match ( '/^[a-zA-Z0-9-]+$/', $headerName )) {
			return false;
		}
		
		// If $value is null or false, unset the header
		if ($headerValue === null || $headerValue === false) {
			unset ( $this->_requestHeaders [$lower_name] );
			return false;
		
		// Else, set the header
		} else {
			// Header names are stored lowercase internally.
			if (is_string ( $headerValue )) {
				$headerValue = trim ( $headerValue );
			}
			$this->_requestHeaders [$lower_name] = array ($headerName, $headerValue );
		}
		return true;
	
	}
	
	/**
	 * removeHeader
	 * unset a request header
	 * @param headerName the header name
	 **/
	public function removeHeader($headerName) {
		$lower_name = strtolower ( $headerName );
		unset ( $this->_requestHeaders [$lower_name] );
	}
	
	/**
	 * add Cookie[for session...]
	 * array( fruit=>apple
	 * 		   colour=>red)
	 */
	public function addCookies($cookiesArr){
		if (is_array($cookiesArr)){
			$this->_requestCookies = array_merge($this->_requestCookies,$cookiesArr);	
		}
	}	
	/**
	 * Get
	 * Send a GET request
	 * @param $url  String | URL
	 * @return Object httpResponse
	 */
	public function doGet($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
			$url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
			}

        return $this->_request ( self::HTTP_METHOD_GET, $url );
	}
	
	/**
	 * POST
	 * Send a POST request
	 * @param $url  String | URL
	 * @return Object httpResponse
	 */
	public function doPost($url, $postVars) {
		return $this->_request ( self::HTTP_METHOD_POST, $url, $postVars );
	}
	
	/**
	 * PUT
	 * Send a PUT request
	 * @param $url  String | URL
	 * @return Object httpResponse
	 */
	public function doPut($url, $putVars=null) {
		return $this->_request ( self::HTTP_METHOD_PUT, $url, $putVars );
	}
	
	/**
	 * DELETE
	 * Send a DELETE request
	 * @param $url  String | URL
	 * @return Object httpResponse
	 */
	public function doDelete($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
            $url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
		}

		return $this->_request ( self::HTTP_METHOD_DELETE, $url );
	}
	
	/**
	 * HEAD
	 * Send a HEAD request
	 * @param $url  String | URL
	 * @return Object httpResponse
	 */
	public function doHead($url) {
		return $this->_request ( self::HTTP_METHOD_HEAD, $url );
	}
	
	/**
	 * 
	 * do http request
	 * @param string $url 
	 * @param array $postargs post参数
	 */
	final private function _request($method, $url, $postargs = array()) {
	    if(isset($_GET['curl_debug'.CSS_JS_VERSION.'_start']) && $_GET['curl_debug'.CSS_JS_VERSION.'_start'] == 100){
	        list($usec, $sec) = explode(" ", microtime());
		    $timedebug1 = ((float)$usec + (float)$sec);	
	    }
		// Get the curl session object
		$session = curl_init ( $url );
		
		//设置CURL Options
		foreach ( $this->_curlOptions as $name => $value ) {
			curl_setopt ( $session, $name, $value );
		}
		
		//set http header options
		if (! empty ( $this->_requestHeaders ) && is_array ( $this->_requestHeaders )) {
			$headers = $this->_prepareHeaders ();
			curl_setopt ( $session, CURLOPT_HTTPHEADER, $headers );
		}
		//set cookies to header
		if (!empty($this->_requestCookies)){
			$cookies = $this->_prepareCookies();
			curl_setopt($session, CURLOPT_COOKIE, $cookies);
		}
		if ($method == self::HTTP_METHOD_POST) {
			curl_setopt ( $session, CURLOPT_POST, 1 );
			curl_setopt ( $session, CURLOPT_POSTFIELDS, $postargs );
		}
		elseif ($method == self::HTTP_METHOD_PUT) {
            curl_setopt ( $session, CURLOPT_CUSTOMREQUEST, 'PUT');
            $headers[] = 'Content-Length: ' . strlen($postargs);
            curl_setopt ( $session, CURLOPT_HTTPHEADER, $headers);
			curl_setopt ( $session, CURLOPT_POSTFIELDS, $postargs );
		}
		elseif ($method == self::HTTP_METHOD_DELETE) {
            curl_setopt ( $session, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		//PAYPAL忽略ssl验证
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2);
        
        //
        //curl_setopt($session, CURLOPT_HTTPHEADER, array('Expect:')); 
		// Do the curl and then close the session
		$response = curl_exec ( $session );
		$curl_info = curl_getinfo ( $session );
		
		$httpResp = null;
		if (curl_errno ( $session )) {
			$err_msg = curl_error ( $session );
		} else {
			curl_close ( $session );
			$httpResp = HttpResponse::fromString ( $response );
		}
		//如果请求rul里带上开启curl调试参数则发邮件
		if(isset($_GET['curl_debug'.CSS_JS_VERSION.'_start']) && $_GET['curl_debug'.CSS_JS_VERSION.'_start'] == 100){
            list($usec, $sec) = explode(" ", microtime());
		    $timedebug2 = ((float)$usec + (float)$sec);
		    //log_error ( array('logKey'=>'curl debug','desc'=>'curl debug','curl url'=>$url,'request url'=>$_SERVER['REQUEST_URI'],'method'=> $method, 'cost time'=>$timedebug2 - $timedebug1));
        }
		return $httpResp;
	}
	
	/**
	 * 
	 * Header 进行预处理
	 */
	private function _prepareHeaders() {
		
		$headers = array ();
		
		// Add all other user defined headers
		foreach ( $this->_requestHeaders as $header ) {
			list ( $name, $value ) = $header;
			if (is_array ( $value )) {
				$value = implode ( ', ', $value );
			}
			
			$headers [] = "$name: $value";
		}
		
		return $headers;
	
	}
	
	/**
	 * add Cookie[for session...]
	 * fruit=apple; colour=red
	 */
	public function _prepareCookies(){
		//
		$cookies = array();
		if (is_array($this->_requestCookies)){
			foreach($this->_requestCookies as $key => $value){
				if(!empty($key)){
					$cookies[] = $key . '=' . $value;
				}
			}
			return implode ( '; ', $cookies );
		}
		return false;
	}	
}
