<?php

namespace Injic\LaravelStatcounter\Library;

use MyCLabs\Enum\Enum;

/**
 * Specify device type.
 *
 * <ul>
 * <li>ALL</li>
 * <li>DESKTOP</li>
 * <li>MOBILE</li>
 * <ul>
 *
 * @method static Device ALL()
 * @method static Device DESKTOP()
 * @method static Device MOBILE()
 */
class Device extends Enum
{
    const ALL = 'all';
    const DESKTOP = 'desktop';
    const MOBILE = 'mobile';
}