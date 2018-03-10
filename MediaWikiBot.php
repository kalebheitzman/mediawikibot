<?php

namespace MediaWikiBot;
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
 *      `For example,`
 *      `$params = array('text' => '==Heading 2==');`
 *      `$bot->parse($params);`
 *
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
 *
 *
 * @method mixed block(array $parameter, bool $multipart = null) calls the block action on the api
 * @method mixed compare(array $parameter, bool $multipart = null) calls the compare action on the api
 * @method mixed createaccount(array $parameter, bool $multipart = null) calls the createaccount action on the api
 * @method mixed delete(array $parameter, bool $multipart = null) calls the delete action on the api
 * @method mixed edit(array $parameter, bool $multipart = null) calls the edit action on the api
 * @method mixed emailuser(array $parameter, bool $multipart = null) calls the emailuser action on the api
 * @method mixed expandtemplates(array $parameter, bool $multipart = null) calls the expandtemplates action on the api
 * @method mixed feedcontributions(array $parameter, bool $multipart = null) calls the feedcontributions action on the api
 * @method mixed feedrecentchanges(array $parameter, bool $multipart = null) calls the feedrecentchanges action on the api
 * @method mixed feedwatchlist(array $parameter, bool $multipart = null) calls the feedwatchlist action on the api
 * @method mixed filerevert(array $parameter, bool $multipart = null) calls the filerevert action on the api
 * @method mixed help(array $parameter, bool $multipart = null) calls the help action on the api
 * @method mixed import(array $parameter, bool $multipart = null) calls the import action on the api
 * @method mixed logout(array $parameter, bool $multipart = null) calls the logout action on the api
 * @method mixed move(array $parameter, bool $multipart = null) calls the move action on the api
 * @method mixed opensearch(array $parameter, bool $multipart = null) calls the opensearch action on the api
 * @method mixed paraminfo(array $parameter, bool $multipart = null) calls the paraminfo action on the api
 * @method mixed parse(array $parameter, bool $multipart = null) calls the parse action on the api
 * @method mixed patrol(array $parameter, bool $multipart = null) calls the patrol action on the api
 * @method mixed protect(array $parameter, bool $multipart = null) calls the protect action on the api
 * @method mixed purge(array $parameter, bool $multipart = null) calls the purge action on the api
 * @method mixed query(array $parameter, bool $multipart = null) calls the query action on the api
 * @method mixed revisiondelete(array $parameter, bool $multipart = null) calls the revisiondelete action on the api
 * @method mixed rollback(array $parameter, bool $multipart = null) calls the rollback action on the api
 * @method mixed rsd(array $parameter, bool $multipart = null) calls the rsd action on the api
 * @method mixed setnotificationtimestamp(array $parameter, bool $multipart = null) calls the setnotificationtimestamp action on the api
 * @method mixed tokens(array $parameter, bool $multipart = null) calls the tokens action on the api
 * @method mixed unblock(array $parameter, bool $multipart = null) calls the unblock action on the api
 * @method mixed undelete(array $parameter, bool $multipart = null) calls the undelete action on the api
 * @method mixed upload(array $parameter, bool $multipart = null) calls the upload action on the api
 * @method mixed userrights(array $parameter, bool $multipart = null) calls the userrights action on the api
 * @method mixed watch(array $parameter, bool $multipart = null) calls the watch action on the api
 *
 * @author      Kaleb Heitzman
 * @email    jkheitzman@gmail.com
 * @license     The MIT License (MIT)
 * @date        2012-12-07 02:55 -0500
 */
class MediaWikiBot {

	/**
	 * Methods set by the mediawiki api
	 */
	protected $apiMethods = [
		'login', 'logout', 'createaccount', 'query', 'expandtemplates', 'parse',
		'opensearch', 'feedcontributions', 'feedrecentchanges', 'feedwatchlist', 'help', 'paraminfo', 'rsd',
		'compare', 'tokens', 'purge', 'setnotificationtimestamp', 'rollback', 'delete', 'undelete', 'protect',
		'block', 'unblock', 'move', 'edit', 'upload', 'filerevert', 'emailuser', 'watch', 'patrol', 'import',
		'userrights', 'revisiondelete',
	];

	/**
	 * Methods that need an xml format
	 */
	protected $xmlMethods = [
		'opensearch', 'feedcontributions',
		'feedwatchlist', 'rsd',
	];

	/**
	 * Methods that need multipart/form-date
	 */
	protected $multipart = [ 'upload', 'import' ];

