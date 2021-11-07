<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

declare(strict_types=1);

namespace HotThisWeek;

use HotThisWeek\Enum\Period;
use HotThisWeek\Enum\SearchFrame;
use HotThisWeek\Object\Artist;
use Dandelionmood\LastFm\LastFm;
use Exception;

libxml_use_internal_errors(true);

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 */
class LastfmAPI
{
	protected $key;
	protected $secret;

	/**
	 * Constructor.
	 *
	 * @param string $key    last.fm API key.
	 * @param string $secret last.fm API secret if authentication routes are needed.
	 */
	public function __construct(string $key, string $secret = '')
	{
		$this->key    = $key;
		$this->secret = $secret;
	}

	/**
	 * Grabs the top artists from the last.fm API.
	 *
	 * @param string  $username last.fm account to look-up.
	 * @param string  $period   last.fm-recognised period. Use the Lastfm Period enum.
	 * @param integer $limit    Amount to return. Default is 5.
	 * @throws Exception if an error occurred during API communication.
	 * @return Artist[]
	 */
	public function getTopFromLastfm(string $username, string $period = Period::WEEK, int $limit = 5): ?array
	{
		$sf      = 'user_get' . SearchFrame::TOPARTISTS;
		$lfm     = new LastFm($this->key, $this->secret);
		try {
			$lfmTops = $lfm->$sf([
				'user'   => $username,
				'period' => $period,
				'limit'  => $limit,
			]);
		} catch (Exception $e) {
			throw $e;
		}

		$top = [];
		foreach ($lfmTops->topartists->artist as $artist) {
			$obj = new Artist();
			$obj->setName($artist->name);
			$obj->setPicture($this->getArtistPicture($artist->url));
			$obj->setListenCount(intval($artist->playcount));
			$top[] = $obj;
		}

		return $top;
	}

	/**
	 * Scrapes the last.fm site for the artist image.
	 *
	 * @param string $url The artist page URL.
	 * @return string Image URL, or blank if none was found.
	 */
	public function getArtistPicture(string $url): string
	{
		$artistHTML = $this->remoteGetContent($url);

		$dom = new \DOMDocument();
		$dom->loadHTML($artistHTML);
		$xpath = new \DOMXPath($dom);

		$imgSrc = "";
		foreach ($xpath->query('//div[contains(@class,"header-new-background-image")]') as $item) {
			$imgSrc = $item->getAttribute('content');
			continue;
		}

		return $imgSrc;
	}

	/**
	 * Makes a CURL request to grab data from the specified URL.
	 *
	 * @param string $url URL to make a request to.
	 * @return string HTML data.
	 */
	public function remoteGetContent(string $url): string
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
}
