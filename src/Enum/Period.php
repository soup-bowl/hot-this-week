<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

declare(strict_types=1);

namespace HotThisWeek\Enum;

/**
 * Represents the last.fm API textual time periods.
 */
abstract class Period
{
    public const WEEK     = "7day";
    public const MONTH    = "1month";
	public const QUARTER  = "3month";
	public const HALFYEAR = "6month";
	public const YEAR     = "12month";
	public const ALL      = "overall";
}
