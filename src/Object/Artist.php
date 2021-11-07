<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

declare(strict_types=1);

namespace HotThisWeek\Object;

class Artist
{
	protected $name;
	protected $picture;
	protected $listens;

	public function __construct()
	{
		$this->name    = '';
		$this->picture = dirname(__FILE__) . '/../../assets/blank.png';
		$this->listens = 0;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getNameConcat(): string
	{
		return ( strlen($this->name) > 19 )
		? substr($this->name, 0, 16) . "..." : $this->name;
	}

	public function getPicture(): string
	{
		return $this->picture;
	}

	public function getListenCount(): int
	{
		return $this->listens;
	}

	public function setName(string $name): Artist
	{
		$this->name = $name;

		return $this;
	}

	public function setPicture(string $picture): Artist
	{
		$this->picture = $picture;

		return $this;
	}

	public function setListenCount(int $listens): Artist
	{
		$this->listens = $listens;

		return $this;
	}
}
