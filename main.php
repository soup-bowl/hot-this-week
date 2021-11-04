<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Dandelionmood\LastFm\LastFm;
use Abraham\TwitterOAuth\TwitterOAuth;
use Tzsk\Collage\MakeCollage;

if (file_exists(__DIR__ . '/.env')) {
	( new Dotenv(true) )->load(__DIR__ . '/.env');
}

libxml_use_internal_errors(true);

/**
 * Main interaction function.
 *
 * @param string[] $argv System argument array.
 * @return void Exit will be called from this function.
 */
function main($argv)
{
	$displayOnly = false;
	$silentMode  = false;
	foreach ($argv as $arg) {
		switch ($arg) {
			case '-s':
			case '--suppress':
			case '--silent':
				$silentMode = true;
				break;
			case '-d':
			case '--display':
				$displayOnly = true;
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
	$cwd = dirname(__FILE__);

	if (! $silentMode) {
		echo '- Scraping from last.fm...' . PHP_EOL;
	}

	$top5 = get_top_from_lastfm();

	if (! $silentMode) {
		echo '- Generating collage...' . PHP_EOL;
	}

	$img  = generate_collage($top5);

	if (! $silentMode) {
		echo '- Composing tweet...' . PHP_EOL;
	}

	$message = "\u{1F4BF} My week with #lastfm:\n";
	foreach ($top5 as $item) {
		$message .= "{$item['artist']} ({$item['count']})\n";
	}


	if (! $silentMode) {
		echo '- Posting to Twitter...' . PHP_EOL;
	}

	if ($displayOnly) {
		echo $message;
		exit();
	} else {
		$response = post_to_twitter($message, $img);
		if (! $silentMode) {
			echo $response->message . PHP_EOL;
		}
		exit(( $response->success ) ? 0 : 1);
	}
}

/**
 * Grabs the top artists from the last.fm API.
 */
function get_top_from_lastfm()
{
	$lfm      = new LastFm(getenv('LASTFM_KEY'), getenv('LASTFM_SECRET'));
	$lfm_tops = $lfm->user_getTopArtists([
		'user'   => getenv('LASTFM_SCAN_USER_NAME'),
		'period' => '7day',
		'limit'  => 5,
	]);

	$top = [];
	foreach ($lfm_tops->topartists->artist as $artist) {
		$top[] = [
			'artist'  => $artist->name,
			'picture' => get_artist_picture($artist->url),
			'count'   => $artist->playcount
		];
	}

	return $top;
}

/**
 * Posts contents to Twitter.
 *
 * @param string $message        Contents of the tweet.
 * @param string $image_location Attach an optional image.
 * @param array Boolean 'success' to indicate state, and counterpart 'message'.
 */
function post_to_twitter($message, $image_location = null)
{
	$connection = new TwitterOAuth(
		getenv('TWITTER_CONSUMER_KEY'),
		getenv('TWITTER_CONSUMER_SECRET'),
		getenv('TWITTER_ACCESS_TOKEN'),
		getenv('TWITTER_ACCESS_SECRET')
	);

	$collage = $connection->upload('media/upload', [ 'media' => $image_location ]);

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
function get_artist_picture($url)
{
	$artist_html = remote_get_content($url);

	$dom = new DOMDocument();
	$dom->loadHTML($artist_html);
	$xpath = new DOMXPath($dom);

	$img_src = "";
	foreach ($xpath->query('//div[contains(@class,"header-new-background-image")]') as $item) {
		$img_src = $item->getAttribute('content');
		continue;
	}

	return $img_src;
}

/**
 * Generates a 1 left, 4 right collage image based on given image sources.
 *
 * @param string[] $top5            last.fm response.
 * @param string   $export_location Location to store photo, default is current directory.
 * @return string Location of generated image on filesystem.
 */
function generate_collage($top5, $export_location = '')
{
	$imgarr = [];
	foreach ($top5 as &$item) {
		$imgarr[]       = $item['picture'];
		$item['artist'] = ( strlen($item['artist']) > 10 ) ? substr($item['artist'], 0, 10) . "..." : $item['artist'];
	}


	$forcol = $imgarr;
	array_shift($forcol);

	if (file_exists('collage.png')) {
		unlink('collage.png');
	}

	$collage     = new MakeCollage();
	$first_image = $collage->make(400, 400)->from($forcol)->encode('png');

	$collage->make(1200, 675)
		->from([ $imgarr[0], $first_image ], function ($a) {
			$a->vertical();
		})
		->text($top5[0]['artist'], 22, 662, function ($font) {
			$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(52);
		})
		->text($top5[0]['artist'], 20, 660, function ($font) {
			$font->file(dirname(__FILE__) . '/ubuntu.ttf')->size(52)->color('#FFF');
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
		->save('collage.png');

	return realpath('collage.png');
}

function remote_get_content($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
}

main($argv);
