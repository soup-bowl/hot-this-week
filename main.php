<?php

/**
 * Grabs details about a user from last.fm and posts a collage on their Twitter.
 *
 * @package lastfm-twitter
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use HotThisWeek\CLI;
use HotThisWeek\Enum\Period;

if ('cli' === php_sapi_name()) {
	$dirpath    = __DIR__ . '/config.json';
	$period     = Period::WEEK;
	$silentMode = false;
	$diplayOnly = false;
	for ($i = 0; $i < $argc; $i++) {
		switch ($argv[$i]) {
			case '-p':
			case '--period':
				$periodSetting = $argv[($i + 1)];
				switch ($periodSetting) {
					case 'week':
					default:
						$period = Period::WEEK;
						break;
					case 'month':
						$period = Period::MONTH;
						break;
					case 'quarter':
						$period = Period::QUARTER;
						break;
					case 'halfyear':
						$period = Period::HALFYEAR;
						break;
					case 'year':
						$period = Period::YEAR;
						break;
					case 'all':
						$period = Period::ALL;
						break;
				}
				break;
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
				echo "-f, --file         Load in a config.json file from a different location." . PHP_EOL;
				echo "-p, --period       Time period to post. If unspecified, the default is 1 week." . PHP_EOL;
				echo "                   Options are 'week' (default), 'month', 'quarter', 'halfyear', 'year' and 'all'." . PHP_EOL;
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
		$response = (new CLI($dirpath, $period, $diplayOnly, $silentMode))->main();
		if ($response) {
			exit();
		} else {
			exit(1);
		}
	} catch (Exception $e) {
		echo 'A failure occurred: ' . $e->getMessage() . PHP_EOL;
		exit(2);
	}
}
