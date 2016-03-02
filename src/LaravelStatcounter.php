<?php namespace Injic\LaravelStatcounter;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

use Injic\LaravelStatcounter\Library\Device;
use Injic\LaravelStatcounter\Library\Granularity;
use Injic\LaravelStatcounter\Library\PublicStats;
use Injic\LaravelStatcounter\Library\SearchEngine;

class LaravelStatcounter
{
    // <editor-fold desc="CONSTANTS">
    //======================================================================
    // CONSTANTS
    //======================================================================

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
        'vn' => '3',  // Version Number
        'f' => null, // Format
        's' => null, // Stats
        'pi' => null, // Project ID
        'u' => null, // Username
        'wt' => null, // Website Title
        'wu' => null, // Website URL
        'ls' => null, // Log Size
        't' => null, // Time of Execution
        'tz' => null, // Time Zone
        'ps' => null, // Public Stats
        'n' => null, // Number of Results (1000 is default)
        'c' => null, // Chop URL
        'ct' => null, // Count Type
        'g' => null, // Granularity
        'sh' => null, // Start Hour
        'sd' => null, // Start Day
        'sm' => null, // Start Month
        'sy' => null, // Start Year
        'sw' => null, // Start Week
        'sq' => null, // Start Quarter
        'eh' => null, // End Hour
        'ed' => null, // End Day
        'em' => null, // End Month
        'ey' => null, // End Year
        'ew' => null, // End Week
        'eq' => null, // End Quarter
        'e' => null, // Exclude External
        'ese' => null, // Exclude Search Engines
        'eek' => null, // Exclude Encrypted Keywords
        'ck' => null, // Combine Keywords
        'gbd' => null, // Group By Domain
        'de' => null, // Device
        'ip' => null,  // IP Address
        'ipl' => null  // IP Label
    ];

    // </editor-fold>

    // <editor-fold desc="FIELDS">
    //======================================================================
    // FIELDS
    //======================================================================

    /**
     * A map of project IDs to project names.
     *
     * @var array
     */
    protected $projects;

    /**
     * A full API query URL.
     *
     * @var string
     */
    protected $url;

    /**
     * The selected query to be called (stats, select_project, etc).
     *
     * @var string
     */
    public $query;

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
     * Config array for laravel-statcounter
     *
     * @var array
     */
    protected $config = [];

    /**
     * Any extra curl options that are needed
     *
     * @var array
     */
    protected $curlopts = [];

    // </editor-fold>

    // <editor-fold desc="CONSTRUCTOR">
    //======================================================================
    // CONSTRUCTOR
    //======================================================================

    /**
     * Create new stat instance.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config  = $config;
        $this->projects = array_flip($this->config['projects']);
        $this->params = self::$API_DEFAULT_PARAMS;
    }

    // </editor-fold>

    // <editor-fold desc="TEMPLATE METHODS">
    //======================================================================
    // TEMPLATE METHODS
    //======================================================================

    /**
     * Displays the StatCounter tracker for the project.
     *
     * @param bool $isHttps
     * @param bool $isVisible
     * @param string $websiteTitle
     * @return string
     */
    public function tracker($isHttps = false, $isVisible = false, $websiteTitle = null)
    {
        if (is_null($websiteTitle)) {
            $websiteTitle = $this->fromConfig('default');
        }

        $pid = $this->projectFromConfig($websiteTitle);
        $security = $this->fromConfig('security-codes.'.$websiteTitle);

        return view('statcounter::tracker', [
            'pid' => $pid,
            'security' => $security,
            'isHttps' => $isHttps,
            'isVisible' => $isVisible
        ]);
    }

    /**
     * Generate the public stats url.
     *
     * @param string $websiteTitle
     * @return string
     */
    public function public($websiteTitle =  null)
    {
        return sprintf('http://statcounter.com/p%s?guest=1', $this->projectFromConfig($websiteTitle));
    }

    // </editor-fold>

    // <editor-fold desc="BASE QUERY METHODS">
    //======================================================================
    // BASE QUERY METHODS
    //======================================================================

    /**
     * Initializes a 'Stats' query.
     *
     * @param string $type
     */
    protected function initStats($type)
    {
        $this->query = 'stats';
        $this->params['s'] = $type;

        $this->usesUsername();
        $this->usesProject();
        $this->usesTime();
    }

    /**
     * Start a 'summary' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function summary()
    {
        $this->initStats('summary');

        return $this;
    }

    /**
     * Start a 'recent visitor' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
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
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
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
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function entryPages()
    {
        $this->initStats('entry');

        return $this;
    }

    /**
     * Start an 'exit pages' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
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
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
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
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
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
     * @param Device $device
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function browsers(Device $device = null)
    {
        if (is_null($device)) $device = Device::ALL();

        $this->initStats('browsers');

        $this->params['de'] = $device->getValue();

        return $this;
    }

    /**
     * Start an 'operating systems' query.
     *
     * @param Device $device
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function operatingSystems(Device $device = null)
    {
        if (is_null($device)) $device = Device::ALL();

        $this->initStats('os');

        $this->params['de'] = $device->getValue();

        return $this;
    }

    /**
     * Start a 'search engines' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function searchEngines()
    {
        $this->initStats('search_engine');

        return $this;
    }

    /**
     * Start a 'country' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function country()
    {
        $this->initStats('country');

        return $this;
    }

    /**
     * Start an 'recent pageload activity' query.
     *
     * @param Device $device
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function recentPageload(Device $device = null)
    {
        if (is_null($device)) $device = Device::ALL();

        $this->initStats('pageload');

        $this->params['de'] = $device->getValue();

        return $this;
    }

    /**
     * Start an 'exit link activity' query.
     *
     * @param Device $device
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function exitLink(Device $device = null)
    {
        if (is_null($device)) $device = Device::ALL();

        $this->initStats('exit-link-activity');

        $this->params['de'] = $device->getValue();

        return $this;
    }

    /**
     * Start an 'download link activity' query.
     *
     * @param Device $device
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function downloadLink(Device $device = null)
    {
        if (is_null($device)) $device = Device::ALL();

        $this->initStats('download-link-activity');

        $this->params['de'] = $device->getValue();

        return $this;
    }

    /**
     * Start a 'visit length' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function visitLength()
    {
        $this->initStats('visit_length');

        return $this;
    }

    /**
     * Start a 'returning visits' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function returningVisits()
    {
        $this->initStats('returning_visits');

        return $this;
    }

    /**
     * Start a 'keyword analysis' query.
     *
     * @param SearchEngine $combineKeywords
     * @param boolean $excludeEncryptedKeywords
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function keywordAnalysis(SearchEngine $combineKeywords = null, $excludeEncryptedKeywords = false)
    {
        if (is_null($combineKeywords)) $combineKeywords = SearchEngine::HOST();

        $this->initStats('keyword_analysis');

        $this->params['ck'] = $combineKeywords->getValue();

        if ($excludeEncryptedKeywords) $this->params['eek'] = '1';

        return $this;
    }

    /**
     * Start a 'lookup visitor' query.
     *
     * @param string $ipAddress
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function lookupVisitor($ipAddress)
    {
        $this->initStats('lookup_visitor');

        $this->params['ip'] = $ipAddress;

        return $this;
    }

    /**
     * Start a 'mobile devices' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function mobileDevices()
    {
        $this->initStats('mobile_device');

        return $this;
    }

    /**
     * Start a 'platform' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function platform()
    {
        $this->initStats('platform');

        return $this;
    }

    /**
     * Start a 'export' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function export()
    {
        $this->initStats('export');

        return $this;
    }

    /**
     * Start a 'incoming traffic' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function incomingTraffic()
    {
        $this->initStats('incoming');

        return $this;
    }

    /**
     * Start a 'language' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function language()
    {
        $this->initStats('language');

        return $this;
    }

    /**
     * Start an 'add project' query.
     *
     * @param string $websiteTitle
     * @param string $websiteUrl
     * @param string $timezone
     * @param PublicStats $publicStats
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function addProject($websiteTitle, $websiteUrl, $timezone, PublicStats $publicStats = null)
    {
        if (is_null($publicStats)) $publicStats = PublicStats::NONE();

        $this->query = 'add_project';

        $this->params['wt'] = $websiteTitle;
        $this->params['wu'] = $websiteUrl;
        $this->params['tz'] = $timezone;
        $this->params['ps'] = $publicStats->getValue();

        $this->usesUsername();
        $this->usesTime();

        return $this;
    }

    /**
     * Start an 'update project' query.
     *
     * @param PublicStats $publicStats
     * @param string $websiteTitle
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function updateProject(PublicStats $publicStats, $websiteTitle = null)
    {
        $this->query = 'update_project';

        $this->params['ps'] = $publicStats->getValue();
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        $this->usesUsername();
        $this->usesTime();


        return $this;
    }

    /**
     * Start an 'update logsize' query.
     *
     * @param integer $logSize
     * @param string $websiteTitle
     * @return LaravelStatcounter|static
     */
    public function updateLogsize($logSize, $websiteTitle = null)
    {
        $this->query = 'update_logsize';

        $this->params['ls'] = $logSize;
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        $this->usesUsername();
        $this->usesTime();


        return $this;
    }

    /**
     * Start an 'account logsizes' query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function accountLogsizes()
    {
        $this->query = 'account_logsizes';

        $this->usesUsername();
        $this->usesTime();

        return $this;
    }

    /**
     * Start an 'user details' query.
     *
     * @param string $username
     * @param string $password
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function userDetails($username = null, $password = null)
    {
        $this->query = 'user_details';

        if (!is_null($password)) {
            $this->config['api-password'] = $password;
        }

        $this->usesUsername($username);
        $this->usesTime();

        return $this;
    }

    /**
     * Start an 'user projects' query.
     *
     * @param string $username
     * @param string $password
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function userProjects($username = null, $password = null)
    {
        $this->query = 'user_projects';

        if (!is_null($password)) {
            $this->config['api-password'] = $password;
        }

        $this->usesUsername($username);
        $this->usesTime();

        return $this;
    }

    /**
     * Start a 'select project' query.
     *
     * @param string $websiteTitle
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function projectDetails($websiteTitle = null)
    {
        $this->query = 'select_project';

        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        $this->usesUsername();
        $this->usesTime();

        return $this;
    }

    /**
     * Start a 'create ip label' query.
     *
     * @param string $ip
     * @param string $label
     * @param string $websiteTitle
     * @return LaravelStatcounter|static
     */
    public function createIpLabel($ip, $label, $websiteTitle = null)
    {
        $this->query = 'create_ip_label';

        $this->params['ip'] = $ip;
        $this->params['ipl'] = $label;
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        $this->usesUsername();
        $this->usesTime();

        return $this;
    }

    /**
     * Start a 'delete ip label' query.
     *
     * @param string $label
     * @param string $websiteTitle
     * @return LaravelStatcounter|static
     */
    public function deleteIpLabel($label, $websiteTitle = null)
    {
        $this->query = 'delete_ip_label';

        $this->params['ipl'] = $label;
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        $this->usesUsername();
        $this->usesTime();

        return $this;
    }

    // </editor-fold>

    // <editor-fold desc="STATCOUNTER QUERY CONSTRAINTS">
    //======================================================================
    // STATCOUNTER QUERY CONSTRAINTS
    //======================================================================

    /**
     * The StatCounter API allows for the retrieval of each statistic type
     * (Popular Pages, Recent Visitors, etc.) within a set time period. To select
     * your required period, just add the necessary details to your API call, as
     * detailed for each appropriate time period below. If no time period is
     * specified, the API will search all available records.
     *
     * @param Granularity $granularity
     * @param integer|string|Carbon $start
     * @param integer|string|Carbon $end
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function setRange(Granularity $granularity, $start, $end)
    {
        if (!($start instanceof Carbon)) {
            if (is_numeric($start)) {
                $start = Carbon::createFromTimeStamp($start);
            } else {
                $start = new Carbon($start);
            }
        }
        if (!($end instanceof Carbon)) {
            if (is_numeric($end)) {
                $end = Carbon::createFromTimeStamp($end);
            } else {
                $end = new Carbon($end);
            }
        }

        switch ($granularity->getValue()) {
            case Granularity::HOURLY:
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
            case Granularity::DAILY:
                $this->params['g'] = 'daily';
                $this->params['sd'] = $start->day;
                $this->params['sm'] = $start->month;
                $this->params['sy'] = $start->year;
                $this->params['ed'] = $end->day;
                $this->params['em'] = $end->month;
                $this->params['ey'] = $end->year;
                break;
            case Granularity::WEEKLY:
                $this->params['g'] = 'weekly';
                $this->params['sw'] = $start->weekOfYear;
                $this->params['sy'] = $start->year;
                $this->params['ew'] = $end->weekOfYear;
                $this->params['ey'] = $end->year;
                break;
            case Granularity::MONTHLY:
                $this->params['g'] = 'monthly';
                $this->params['sm'] = $start->month;
                $this->params['sy'] = $start->year;
                $this->params['em'] = $end->month;
                $this->params['ey'] = $end->year;
                break;
            case Granularity::QUARTERLY:
                $this->params['g'] = 'quarterly';
                $this->params['sq'] = $start->quarter;
                $this->params['sy'] = $start->year;
                $this->params['eq'] = $end->quarter;
                $this->params['ey'] = $end->year;
                break;
            case Granularity::YEARLY:
                $this->params['g'] = 'yearly';
                $this->params['sy'] = $start->year;
                $this->params['ey'] = $end->year;
                break;
        }

        return $this;
    }

    /**
     * Set the number of results to return. Differs from take and limit in that
     * it sends this straight to the API.
     *
     * @param int $num
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function numberOfResults($num)
    {
        if (is_numeric($num)) {
            $this->params['n'] = $num;
        } else {
            throw new InvalidArgumentException('Non-integer given');
        }

        return $this;
    }

    /**
     * Sets a project to the query.
     *
     * @param string $websiteTitle
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function project($websiteTitle)
    {
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);

        return $this;
    }

    /**
     * Overrides the username for this pageload.
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->params['u'] = $username;
    }

    /**
     * Overrides the password for this pageload.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->config['api-password'] = $password;
    }

    // </editor-fold>

    // <editor-fold desc="RESULT CONSTRAINTS">
    //======================================================================
    // RESULT CONSTRAINTS
    //======================================================================

    /**
     * Set the "offset" value of the query.
     *
     * @param  int $value
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int $value
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function limit($value)
    {
        if ($value > 0) $this->limit = $value;

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int $value
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public function value($column)
    {
        $result = (array)$this->first(array($column));

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array $columns
     * @return mixed|static
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? reset($results) : null;
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
     * @param array $columns
     * @return int
     */
    public function count($columns = ['*'])
    {
        $result = count($this->get($columns));

        return $result;
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

        foreach ($array as $element) {
            foreach ($element as $key => $value) {
                if (array_search($key, $columns) === false) $columns[] = $key;
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

        foreach ($array as $element) {
            foreach ($element as $key => $value) {
                if (array_search($key, $columns) === false) $columns[] = $key;
            }
        }

        return $columns;
    }

    // </editor-fold>

    // <editor-fold desc="TERMINATING METHODS">
    //======================================================================
    // TERMINATING METHODS
    //======================================================================

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return array
     */
    public function get($columns = ['*'])
    {
        $results = $this->getFresh($columns);

        $results = array_slice($results, $this->offset, $this->limit, true);

        return $results;
    }

    /**
     * Compiles the URL with the current parameters.
     *
     * @param bool $isFull whether to include sha1 param
     * @return string
     */
    public function toUrl($isFull = true)
    {
        if (!is_null($this->url)) return $this->url;

        $tmpParams = ($isFull ? $this->params : array_except($this->params, ['t']));

        $encodedUrl = '?' . http_build_query($tmpParams, null, '&');

        $url = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $encodedUrl);

        return self::$API_URL . $this->query . ($isFull ? $url . '&sha1=' .
            sha1($url . $this->config['api-password']) : $url);
    }

    /**
     * Execute the query as a fresh call to the API.
     *
     * @param array $columns
     * @return array
     */
    public function getFresh($columns = ['*'])
    {
        if (is_null($this->columns)) $this->columns = $columns;

        return $this->runQuery(self::toUrl());
    }

    /**
     * Run the query against the API.
     *
     * @param string $url
     * @throws RuntimeException
     * @return array
     */
    protected function runQuery($url)
    {
        $ch = curl_init();

        $optArray = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,  // return content
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true,  // follow redirects
            CURLOPT_ENCODING => "",    // handle compressed
            CURLOPT_AUTOREFERER => true,  // set referer on redirect
            CURLOPT_MAXREDIRS => 10,    // stop after 10 redirects
            CURLOPT_FAILONERROR => true   // fail if HTTP returns error
        ];
        $optArray = array_replace($optArray, $this->curlopts);
        curl_setopt_array($ch, $optArray);

        // send query and store response
        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException('Error connecting to API: ' . curl_error($ch));
        }

        $result = json_decode($response);

        if ($result === null) {
            throw new RuntimeException("Error decoding API response: " . $response);
        }

        if ($result->{"@attributes"}->status === 'fail' || $result->{"@attributes"}->status != 'ok') {
            $messages = '';
            foreach ($result->error as $error) {
                $messages .= $error->description . "\n";
            }
            throw new RuntimeException("StatCounter API Error: " . $messages);
        }

        if (property_exists($result, 'sc_data')) {
            $data = $this->trimColumns($result->sc_data);
        } else {
            $data = $result;
        }

        return $data;
    }

    // </editor-fold>

    // <editor-fold desc="PAGINATION METHODS">
    //======================================================================
    // PAGINATION METHODS
    //======================================================================

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = $this->getCountForPagination($columns);

        $results = $this->forPage($page, $perPage)->get($columns);

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page')
    {
        $page = Paginator::resolveCurrentPage($pageName);

        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return new Paginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the count of the total records for pagination.
     *
     * @param array $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        $this->backupFieldsForCount();

        // Does a total count, without limit or offset
        $total = $this->count($columns);

        $this->restoreFieldsForCount();

        return $total;
    }

    /**
     * Backup certain fields for a pagination count.
     *
     * @return void
     */
    protected function backupFieldsForCount()
    {
        foreach (array('params', 'limit', 'offset', 'columns', 'url') as $field) {
            $this->backups[$field] = $this->{$field};

            $this->{$field} = null;
        }

        $this->params = $this->backups['params'];
        $this->columns = ['*'];
        $this->url = $this->backups['url'];
    }

    /**
     * Restore certain fields for a pagination count.
     *
     * @return void
     */
    protected function restoreFieldsForCount()
    {
        foreach (array('params', 'limit', 'offset', 'columns', 'url') as $field) {
            $this->{$field} = $this->backups[$field];
        }

        $this->backups = array();
    }

    // </editor-fold>

    // <editor-fold desc="RESULT HANDLING METHODS">
    //======================================================================
    // RESULT HANDLING METHODS
    //======================================================================

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();

        while (count($results) > 0) {
            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if (call_user_func($callback, $results) === false) {
                return false;
            }

            $page++;

            $results = $this->forPage($page, $count)->get();
        }

        return true;
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function each(callable $callback, $count = 1000)
    {
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get(is_null($key) ? [$column] : [$column, $key]);

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        return Arr::pluck($results, $column, $key);
    }

    /**
     * Alias for the "pluck" method.
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     *
     * @deprecated since version 5.2. Use the "pluck" method directly.
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key);
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return implode($glue, $this->pluck($column));
    }

    // </editor-fold>

    // <editor-fold desc="MISC METHODS">
    //======================================================================
    // MISC METHODS
    //======================================================================

    /**
     * Get a new instance of the stat query.
     *
     * @return \Injic\LaravelStatcounter\LaravelStatcounter
     */
    public function newQuery()
    {
        return new self($this->config);
    }

    /**
     * Create a raw query to the API.
     *
     * @param  mixed $value
     * @return \Injic\LaravelStatcounter\LaravelStatcounter|static
     */
    public function raw($value)
    {
        if (is_string($value)) {
            $params = [];

            $url = parse_url($value);
            if (array_key_exists('query', $url)) {
                parse_str($url['query'], $params);
            }

            // If fully qualify URL with SHA-1 is passed, use it
            if (array_key_exists('scheme', $url)
                && array_key_exists('host', $url)
                && array_key_exists('query', $url)
                && array_key_exists('sha1', $params)
            ) {
                $this->url = $value;
                return $this;
            }

            $this->params = array_merge($this->params, $params);

            if (array_key_exists('path', $url)) {
                $this->query = ($url['path'][0] == '/' ? substr($url['path'], 1) : $url['path']);
            } else {
                $this->query = 'stats/';
            }
        } else if (is_array($value)) {
            if (array_key_exists('query', $value)) {
                $this->query = $value['query'] . (preg_match('/\\/$/',$value['query']) ? '' : '/');
                unset($value['query']);
            } else {
                $this->query = 'stats/';
            }

            $this->params = array_merge($this->params, $value);
        } else {
            throw new InvalidArgumentException('Invalid raw value submitted');
        }

        return $this;
    }

    /**
     * Set any extra curl options that are needed.
     *
     * @param $options
     */
    public function curlopts($options)
    {
        $this->curlopts = array_merge($this->curlopts, $options);
    }

    // </editor-fold>

    // <editor-fold desc="INTERNAL METHODS">
    //======================================================================
    // INTERNAL METHODS
    //======================================================================

    /**
     * Trims result array by specified columns.
     *
     * @param mixed $array
     * @return array
     */
    protected function trimColumns($array)
    {
        if (array_search('*', $this->columns) !== false) return $array;

        foreach ($array as &$subarray) {
            foreach ($subarray as $key => $value) {
                if (array_search($key, $this->columns) === false) {
                    unset($subarray->{$key});
                }
            }
        }

        return $array;
    }

    /**
     * Adds username to the parameters.
     *
     * @param string $username
     */
    protected function usesUsername($username = null)
    {
        if (is_null($username)) {
            $username = $this->fromConfig('username');
        }

        $this->params['u'] = $username;
    }

    /**
     * Adds project to the parameters.
     *
     * @param string $websiteTitle
     */
    protected function usesProject($websiteTitle = null)
    {
        $this->params['pi'] = $this->projectFromConfig($websiteTitle);
    }

    /**
     * Adds project to the parameters.
     */
    protected function usesTime()
    {
        $this->params['t'] = time();
    }

    /**
     * Retrieves project id from config, whether default or specified
     *
     * @param string $websiteTitle
     * @return string
     */
    protected function projectFromConfig($websiteTitle = null)
    {
        if (is_null($websiteTitle)) {
            $websiteTitle = $this->fromConfig('default');
        }

        return $this->fromConfig('projects.' . $websiteTitle);
    }

    /**
     * Retrieves key from config
     *
     * @param $key
     * @return string
     */
    protected function fromConfig($key)
    {
        if (array_has($this->config, $key)) {
            return array_get($this->config, $key);
        } else {
            throw new RuntimeException("Could not find $key in config");
        }
    }

    // </editor-fold>

}