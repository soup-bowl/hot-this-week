<?php

require __DIR__ . '/vendor/autoload.php';

use Dandelionmood\LastFm\LastFm;
use Abraham\TwitterOAuth\TwitterOAuth;


if ( file_exists( __DIR__ . '/vendor/symfony/dotenv/composer.json' ) ) {
	( new Symfony\Component\Dotenv\Dotenv( true ) )->load(__DIR__ . '/.env');
}

libxml_use_internal_errors( true );

// last.fm - Scrape stuff.

$lfm      = new LastFm( getenv( 'LASTFM_KEY' ), getenv( 'LASTFM_SECRET' ) );
$lfm_tops = $lfm->user_getTopTracks([
	'user'   => getenv( 'LASTFM_SCAN_USER_NAME' ),
	'period' => '7day',
	'limit'  => getenv( 'LASTFM_DISPLAY_AMOUNT' ),
]);

$top5 = [];
foreach ( $lfm_tops->toptracks->track as $track ) {
	$top5[] = [
		'artist'  => $track->artist->name,
		'picture' => get_artist_picture( $track->artist->url ),
		'track'   => $track->name,
		'count'   => $track->playcount
	];
}

$message = "\u{1F4BF} #lastfm:\n";
foreach( $top5 as $item ) {
	$message .= "{$item['artist']} - {$item['track']} ({$item['count']})\n";
}

// Twitter - Posting stuff.

$connection = new TwitterOAuth(
	getenv( 'TWITTER_CONSUMER_KEY' ),
	getenv( 'TWITTER_CONSUMER_SECRET' ),
	getenv( 'TWITTER_ACCESS_TOKEN' ),
	getenv( 'TWITTER_ACCESS_SECRET' )
);

$tweet_on = ( getenv('GENERAL_TWEET_ENABLED') === '1' ) ? true : false;
if (!$tweet_on) {
	echo 'Tweeting is off.' . PHP_EOL . $message . PHP_EOL;
	exit(0);
}

$connection->post( 'statuses/update', [ 'status' => $message ] );
if ($connection->getLastHttpCode() == 200) {
    echo 'Tweet posted successfully.' . PHP_EOL;
	exit(0);
} else {
	echo "An error occurred during tweeting: ({$connection->getLastBody()->errors[0]->code}) {$connection->getLastBody()->errors[0]->message}" . PHP_EOL;
	exit(1);
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
