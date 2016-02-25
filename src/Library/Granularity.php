<?php

namespace Injic\LaravelStatcounter\Library;

use MyCLabs\Enum\Enum;

/**
 * Specify date range granularity.
 *
 * <ul>
 * <li>HOURLY</li>
 * <li>DAILY</li>
 * <li>WEEKLY</li>
 * <li>MONTHLY</li>
 * <li>QUARTERLY</li>
 * <li>YEARLY</li>
 * <ul>
 *
 * @method static Granularity HOURLY()
 * @method static Granularity DAILY()
 * @method static Granularity WEEKLY()
 * @method static Granularity MONTHLY()
 * @method static Granularity QUARTERLY()
 * @method static Granularity YEARLY()
 */
class Granularity extends Enum
{
    const HOURLY = 0;
    const DAILY = 1;
    const WEEKLY = 2;
    const MONTHLY = 3;
    const QUARTERLY = 4;
    const YEARLY = 5;
}