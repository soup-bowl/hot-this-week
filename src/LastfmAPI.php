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
use Dandelionmood\LastFm\LastFm;
use Tzsk\Collage\MakeCollage;

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
	 * @param string  $path        Location to the configuraton file.
	 * @param boolean $displayOnly Determines whether the tweet action is concluded.
	 * @param boolean $silentMode  Don't output updates to stdout.
	 */
	public function __construct(string $key, string $secret)
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
	 * @return array|null
	 */
	public function getTopFromLastfm(string $username, string $period = Period::WEEK, int $limit = 5): ?array
	{
		$sf       = 'user_get' . SearchFrame::TOPARTISTS;
		$lfm      = new LastFm($this->key, $this->secret);
		$lfm_tops = $lfm->$sf([
			'user'   => $username,
			'period' => $period,
			'limit'  => $limit,
		]);

		$top = [];
		foreach ($lfm_tops->topartists->artist as $artist) {
			$top[] = [
				'artist'  => $artist->name,
				'picture' => $this->getArtistPicture($artist->url),
				'count'   => $artist->playcount
			];
		}

		if (count($top) >= 5) {
			return $top;
		} else {
			return null;
		}
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
	 * Generates a 1 left, 4 right collage image based on given image sources.
	 *
	 * @param string[] $top5       last.fm response.
	 * @param string   $exportPath Optional ath to override the export to.
	 * @return string Location of generated image on filesystem.
	 */
	public function generateCollage(array $top5, string $exportPath = ''): string
	{
		$imgFile = ( (empty($exportPath)) ? sys_get_temp_dir() : $exportPath ) . '/sbimg_' . uniqid() . '.png';
		$imgarr  = [];
		foreach ($top5 as &$item) {
			$imgarr[]       = $item['picture'];
			$item['artist'] = ( strlen($item['artist']) > 19 )
				? substr($item['artist'], 0, 16) . "..." : $item['artist'];
		}

		$forcol = $imgarr;
		array_shift($forcol);

		if (file_exists($imgFile)) {
			unlink($imgFile);
		}

		$collage    = new MakeCollage();
		$firstImage = $collage->make(400, 400)->from($forcol)->encode('png');

		$collage->make(1200, 675)
			->from([ $imgarr[0], $firstImage ], function ($a) {
				$a->vertical();
			})
			->text($top5[0]['artist'], 22, 662, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(54);
			})
			->text($top5[0]['artist'], 20, 660, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(54)->color('#FFF');
			})
			->text($top5[1]['artist'], 621, 321, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28);
			})
			->text($top5[1]['artist'], 620, 320, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28)->color('#FFF');
			})
			->text($top5[2]['artist'], 921, 321, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28);
			})
			->text($top5[2]['artist'], 920, 320, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28)->color('#FFF');
			})
			->text($top5[3]['artist'], 621, 661, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28);
			})
			->text($top5[3]['artist'], 620, 660, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28)->color('#FFF');
			})
			->text($top5[4]['artist'], 921, 661, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28);
			})
			->text($top5[4]['artist'], 920, 660, function ($font) {
				$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(28)->color('#FFF');
			})
			->save($imgFile);

		return realpath($imgFile);
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
