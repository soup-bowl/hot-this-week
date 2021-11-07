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

use HotThisWeek\Collage;
use PHPUnit\Framework\TestCase;

class CollageTest extends TestCase
{
	protected $collage;
	public function setUp(): void
	{
		$this->collage = new Collage();
	}

	/**
	 * Checks the collage export function is working.
	 *
	 * If you wish to physically inspect the file, this test will export the image to the root directory.
	 */
	public function testGenerateCollage(): void
	{
		$dummy5 = [];
		for ($i = 0; $i < 5; $i++) {
			$dummy5[] = [
				'artist'  => 'Dummy Artist #' . ($i + 1),
				'picture' => 'https://source.unsplash.com/random',
				'count'   => rand(1, 200),
			];
		}
		$dummy5[2]['artist'] = 'Really long artist name to test concatenation';

		$collage = $this->collage->generateCollage($dummy5, realpath(dirname(__FILE__) . '/..'));
		$this->assertStringNotEqualsFile($collage, '', 'The binary image created by the collage routine was empty.');
	}
}
