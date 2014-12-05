<?php

/**                     _ _                _ _    _ _           _
 *                     | (_)              (_) |  (_) |         | |
 *   _ __ ___   ___  __| |_  __ ___      ___| | ___| |__   ___ | |_
 *  | '_ ` _ \ / _ \/ _` | |/ _` \ \ /\ / / | |/ / | '_ \ / _ \| __|
 *  | | | | | |  __/ (_| | | (_| |\ V  V /| |   <| | |_) | (_) | |_
 *  |_| |_| |_|\___|\__,_|_|\__,_| \_/\_/ |_|_|\_\_|_.__/ \___/ \__|
 *
 *  MediaWikiBot PHP Class
 *
 *  The MediaWikiBot PHP Class provides an easy to use interface for the
 *  MediaWiki api.  It dynamically builds functions based on what is available
 *  in the api.  This version supports Semantic MediaWiki.
 *
 *  You do a simple require_once('/path/to/mediawikibot.class.php') in your
 *  own bot file and initiate a new MediaWikiBot() object.  This class
 *  supports all of the api calls that you can find on your wiki/api.php page.
 *
 *  You build the $params and then call the action.
 *
 *  For example,
 *  $params = array('text' => '==Heading 2==');
 *  $bot->parse($params);
 *
 *  @author 	Kaleb Heitzman
 *  @email  	jkheitzman@gmail.com
 *  @license 	The MIT License (MIT)
 *  @date		2012-12-07 02:55 -0500
 *
 *  The MIT License (MIT) Copyright (c) 2011 Kaleb Heitzman
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a
 *  copy of this software and associated documentation files (the "Software"),
 *  to deal in the Software without restriction, including without limitation
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *  and/or sell copies of the Software, and to permit persons to whom the
 *  Software is furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 *  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 */

class MediaWikiBot {

	/** Methods set by the mediawiki api
	 */
	protected $apimethods = array('smwinfo', 'login', 'logout', 'query',
		'expandtemplates', 'parse', 'opensearch', 'feedcontributions',
		'feedwatchlist', 'help', 'paraminfo', 'rsd', 'compare', 'purge',
		'rollback', 'delete', 'undelete', 'protect', 'block', 'unblock',
		'move', 'edit', 'upload', 'filerevert', 'emailuser', 'watch',
		'patrol', 'import', 'userrights', 'tokens');

	/** Methods that need an xml format
	 */
	protected $xmlmethods = array('opensearch', 'feedcontributions',
		'feedwatchlist', 'rsd');

	/** Methods that need multipart/form-date
	 */
	protected $multipart = array('upload', 'import');

	/** Methods that do not need a param check
	 */
	protected $parampass = array('login', 'logout', 'rsd');
	
	/**
	 * Configuration variables 
	 */
	protected $api;
	protected $username    = 'bot';
	protected $password    = 'password';
	protected $cookiestore = 'cookies.tmp';
	protected $useragent   = 'WikimediaBot Framework by JKH';
	protected $apiformat   = 'php';
	
	private $debugMode = false;
	
	/**
	 * Stored tokens
	 */
	private $edittoken = null;

	/** Constructor
	 */
	public function __construct( $api, $username = null, $password = null )
	{
		$this->api = $api;
		$this->username = $username;
		$this->password = $password;
	}

	/** Dynamic method server
	 *
	 *  This builds dyamic api calls based on the protected apimethods var.
	 *  If the method exists in the array then it is a valid api call and
	 *  based on some php5 magic, the call is executed.
	 */
	public function __call($method, $args) {
		// get the params
		$params = $args[0];
		// check for forced multipart
		$multipart = null;
		if (isset($args[1])) $multipart = $args[1];
		// check for valid method
		if (in_array($method, $this->apimethods)) {
			// get multipart info
			if ($multipart == null) {
				$multipart = $this->multipart($method);
			}
			// process the params
			return $this->standard_process($method, $params, $multipart);
		} else {
			// not a valid method, kill the process
			die("$method is not a valid method \r\n");
		}
	}
	
	/**
	 * Sets the debug mode
	 *
	 */
	public function setDebugMode($debugMode) {
		$this->debugMode = $debugMode;
	}

