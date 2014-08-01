<?php
/**
 * Abstract Service Class  参考Zend Framework
 *
 */

/**
 *  Http CURL Cliect 
 */
require_once CLASS_DIR.'/http/curl.php';

abstract class ServiceAbstract
{
    /**
     * HTTP Client used to query all web services
     *
     * @var httpClient
     */
    protected static $_httpClient = null;


    /**
     * Sets the HTTP client object to use for retrieving the feeds.  If none
     * is set, the default httpCurl will be used.
     *
     * @param httpCurl $httpClient
     */
    final public static function setHttpClient(HttpCurl $httpClient)
    {
        self::$_httpClient = $httpClient;
    }


    /**
     * Gets the HTTP client object.
     *
     * @return httpCurl 
     */
    final public static function getHttpClient()
    {
        if (!self::$_httpClient instanceof HttpCurl) {
            self::$_httpClient = new HttpCurl();
        }

        return self::$_httpClient;
    }
}

