from sys import exit
from os import getenv
from os.path import realpath, exists
from pathlib import Path
from htw.lfm import lfm, lfmperiod
from htw.collage import collage
from htw.twitter import compose_tweet, post_to_twitter
import getopt, json

class cli(object):
	def __init__(self):
		self.suppress      = False
		self.display_only  = False
		self.keep_pic      = False
		self.conf_path     = "config.json"
		self.lfm_period    = lfmperiod.week
		self.lastfm_key    = getenv('LASTFM_KEY')
		self.twitter_key   = getenv('TWITTER_CONSUMER_KEY')
		self.twitter_srt   = getenv('TWITTER_CONSUMER_SECRET')
		self.twitter_users = None

	def main(self, argv):
		try:
			opts, args = getopt.getopt(
				argv[1::],
				"hvsdhkf:p:",
				["help", "version", "silent", "display", "keep", "file=", "period="]
			)
		except getopt.GetoptError:
			print("Invalid command(s).\n")
			self.print_help()
			exit(2)

		for opt, arg in opts:
			if opt in ('-h', '--help'):
				self.print_help()
				exit()
			elif opt in ('-v', '--version'):
				self.print_version()
				exit()
			elif opt in ('-s', '--silent'):
				self.suppress = True
			elif opt in ('-k', '--keep'):
				self.keep_pic = True
			elif opt in ('-d', '--display'):
				self.display_only = True
			elif opt in ('-p', '--period'):
				if arg in ('week', 'weekly'):
					self.lfm_period = lfmperiod.week
				if arg in ('month', 'monthly'):
					self.lfm_period = lfmperiod.month
				elif arg == 'quarter':
					self.lfm_period = lfmperiod.quarter
				elif arg == 'halfyear':
					self.lfm_period = lfmperiod.halfyear
				elif arg == 'year':
					self.lfm_period = lfmperiod.year
				elif arg == 'all':
					self.lfm_period = lfmperiod.all
				else:
					if not self.suppress:
						print("Invalid period specifier \"%s\" - defaulting to weekly." % arg)
					self.lfm_period = lfmperiod.week
			elif opt in ("-f", "--file"):
				self.conf_path = realpath( arg )

		if exists( self.conf_path ):
			self.read_config( self.conf_path )
		else:
			if not self.suppress:
				print("The configuration file could not be found.")
				print("Run with -h/--help to see the help documentation.")
			exit(3)

		if self.twitter_users is None:
			if not self.suppress:
				print("Participant collection is empty. Please specify a configuration file per-user (array object we look for is 'clients').")
				print("Run with -h/--help to see the help documentation.")
			exit(4)

		success_count = 0
		failure_count = 0
		for item in self.twitter_users:
			if not self.suppress:
				print("Processing \033[92m%s\033[00m" % item['lastfmUsername'])
				print("- Scraping from \033[91mlast.fm\033[00m...")
			artists = lfm(self.lastfm_key).get_top_artists(item['lastfmUsername'], self.lfm_period)

			if not self.suppress:
				print("- Generating collage...")
			colgen = collage()
			pic    = colgen.new(artists, self.keep_pic)

			if not self.suppress:
				print("- Composing tweet...")
			tweet = compose_tweet(artists, item['lastfmUsername'])

			if self.display_only:
				print(tweet)
				print("---")
				print("\033[92mCollage\033[00m: %s" % pic)
				success_count += 1
			else:
				if not self.suppress:
					print("- Posting to \033[96mTwitter\033[00m...")

				post_to_twitter(
					tweet,
					pic,
					self.twitter_key,
					self.twitter_srt,
					item['twitterAccessToken'],
					item['twitterAccessSecret']
				)
				success_count += 1

			colgen.cleanup()
		if not self.suppress:
			print("Processing %s complete - \033[92m%s\033[00m successful, \033[91m%s\033[00m failures." % (success_count + failure_count, success_count, failure_count))

	def read_config(self, location):
		conf = json.loads( Path( location ).read_text() )
		if 'config' not in conf:
			return None

		if 'clients' in conf:
			self.twitter_users = conf['clients']

		if 'lastfmKey' in conf['config']:
			self.lastfm_key  = conf['config']['lastfmKey'] if self.lastfm_key is None else self.lastfm_key
		if 'twitterConsumerKey' in conf['config']:
			self.twitter_key = conf['config']['twitterConsumerKey'] if self.twitter_key is None else self.twitter_key
		if 'twitterConsumerSecret' in conf['config']:
			self.twitter_srt = conf['config']['twitterConsumerSecret'] if self.twitter_srt is None else self.twitter_srt

	def print_help(self):
		if self.suppress:
			return None

		print("Run without arguments to process last.fm & Twitter using environmental variables.")
		print("Script will also check and use environment variables stored in '.env'.")
		print("")
		print("\033[93mOptions:\033[00m")
		print("\033[92m-f, --file         \033[00mLoad in a config.json file from a different location.")
		print("                   Default is \033[93mconfig.json\033[00m in the current directory.")
		print("\033[92m-p, --period       \033[00mTime period to post. If unspecified, the default is 1 week.")
		print("                   Options are \033[93m'week' (default)\033[00m, 'month', 'quarter', 'halfyear', 'year' and 'all'.")
		print("\033[92m-k, --keep         \033[00mThe collage is dumped into the working directory instead of a temporary disposable directory.")
		print("\033[92m-s, --silent       \033[00mScript does not output anything, just success/fail code.")
		print("\033[92m-d, --display      \033[00mDisplays tweet, but does not post to Twitter.")
		print("")
		print("\033[92m-v, --version      \033[00mDisplay script version.")
		print("\033[92m-h, --help         \033[00mDisplay help information.")

	def print_version(self):
		if self.suppress:
			return None

		print("Hot this Week by soup-bowl - \033[93m%s\033[00m." % "pre-alpha")
		print("https://github.com/soup-bowl/lastfm-twitter/")

