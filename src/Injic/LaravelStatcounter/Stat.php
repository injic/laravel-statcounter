<?php namespace Injic\LaravelStatcounter;

use Closure;
use \Carbon\Carbon;

class Stat {
  
  /**
   * Date Range Granularity: Hourly
   */
  const GRAN_HOURLY = 0;
  /**
   * Date Range Granularity: Daily
   */
  const GRAN_DAILY = 1;
  /**
   * Date Range Granularity: Weekly
   */
  const GRAN_WEEKLY = 2;
  /**
   * Date Range Granularity: Monthly
   */
  const GRAN_MONTHLY = 3;
  /**
   * Date Range Granularity: Quarterly
   */
  const GRAN_QUARTERLY = 4;
  /**
   * Date Range Granularity: Yearly
   */
  const GRAN_YEARLY = 5;
  /**
   * Device Type: All
   */
  const DEVICE_ALL = 0;
  /**
   * Device Type: Desktop
   */
  const DEVICE_DESKTOP = 1;
  /**
   * Device Type: Mobile
   */
  const DEVICE_MOBILE = 2;
  /**
   * Combine Keywords by: Search Engine Host
   */
  const SEARCH_ENGINE_HOST = 0;
  /**
   * Combine Keywords by: Search Engine Name
   */
  const SEARCH_ENGINE_NAME = 1;
  /**
   * Combine Keywords by: Together (assuming by both Search Engine Host
   * and Search Engine Name)
   */
  const SEARCH_ENGINE_TOGETHER = 2;
  /**
   * Level of Public Access: All private
   */
  const PUBLIC_STATS_NONE = 0;
  /**
   * Level of Public Access: All public
   */
  const PUBLIC_STATS_ALL = 1;
  /**
   * Level of Public Access: Only summary is public
   */
  const PUBLIC_STATS_SUMMARY = 2;
  
  /**
   * The base URL of the StatCounter API.
   *
   * @var string
   */
  protected static $API_URL = 'https://api.statcounter.com/';

  /**
   * The default parameter array for fresh queries.
   *
   * @see http://api.statcounter.com/docs/v3
   * @var array(string)
   */
  protected static $API_DEFAULT_PARAMS = [ 
      'vn'  => '3',  // Version Number
      'f'   => null, // Format
      's'   => null, // Stats
      'pi'  => null, // Project ID
      'u'   => null, // Username
      'wt'  => null, // Website Title
      'wu'  => null, // Website URL
      't'   => null, // Time of Execution
      'tz'  => null, // Time Zone
      'ps'  => null, // Public Stats
      'n'   => null, // Number of Results
      'c'   => null, // Chop URL
      'ct'  => null, // Count Type
      'g'   => null, // Granularity
      'sh'  => null, // Start Hour
      'sd'  => null, // Start Day
      'sm'  => null, // Start Month
      'sy'  => null, // Start Year
      'sw'  => null, // Start Week
      'sq'  => null, // Start Quarter
      'eh'  => null, // End Hour
      'ed'  => null, // End Day
      'em'  => null, // End Month
      'ey'  => null, // End Year
      'ew'  => null, // End Week
      'eq'  => null, // End Quarter
      'e'   => null, // Exclude External
      'ese' => null, // Exclude Search Engines
      'eek' => null, // Exclude Encrypted Keywords
      'ck'  => null, // Combine Keywords
      'gbd' => null, // Group By Domain
      'de'  => null, // Device
      'ip'  => null  // IP Address
  ];
  
  /**
   * A map of project IDs to project names.
   * 
   * @var array
   */
  protected $projects;

  /**
   * The selected function to be called (stats, select_project, etc).
   *
   * @var string
   */
  protected $func;

  /**
   * The parameters to be passed to the selected API function.
   *
   * @var array(string)
   */
  protected $params;

	/**
	 * The maximum number of records to return.
	 *
	 * @var int
	 */
	public $limit = null;

	/**
	 * The number of records to skip.
	 *
	 * @var int
	 */
	public $offset = 0;

  /**
   * The key that should be used when caching the query.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * The number of minutes to cache the query.
   *
   * @var int
   */
  protected $cacheMinutes;

  /**
   * The tags for the query cache.
   *
   * @var array
   */
  protected $cacheTags;

