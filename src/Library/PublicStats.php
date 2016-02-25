<?php

namespace Injic\LaravelStatcounter\Library;

use MyCLabs\Enum\Enum;

/**
 * Specify the level of public access.
 *
 * <ul>
 * <li>NONE</li>
 * <li>ALL</li>
 * <li>SUMMARY</li>
 * <ul>
 *
 * @method static PublicStats NONE()
 * @method static PublicStats ALL()
 * @method static PublicStats SUMMARY()
 */
class PublicStats extends Enum
{
    const NONE = 0;
    const ALL = 1;
    const SUMMARY = 2;
}