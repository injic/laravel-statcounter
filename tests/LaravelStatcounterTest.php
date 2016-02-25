<?php

use Injic\LaravelStatcounter\LaravelStatcounter;
use Injic\LaravelStatcounter\Library\Device;
use Injic\LaravelStatcounter\Library\PublicStats;
use Injic\LaravelStatcounter\Library\SearchEngine;

class LaravelStatcounterTest extends PHPUnit_Framework_TestCase
{
    const CONFIG = [
        'username' => 'DaFlyinJ',
        'api-password' => 'xemYzgqT',
        'default' => 'mmc',
        'projects' => [
            'mmc' => '9917949',
            'test' => '1234567'
        ],
        'security-codes' => [
            'mmc' => 'd420b3cd',
            'test' => '1234567'
        ],
    ];

    // <editor-fold desc="INTERNAL METHODS">
    //======================================================================
    // INTERNAL METHODS
    //======================================================================

    /**
     * %1$s - username
     * %2$s - time
     * %3$s - project code
     * ends with sha
     *
     * @param $baseurl
     * @param $params
     * @param bool $format
     * @param null $password
     * @return string
     * @internal param $url
     */
    protected function formatUrl($baseurl, $params, $format = true, $password = null)
    {
        if ($format) {
            $furl = sprintf($params, self::CONFIG['username'], time(), self::CONFIG['projects']['mmc']);
        } else {
            $furl = $params;
        }

        return $baseurl . $furl . '&sha1=' . sha1($furl . ($password ?: self::CONFIG['api-password']));
    }

    // </editor-fold>

    // <editor-fold desc="BASE QUERY METHODS">
    //======================================================================
    // BASE QUERY METHODS
    //======================================================================

