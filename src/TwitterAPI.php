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

class TwitterAPI
{
	protected $key;
	protected $secret;
	protected $text_limit;

	/**
	 * Constructor.
	 *
	 * @param string  $path        Location to the configuraton file.
	 * @param boolean $displayOnly Determines whether the tweet action is concluded.
	 * @param boolean $silentMode  Don't output updates to stdout.
	 */
	public function __construct(string $key, string $secret)
	{
		$this->key         = $key;
		$this->secret      = $secret;
		$this->text_limit  = 280;
	}

	/**
	 * Composes a tweet message.
	 *
	 * @param array  $top       last.fm listing array.
	 * @param string $timeframe Timeframe we're posting about.
	 * @param string $url       Credit URL.
	 * @return object 'message', 'count' and 'limit'.
	 */
	public function composeTweet(array $top, string $timeframe, string $url): object
	{
		$message = "\u{1F4BF} {$this->timeframeLabel($timeframe)} #lastfm:\n";
		foreach ($top as $item) {
			$message .= "{$item['artist']} ({$item['count']})\n";
		}
		$message .= $url;

		return (object) [
			'message' => $message,
			'count'   => strlen($message),
			'limit'   => $this->text_limit,
		];
	}

	/**
	 * Posts contents to Twitter.
	 *
	 * @param string $key           Twitter user key.
	 * @param string $secret        Twitter user secret.
	 * @param string $message       Contents of the tweet.
	 * @param string $imageLocation Attach an optional image.
	 * @param array Boolean 'success' to indicate state, and counterpart 'message'.
	 */
	public function postToTwitter(string $key, string $secret, string $message, ?string $imageLocation = null): object
	{
		$connection = new TwitterOAuth($this->key, $this->secret, $key, $secret);

		$collage = $connection->upload('media/upload', [ 'media' => $imageLocation ]);

		$connection->post(
			'statuses/update',
			[
				'status'    => $message,
				'media_ids' => implode(',', [ $collage->media_id_string ])
			]
		);

		if ($connection->getLastHttpCode() == 200) {
			return (object) [
				'success' => true,
				'message' => 'Tweet posted successfully.',
			];
		} else {
			$error = "{$connection->getLastBody()->errors[0]->code}) {$connection->getLastBody()->errors[0]->message}";
			return (object) [
				'success' => false,
				'message' => "An error occurred during tweeting: ({$error})",
			];
		}
	}

	/**
	 * Creates a timeframe string based on the Lastfm Period setting.
	 *
	 * @param string $label Timeframe from the Lastfm Period enum.
	 * @return string English phrase.
	 */
	private function timeframeLabel(string $label): string
	{
		$tf = '';
		switch ($label) {
			case Period::WEEK:
				$tf = 'week';
				break;
			case Period::MONTH:
				$tf = 'month';
				break;
			case Period::QUARTER:
				$tf = '3 months';
				break;
			case Period::HALFYEAR:
				$tf = '6 months';
				break;
			case Period::YEAR:
				$tf = 'year';
				break;
			case Period::ALL:
				$tf = 'whole time';
				break;
			default:
				$tf = 'invalid timeframe';
				break;
		}

		return "my {$tf} with";
	}
}
