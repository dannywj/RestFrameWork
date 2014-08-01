<?php 
/**
 * RESTful Client Class  参考Zend Framework
 *
 * @package classes.rest
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 * @date 2011/02/14 16:53:50
 * @version  1.4 $
 */

/** serviceAbstract */
require_once CLASS_DIR.'/abstract.php';

/** httpResponse */
require_once CLASS_DIR.'/http/response.php';

/**
 * @category   classes
 * @package    classes.rest
 * @subpackage client
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 */
class RestClient extends ServiceAbstract {
	
	private $_contentFormat = null;
	private $_charset = "utf-8";
	private $_originalResponse = null;
	
	const FORMAT_JSON = 'JSON';
	const FORMAT_XML = 'XML';
	const FORMAT_NAMEVALUE = 'NAMEVALUE';
	
	/**
	 * 
	 * 构造函数
	 * @param String $contentFormat
	 */
	public function __construct($contentFormat = null) {
		//
		$this->setContentFormat ( $contentFormat );
	}
	
	
	public function getOriginalResponse(){
		return $this->_originalResponse;
	}
	
	public function getResponseBody(){
		$parts = preg_split ( '|(?:\r?\n){2}|m', $this->_originalResponse, 2 );
		if (isset ( $parts [1] )) {
			return $parts [1];
		}
		return '';
	}

	/**
	 * 
	 * 设置内容解释格式
	 * @param String $contentFormat XML | JSON
	 */
	public function setContentFormat($contentFormat) {
		
		if (! empty ( $contentFormat )) {
			$this->_contentFormat = $contentFormat;
		}
	}
	/**
	 * 
	 * set Content-Type中的 Charset 
	 * @param String $charset default utf-8
	 */
	public function setCharset($charset) {
		
		if (! empty ( $charset )) {
			$this->_charset = $charset;
		}
	}
	
	public function setHeaders($headers) {
		self::getHttpClient ()->setHeaders($headers);
	}
	
	/**
	 * Call a remote REST web service
	 * @return void
	 */
	final private function _prepareRest() {
		
		self::getHttpClient ()->reset ();
		
		if ($this->_contentFormat == self::FORMAT_JSON) {
			self::getHttpClient ()->addHeader ( 'Content-Type', "application/json;charset={$this->_charset}" );
			self::getHttpClient ()->addHeader ( 'Accept', 'application/json, */*' );
		} else if ($this->_contentFormat == self::FORMAT_XML) {
			self::getHttpClient ()->addHeader ( 'Content-Type', "application/xml;charset={$this->_charset}" );
			self::getHttpClient ()->addHeader ( 'Accept', 'application/xml, */*' );
		} else if ($this->_contentFormat == self::FORMAT_NAMEVALUE) {
			self::getHttpClient ()->addHeader ( 'Content-Type', "text/namevalue;charset={$this->_charset}" );
		}
	}
	
	/**
	 * Performs an HTTP GET request to the $path.
	 *
	 * @param string $path
	 * @param array  $query Array of GET parameters
	 * @return Object httpResponse
	 */
	final public function restGet($path, array $query = null) {
		$this->_prepareRest ();
		$client = self::getHttpClient ();
		$response = $client->doGet ( $path, $query);
		$this->_originalResponse = $response;
		return $this->_getResult ( $response );
	}
	
	/**
	 * Performs an HTTP POST request to $path.
	 *
	 * @param string $path
	 * @param mixed $data Raw data to send
	 * @return String Http Response
	 */
	final public function restPost($path, $data = null) {
		$this->_prepareRest ();
		$data = $this->_preparePostArgs($data);
		$client = self::getHttpClient ();
		$response = $client->doPost ( $path, $data);
        $this->_originalResponse = $response;
		return $this->_getResult ( $response );
	}
	
	/**
	 * Performs an HTTP PUT request to $path.
	 *
	 * @param string $path
	 * @param mixed $data Raw data to send in request
	 * @return String Http Response
	 */
	final public function restPut($path, $data = null) {
		$this->_prepareRest ();
        $data = $this->_preparePostArgs($data);
		$client = self::getHttpClient ();
		
		$response = $client->doPut ( $path, $data );
		
		return $this->_getResult ( $response );
	}
	
	/**
	 * Performs an HTTP DELETE request to $path.
	 *
	 * @param string $path
	 * @return String Http Response
	 */
	final public function restDelete($path, array $query = null) {
		
		$this->_prepareRest ();
		$client = self::getHttpClient ();
		
		$response = $client->doDelete ( $path, $query);
		$this->_originalResponse = $response;
		return $this->_getResult ( $response );
	}
	
