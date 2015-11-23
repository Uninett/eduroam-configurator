<?php namespace Eduroam\CAT;

use \DomainException;

/**
 * cURL wrapper around the CAT API.
 */
class CAT {

	/**
	 * Base URL for CAT API.
	 * @var string
	 */
	protected $base;
	/**
	 * Amount of seconds answers from CAT API are cached.
	 * Note that profile downloads are always initiated by the client
	 * and therefore never cached server-side.
	 * @var integer
	 */
	protected $cache;
	/**
	 * cURL handle
	 * @var resource
	 */
	private $ch = null;

	/**
	 * Construct a new CAT API object.
	 *
	 * @param string $base Base URL for CAT API
	 * @param int $cache Amount of seconds answers from CAT API are cached
	 */
	public function __construct($base = 'https://cat.eduroam.org/user/API.php', $cache = 86400) {
		$parse = parse_url($base);
		if (strpos($base, '?') || $parse === false || isset($parse['fragment']) || is_null($parse['host'])) {
			throw new DomainException('Malformed URL');
		}
		$this->base = $base;
		$this->cache = (int) $cache;
	}

	/**
	 * Get contents by URL through cURL.
	 *
	 * @param string $url URL to retrieve
	 * @param string $accept Expected content type
	 * @param array $opts cURL options as documented on http://php.net/curl_setopt
	 */
	protected function file_get_contents_curl($url, $accept = 'application/json', $opts = []) {
		if (!$this->ch) {
			$this->ch = curl_init();
		}

		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_FAILONERROR, TRUE);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
			'Accept: ' . $accept,
		]);

		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		foreach($opts as $key => $value) {
			curl_setopt($this->ch, $key, $value);
		}

		$data = curl_exec($this->ch);

		return $data;
	}

	/**
	 * Get the base URL for CAT API.
	 *
	 * @return string Base URL for CAT API
	 */
	public function getBase() {
		return $this->base;
	}

	/**
	 * Get JSON data structure as PHP object from the CAT API.
	 *
	 * @param string[] $query Parameters for the CAT API, needs at least action
	 * @param string $lang Desired language for friendly strings
	 *
	 * @return stdClass|array JSON-decoded answer from CAT API
	 */
	private function catJSONQuery($query, $lang = '') {
		$rawResult = $this->executeCatQuery($query, $lang, 'application/json');
		$result = json_decode($rawResult);
		if ($result) {
			return $result;
		} else {
			$this->flushCatQuery($query, $lang, 'application/json');
			$rawResult = $this->executeCatQuery($query, $lang, 'application/json');
			$result = json_decode($rawResult);
			if ($result) {
				return $result;
			}
		}
		throw new DomainException('Illegal result from ' . $url . ': ' . $rawResult);
	}

	/**
	 * Get raw answer from CAT API.
	 *
	 * @param string[] $query Parameters for the CAT API, needs at least action
	 * @param string $lang Desired language for friendly strings
	 * @param string $accept Accepted content type for answer (request is always form-encoded)
	 *
	 * @return string Raw answer from CAT query
	 */
	private function executeCatQuery($query, $lang = '', $accept = 'application/json') {
		if ($lang) {
			$query['lang'] = $lang;
		}
		$file = $this->getCatQueryFilename($query, $lang, $accept);
		$useLocal = file_exists($file) && filemtime($file) > time() - $this->cache;
		$url = $this->getBase() . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
		if ($useLocal) {
			$result = file_get_contents($file);
		} else {
			$result = $this->file_get_contents_curl($url, $accept);
			file_put_contents($file, $result);
		}
		if ($result) {
			return $result;
		} else {
			$this->flushCatQuery($query, $lang, $accept);
			throw new DomainException('Empty result from ' . $url);
		}
	}

	/**
	 * Get the filename for a cached CAT API response.
	 *
	 * @param string[] $query Parameters for the CAT API, needs at least action
	 * @param string $lang Desired language for friendly strings
	 * @param string $accept Accepted content type for answer (request is always form-encoded)
	 *
	 * @return string Full path to the cached CAT API response (may not exist yet)
	 */
	private function getCatQueryFilename($query, $lang = '', $accept = 'application/json') {
		$hash = md5(serialize($query)) . '-' . md5($this->getBase());
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eduroam-';
		if (isset($query['action'])) {
			$file .= $query['action'] . '-';
		}
		if (isset($query['id'])) {
			$file .= $query['id'] . '-';
		}
		if (isset($query['profile'])) {
			$file .= $query['profile'] . '-';
		}
		$file .= $hash;
		return $file;
	}

	/**
	 * Make sure that the answer to the provided query is not cached.
	 *
	 * This is done by removing the cache file.
	 * After this function completes, the next call to #executeCatQuery will
	 * guaranteed get its result from the server.
	 *
	 * @param string[] $query Parameters for the CAT API, needs at least action
	 * @param string $lang Desired language for friendly strings
	 * @param string $accept Accepted content type for answer (request is always form-encoded)
	 */
	private function flushCatQuery($query, $lang = '', $accept = 'application/json') {
		$file = $this->getCatQueryFilename($query, $lang, $accept);
		unlink($file);
	}

	/**
	 * List all identity providers.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listAllIdentityProviders
	 *
	 * @param string $lang Desired language for friendly strings
	 */
	public function listAllIdentityProviders($lang = '') {
		return $this->catJSONQuery([
			'action' => 'listAllIdentityProviders',
		], $lang);
	}

	/**
	 * List identity providers by country.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listIdentityProviders
	 *
	 * @param string $country 2-letter ISO code in caps representing the country, for example <code>NO</code>
	 * @param string $lang Desired language for friendly strings
	 */
	public function listIdentityProviders($country, $lang = '') {
		return $this->catJSONQuery([
			'action' => 'listIdentityProviders',
			'id' => $country,
		], $lang)->data;
	}

	/**
	 * Get all profiles for an identity provider.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listProfiles
	 *
	 * @param int $institutionID ID number in CAT database representing the identity provider
	 * @param string $lang Desired language for friendly strings
	 */
	public function listProfiles($idpID, $lang = '') {
		return $this->catJSONQuery([
			'action' => 'listProfiles',
			'id' => $idpID,
		], $lang)->data;
	}

	/**
	 * Get attributes for a profile, these include support information, description and devices, but not the name. 
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.profileAttributes
	 *
	 * @param int $profileID The ID number of the profile in the CAT database
	 * @param string $lang Desired language for friendly strings
	 */
	public function profileAttributes($profileID, $lang = '') {
		return $this->catJSONQuery([
			'action' => 'profileAttributes',
			'id' => $profileID,
		], $lang)->data;
	}

	/**
	 * Ensure that an installer is generated for the profile and operating system combination.
	 * Must be run at least once before the user downloads, but it's not needed to do this every time.
	 * The built-in caching mechanism of this class should take care of that.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.generateInstaller
	 *
	 * @param string $osName Name of the operating system as presented in the CAT database (w8, mobileconfig, linux)
	 * @param int $profileID The ID number of the profile in the CAT database
	 * @param string $lang Desired language for friendly strings
	 */
	public function generateInstaller($osName, $profileID, $lang = '') {
		return $this->catJSONQuery([
			'action' => 'generateInstaller',
			'id' => $osName,
			'profile' => $profileID,
		], $lang)->data;
	}

	/**
	 * List the devices just like #profileAttributes(int, string), but without custom texts.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listDevices
	 *
	 * @param int $profileID The ID number of the profile in the CAT database
	 * @param string $lang Desired language for friendly strings
	 */
	public function listDevices($profileID, $lang = '') {
		return $this->catJSONQuery([
			'action' => 'listDevices',
			'id' => $profileID,
		], $lang)->data;
	}

	/**
	 * Show device information, undocumented CAT feature.
	 *
	 * This feature is mentioned on the cat-users mailing list by Tomaz and
	 * Stefan, and returns CAT-issued HTML messages per device.
	 *
	 * The API also asks for a Profile ID, but it doesn't seem like this makes
	 * any difference in the outcome of the API call.
	 *
	 * @param string $osName Name of the operating system as presented in the CAT database (w8, mobileconfig, linux)
	 * @param int $profileID The ID number of the profile in the CAT database
	 * @param string $lang Desired language for friendly strings
	 */
	public function getDeviceInfo($osName, $profileID, $lang = '') {
		return $this->executeCatQuery([
			'action' => 'deviceInfo',
			'id' => $osName,
			'profile' => $profileID,
		], $lang, 'text/html');
	}

}
