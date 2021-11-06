<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

declare(strict_types=1);

namespace TestThisWeek;

use HotThisWeek\LastfmAPI;
use PHPUnit\Framework\TestCase;

class LastfmAPITest extends TestCase
{
	protected $lastfm;
	public function setUp(): void
	{
		$this->lastfm = new LastfmAPI('aaa', 'bbb');
	}

	/**
	 * Checks to see if the scraper functionality is working to return an image or a valid exit.
	 */
	public function testGetArtistPicture(): void
	{
		$goodResponse = $this->lastfm->getArtistPicture('https://www.last.fm/music/Moby');
		$this->assertMatchesRegularExpression('/https:\/\/lastfm(.*).(jpg|png|webp)/', $goodResponse, 'Expected a lastfm (fastly) image URL response, but did not match expected criteria.');

		$badResponse = $this->lastfm->getArtistPicture('https://www.last.fm/music/KJDh78asfysdgrg38ft57sd657657453762');
		$this->assertEmpty($badResponse, 'A non-existent artsist URL returned a non-null value.');

		$this->assertTrue(true);
	}

	/**
	 * Checks the collage export function is working.
	 *
	 * If you wish to physically inspect the file, this test will export the image to the root directory.
	 */
	public function testGenerateCollage():void {
		$dummy5 = [];
		for ($i = 0; $i < 5; $i++) { 
			$dummy5[] = [
				'artist'  => 'Dummy Artist #' . ($i + 1),
				'picture' => 'https://source.unsplash.com/random',
				'count'   => rand(1, 200),
			];
		}
		$dummy5[2]['artist'] = 'Really long artist name to test concatenation';

		$collage = $this->lastfm->generateCollage($dummy5, realpath(dirname(__FILE__) . '/..'));
		$this->assertStringNotEqualsFile($collage, '', 'The binary image created by the collage routine was empty.');
	}
}
