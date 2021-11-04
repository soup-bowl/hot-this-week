<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace soupbowl;

require __DIR__ . '/vendor/autoload.php';

use Dandelionmood\LastFm\LastFm;
use Abraham\TwitterOAuth\TwitterOAuth;
use Exception;
use Tzsk\Collage\MakeCollage;

libxml_use_internal_errors(true);

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 */
class Lfmhot
{
	protected $clients;
	protected $path;
	protected $lastfm_key;
	protected $lastfm_secret;
	protected $twitter_key;
	protected $twitter_secret;
	protected $display_only;
	protected $silent_mode;

	public function __construct($path, $displayOnly = false, $silentMode = false)
	{
		$this->path         = $path;
		$this->display_only = $displayOnly;
		$this->silent_mode  = $silentMode;
		$this->setConfigurationFromJSON($path);
	}

	/**
	 * Main interaction function.
	 *
	 * @return boolean True if everything succeeded, false if there was failures.
	 */
	public function main()
	{
		$successCount = 0;
		$failureCount = 0;
		foreach ($this->clients as $client) {
			if (! $this->silent_mode) {
				echo 'Processing ' . $client['lastfmUsername'] . PHP_EOL;
				echo '- Scraping from last.fm...' . PHP_EOL;
			}

			$top5 = $this->getTopFromLastfm($client['lastfmUsername']);
			if (empty($top5)) {
				echo 'last.fm has not got enough data on the user to proceed.' . PHP_EOL;
				$failureCount++;
				continue;
			}

			if (! $this->silent_mode) {
				echo '- Generating collage...' . PHP_EOL;
			}

			$img  = $this->generateCollage($top5);

			if (! $this->silent_mode) {
				echo '- Composing tweet...' . PHP_EOL;
			}

			$message = "\u{1F4BF} My week with #lastfm:\n";
			foreach ($top5 as $item) {
				$message .= "{$item['artist']} ({$item['count']})\n";
			}


			if (! $this->silent_mode) {
				echo '- Posting to Twitter...' . PHP_EOL;
			}

			if ($this->display_only) {
				echo $message;
				$successCount++;
			} else {
				$response = $this->postToTwitter(
					$client['twitterAccessToken'],
					$client['twitterAccessSecret'],
					$message,
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
	 * Grabs the top artists from the last.fm API.
	 *
	 * @param string $username last.fm account to look-up.
	 * @return array|null
	 */
	public function getTopFromLastfm($username)
	{
		$lfm      = new LastFm($this->lastfm_key, $this->lastfm_secret);
		$lfm_tops = $lfm->user_getTopArtists([
			'user'   => $username,
			'period' => '7day',
			'limit'  => 5,
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
	 * Posts contents to Twitter.
	 *
	 * @param string $key           Twitter user key.
	 * @param string $secret        Twitter user secret.
	 * @param string $message       Contents of the tweet.
	 * @param string $imageLocation Attach an optional image.
	 * @param array Boolean 'success' to indicate state, and counterpart 'message'.
	 */
	public function postToTwitter($key, $secret, $message, $imageLocation = null)
	{
		$connection = new TwitterOAuth($this->twitter_key, $this->twitter_secret, $key, $secret);

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
	 * Scrapes the last.fm site for the artist image.
	 *
	 * @param string $url The artist page URL.
	 * @return string Image URL, or blank if none was found.
	 */
	public function getArtistPicture($url)
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
	 * @param string[] $top5 last.fm response.
	 * @return string Location of generated image on filesystem.
	 */
	public function generateCollage($top5)
	{
		$imgFile = sys_get_temp_dir() . '/collage.png';
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
	public function remoteGetContent($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}

	/**
	 * Sets the application configuration by a supplied configuration JSON file.
	 *
	 * @param string $path Path to the desired configuration JSON.
	 * @return void Sets configuration to the class instantiation.
	 */
	private function setConfigurationFromJSON($path)
	{
		if (file_exists($path)) {
			$json = json_decode(file_get_contents($path), true);
			$this->clients        = (isset($json['clients'])) ? $json['clients'] : [];

			// Required
			if (isset($json, $json['config'], $json['config']['lastfmKey'], $json['config']['lastfmSecret'])) {
				$this->lastfm_key     = $json['config']['lastfmKey'];
				$this->lastfm_secret  = $json['config']['lastfmSecret'];
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

$dirpath    = __DIR__ . '/config.json';
$silentMode = false;
$diplayOnly = false;
for ($i = 0; $i < $argc; $i++) {
	switch ($argv[$i]) {
		case '-f':
		case '--file':
			$dirpath = $argv[($i + 1)];
			break;
		case '-s':
		case '--suppress':
		case '--silent':
			$silentMode = true;
			break;
		case '-d':
		case '--display':
			$diplayOnly = true;
			break;
		case '-h':
		case '--help':
			echo "Run without arguments to process last.fm & Twitter using environmental variables." . PHP_EOL;
			echo "Script will also check and use environment variables stored in '.env'." . PHP_EOL;
			echo PHP_EOL;
			echo "Options:" . PHP_EOL;
			echo "-s, --silent       Script does not output anything, just success/fail code." . PHP_EOL;
			echo "-d, --display      Displays tweet, but does not post to Twitter." . PHP_EOL;
			echo PHP_EOL;
			echo "-v, --version      Display script version." . PHP_EOL;
			echo "-h, --help         Display help information." . PHP_EOL;
			exit;
		case '-v':
		case '--version':
			echo "Last.fm Twitter bot by soup-bowl - pre-alpha." . PHP_EOL;
			echo "https://github.com/soup-bowl/lastfm-twitter/" . PHP_EOL;
			exit;
		default:
			break;
	}
}

try {
	$response = (new Lfmhot($dirpath, $diplayOnly, $silentMode))->main();
	if ($response) {
		exit();
	} else {
		exit(1);
	}
} catch (Exception $e) {
	echo 'A failure occurred: ' . $e->getMessage() . PHP_EOL;
	exit(2);
}
