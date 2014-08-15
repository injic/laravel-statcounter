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
      'n'   => 1000, // Number of Results
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
   * The API query URL.
   * 
   * @var string
   */
  protected $url;

  /**
   * The selected function to be called (stats, select_project, etc).
   *
   * @var string
   */
  public $func;

  /**
   * The parameters to be passed to the selected API function.
   *
   * @var array(string)
   */
  public $params;

  /**
   * The columns that should be returned.
   *
   * @var array
   */
  public $columns;

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
   * The backups of fields while doing a pagination count.
   *
   * @var array
   */
  protected $backups = array();
  
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
   * Displays the StatCounter tracker for the project.
   * 
   * @param string $isHttps
   * @param string $isVisible
   * @param string $websiteTitle
   * @return string
   */
  public function tracker($isHttps = false, $isVisible = false, $websiteTitle = null) {
    $pid = '';
    $security = '';
    if (is_null($websiteTitle)) 
    {
      $pid = \Config::get( 'laravel-statcounter::projects.' .
          \Config::get( 'laravel-statcounter::default' ) );
      $security = \Config::get( 'laravel-statcounter::security-codes.' .
          \Config::get( 'laravel-statcounter::default' ) );
    }
    else if (is_string($websiteTitle))
    {
      $pid = \Config::get( 'laravel-statcounter::projects.' . $websiteTitle);
      $security = \Config::get( 'laravel-statcounter::security-codes.' . $websiteTitle);
    }
    
    return '<script type="text/javascript">
var sc_project=' . $pid . '; 
var sc_invisible=' . ( $isVisible ? '0' : '1' ) . '; 
var sc_security="' . $security . '"; 
var sc_https=' . ( $isHttps ? '0' : '1' ) . '; 
var scJsHost = (("https:" == document.location.protocol) ?
"https://secure." : "http://www.");
document.write("<sc"+"ript type=\'text/javascript\' src=\'" +
scJsHost+
"statcounter.com/counter/counter.js\'></"+"script>");
</script>
<noscript><div class="statcounter"><a title="hits counter"
href="http://statcounter.com/" target="_blank"><img
class="statcounter"
src="http://c.statcounter.com/' . $pid . '/0/' . $security . '/' . ( $isVisible ? '0' : '1' ) . '/"
alt="hits counter"></a></div></noscript>';
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function entryPages()
  {
    $this->initStats('entry');
  
    return $this;
  }

  /**
   * Start an 'exit pages' query.
   *
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function searchEngines()
  {
    $this->initStats('search_engine');
  
    return $this;
  }

  /**
   * Start a 'country' query.
   *
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function visitLength()
  {
    $this->initStats('visit_length');
  
    return $this;
  }

  /**
   * Start a 'returning visits' query.
   *
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
      $this->project($websiteTitle);
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
      $this->project($websiteTitle);
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
      $this->project($websiteTitle);
    }
    
    return $this->get();
  }
  
  /**
   * Set the "offset" value of the query.
   *
   * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function skip($value)
  {
    return $this->offset($value);
  }

  /**
   * Set the "limit" value of the query.
   *
   * @param  int  $value
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function take($value)
  {
    return $this->limit($value);
  }
  
  /**
   * Compiles the URL with the current parameters.
   *
   * @return string
   */
  public function toUrl($isFull = true)
  {
    if (!is_null($this->url)) return $this->url;
    
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if (!array_key_exists('u', $this->params) || is_null($this->params['u']))
    {
      $this->params['u'] = \Config::get( 'laravel-statcounter::username' );
    }
    
    $this->params['t'] = time();
    
    if (!array_key_exists('pi', $this->params) || is_null($this->params['pi']))
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function remember($minutes)
  {
    $this->cacheMinutes = $minutes;
    
    return $this;
  }

  /**
   * Indicate that the query results should be cached forever.
   *
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function rememberForever()
  {
    return $this->remember( - 1 );
  }

  /**
   * Indicate that the results, if cached, should use the given cache tags.
   *
   * @param array|dynamic $cacheTags          
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function cacheDriver($cacheDriver)
  {
    $this->cacheDriver = $cacheDriver;
    
    return $this;
  }

  /**
   * Pluck a single column's value from the first result of a query.
   *
   * @param  string  $column
   * @return mixed
   */
  public function pluck($column)
  {
    
    $result = (array) $this->first(array($column));

    return count($result) > 0 ? reset($result) : null;
  }

  /**
   * Execute the query and get the first result.
   *
   * @param  array   $columns
   * @return mixed|static
   */
  public function first($columns = array('*'))
  {
    $results = $this->numberOfResults(1)->get($columns);
  
    return count($results) > 0 ? reset($results) : null;
  }
  
  /**
   * Execute the query as a "select" statement.
   *
   * @param array $columns
   * @return array
   */
  public function get($columns = array('*'))
  {
    $data = [];
    
    if (! is_null( $this->cacheMinutes ))
    { 
      $data = $this->getCached( $columns );
    }
    else 
    {
      $data = $this->getFresh( $columns );
    }
    
    $this->params = null;
    $this->columns = array('*');
    $this->url = null;
    
    $result = array_slice($data, $this->offset, $this->limit, true);
    
    $this->limit = null;
    $this->offset = 0;
    
    return $result;
  }

  /**
   * Execute the query as a fresh call to the API.
   *
   * @param array $columns
   * @return array
   */
  public function getFresh($columns = array('*'))
  {
    if (is_null($this->columns)) $this->columns = $columns;

    return $this->runQuery(self::toUrl());
  }
  
  /**
   * Run the query against the API.
   * 
   * @param string $url
   * @throws StatException 
   * @return array
   */
  protected function runQuery($url)
  {
    $ch = curl_init();
    
    $optArray = array (
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,  // return content
        CURLOPT_HEADER         => false, // don't return headers
        CURLOPT_FOLLOWLOCATION => true,  // follow redirects
        CURLOPT_ENCODING       => "",    // handle compressed
        CURLOPT_AUTOREFERER    => true,  // set referer on redirect
        CURLOPT_MAXREDIRS      => 10,    // stop after 10 redirects
        CURLOPT_FAILONERROR    => true   // fail if HTTP returns error
    );
    curl_setopt_array( $ch, $optArray );
    
    // send query and store response
    $response = curl_exec( $ch );
    
    if ($response === false)
    {
      throw new StatException( 'Error connecting to API: ' . curl_error( $ch ) );
    }

    $result = json_decode( $response );
    
    if ($result === null)
    {
      throw new StatException( "Error decoding API response: " . $response );
    }
    
    if ($result->{"@attributes"}->status === 'fail' || $result->{"@attributes"}->status != 'ok')
    {
      $messages = '';
      foreach ( $result->error as $error )
      {
        $messages .= $error->description . "\n";
      }
      throw new StatException( "StatCounter API Error: " . $messages );
    }
    
    $data = [];
    // Handle multiple projects if necessary
    if (is_array($this->params['pi']))
    {
      foreach($result->project as $project)
      {
        if (!property_exists($project,'id') || !property_exists($project,'sc_data')) continue;
        $data[$this->projects[$project->id]] = $this->trimColumns($project->sc_data);
      }
    }
    else if (property_exists($result,'sc_data'))
    {
      $data = $this->trimColumns($result->sc_data);
    }
    
    return $data;
  }

  /**
   * Execute the query as a cached call to the API.
   *
   * @param array $columns
   * @return array
   */
  public function getCached($columns = array('*'))
  {
    if (is_null($this->columns)) $this->columns = $columns;
        
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
   * @param array $columns          
   * @return \Closure
   */
  protected function getCacheCallback($columns)
  {
    return function () use($columns)
    {
      return $this->getFresh( $columns );
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
   * @return \Injic\LaravelStatcounter\Stat|static
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
  
  /**
   * Set the limit and offset for a given page.
   *
   * @param  int  $page
   * @param  int  $perPage
   * @return \Injic\LaravelStatcounter\Stat|static
   */
  public function forPage($page, $perPage = 15)
  {
    return $this->skip(($page - 1) * $perPage)->take($perPage);
  }
  
  /**
   * Set the number of results to return. Differs from take and limit in that
   * it sends this straight to the API.
   * 
   * @param int $num
   * @return \Injic\LaravelStatcounter|static
   */
  public function numberOfResults($num)
  {
    if (is_numeric($num))
    {
      $this->params['n'] = $num;
    }
    else
    {
      throw new StatException('Non-integer given');
    }
    
    return $this;
  }

  /**
   * Chunk the results of the query.
   *
   * @param  int  $count
   * @param  callable  $callback
   * @return void
   */
  public function chunk($count, callable $callback)
  {
    // Added a short caching of the query to help with chunking performance
    $results = $this->forPage($page = 1, $count)->remember(1)->get();

    while (count($results) > 0)
    {
      // On each chunk result set, we will pass them to the callback and then let the
      // developer take care of everything within the callback, which allows us to
      // keep the memory low for spinning through large result sets for working.
      call_user_func($callback, $results);

      $page++;

      $results = $this->forPage($page, $count)->get();
    }
  }

  /**
   * Get a paginator for the "select" statement.
   *
   * @param  int    $perPage
   * @param  array  $columns
   * @return \Illuminate\Pagination\Paginator
   */
  public function paginate($perPage = 15, $columns = array('*'))
  {
    $paginator = \App::make('paginator');

    if (isset($this->groups))
    {
      // Grouping not an implemented feature with the current API
      //return $this->groupedPaginate($paginator, $perPage, $columns);
    }
    else
    {
      return $this->ungroupedPaginate($paginator, $perPage, $columns);
    }
  }

  /**
   * Create a paginator for an un-grouped pagination statement.
   *
   * @param  \Illuminate\Pagination\Factory  $paginator
   * @param  int    $perPage
   * @param  array  $columns
   * @return \Illuminate\Pagination\Paginator
   */
  protected function ungroupedPaginate($paginator, $perPage, $columns)
  {
    $total = $this->getPaginationCount();

    // Once we have the total number of records to be paginated, we can grab the
    // current page and the result array. Then we are ready to create a brand
    // new Paginator instances for the results which will create the links.
    $page = $paginator->getCurrentPage($total);

    $results = $this->forPage($page, $perPage)->get($columns);

    return $paginator->make($results, $total, $perPage);
  }

  /**
   * Get the count of the total records for pagination.
   *
   * @return int
   */
  public function getPaginationCount()
  {
    $this->backupFieldsForCount();

    // Does a total count, without limit or offset
    $total = $this->count();

    $this->restoreFieldsForCount();

    return $total;
  }

  /**
   * Get a paginator only supporting simple next and previous links.
   *
   * This is more efficient on larger data-sets, etc.
   *
   * @param  int    $perPage
   * @param  array  $columns
   * @return \Illuminate\Pagination\Paginator
   */
  public function simplePaginate($perPage = 15, $columns = array('*'))
  {
    $paginator = \App::make('paginator');

    $page = $paginator->getCurrentPage();

    $this->skip(($page - 1) * $perPage)->take($perPage + 1);

    return $paginator->make($this->get($columns), $perPage);
  }

  /**
   * Backup certain fields for a pagination count.
   *
   * @return void
   */
  protected function backupFieldsForCount()
  {
    foreach (array('params', 'limit', 'offset', 'columns', 'url') as $field)
    {
      $this->backups[$field] = $this->{$field};

      $this->{$field} = null;
    }

    $this->params = $this->backups['params'];
    $this->columns = array('*');
    $this->url = $this->backups['url'];
  }

  /**
   * Restore certain fields for a pagination count.
   *
   * @return void
   */
  protected function restoreFieldsForCount()
  {
    foreach (array('params', 'limit', 'offset', 'columns', 'url') as $field)
    {
      $this->{$field} = $this->backups[$field];
    }

    $this->backups = array();
  }

  /**
   * Determine if any rows exist for the current query.
   *
   * @return bool
   */
  public function exists()
  {
    return $this->count() > 0;
  }

  /**
   * Retrieve the "count" result of the query.
   *
   * @param  string  $columns
   * @return int
   */
  public function count($columns = array('*'))
  {
    $result = count( $this->get($columns) );

    return $result;
  }

  /**
   * Get a new instance of the stat query.
   *
   * @return \Injic\LaravelStatcounter
   */
  public function newQuery()
  {
    return new Stat();
  }
  
  /**
   * Create a raw query to the API.
   *
   * @param  mixed  $value
   * @return \Injic\LaravelStatcounter|static
   */
  public function raw($value)
  {
    if (is_null($this->params)) $this->params = self::$API_DEFAULT_PARAMS;
    
    if ( is_string($value) )
    {
      $params = [];
      
      $url = parse_url($value);
      if (array_key_exists('query',$url))
      {
        parse_str($url['query'], $params);
      }
      else
      {
        parse_str($test, $params);
      }
      
      // If fully qualify URL with SHA-1 is passed, use it
      if (array_key_exists('scheme',$url)
        && array_key_exists('host',$url)
        && array_key_exists('query',$url)
        && array_key_exists('sha1',$params))
      {
        $this->url = $value;
        return $this;
      }
      
      // Otherwise, remove SHA-1 and use params
      if (array_key_exists('sha1',$params)) unset($params['sha1']);
      
      $this->params = array_merge($this->params, $params);
      
      if (array_key_exists('path',$url))
      {
        $this->func = ( $url['path'][0]=='/' ? substr($url['path'],1) : $url['path'] );
      }
      else
      {
        $this->func = 'stats/';
      }
    }
    else if ( is_array($value) )
    {
      $this->params = array_merge($this->params, $value);
    }
    else
    {
      throw new StatException('Invalid raw value submitted');
    }
     
    return $this;
  }

  /**
   * Trims result array by specified columns.
   * 
   * @param mixed $array
   * @return array
   */
  protected function trimColumns($array)
  {
    if (array_search('*',$this->columns)!==false) return $array;
    
    foreach($array as &$subarray)
    {
      foreach($subarray as $key => $value)
      {
        if (array_search($key,$this->columns)===false)
        {
          unset($subarray->{$key});
        }
      }
    }
    
    return $array;
  }
  
  /**
   * Reads query results for columns.
   * 
   * @return array
   */
  public function columns()
  {
    $columns = [];
    
    $array = $this->get();
     
    foreach($array as $element)
    {
      foreach($element as $key => $value)
      {
        if (array_search($key,$columns)===false) $columns[] = $key;
      }
    }
     
    return $columns;
  }
  
  /**
   * Reads array for column values.
   * 
   * @param array $array
   * @return array
   */
  public static function getColumns($array)
  {
    $columns = [];
    
    foreach($array as $element)
    {
      foreach($element as $key => $value)
      {
        if (array_search($key,$columns)===false) $columns[] = $key;
      }
    }
    
    return $columns;
  }
  
}