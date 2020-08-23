<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Dandelionmood\LastFm\LastFm;
use Abraham\TwitterOAuth\TwitterOAuth;

( new Dotenv(true) )->load(__DIR__ . '/.env');

// last.fm - Scrape stuff.

$lfm      = new LastFm( getenv( 'LASTFM_KEY' ), getenv( 'LASTFM_SECRET' ) );
$lfm_tops = $lfm->user_getTopTracks([
	'user'   => getenv( 'LASTFM_SCAN_USER_NAME' ),
	'period' => '7days',
	'limit'  => getenv( 'LASTFM_DISPLAY_AMOUNT' ),
]);

$top5 = [];
foreach ( $lfm_tops->toptracks->track as $track ) {
	$top5[] = "{$track->artist->name} - {$track->name} ({$track->playcount})";
}

$message = "\u{1F4BF} #lastfm: " . implode( ', ', $top5 ) . '.';

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
