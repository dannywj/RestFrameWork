<?php 
/**
 * RESTful Server Class  参考Zend Framework
 *
 * @package classes.rest
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 * @date 2011/02/14 16:53:50
 * @version  1.4 $
 */

/**
 * @see ServerAbstract
 */
require_once CLASS_DIR.'../abstract.php';

/**
 * @category   classes
 * @package    classes.rest
 * @subpackage client
 * @copyright Copyright 2011 Lightinthebox.com R&D Team
 * @author yilee@lightinthebox.com
 */
class RestServer extends ServerAbstract {
	
	const FORMAT_JSON = 'JSON';
	const FORMAT_XML = 'XML';
	
	/**
	 * Class Constructor Args
	 * @var array
	 */
	protected $_args = array ();
	
	/**
	 * @var string Encoding
	 */
	protected $_encoding = 'UTF-8';
	
	/**
	 * @var String 
	 */
	protected $_format = 'JSON';
	
	/**
	 * @var array Array of headers to send
	 */
	protected $_headers = array ();
	
	/**
	 * @var string Current Method
	 */
	protected $_method;
	
	/**
	 * Constructor
	 */
	public function __construct($serverDefinition = null ) {
		//用于创建运行时期间的用户自己的异常处理方法
		set_exception_handler ( array ($this, "fault" ) );
		parent::__construct ($serverDefinition);
	}
	
	/**
	 * Set XML encoding
	 *
	 * @param  string $encoding
	 * @return RestServer
	 */
	public function setEncoding($encoding) {
		$this->_encoding = ( string ) $encoding;
		return $this;
	}
	
	/**
	 * Get XML encoding
	 *
	 * @return string
	 */
	public function getEncoding() {
		return $this->_encoding;
	}
	
	public function setFormat($format){
		if ($format == self::FORMAT_XML || $format == self::FORMAT_JSON){
			$this->_format = $format;
		}
	}
	
	/**
	 * Lowercase a string
	 *
	 * Lowercase's a string by reference
	 *
	 * @param string $value
	 * @param string $key
	 * @return string Lower cased string
	 */
	public static function lowerCase(&$value, &$key) {
		return $value = strtolower ( $value );
	}
	
	protected function getServerMethod() {
		if ($this->_method == NULL) {
			$this->_method = $_REQUEST ['method'];
		}
		return parent::getServerMethod ( $this->_method );
	}
	/**
	 * Implement ServerAbstract::handler()
	 *
	 * @param  array $request
	 * @return string|void
	 */
	public function handler($request = false) {
		
		if (! $request) {
			$request = $_REQUEST;
		}
		if (isset ( $request ['method'] )) {
			$this->_method = $request ['method'];
			$serverMethod = $this->getServerMethod ();	
			if ($serverMethod !== false) {
				$calling_args = $this->_handlerCallingArgs($request);
				if ($calling_args === false){
					$result = $this->fault ('Invalid Method Call to ' . $this->_method . '. Missing argument(s) ' . '.' , 400 );
				}else{
					$result = $this->_dispatch ( $serverMethod, $calling_args );
				}
			} else {
				$result = $this->fault ( "Unknown Method '$this->_method'", 404 );
			}
		} else {
			$result = $this->fault ( "No Method Specified.", 404 );
		}
		//
		$this->_handlerReponseFormat();
		//
		$this->_sendHeaders ();
		//
		$processed_result = $this->_handlerResponse ($result);
		
		return $processed_result;
	}
	
	/**
	 * 
	 * 输出结果
	 * @param Mix $result
	 */
	protected function _handlerResponse($result) {
		
		if ($this->_format == self::FORMAT_JSON){
			return self::buildJsonContent($result);
		}else if ($this->_format == self::FORMAT_XML){
			return self::buildXmlContent($result);
		}else{
			return self::buildJsonContent($result);
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	protected function _handlerReponseFormat(){
		$format = $_REQUEST['format'];
		if(!empty($format)){
			$this->setFormat($format);
		}
				
	   if ($this->_format == self::FORMAT_JSON) {
	   		$this->_headers = array ("Content-Type: application/json;charset={$this->_encoding}" );
		} else if ($this->_format == self::FORMAT_XML) {
			$this->_headers = array ("Content-Type: application/xml;charset={$this->_encoding}" );
		} else {
			$this->_headers = array ("Content-Type: application/json;charset={$this->_charset}" );
		}
	}
	
	protected function _handlerCallingArgs($request) {
		$request_keys = array_keys ( $request );
		array_walk ( $request_keys, array (__CLASS__, "lowerCase" ) );
		$request = array_combine ( $request_keys, $request );
		
		$func_args = $this->getServerMethod()->getParameters ();
		$calling_args = array ();
		$missing_args = array ();
		
		foreach ( $func_args as $argName => $defaultValue) {
			if (isset ( $request [strtolower ( $argName)] )) {
				$calling_args [] = $request [strtolower ( $argName )];
			} elseif ($defaultValue !== null) { // 参数有默认值
				$calling_args [] = $defaultValue;
			} else {
				$missing_args [] = $argName;
			}
		}
		//必须的参数没有输入
		if (count($missing_args) > 0){
			return false;
		}

		return $calling_args;
	}
	/**
	 * 
	 * 发送
	 */
	protected function _sendHeaders() {
		if (! headers_sent ()) {
			foreach ( $this->_headers as $header ) {
				header ( $header );
			}
		}
	}
	
	/**
	 * Implement fault()
	 *
	 * Creates error response,return response array.
	 *
	 * @param string|Exception $fault Message
	 * @param int $code Error Code
	 * @return array
	 */
	public function fault($exception = null, $code = null) {
		
		$errorMessage = array('status' => 'failed',
								'method' => $this->_method,
								'code' => $code
		);
		if (($exception !== null)) {
			$errorMessage['message'] = $exception ;
		} else {
			$errorMessage['message'] = 'An unknown error occured. Please try again.';
		}

		// Headers to send
		if ($code === null || (404 != $code)) {
			$this->_headers [] = 'HTTP/1.0 400 Bad Request';
		} else {
			$this->_headers [] = 'HTTP/1.0 404 File Not Found';
		}
		
		return $errorMessage;
	}
	
	/**
	 * Retrieve any HTTP extra headers set by the server
	 *
	 * @return array
	 */
	public function getHeaders() {
		return $this->_headers;
	}
	
	/**
	 * build  content with json formated 
	 * @param Mix $content
	 * @return String 
	 * @todo 独立作为一个类封装
	 */
	final public static function buildJsonContent($content) {
		//TODO UTF-8字符转码
		return json_encode ( $content );
	}
	
    /**
	 * build  content with XML formated 
	 * @param Mix $content
	 * @return String 
	 * @todo 独立作为一个类封装
	 */
	final public static function buildXmlContent($content) {
		//TODO
		return var_export($content);
	}
}