	/**
	 * Methods that do not need a param check
	 */
	protected $noParameterNeeded = [ 'login', 'logout', 'rsd' ];

	/**
	 * Configuration variables
	 */
	protected $api;
	protected $username = 'bot';
	protected $password = 'password';
	protected $cookieStore = 'cookies.tmp';
	protected $userAgent = 'MediaWikiBot Framework by JKH';
	protected $apiFormat = 'php';

	private $debugMode = false;

	/**
	 * Stored tokens
	 */
	private $editToken = null;

	/**
	 * Constructor
	 *
	 * Creates a new MediaWikiBot. No action is taken, just values initialized.
	 *
	 * @param string $api      full url to api.php. including the api.php, e.g. `https://en.wikipedia.org/w/api.php`
	 * @param string $username (optional) the username in the wiki used for operations, defaults to predefined value
	 * @param string $password (optional) the password used to authenticate the `$username`, defaults to predefined value
	 */
	public function __construct( $api, $username = null, $password = null ) {
		$this->api = $api;
		if ( $username != null ) {
			$this->username = $username;
		}
		if ( $password != null ) {
			$this->password = $password;
		}
	}

	/** Dynamic method server
	 *
	 * This builds dynamic api calls based on the protected apiMethods var.
	 * If the method exists in the array then it is a valid api call and
	 * based on some php5 magic, the call is executed.
	 *
	 * @param string $method the api action to call
	 * @param array  $args   parameters for the api action
	 *
	 * @throws \BadMethodCallException if you are trying to call an invalid api action (aka not in \MediaWikiBot\MediaWikiBot::$apiMethods)
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		// get the params
		$params = $args[0];
		// check for forced multipart
		$multipart = null;
		if ( isset( $args[1] ) ) {
			$multipart = $args[1];
		}
		// check for valid method
		if ( in_array( $method, $this->apiMethods ) ) {
			// get multipart info
			if ( $multipart == null ) {
				$multipart = $this->multipart( $method );
			}
			// process the params
			return $this->standard_process( $method, $params, $multipart );
		} else {
			// not a valid method, kill the process
			throw new \BadMethodCallException( "$method is not a valid method" );
		}
	}

	/**
	 * Return edit token - if none is available try to get one from the api
	 *
	 * @return  string  the edit token
	 */
	public function getEditToken() {
		if ( $this->editToken == null ) {
			$this->editToken = $this->acquireEditToken();
		}
		return $this->editToken;
	}

	/**
	 * Log in and get the authentication tokens
	 *
	 * MediaWiki requires a dual login method to confirm authenticity. This
	 * entire method takes that into account.
	 *
	 * @param bool $init controls state in dual login process, do not provide manually!
	 *
	 * @return array    return result of login attempt. note: successful login is indicated via: $data['login']['result'] == "Success"
	 */
	public function login( $init = null ) {
		// build the url
		$url = $this->api_url( __FUNCTION__ );
		// build the params
		$params = [
			'lgname'     => $this->username,
			'lgpassword' => $this->password,
			'format'     => 'php' // do not change this from php
		];
		// get initial login info
		if ( $init == null ) {
			$results = $this->login( true );
			$results = (array) $results;
		} else {
			$results = null;
		}
		// pass token if not null
		if ( $results != null ) {
			$params['lgtoken'] = $results['login']['token'];
		}
		// get the data
		$data = $this->curl_post( $url, $params );
		// return data, success or not. let caller deal with flow handling
		return $data;
	}

	/**
	 * Enables or disables the debug mode
	 *
	 * @param bool $debugMode
	 */
	public function setDebugMode( $debugMode ) {
		$this->debugMode = $debugMode;
	}

	/**
	 * Try to acquire an edit token from the api. If successful, return it
	 *
	 * @return string   the edit token
	 */
	protected function acquireEditToken() {
		$editToken = null;

		// see https://www.mediawiki.org/wiki/API:Tokens
		$data = [ 'type' => 'edit' ];
		$ret = $this->tokens( $data );
		if ( isset( $ret['tokens']['edittoken'] ) ) {
			$editToken = $ret['tokens']['edittoken'];
		}
		return $editToken;
	}

	/**
	 * Build the needed api url
	 *
	 * @param string $function which action to use in url
	 *
	 * @return string beginning part of the api url including the action parameter
	 */
	protected function api_url( $function ) {
		// return the url
		return $this->api . "?action={$function}&";
	}

