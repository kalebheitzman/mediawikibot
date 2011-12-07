<?php
/** MediaWikiBot Class
 *
 *  The MediaWikiBot Class provides an easy to use interface for the 
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
 *  @author Kaleb Heitzman
 *  @email  jkheitzman@gmail.com
 *  @date	2012-12-07 01:10 -0500
 */
class MediaWikiBot {
	
	/** Methods set by the mediawiki api
	 */
	protected $apimethods = array('smwinfo', 'login', 'logout', 'query', 
								'expandtemplates', 'parse', 'opensearch', 
								'feedcontributions', 'feedwatchlist', 'help', 
								'paraminfo', 'rsd', 'compare', 'purge', 
								'rollback', 'delete', 'undelete', 'protect', 
								'block', 'unblock', 'move', 'edit', 'upload', 
								'filerevert', 'emailuser', 'watch', 'patrol', 
								'import', 'userrights');
	
	/** Methods that need an xml format
	 */
	protected $xmlmethods = array('opensearch', 'feedcontributions', 
								'feedwatchlist', 'rsd');
	
	/** Methods that need multipart/form-date
	 */
	protected $multipart = array('upload', 'import');
								
	/** Methods that do not need a param check
	 */
	protected $parampass = array('rsd');

	/** Constructor
	 */
	public function __construct()
	{
		/** Set some constants
		 *
		 *  You should override these in the bot that you are creating.
		 *  Simply redeclare them after you have done a php require on the 
		 *  MediaWikiBot class.
		 */
		define('DOMAIN', 'http://example.com');
		define('WIKI', '/wiki');
		define('USERNAME', 'bot');
		define('PASSWORD', 'password');
		define('USERAGENT', 'WikimediaBot Framework by JKH');		
		define('FORMAT', 'php');
	}	
	
	/** Dynamic function server
	 *
	 *  This builds dyamic api calls based on the protected apimethods var.
	 *  If the method exists in the array then it is a valid api call and 
	 *  based on some php5 magic, the call is executed.
	 */
	public function __call($method, $params) {
		// check for valid method
		if (in_array($method, $this->apimethods)) {
			// specify xml format if needed
			if (in_array($method, $this->xmlmethods)) {
				$params['format'] = 'xml';
			}
			// specify multipart/form-date if needed
			if (in_array($method, $this->multipart)) {
				$multipart = true;
			} else {
				$multipart = false;
			}
			// unset the session if logout called
			if ($method == 'logout') {
				unset($this->session);
			}
			// process the params	
			return $this->standard_process($method, $params, $multipart);
		} else {
			// not a valid method, kill the process
			die("$method is not a valid method \r\n");
		}
		
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
		// get initial login info
		if ($init == null) {
			$results = $this->login(true);
			$results = (array) $results;
		} else {
			$results = null;
		}	
		// build the params
		$params = array(
			'lgname' => USERNAME,
			'lgpassword' => PASSWORD,
			'format' => 'php' // don't change this form php
		);
		// pass token if not null
		if ($results != null) {
			$params['lgtoken'] = $results['login']['token'];
		}
		// set some options
		$options = array();
		if ($results != null) {
			$options = array(
				CURLOPT_COOKIE => $results['login']['cookieprefix'] . "_session=" . $results['login']['sessionid']
			);
		}
		// get the data
		$data = $this->curl_post($url, $params, $options);
		// return or set data
		if ($data['login']['result'] == "Success") {
			// set session information
			$this->session = $this->build_session($data);
		} else {
			// return the data for confirmation
			return $data;
		}				
	}
		
	/** Query API module allows applications to get needed pieces of data from 
	 *	the MediaWiki databases.
	 */
	function query($method = null, $params = null) 
	{
		// check for method
		if ($method == null) { die("You didn't specify a query method. \r\n"); }
		
		// check for params
		$this->check_params($params);
		
		// available methods
		$methods = array(
			'titles', 
			'pageids', 
			'revids', 
			'prop', 
			'list', 
			'meta', 
			'generator', 
			'redirects', 
			'converttitles', 
			'indexpageids', 
			'export', 
			'exportnowrap', 
			'iwurl'
		);
		$method = "query_{$method}";

		$this->$method();
	}
	
	/** Check for null params
	 *
	 *  If needed params are not passed then kill the script. 
	 */
	private function check_params($params)
	{
		// check for null
		if ($params == null) {
			die("You didn't pass any params. \r\n");
		} else {
			return;
		}
	}
	
	/** Build a url string out of params
	 */
	private function urlize_params($params)
	{
		// url-ify the data for POST
		foreach ($params as $key => $value) {
			$urlstring .= $key . '=' . $value . '&';
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
		// build the url
		$url = DOMAIN . WIKI . "/api.php?action={$function}&";
		// return the url
		return $url;
	}
	
	/** Build the needed session info
	 */
	private function build_session($login)
	{
		// get the login info
		$session = $login['login'];
		// get the cookie prefix
		$cookie = $session['cookieprefix'];
		// build the fields
		$fields["{$cookie}_session"] = $session['sessionid'];
		$fields["{$cookie}UserName"] = $session['lgusername'];
		$fields["{$cookie}UserID"] = $session['lguserid'];
		$fields["{$cookie}Token"] = $session['lgtoken'];
		// return the fields
		return $fields;
	}
	
	/** Execute curl post
	 */
	private function curl_post($url, $params, $options = null)
	{
		// set the format if not specified
		if (empty($params['format'])) {
			$params['format'] = FORMAT;
		}
		// open the connection
		$ch = curl_init();
		// set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_POST, count($parms));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->urlize_params($params));
		// set more curl opts for login confirmation
		if ($options != null) {
			foreach ($options as $key => $value) {
				curl_setopt($ch, $key, $value);
			}
		}
		// execute the post
		$results = curl_exec($ch);			
		// close the connection
		curl_close($ch);
		// return the unserialized results
		return $this->format_results($results, $params['format']);
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
		if ( ! in_array($method, $this->apimethods)) {
			$this->check_params($params);			
		}
		// build the url
		$url = $this->api_url($method);
		// get the data
		$data = $this->curl_post($url, $params);
		// set smwinfo
		$this->$method = $data;
		// return the data
		return $data;
	}
	
	/** Format results based on format (default=php)
	 */
	private function format_results($results, $format)
	{
		switch($format) {
			case 'json':
				return json_decode($results);
				break;
			case 'jsonfm':
				return $results;
				break;
			case 'php':
				return unserialize($results);
				break;
			case 'phpfm':
				return $results;
				break;
			case 'wddx':
				return $results;
				break;
			case 'wddxfm':
				return $results;
				break;
			case 'xml':
				return $results;
				break;
			case 'xmlfm':
				return $results;
				break;
			case 'yaml':
				return $results;
				break;
			case 'yamlfm':
				return $results;
				break;
			case 'rawfm':
				return $results;
				break;
			case 'txt':
				return $results;
				break;
			case 'txtfm':
				return $results;
				break;
			case 'dbg':
				return $results;
				break;
			case 'dbgfm':
				return $results;
				break;
			case 'dump':
				return $results;
				break;
			case 'dumpfm':
				return $results;
				break;
		}
	}

}