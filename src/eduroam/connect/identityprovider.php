<?php namespace Eduroam\Connect;

use \Eduroam\CAT\CAT;

/**
 * Identity Provider
 *
 * An identity provider represents an entity in the real world that is eduroam,
 * IdP, usually an institution.  Identity providers have some attributes and
 * profiles, which can be accessed through instance methods.
 */
class IdentityProvider {

	/**
	 * List of all identity providers by CAT base, language and IdP entity id.
	 *
	 * This variable is static to facilitate lazy-loading.
	 * The CAT API has no support to get one identity provider,
	 * so we'll have to get them all at the same time.
	 *
	 * This API call is actually meant for DiscoJuice according to the documentation,
	 * but it contains information that the other APIs don't have,
	 * such as icon and geolocation.
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listAllIdentityProviders
	 * @var array
	 */
	static $allIdentityProviders = [];

	/**
	 * List of all identity providers by CAT base, country, language and IdP entity id.
	 *
	 * This variable is static to facilitate lazy-loading.
	 * The CAT API has no support to get one identity provider,
	 * so we'll have to get them all at the same time.
	 *
	 * This API call is the preferred one according to the documentation,
	 * but it is lacking in features.
	 * Lazy loading will use this if possible, and fall back to allIdentityProviders
	 * if the requested information is not available through IdPbyCountry
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listIdentityProviders
	 * @var array
	 */
	static $identityProvidersByCountry = [];

	/**
	 * Fill lazy loaded $allIdentityProviders
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listAllIdentityProviders
	 *
	 * @param CAT $cat CAT instance
	 * @param string $lang Language
	 */
	private static function loadAllIdentityProviders(CAT $cat, $lang = '') {
		if (!isset(static::$allIdentityProviders[$cat->getBase()][$lang])) {
			$idps = $cat->listAllIdentityProviders($lang);
			foreach($idps as $idp) {
				static::$allIdentityProviders[$cat->getBase()][$lang][$idp->id] = $idp;
			}
		}
	}
	/**
	 * Get all identity providers as objects as an indexed array
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listAllIdentityProviders
	 *
	 * @param CAT $cat CAT instance
	 * @param string $lang Language
	 *
	 * @return IdentityProvider[]
	 */
	public static function getAllIdentityProviders(CAT $cat, $lang = '') {
		static::loadAllIdentityProviders($cat, $lang);
		$idps = [];
		foreach(static::$allIdentityProviders[$cat->getBase()][$lang] as $idpData) {
			$idps[$idpData->entityID] = new IdentityProvider($cat, $idpData->entityID, $lang);
		}
		return $idps;
	}

	/**
	 * Fill lazy loaded $identityProvidersByCountry
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listIdentityProviders
	 *
	 * @param CAT $cat CAT instance
	 * @param string $c Country, usually two uppercase letters
	 * @param string $lang Language
	 */
	private static function loadIdentityProvidersByCountry(CAT $cat, $c, $lang = '') {
		if (!isset(static::$identityProvidersByCountry[$cat->getBase()][$c][$lang])) {
			$idps = $cat->listIdentityProviders($c, $lang);
			foreach($idps as $idp) {
				static::$identityProvidersByCountry[$cat->getBase()][$c][$lang][$idp->id] = $idp;
			}
		}
	}

	/**
	 * Get all identity providers from the given country as objects
	 *
	 * @link https://cat.eduroam.org/doc/UserAPI/tutorial_UserAPI.pkg.html#actions.listIdentityProviders
	 *
	 * @param CAT $cat CAT instance
	 * @param string $c Country, usually two uppercase letters
	 * @param string $lang Language
	 */
	public static function getIdentityProvidersByCountry(CAT $cat, $c, $lang = '') {
		static::loadIdentityProvidersByCountry($cat, $c, $lang);
		$idps = [];
		foreach(static::$identityProvidersByCountry[$cat->getBase()][$c][$lang] as $idpData) {
			$idps[$idpData->id] = new IdentityProvider($cat, $idpData->id, $lang);
			$idps[$idpData->id]->c = $c;
		}
		return $idps;
	}

	/**
	 * CAT instance
	 * @var CAT
	 */
	private $cat;
	/**
	 * Identity provider Entity ID in CAT API
	 * @var int
	 */
	private $id;
	/**
	 * 2-char country code
	 * @var string
	 */
	private $c;
	/**
	 * Language flag to use in requests against CAT
	 * @var string
	 */
	private $lang;