  /**
   * The cache driver to be used.
   *
   * @var string
   */
  protected $cacheDriver;

  /**
   * Create new stat instance.
   */
  public function __construct()
  {
    $this->projects = array_flip( \Config::get('laravel-statcounter::projects') );
  }
  
  /**
   * Initializes a 'Stats' query.
   * 
   * @param unknown $type
   */
  protected function initStats($type) {
    $this->func = 'stats/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    $this->params['s'] = $type;
  }
  
  /**
   * Start a 'recent visitor' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function recentVisitors()
  {
    $this->initStats('visitor');
    
    return $this;
  }
  
  /**
   * Start a 'popular pages' query.
   * 
   * @param boolean $chopUrl
   * @param string $countType
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function popularPages($chopUrl = true, $countType = null)
  {
    $this->initStats('popular');
    
    if (!$chopUrl) $this->params['c'] = '0';
    
    if (!is_null($countType)) $this->params['ct'] = $countType;
    
    return $this;
  }

  /**
   * Start an 'entry pages' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function entryPages()
  {
    $this->initStats('entry');
  
    return $this;
  }

  /**
   * Start an 'exit pages' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function exitPages()
  {
    $this->initStats('exit');
  
    return $this;
  }

  /**
   * Start a 'came from' query.
   * 
   * @param boolean $external
   * @param boolean $excludeSearchEngines
   * @param boolean $groupByDomain
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function cameFrom($external = true, $excludeSearchEngines = false, $groupByDomain = false)
  {
    $this->initStats('camefrom');
    
    if (!$external) $this->params['e'] = '0';
    
    if ($excludeSearchEngines) $this->params['ese'] = '1';
    
    if ($groupByDomain) $this->params['gbd'] = '1';
  
    return $this;
  }
  
  /**
   * Start a 'recent keyword activity' query.
   * 
   * @param boolean $excludeEncryptedKeywords
   * @param boolean $external
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function recentKeywords($excludeEncryptedKeywords = false, $external = true)
  {
    $this->initStats('keyword-activity');
    
    if (!$external) $this->params['e'] = '0';
    
    if ($excludeEncryptedKeywords) $this->params['eek'] = '1';
  
    return $this;
  }

  /**
   * Start a 'browsers' query.
   * 
   * @param integer $device
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function browsers($device = self::DEVICE_ALL)
  {
    $this->initStats('browsers');
    
    if ($device != self::DEVICE_ALL) {
      switch ($device)
      {
      	case self::DEVICE_DESKTOP:
      	  $this->params['de'] = 'desktop';
      	  break;
      	case self::DEVICE_MOBILE:
      	  $this->params['de'] = 'mobile';
      	  break;
      }
    }
  
    return $this;
  }

  /**
   * Start an 'operating systems' query.
   *
   * @param integer $device
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function operatingSystems($device = self::DEVICE_ALL)
  {
    $this->initStats('os');
  
    if ($device != self::DEVICE_ALL) {
      switch ($device)
      {
      	case self::DEVICE_DESKTOP:
      	  $this->params['de'] = 'desktop';
      	  break;
      	case self::DEVICE_MOBILE:
      	  $this->params['de'] = 'mobile';
      	  break;
      }
    }
  
    return $this;
  }

  /**
   * Start a 'search engines' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function searchEngines()
  {
    $this->initStats('search_engine');
  
    return $this;
  }

  /**
   * Start a 'country' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function country()
  {
    $this->initStats('country');
  
    return $this;
  }

  /**
   * Start an 'recent pageload activity' query.
   *
   * @param integer $device
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function recentPageload($device = self::DEVICE_ALL)
  {
    $this->initStats('pageload');
  
    if ($device != self::DEVICE_ALL) {
      switch ($device)
      {
      	case self::DEVICE_DESKTOP:
      	  $this->params['de'] = 'desktop';
      	  break;
      	case self::DEVICE_MOBILE:
      	  $this->params['de'] = 'mobile';
      	  break;
      }
    }
  
    return $this;
  }

  /**
   * Start an 'exit link activity' query.
   *
   * @param integer $device
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function exitLink($device = self::DEVICE_ALL)
  {
    $this->initStats('exit-link-activity');
  
    if ($device != self::DEVICE_ALL) {
      switch ($device)
      {
      	case self::DEVICE_DESKTOP:
      	  $this->params['de'] = 'desktop';
      	  break;
      	case self::DEVICE_MOBILE:
      	  $this->params['de'] = 'mobile';
      	  break;
      }
    }
  
    return $this;
  }

  /**
   * Start an 'download link activity' query.
   *
   * @param integer $device
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function downloadLink($device = self::DEVICE_ALL)
  {
    $this->initStats('download-link-activity');
  
    if ($device != self::DEVICE_ALL) {
      switch ($device)
      {
      	case self::DEVICE_DESKTOP:
      	  $this->params['de'] = 'desktop';
      	  break;
      	case self::DEVICE_MOBILE:
      	  $this->params['de'] = 'mobile';
      	  break;
      }
    }
  
    return $this;
  }

  /**
   * Start a 'visit length' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function visitLength()
  {
    $this->initStats('visit_length');
  
    return $this;
  }

  /**
   * Start a 'returning visits' query.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function returningVisits()
  {
    $this->initStats('returning_visits');
  
    return $this;
  }

  /**
   * Start a 'keyword analysis' query.
   * 
   * @param integer $combineKeywords
   * @param boolean $excludeEncryptedKeywords
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function keywordAnalysis($combineKeywords = self::SEARCH_ENGINE_HOST, $excludeEncryptedKeywords = false)
  {
    $this->initStats('keyword_analysis');
    
    if ($combineKeywords != self::SEARCH_ENGINE_HOST) {
      switch ($combineKeywords)
      {
      	case self::SEARCH_ENGINE_NAME:
      	  $this->params['ck'] = 'search_engine_name';
      	  break;
      	case self::SEARCH_ENGINE_TOGETHER:
      	  $this->params['ck'] = 'together';
      	  break;
      }
    }
    
    if ($excludeEncryptedKeywords) $this->params['eek'] = '1';
    
    return $this;
  }

  /**
   * Start a 'lookup visitor' query.
   * 
   * @param string $ipAddress
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function lookupVisitor($ipAddress)
  {
    $this->initStats('lookup_visitor');
    
    $this->params['ip'] = $ipAddress;
  
    return $this;
  }
  
  /**
   * Start an 'add project' query.
   * 
   * @param string $websiteTitle
   * @param string $websiteUrl
   * @param string $timezone
   * @param boolean $publicStats
   * @return array
   */
  public function addProject($websiteTitle, $websiteUrl, $timezone, $publicStats = self::PUBLIC_STATS_NONE)
  {
    $this->func = 'add_project/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    $this->params['wt'] = $websiteTitle;
    $this->params['wu'] = $websiteUrl;
    $this->params['tz'] = $timezone;
    
    if ($publicStats != self::PUBLIC_STATS_ALL) {
      $this->params['ps'] = $publicStats;
    }
  
    return $this->get();
  }
  
