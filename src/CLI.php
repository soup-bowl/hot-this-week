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

use Exception;
use HotThisWeek\LastfmAPI;
use HotThisWeek\TwitterAPI;
use HotThisWeek\Collage;
use HotThisWeek\Enum\Period;

class CLI
{
	protected $clients;
	protected $path;
	protected $period;
	protected $lastfm_key;
	protected $lastfm_secret;
	protected $twitter_key;
	protected $twitter_secret;
	protected $display_only;
	protected $silent_mode;

	protected $lastfm;
	protected $twitter;
	protected $collage;

	/**
	 * Constructor.
	 *
	 * @param string  $path        Location to the configuraton file.
	 * @param string  $period      Time period chosen by the user to display.
	 * @param boolean $displayOnly Determines whether the tweet action is concluded.
	 * @param boolean $silentMode  Don't output updates to stdout.
	 */
	public function __construct(string $path, string $period = Period::WEEK, bool $displayOnly = false, bool $silentMode = false)
	{
		$this->path         = $path;
		$this->period       = $period;
		$this->display_only = $displayOnly;
		$this->silent_mode  = $silentMode;
		$this->setConfigurationFromJSON($path);

		$this->lastfm  = new LastfmAPI($this->lastfm_key, $this->lastfm_secret);
		$this->twitter = new TwitterAPI($this->twitter_key, $this->twitter_secret);
		$this->collage = new Collage();
	}

	/**
	 * Main interaction function.
	 *
	 * @return boolean True if everything succeeded, false if there was failures.
	 */
	public function main(): bool
	{
		$successCount = 0;
		$failureCount = 0;
		foreach ($this->clients as $client) {
			if (! $this->silent_mode) {
				echo 'Processing ' . $client['lastfmUsername'] . PHP_EOL;
				echo '- Scraping from last.fm...' . PHP_EOL;
			}

			try {
				$top5 = $this->lastfm->getTopFromLastfm($client['lastfmUsername'], $this->period);
			} catch (Exception $e) {
				echo '- Failed communicating with the last.fm API server: ';
				if (stristr($e->getMessage(), 'User not found')) {
					echo 'User not found.';
				} else {
					echo 'Unknown error.';
				}
				echo PHP_EOL;
				$failureCount++;
				continue;
			}

			if (! $this->silent_mode) {
				echo '- Generating collage...' . PHP_EOL;
			}

			$img = $this->collage->generateCollage($top5);

			if (! $this->silent_mode) {
				echo '- Composing tweet...' . PHP_EOL;
			}

			$message = $this->twitter->composeTweet($top5, $this->period, "https://www.last.fm/user/{$client['lastfmUsername']}");

			if (! $this->silent_mode) {
				echo '- Posting to Twitter...' . PHP_EOL;
			}

			if ($this->display_only) {
				echo $message->message . PHP_EOL;
				echo "---" . PHP_EOL;
				echo "Counter: {$message->count} of {$message->limit}."  . PHP_EOL;
				echo "Collage: {$img}" . PHP_EOL;
				$successCount++;
			} else {
				$response = $this->twitter->postToTwitter(
					$client['twitterAccessToken'],
					$client['twitterAccessSecret'],
					$message->message,
					$img
				);

				if (! $this->silent_mode) {
					echo $response->message . PHP_EOL;
				}
				( $response->success ) ? $successCount++ : $failureCount++;
			}
		}

		if (! $this->silent_mode) {
			echo PHP_EOL . "Processing complete - {$successCount} successful, {$failureCount} failures." . PHP_EOL;
			return ($failureCount > 0) ? false : true;
		}
	}

	/**
	 * Sets the application configuration by a supplied configuration JSON file.
	 *
	 * @param string $path Path to the desired configuration JSON.
	 * @return void Sets configuration to the class instantiation.
	 */
	private function setConfigurationFromJSON(string $path): void
	{
		if (file_exists($path)) {
			$json = json_decode(file_get_contents($path), true);
			$this->clients        = (isset($json['clients'])) ? $json['clients'] : [];

			// Required
			if (isset($json, $json['config'], $json['config']['lastfmKey'])) {
				$this->lastfm_key     = $json['config']['lastfmKey'];
				$this->lastfm_secret  = (isset($json['config']['lastfmSecret'])) ? $json['config']['lastfmSecret'] : '';
			} else {
				throw new Exception('lastfm API keys not set.');
			}

			if (isset($json['config']['twitterConsumerKey'], $json['config']['twitterConsumerSecret'])) {
				$this->twitter_key    = $json['config']['twitterConsumerKey'];
				$this->twitter_secret = $json['config']['twitterConsumerSecret'];
			} else {
				throw new Exception('Twitter application API keys not set.');
			}
		} else {
			throw new Exception("Configuration file not found or invalid ({$this->path}).");
		}
	}
}