	/**
	 * Construct a lazy-loaded identity provider
	 *
	 * When any of the getters is called, the class will load the value from the
	 * static cache, or fill the static cache.
	 *
	 * @param CAT $cat CAT instance
	 * @param int $id Entity ID
	 * @param string $lang Language
	 */
	public function __construct(CAT $cat, $id, $lang = '') {
		$this->cat = $cat;
		$this->id = $id;
		$this->lang = $lang;
	}

	/**
	 * Get the raw data associated with this identity provider.
	 *
	 * This is the JSON data converted to a PHP object.
	 *
	 * @return stdClass
	 */
	public function getRaw() {
		$this->loadAllIdentityProviders($this->cat, $this->lang);
		return static::$allIdentityProviders[$this->cat->getBase()][$this->lang][$this->id];
	}

	/**
	 * Get the entity ID of this identity provider.
	 *
	 * @return int Entity ID
	 */
	public function getEntityID() {
		return $this->id;
	}

	/**
	 * Get the country the identity provider is located in.
	 * Country is usually represented with two uppercase letters.
	 *
	 * @return string Country code
	 */
	public function getCountry() {
		if (isset($this->c)) {
			return $this->c;
		}
		$this->c = $this->getRaw()->country;
		return $this->c;
	}

	/**
	 * Get the icon ID for this institution.
	 * This ID is usually the same as Entity ID if an icon is set,
	 * or <code>NULL</code> if no icon is set.
	 *
	 * @return int Icon ID
	 */
	public function getIconID() {
		return isset($this->getRaw()->icon) ? $this->getRaw()->icon : null;
	}

	/**
	 * Get the icon URL on CAT for this institution.
	 * If the institution has no icon, return NULL instead.
	 *
	 * @return string URL to icon, for hotlinking from CAT
	 */
	public function getIconUrl() {
		$icon = $this->getIconID();
		if (!is_null($icon)) {
			return $this->cat->getBase() . '?'
				. http_build_query([
						'action' => 'sendLogo', 
						'id' => $icon
					], '', '&', PHP_QUERY_RFC3986);
		}
	}

	/**
	 * Alias for #getTitle()
	 *
	 * @return string The title
	 */
	public function getDisplay() {
		return $this->getTitle();
	}

	/**
	 * Get the title of the identity provider.
	 * This is typically the friendly name of the institution.
	 *
	 * @return string The title
	 */
	public function getTitle() {
		if (isset($this->c)
			&& !isset($this->getRaw()->title)
		) {
			$this->loadIdentityProvidersByCountry($this->cat, $this->c, $this->lang);
			return $this->getRaw()->display;
		}
		return $this->getRaw()->title;
	}

	/**
	 * Get the latitude and longitude where the institution is located.
	 * This function returns a list of objects that have <code>lat</code> and <code>lon</code> properties.
	 * If no geolocation information is known, the array is empty.
	 *
	 * @return stdClass[]
	 */
	public function getGeo() {
		$idp = $this->getRaw();
		return isset($idp->geo) ? $idp->geo : [];
	}

	/**
	 * Get the distances from the identity provider to the provided location.
	 * This function will always return a filled array,
	 * if the institution doesn't have a location, the array will contain
	 * one infinity value.  This makes it easier to call #min() and #max().
	 *
	 * @param int $lat Latitude
	 * @param int $lon Longitude
	 * @return float[]
	 */
	public function getDistanceFrom($lat, $lon) {
		$results = [];
		$geos = $this->getGeo();
		if (!$geos) {
			return [INF];
		}
		foreach($geos as $geo) {
			$lat2 = deg2rad($geo->lat);
			$lat1 = deg2rad($lat);
			$lon2 = deg2rad($geo->lon);
			$lon1 = deg2rad($lon);
			$results[] = acos(sin($lat1)*sin($lat2)+cos($lat1)*cos($lat2)*cos($lon2-$lon1))*6371;
		}
		return $results;
	}

	/*
	 * #getEmail(), #getPhone() and #getWebsite() are omitted because
	 * these are not exposed via the public CAT API.
	 */

	/**
	 * Return all profiles associated with this identity provider
	 *
	 * @return Profile[]
	 */
	public function getProfiles() {
		return Profile::getProfilesByIdP($this->cat, $this, $this->lang);
	}

}