    function testSummaryGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->summary()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRecentVisitorsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->recentVisitors()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=visitor&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testPopularPagesGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->popularPages()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=popular&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testPopularPagesGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->popularPages(false, 'visitor')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=popular&pi=%3$s&u=%1$s&t=%2$s&c=0&ct=visitor'), $url);
    }

    function testEntryPagesGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->entryPages()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=entry&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testExitPagesGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->exitPages()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=exit&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testCameFromGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->cameFrom()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=camefrom&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testCameFromGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->cameFrom(false, true, true)->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=camefrom&pi=%3$s&u=%1$s&t=%2$s&e=0&ese=1&gbd=1'), $url);
    }

    function testRecentKeywordsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->recentKeywords()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=keyword-activity&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRecentKeywordsGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->recentKeywords(true, false)->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=keyword-activity&pi=%3$s&u=%1$s&t=%2$s&e=0&eek=1'), $url);
    }

    function testBrowsersGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->browsers()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=browsers&pi=%3$s&u=%1$s&t=%2$s&de=all'), $url);
    }

    function testBrowsersGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->browsers(Device::DESKTOP())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=browsers&pi=%3$s&u=%1$s&t=%2$s&de=desktop'), $url);
    }

    function testOperatingSystemsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->operatingSystems()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=os&pi=%3$s&u=%1$s&t=%2$s&de=all'), $url);
    }

    function testOperatingSystemsGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->operatingSystems(Device::MOBILE())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=os&pi=%3$s&u=%1$s&t=%2$s&de=mobile'), $url);
    }

    function testSearchEnginesGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->searchEngines()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=search_engine&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testCountryGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->country()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=country&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRecentPageloadGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->recentPageload()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=pageload&pi=%3$s&u=%1$s&t=%2$s&de=all'), $url);
    }

    function testRecentPageloadGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->recentPageload(Device::DESKTOP())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=pageload&pi=%3$s&u=%1$s&t=%2$s&de=desktop'), $url);
    }

    function testExitLinkGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->exitLink()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=exit-link-activity&pi=%3$s&u=%1$s&t=%2$s&de=all'), $url);
    }

    function testExitLinkloadGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->exitLink(Device::DESKTOP())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=exit-link-activity&pi=%3$s&u=%1$s&t=%2$s&de=desktop'), $url);
    }

    function testDownloadLinkGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->downloadLink()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=download-link-activity&pi=%3$s&u=%1$s&t=%2$s&de=all'), $url);
    }

    function testDownloadLinkGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->downloadLink(Device::DESKTOP())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=download-link-activity&pi=%3$s&u=%1$s&t=%2$s&de=desktop'), $url);
    }

    function testVisitLengthGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->visitLength()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=visit_length&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testReturningVisitsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->returningVisits()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=returning_visits&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testKeywordAnalysisGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->keywordAnalysis()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=keyword_analysis&pi=%3$s&u=%1$s&t=%2$s&ck=search_engine_host'), $url);
    }

    function testKeywordAnalysisGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->keywordAnalysis(SearchEngine::NAME(), true)->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=keyword_analysis&pi=%3$s&u=%1$s&t=%2$s&eek=1&ck=search_engine_name'), $url);
    }

    function testLookupVisitorGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->lookupVisitor('192.168.1.1')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=lookup_visitor&pi=%3$s&u=%1$s&t=%2$s&ip=192.168.1.1'), $url);
    }

    function testAddProjectGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->addProject('Archon Crafters', 'http://archoncrafters.com/', 'America/Chicago')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/add_project/','?vn=3&u='.self::CONFIG['username'].'&wt=Archon+Crafters&wu=http%3A%2F%2Farchoncrafters.com%2F&t='.time().'&tz=America%2FChicago&ps=0', false), $url);
    }

    function testAddProjectGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->addProject('Archon Crafters', 'http://archoncrafters.com/', 'America/Chicago', PublicStats::ALL())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/add_project/','?vn=3&u='.self::CONFIG['username'].'&wt=Archon+Crafters&wu=http%3A%2F%2Farchoncrafters.com%2F&t='.time().'&tz=America%2FChicago&ps=1', false), $url);
    }

    function testUpdateProjectGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->updateProject(PublicStats::ALL())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/update_project/','?vn=3&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time().'&ps=1', false), $url);
    }

    function testUpdateProjectGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->updateProject(PublicStats::ALL(), 'test')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/update_project/','?vn=3&pi='.self::CONFIG['projects']['test'].'&u='.self::CONFIG['username'].'&t='.time().'&ps=1', false), $url);
    }

    function testUpdateLogsizeGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->updateLogsize(5000)->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/update_logsize/','?vn=3&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&ls=5000&t='.time(), false), $url);
    }

    function testUpdateLogsizeGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->updateLogsize(6000, 'test')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/update_logsize/','?vn=3&pi='.self::CONFIG['projects']['test'].'&u='.self::CONFIG['username'].'&ls=6000&t='.time(), false), $url);
    }

    function testAccountLogsizesGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->accountLogsizes()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/account_logsizes/','?vn=3&u='.self::CONFIG['username'].'&t='.time(), false), $url);
    }

    function testUserDetailsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->userDetails()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/user_details/','?vn=3&u='.self::CONFIG['username'].'&t='.time(), false), $url);
    }

    function testUserDetailsGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->userDetails('banana','Pa55w0rd')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/user_details/','?vn=3&u=banana&t='.time(), false, 'Pa55w0rd'), $url);
    }

    function testUserProjectsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->userProjects()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/user_projects/','?vn=3&u='.self::CONFIG['username'].'&t='.time(), false), $url);
    }

    function testUserProjectsGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->userProjects('banana','Pa55w0rd')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/user_projects/','?vn=3&u=banana&t='.time(), false, 'Pa55w0rd'), $url);
    }

    function testProjectDetailsGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->projectDetails()->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/select_project/','?vn=3&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time(), false), $url);
    }

    function testProjectDetailsGetWithOptions()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->projectDetails('test')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/select_project/','?vn=3&pi='.self::CONFIG['projects']['test'].'&u='.self::CONFIG['username'].'&t='.time(), false), $url);
    }

    function testCreateIpLabelGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->createIpLabel('192.168.1.1','Home')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/create_ip_label/','?vn=3&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time().'&ip=192.168.1.1&ipl=Home', false), $url);
    }

    function testDeleteIpLabelGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->deleteIpLabel('Home')->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/delete_ip_label/','?vn=3&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time().'&ipl=Home', false), $url);
    }

    // </editor-fold>

    // <editor-fold desc="MISC METHODS">
    //======================================================================
    // MISC METHODS
    //======================================================================

    function testRawGet()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->raw('https://api.statcounter.com/stats/?vn=3&s=summary&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRawGetWithSha1()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->raw($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'))->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRawGetWithParamsOnly()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->raw('?vn=3&s=summary&pi='.self::CONFIG['projects']['mmc'].'&u='.self::CONFIG['username'].'&t='.time())->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    function testRawGetWithArray()
    {
        $statcounter = new LaravelStatcounter(self::CONFIG);

        $url = $statcounter->raw([
            'query' => 'stats',
            's' => 'summary',
            'pi' => self::CONFIG['projects']['mmc'],
            'u' => self::CONFIG['username'],
            't' => time()
        ])->toUrl();

        $this->assertNotEmpty($url);
        $this->assertEquals($this->formatUrl('https://api.statcounter.com/stats/','?vn=3&s=summary&pi=%3$s&u=%1$s&t=%2$s'), $url);
    }

    // </editor-fold>
}