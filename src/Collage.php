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

use HotThisWeek\Object\Artist;
use Tzsk\Collage\MakeCollage;

/**
 * Collage generation.
 */
class Collage
{
	/**
	 * Generates a 1 left, 4 right collage image based on given image sources.
	 *
	 * @param Artist[] $top5       last.fm response.
	 * @param string   $exportPath Optional ath to override the export to.
	 * @return string Location of generated image on filesystem.
	 */
	public function generateCollage(array $top5, string $exportPath = ''): string
	{
		$missing = (5 - count($top5));
		for ($i = 0; $i < $missing; $i++) {
			$top5[] = new Artist();
		}

		$imgFile = ( (empty($exportPath)) ? sys_get_temp_dir() : $exportPath ) . '/sbimg_' . uniqid() . '.png';
		$imgarr  = [];
		foreach ($top5 as &$item) {
			$imgarr[] = $item->getPicture();
		}

		$forcol = $imgarr;
		array_shift($forcol);

		if (file_exists($imgFile)) {
			unlink($imgFile);
		}

		$collage    = new MakeCollage();
		$firstImage = $collage->make(400, 400)->from($forcol)->encode('png');
		$fontpath   = dirname(__FILE__) . '/../assets/ubuntu.ttf';

		$collage->make(1200, 675)
			->from([ $imgarr[0], $firstImage ], function ($settings) {
				$settings->vertical();
			})
			->text($top5[0]->getNameConcat(), 22, 662, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(54);
			})
			->text($top5[0]->getNameConcat(), 20, 660, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(54)->color('#FFF');
			})
			->text($top5[1]->getNameConcat(), 621, 321, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28);
			})
			->text($top5[1]->getNameConcat(), 620, 320, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28)->color('#FFF');
			})
			->text($top5[2]->getNameConcat(), 921, 321, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28);
			})
			->text($top5[2]->getNameConcat(), 920, 320, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28)->color('#FFF');
			})
			->text($top5[3]->getNameConcat(), 621, 661, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28);
			})
			->text($top5[3]->getNameConcat(), 620, 660, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28)->color('#FFF');
			})
			->text($top5[4]->getNameConcat(), 921, 661, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28);
			})
			->text($top5[4]->getNameConcat(), 920, 660, function ($font) use ($fontpath) {
				$font->file($fontpath)->size(28)->color('#FFF');
			})
			->save($imgFile);

		return realpath($imgFile);
	}
}
