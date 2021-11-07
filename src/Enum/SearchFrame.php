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
 * Represents the last.fm API search types.
 */
abstract class SearchFrame
{
    public const TOPARTISTS = "TopArtists";
}