	/**
	 * Call a remote REST web service URI and return the Zend_Http_Response object
	 *
	 * @param  string $path            The path to append to the URI
	 * @throws Zend_Rest_Client_Exception
	 * @return 
	 */
	final private function _preparePostArgs($args) {
		
		if ($this->_contentFormat == self::FORMAT_JSON) {
			return self::buildJsonContent ( $args );
		} else if ($this->_contentFormat == self::FORMAT_XML) {
			return self::buildXmlContent ( $args );
		} else if ($this->_contentFormat == self::FORMAT_NAMEVALUE) {
			return self::buildNamevalueList ( $args );
		}
		return $args;
	}
	/**
	 * 
	 * 获取RESTful 请求结果
	 * @param httpResponse $response
	 * @return Mix (Array or SimpleXMLElement Object or String)
	 */
	final private function _getResult($response) {
		if ($response != null && $response instanceof HttpResponse && $response->isSuccessful ()) {
			$body = $response->getBody();
			//判断是否为http return 100, 重新获取body进行解释
			if ($response->getStatus() == 100){
				$continueResponse = HttpResponse::fromString ( $body );
				$body = $continueResponse->getBody();
			}
			if ($this->_contentFormat == self::FORMAT_JSON) {
				return self::parserJsonContent ( $body );
			}elseif ($this->_contentFormat == self::FORMAT_XML) {
				return self::parserXmlContent ( $body );
			}elseif($this->_contentFormat == self::FORMAT_NAMEVALUE){
				return self::parseNameValueList($body);
			}
			
			return $body;
		}
		
		return false;
	}
	
	/**
	 * build  content with json formated 
	 * @param Mix $content
	 * @return String 
	 * @todo 独立作为一个类封装
	 */
	final public static function buildJsonContent($content) {
		
		return json_encode ( $content );
	}
	
	/**
	 * Parser content with json formated 
	 * @param String $content
	 * @return Array 
	 * @todo 独立作为一个类封装
	 */
	final public static function parserJsonContent($content) {

		$decode_content = json_decode ( $content, true );
		if ($decode_content == NULL){
			return $content;
		}else{
			return $decode_content;
		}
	}
	
	/**
	 * 把PHP对象作为XML文本输出
	 * 
	 * @param Mix $content
	 * @return String
	 */
	final public static function buildXmlContent($content) {
		//TODO 此处需要做XML模板的处理
		//暂时直接输出参数
		return $content;
	}
	
	/**
	 * Parser content with XML formated 
	 * @param String $content
	 * @return SimpleXMLElement 
	 * The SimpleXML extension provides a very simple and easily usable toolset to convert XML to an object,
	 * that can be processed with normal property selectors and array iterators.
	 * @todo 独立作为一个类封装
	 */
	final public static function parserXmlContent($content) {
		try {
			return new SimpleXMLElement ( $content );
		} catch ( Exception $e ) {
			self::log_error ( $e );
		}
	}
	
	/**
	 * Take an array of name-value pairs and return a properly
	 * formatted list. Enforces the following rules:
	 *
	 * - Names must be uppercase, all characters must match [A-Z].
	 * - Values cannot contain quotes.
	 * - If values contain & or =, the name has the length appended to
	 * it in brackets (NAME[4] for a 4-character value.
	 *
	 * If any of the "cannot" conditions are violated the function
	 * returns false, and the caller must abort and not proceed with
	 * the transaction.
	 */
	final public static function buildNameValueList($pairs) {
		
		$string = array ();
		foreach ( $pairs as $name => $value ) {
			if (preg_match ( '/[^A-Z_0-9a-z]/', $name )) {
				return false;
			}
			if (strpos ( $value, '"' ) !== false) {
				return false;
			}
			//if (strpos ( $value, '&' ) !== false || strpos ( $value, '=' ) !== false) {
			if (strpos ( $value, '&' ) !== false) {
				$string [] = $name . '[' . strlen ( $value ) . ']=' . $value;
			} else {
				$string [] = $name . '=' . $value;
			}
		}
		$lastParamList = implode ( '&', $string );
		return $lastParamList;
	}
	/**
	 * Take a name/value response string and parse it into an
	 * associative array. Doesn't handle length tags in the response
	 * as they should not be present.
	 */
	final public static function parseNameValueList($string) {
		$pairs = explode ( '&', $string );
		$values = array ();
		foreach ( $pairs as $pair ) {
			list ( $name, $value ) = explode ( '=', urldecode($pair));
			$values [$name] = $value;
		}
		return $values;
	}
}
