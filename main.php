<?php

require __DIR__ . '/vendor/autoload.php';

use Dandelionmood\LastFm\LastFm;
use Abraham\TwitterOAuth\TwitterOAuth;
use Tzsk\Collage\MakeCollage;

if ( file_exists( __DIR__ . '/vendor/symfony/dotenv/composer.json' ) ) {
	( new Symfony\Component\Dotenv\Dotenv( true ) )->load(__DIR__ . '/.env');
}

libxml_use_internal_errors( true );

/**
 * Main interaction function.
 */
function main() {
	// last.fm - Scrape stuff.
	$top5 = get_top_from_lastfm();

	$imgarr = [];
	foreach ( $top5 as $item ) {
		$imgarr[] = $item['picture'];
	}

	$img = generate_collage( $imgarr );

	$message = "\u{1F4BF} #lastfm:\n";
	foreach( $top5 as $item ) {
		$message .= "{$item['artist']} - {$item['track']} ({$item['count']})\n";
	}

	// Twitter - Posting stuff.
	$response = post_to_twitter( $message, $img );
	echo $response->message . PHP_EOL;
	exit( ( $response->success ) ? 0 : 1 );
}

/**
 * Grabs the top tracks from the last.fm API.
 */
function get_top_from_lastfm() {
	$lfm      = new LastFm( getenv( 'LASTFM_KEY' ), getenv( 'LASTFM_SECRET' ) );
	$lfm_tops = $lfm->user_getTopTracks([
		'user'   => getenv( 'LASTFM_SCAN_USER_NAME' ),
		'period' => '7day',
		'limit'  => getenv( 'LASTFM_DISPLAY_AMOUNT' ),
	]);

	$top = [];
	foreach ( $lfm_tops->toptracks->track as $track ) {
		$top[] = [
			'artist'  => $track->artist->name,
			'picture' => get_artist_picture( $track->artist->url ),
			'track'   => $track->name,
			'count'   => $track->playcount
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
function post_to_twitter( $message, $image_location = null ) {
	$connection = new TwitterOAuth(
		getenv( 'TWITTER_CONSUMER_KEY' ),
		getenv( 'TWITTER_CONSUMER_SECRET' ),
		getenv( 'TWITTER_ACCESS_TOKEN' ),
		getenv( 'TWITTER_ACCESS_SECRET' )
	);
	
	$tweet_on = ( getenv('GENERAL_TWEET_ENABLED') === '1' ) ? true : false;
	if (!$tweet_on) {
		return (object) [
			'success' => false,
			'message' => 'Tweeting is off.' . PHP_EOL . $message,
		];
	}
	
	$collage = $connection->upload( 'media/upload', [ 'media' => $image_location ] );
	
	$connection->post(
		'statuses/update',
		[
			'status'    => $message,
			'media_ids' => implode( ',', [ $collage->media_id_string ] )
		]
	);
	
	if ($connection->getLastHttpCode() == 200) {
		return (object) [
			'success' => true,
			'message' => 'Tweet posted successfully.',
		];
	} else {
		return (object) [
			'success' => false,
			'message' => "An error occurred during tweeting: ({$connection->getLastBody()->errors[0]->code}) {$connection->getLastBody()->errors[0]->message}",
		];
	}
}

/**
 * Scrapes the last.fm site for the artist image.
 *
 * @param string $url The artist page URL.
 * @return string Image URL, or blank if none was found.
 */
function get_artist_picture( $url ) {
	$artist_html = file_get_contents( $url );

	$dom = new DOMDocument();
    $dom->loadHTML( $artist_html );
    $xpath = new DOMXPath( $dom );

    $img_src = "";
	foreach ( $xpath->query( '//div[contains(@class,"header-new-background-image")]' ) as $item ) {
        $img_src = $item->getAttribute('content');
        continue;
	}

	return $img_src;
}

/**
 * Generates a 1 left, 4 right collage image based on given image sources.
 *
 * @param string[] $imgarr          Either local filesystem or remote image sources (5 needed).
 * @param string   $export_location Location to store photo, default is current directory.
 * @return string Location of generated image on filesystem.
 */
function generate_collage( $imgarr, $export_location = '' ) {
	$forcol = $imgarr;
	array_shift( $forcol );

	unlink( 'collage.png' );

	$collage     = new MakeCollage();
	$first_image = $collage->make( 400, 400 )->from( $forcol );
	$first_image->save( 'first-img.png' );

	$main_image = $collage->make( 800, 400 )->from( [ $imgarr[0], 'first-img.png' ], function( $a ) { $a->vertical(); } );
	$main_image->save('collage.png');

	unlink( 'first-img.png' );

	return realpath( 'collage.png' );
}

main();
