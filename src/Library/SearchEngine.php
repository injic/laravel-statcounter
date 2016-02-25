<?php

namespace Injic\LaravelStatcounter\Library;

use MyCLabs\Enum\Enum;

/**
 * Combine keywords by search engine host, name, or both.
 *
 * <ul>
 * <li>HOST</li>
 * <li>NAME</li>
 * <li>TOGETHER</li>
 * <ul>
 *
 * @method static SearchEngine HOST()
 * @method static SearchEngine NAME()
 * @method static SearchEngine TOGETHER()
 */
class SearchEngine extends Enum
{
    const HOST = 'search_engine_host';
    const NAME = 'search_engine_name';
    const TOGETHER = 'together';
}