	/** Log in and get the authentication tokens
	 *
	 *  MediaWiki requires a dual login method to confirm authenticity. This
	 *  entire method takes that into account.
	 */
	public function login($init = null)
	{
		// build the url
		$url = $this->api_url(__FUNCTION__);
		// build the params
		$params = array(
			'lgname' => $this->username,
			'lgpassword' => $this->password,
			'format' => 'php' // do not change this from php
		);
		// get initial login info
		if ($init == null) {
			$results = $this->login(true);
			$results = (array) $results;
		} else {
			$results = null;
		}
		// pass token if not null
		if ($results != null) {
			$params['lgtoken'] = $results['login']['token'];
		}
		// get the data
		$data = $this->curl_post($url, $params);
		// return or set data
		if ($data['login']['result'] != "Success") {
			return $data;
		}
	}
	
	/** Return edit token - if none is available try to get one from the api
	 */
	public function getEditToken() {
		if ($this->edittoken == null) {
			$this->edittoken = $this->aquireEditToken();
		}
		return $this->edittoken;
	}
	
	/** Try to aquire an edit token from the api
	 */
	protected function aquireEditToken() {
		$edittoken = null;
		
		// see https://www.mediawiki.org/wiki/API:Tokens
		$data = array( 'type' => 'edit' );
		$retval = $this->tokens($data);
		if (isset($retval['tokens']['edittoken'])) {
			$edittoken = $retval['tokens']['edittoken'];
		}
		return $edittoken;
	}

	/** Standard processesing method
	 *
	 *  The standard process methods calls the correct api url with params
	 *  and executes a curl post request.  It then returns processed data
	 *  based on what format has been set (default=php).
	 */
	private function standard_process($method, $params = null, $multipart = false)
	{
		// check for null params
		if ( ! in_array($method, $this->parampass)) {
			$this->check_params($params);
		}
		// specify xml format if needed
		if (in_array($method, $this->xmlmethods)) {
			$params['format'] = 'xml';
		}
		// build the url
		$url = $this->api_url($method);
		// get the data
		$data = $this->curl_post($url, $params, $multipart);
		// set smwinfo
		$this->$method = $data;
		// return the data
		return $data;
	}

	/** Execute curl post
	 */
	private function curl_post($url, $params, $multipart = false)
	{
		// set the format if not specified
		if (empty($params['format'])) {
			$params['format'] = $this->apiformat;
		}
		// open the connection
		$ch = curl_init();
		// set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiestore);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiestore);
		curl_setopt($ch, CURLOPT_POST, count($params));
		// choose multipart if necessary
		if ($multipart) {
			// submit as multipart
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		else {
			// submit as normal
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->urlize_params($params));
		}
		// execute the post
		$results = curl_exec($ch);
		// close the connection
		curl_close($ch);
		// return the unserialized results
		$results = $this->format_results($results, $params['format']);
		
		if ($this->debugMode) {
			echo "request to ". $url ."\n";
			echo "with data ". print_r($params, true);
			if ($multipart) {
				$contenttype = "multipart/form-data";
			}
			else {
				$contenttype = "application/x-www-form-urlencoded";
			}
			echo "posted as ". $contenttype . "\n\n";
			echo "returned ". print_r($results, true) ."\n";
		}
		return $results;
	}

	/** Check for multipart method
	 */
	private function multipart($method)
	{
		// get multipart true/false
		$multipart = in_array($method, $this->multipart);
		// check to see if multipart method exists and return true/false
		return $multipart;
	}

	/** Format results based on format (default=php)
	 */
	private function format_results($results, $format)
	{
		switch($format) {
			case 'json':
				return json_decode($results);
			case 'php':
				return unserialize($results);
			case 'wddx':
				return wddx_deserialize($results);
			case 'xml':
				return simplexml_load_string($results);
			case 'yaml':
				return $results;
			case 'txt':
				return $results;
			case 'dbg':
				return $results;
			case 'dump':
				return $results;
		}
	}

	/** Check for null params
	 *
	 *  If needed params are not passed then kill the script.
	 */
	private function check_params($params)
	{
		// check for null
		if ($params == null) die("You didn't pass any params. \r\n");
	}

	/** Build a url string out of params
	 */
	private function urlize_params($params)
	{
		// url-ify the data for POST
		$urlstring = "";
		foreach ($params as $key => $value) {
			$urlstring .= urlencode($key) . '=' . urlencode($value) . '&';
		}
		// pull the & off the end
		rtrim($urlstring, '&');
		// return the string
		return $urlstring;
	}

	/** Build the needed api url
	 */
	private function api_url($function)
	{
		// return the url
		return $this->api . "?action={$function}&";
	}

}