  /**
   * Start an 'update project' query.
   * 
   * @param string $websiteTitle
   * @param boolean $publicStats
   * @return array
   */
  public function updateProject($websiteTitle = null, $publicStats = self::PUBLIC_STATS_NONE)
  {
    $this->func = 'update_project/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if (!is_null($websiteTitle))
    {
      $this->projects($websiteTitle);
    }
    
    if ($publicStats != self::PUBLIC_STATS_ALL) {
      $this->params['ps'] = $publicStats;
    }
  
    return $this->get();
  }

  /**
   * Start an 'update logsize' query.
   *
   * @param string $websiteTitle
   * @return array
   */
  public function updateLogsize($websiteTitle = null)
  {
    $this->func = 'update_logsize/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
  
    if (!is_null($websiteTitle))
    {
      $this->projects($websiteTitle);
    }
  
    return $this->get();
  }

  /**
   * Start an 'account logsizes' query.
   *
   * @return array
   */
  public function accountLogsizes()
  {
    $this->func = 'account_logsizes/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
  
    return $this->get();
  }

  /**
   * Start an 'user details' query.
   *
   * @param string $username
   * @param string $password
   * @return array
   */
  public function userDetails($username = null, $password = null)
  {
    $this->func = 'user_details/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if (!is_null($username))
    {
      $this->params['u'] = $username;
    }
    
    $originalPassword = null;
    if (!is_null($password))
    {
      $originalPassword = \Config::get( 'laravel-statcounter::api-password' );
      \Config::set( 'laravel-statcounter::api-password', $password);
      $overrodePassword = true;
    }
  
    $data = $this->get();
    
    if (!is_null($originalPassword))
    {
      \Config::set( 'laravel-statcounter::api-password', $originalPassword);
    }
    
    return $data;
  }