	/**
	 * Execute curl post
	 *
	 * @param string $url       full url to api.php, followed by the action=<action> part
	 * @param array  $params    api parameters to use in the request
	 * @param bool   $multipart is this a multipart action/request?
	 *
	 * @return array|string     the formatted curl request result. format depends on `$params['format']`
	 */
	protected function curl_post( $url, $params, $multipart = false ) {
		// set the format if not specified
		if ( empty( $params['format'] ) ) {
			$params['format'] = $this->apiFormat;
		}
		// open the connection
		$ch = curl_init();
		// set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, $this->userAgent );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookieStore );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookieStore );
		curl_setopt( $ch, CURLOPT_POST, count( $params ) );
		// choose multipart if necessary
		if ( $multipart ) {
			// submit as multipart
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		} else {
			// submit as normal
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->urlize_params( $params ) );
		}
		// execute the post
		$results = curl_exec( $ch );
		// close the connection
		curl_close( $ch );
		// return the un-serialized results
		$results = $this->format_results( $results, $params['format'] );

		if ( $this->debugMode ) {
			echo "request to " . $url . "\n";
			echo "with data " . print_r( $params, true );
			if ( $multipart ) {
				$contentType = "multipart/form-data";
			} else {
				$contentType = "application/x-www-form-urlencoded";
			}
			echo "posted as " . $contentType . "\n\n";
			echo "returned " . print_r( $results, true ) . "\n";
		}
		return $results;
	}

	/**
	 * Check for multipart method
	 *
	 * @param string $method the method to check
	 *
	 * @return bool true, if multipart
	 */
	private function multipart( $method ) {
		// get multipart true/false
		$multipart = in_array( $method, $this->multipart );
		// check to see if multipart method exists and return true/false
		return $multipart;
	}

	/**
	 * Check for null params
	 *
	 * If needed params are not passed then kill the script.
	 *
	 * @param mixed $params your parameter array
	 *
	 * @throws \BadMethodCallException if parameters array is not set or empty
	 */
	private function check_params( $params ) {
		// check for null
		if ( $params == null || !is_array( $params ) || !sizeof( $params ) ) {
			throw new \BadMethodCallException( "You did not pass any parameters for your action!" );
		}
	}

	/**
	 * Format results based on format (default=php)
	 *
	 * @param string $results the api result string
	 * @param string $format  the format type to decode after
	 *
	 * @return array|string depending on `$type`
	 */
	private function format_results( $results, $format = 'php' ) {
		$results = trim( $results ); //FIX: Added a trim to cut off preceding whitespace
		switch ( $format ) {
			case 'json':
				return json_decode( $results );
			case 'php':
				return unserialize( $results );
			case 'wddx':
				return wddx_deserialize( $results );
			case 'xml':
				return simplexml_load_string( $results );
			case 'yaml':
				return $results;
			case 'txt':
				return $results;
			case 'dbg':
				return $results;
			case 'dump':
				return $results;
			default :
				return unserialize( $results );
		}
	}

	/**
	 * Standard processing method
	 *
	 * The standard process methods calls the correct api url with params
	 * and executes a curl post request.  It then returns processed data
	 * based on what format has been set (default=php).
	 *
	 * @param string     $method    the api action
	 * @param array|null $params    parameter array for the api request
	 * @param bool       $multipart is this a multipart action?
	 *
	 * @return array|string     the result of the api request
	 */
	private function standard_process( $method, $params = null, $multipart = false ) {
		// check for null params
		if ( !in_array( $method, $this->noParameterNeeded ) ) {
			$this->check_params( $params );
		}
		// specify xml format if needed
		if ( in_array( $method, $this->xmlMethods ) ) {
			$params['format'] = 'xml';
		}
		// build the url
		$url = $this->api_url( $method );
		// get the data
		$data = $this->curl_post( $url, $params, $multipart );
		// set smwinfo
		// @FIXME deprecated?
		$this->$method = $data;
		// return the data
		return $data;
	}

	/**
	 * Build a url string out of params (aka everything after api.php?action=<action>&; url-encodes the parameter keys and values
	 *
	 * @param array $params your parameter array
	 *
	 * @return string the assembled, url-encoded and &-concatenated url string to append after the api-action
	 */
	private function urlize_params( $params ) {
		// url-ify the data for POST
		$urlString = "";
		foreach ( $params as $key => $value ) {
			$urlString .= urlencode( $key ) . '=' . urlencode( $value ) . '&';
		}
		// pull the & off the end
		rtrim( $urlString, '&' );
		// return the string
		return $urlString;
	}
}