  /**
   * Start an 'user projects' query.
   *
   * @param string $username
   * @param string $password
   * @return array
   */
  public function userProjects($username = null, $password = null)
  {
    $this->func = 'user_projects/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if (!is_null($username))
    {
      $this->params['u'] = $username;
    }
    
    $originalPassword = null;
    if (!is_null($password))
    {
      $originalPassword = \Config::get( 'laravel-statcounter::api-password' );
      \Config::set( 'laravel-statcounter::api-password', $password);
      $overrodePassword = true;
    }
  
    $data = $this->get();
    
    if (!is_null($originalPassword))
    {
      \Config::set( 'laravel-statcounter::api-password', $originalPassword);
    }
    
    return $data;
  }

  /**
   * Start a 'select project' query.
   *
   * @param string $websiteTitle
   * @return array
   */
  public function selectProject($websiteTitle = null)
  {
    $this->func = 'select_project/';
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
  
    if (!is_null($websiteTitle))
    {
      $this->projects($websiteTitle);
    }
    
    return $this->get();
  }
  
  /**
   * Compiles the URL with the current parameters.
   *
   * @return string
   */
  public function toUrl($isFull = true)
  {
    if (is_null($this->params['u']))
    {
      $this->params['u'] = \Config::get( 'laravel-statcounter::username' );
    }
    
    $this->params['t'] = time();
    
    if (is_null($this->params['pi']))
    {
      $this->params['pi'] = \Config::get( 'laravel-statcounter::projects.' . 
          \Config::get( 'laravel-statcounter::default' ) );
    }
    
    $tmpParams = ($isFull ? $this->params : array_except( $this->params, [ 
        't' 
    ] ));
    
    $encodedUrl = '?' . http_build_query( $tmpParams, null, '&' );
    
    $url = preg_replace( '/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $encodedUrl );
    
    return self::$API_URL . $this->func . ($isFull ? $url . '&sha1=' . 
        sha1( $url . \Config::get( 'laravel-statcounter::api-password' ) ) : $url);
  }

  /**
   * Indicate that the query results should be cached.
   *
   * @param integer $minutes
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function remember($minutes)
  {
    $this->cacheMinutes = $minutes;
    
    return $this;
  }

  /**
   * Indicate that the query results should be cached forever.
   *
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function rememberForever()
  {
    return $this->remember( - 1 );
  }

  /**
   * Indicate that the results, if cached, should use the given cache tags.
   *
   * @param array|dynamic $cacheTags          
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function cacheTags($cacheTags)
  {
    $this->cacheTags = $cacheTags;
    
    return $this;
  }

  /**
   * Indicate that the results, if cached, should use the given cache driver.
   *
   * @param string $cacheDriver          
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function cacheDriver($cacheDriver)
  {
    $this->cacheDriver = $cacheDriver;
    
    return $this;
  }

  /**
   * Execute the query and get the first result.
   *
   * @param  array   $columns
   * @return mixed|static
   */
  public function first($params = null)
  {
    $results = $this->take(1)->get($params);
  
    return count($results) > 0 ? reset($results) : null;
  }
  
  /**
   * Execute the query as a "select" statement.
   *
   * @param array $params
   * @return array
   */
  public function get($params = null)
  {
    $data = [];
    
    if (! is_null( $this->cacheMinutes ))
    { 
      $data = $this->getCached( $params );
    }
    else 
    {
      $data = $this->getFresh( $params );
    }
    
    $this->params = null;
    
    return array_slice($data, $this->offset, $this->limit, true);
  }

  /**
   * Execute the query as a fresh call to the API.
   *
   * @param array $params
   * @return array
   */
  public function getFresh($params = null)
  {
    if (is_null($this->params)) $this->params = $params;
    
    $ch = curl_init();
    
    $optArray = array (
        CURLOPT_URL            => self::toUrl(),
        CURLOPT_RETURNTRANSFER => true,  // return content
        CURLOPT_HEADER         => false, // don't return headers
        CURLOPT_FOLLOWLOCATION => true,  // follow redirects
        CURLOPT_ENCODING       => "",    // handle compressed
        CURLOPT_AUTOREFERER    => true,  // set referer on redirect
        CURLOPT_MAXREDIRS      => 10,    // stop after 10 redirects
        CURLOPT_FAILONERROR    => true   // fail if HTTP returns error
    );
    curl_setopt_array( $ch, $optArray );
    
    $response = curl_exec( $ch );
    
    if ($response === false)
    {
      throw new StatException( 'Error connecting to API: ' . curl_error( $ch ) );
    }
    
    $result = json_decode( $response );
    
    if ($result === null)
    {
      throw new StatException( "Error decoding API response: \n" . $response );
    }
    
    if ($result->{"@attributes"}->status === 'fail' || $result->{"@attributes"}->status != 'ok')
    {
      $messages = '';
      foreach ( $result->error as $error )
      {
        $messages .= $error->description . "\n";
      }
      throw new StatException( "Error response from API: \n" . $messages );
    }
    
    $data = null;
    if (is_array($this->params['pi']))
    {
      foreach($result->project as $project)
      {
      	$data[$this->projects[$project->id]] = $project->sc_data;
      }
    }
    else if (property_exists($result,'sc_data'))
    {
      $data = $result->sc_data;
    }
    
    return $data;
  }

  /**
   * Execute the query as a cached call to the API.
   *
   * @param array $params
   * @return array
   */
  public function getCached($params = null)
  {
    if (is_null($this->params)) $this->params = $params;
    
    // If the query is requested to be cached, we will cache it using a unique key
    // for this database connection and query statement, including the bindings
    // that are used on this query, providing great convenience when caching.
    list ( $key, $minutes ) = $this->getCacheInfo();
    
    $cache = $this->getCache();
    
    $callback = $this->getCacheCallback( $this->params );
    
    // If the "minutes" value is less than zero, we will use that as the indicator
    // that the value should be remembered values should be stored indefinitely
    // and if we have minutes we will use the typical remember function here.
    if ($minutes < 0)
    {
      return $cache->rememberForever( $key, $callback );
    }
    else
    {
      return $cache->remember( $key, $minutes, $callback );
    }
  }

  /**
   * Get the cache object with tags assigned, if applicable.
   *
   * @return \Illuminate\Cache\CacheManager
   */
  protected function getCache()
  {
    $cache = \Cache::driver( $this->cacheDriver );
    
    return $this->cacheTags ? $cache->tags( $this->cacheTags ) : $cache;
  }

  /**
   * Get the cache key and cache minutes as an array.
   *
   * @return array
   */
  protected function getCacheInfo()
  {
    return array (
        $this->getCacheKey(),
        $this->cacheMinutes 
    );
  }

  /**
   * Get a unique cache key for the complete query.
   *
   * @return string
   */
  public function getCacheKey()
  {
    return $this->cacheKey ?  : $this->generateCacheKey();
  }

  /**
   * Generate the unique cache key for the query.
   *
   * @return string
   */
  public function generateCacheKey()
  {
    return sha1( $this->toUrl( false ) . \Config::get( 'laravel-statcounter::api-password' ) );
  }

  /**
   * Get the Closure callback used when caching queries.
   *
   * @param array $params          
   * @return \Closure
   */
  protected function getCacheCallback($params)
  {
    return function () use($params)
    {
      return $this->getFresh( $params );
    };
  }
  
  /**
   * The StatCounter API allows for the retrieval of each statistic type 
   * (Popular Pages, Recent Visitors, etc.) within a set time period. To select
   * your required period, just add the necessary details to your API call, as 
   * detailed for each appropriate time period below. If no time period is 
   * specified, the API will search all available records.
   * 
   * @param integer               $granularity
   * @param integer|string|Carbon $start
   * @param integer|string|Carbon $end
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function setRange($granularity, $start, $end)
  {
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if ( !($start instanceof Carbon) )
    {
      if (is_numeric($start))
      {
        $start = Carbon::createFromTimeStamp($start);
      }
      else 
      {
        $start = new Carbon($start);
      }
    }
    if ( !($end instanceof Carbon) )
    {
      if (is_numeric($end))
      {
        $end = Carbon::createFromTimeStamp($end);
      }
      else
      {
        $end = new Carbon($end);
      }
    }
    
    switch($granularity)
    {
    	case self::GRAN_HOURLY:
    	  $this->params['g'] = 'hourly';
    	  $this->params['sh'] = $start->hour;
    	  $this->params['sd'] = $start->day;
    	  $this->params['sm'] = $start->month;
    	  $this->params['sy'] = $start->year;
    	  $this->params['eh'] = $end->hour;
    	  $this->params['ed'] = $end->day;
    	  $this->params['em'] = $end->month;
    	  $this->params['ey'] = $end->year;
    	  break;
    	case self::GRAN_DAILY:
    	  $this->params['g'] = 'daily';
    	  $this->params['sd'] = $start->day;
    	  $this->params['sm'] = $start->month;
    	  $this->params['sy'] = $start->year;
    	  $this->params['ed'] = $end->day;
    	  $this->params['em'] = $end->month;
    	  $this->params['ey'] = $end->year;
    	  break;
    	case self::GRAN_WEEKLY:
    	  $this->params['g'] = 'weekly';
    	  $this->params['sw'] = $start->weekOfYear;
    	  $this->params['sy'] = $start->year;
    	  $this->params['ew'] = $end->weekOfYear;
    	  $this->params['ey'] = $end->year;
    	  break;
    	case self::GRAN_MONTHLY:
    	  $this->params['g'] = 'monthly';
    	  $this->params['sm'] = $start->month;
    	  $this->params['sy'] = $start->year;
    	  $this->params['em'] = $end->month;
    	  $this->params['ey'] = $end->year;
    	  break;
    	case self::GRAN_QUARTERLY:
    	  $this->params['g'] = 'quarterly';
    	  $this->params['sq'] = $start->quarter;
    	  $this->params['sy'] = $start->year;
    	  $this->params['eq'] = $end->quarter;
    	  $this->params['ey'] = $end->year;
    	  break;
    	case self::GRAN_YEARLY:
    	  $this->params['g'] = 'yearly';
    	  $this->params['sy'] = $start->year;
    	  $this->params['ey'] = $end->year;
    	  break;
    }
    
    return $this;
  }
  
  /**
   * Sets or adds a project to the query.
   * 
   * @param string $websiteTitle
   * @return \Injic\LaravelStatcounter\Stat
   */
  public function project($websiteTitle)
  {
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    $projects = \Config::get('laravel-statcounter::projects');
    if ($this->params['pi'] === null)
    {
      $this->params['pi'] = $projects[$websiteTitle];
    }
    else if (is_string($this->params['pi']))
    {
      $tmp = $this->params['pi'];
      $this->params['pi'] = [];
      $this->params['pi'][] = $tmp;
      $this->params['pi'][] = $projects[$websiteTitle];
    }
    else if (is_array($this->params['pi']))
    {
      $this->params['pi'][] = $projects[$websiteTitle];
    }
    
    return $this;
  }
  
	/**
	 * Set the "offset" value of the query.
	 *
	 * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat
	 */
	public function offset($value)
	{
		$this->offset = max(0, $value);

		return $this;
	}

	/**
	 * Alias to set the "offset" value of the query.
	 *
	 * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat
	 */
	public function skip($value)
	{
		return $this->offset($value);
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat
	 */
	public function limit($value)
	{
		if ($value > 0) $this->limit = $value;

		return $this;
	}

	/**
	 * Alias to set the "limit" value of the query.
	 *
	 * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat
	 */
	public function take($value)
	{
		return $this->limit($value);
	}
  
	/**
	 * Overrides the username for this pageload.
	 * 
	 * @param string $username
	 */
	public function setUsername($username)
	{
	  \Config::set( 'laravel-statcounter::username', $username);
	}

	/**
	 * Overrides the password for this pageload.
	 * 
	 * @param string $password
	 */
	public function setPassword($password)
	{
	  \Config::set( 'laravel-statcounter::api-password', $password);
	}
	
}