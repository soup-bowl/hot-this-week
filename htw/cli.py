from sys import exit
from os import getenv
from os.path import realpath, exists
from pathlib import Path
from htw.lfm import lfm
from htw.collage import collage
from htw.twitter import compose_tweet, post_to_twitter
import getopt, json

class cli(object):
	def __init__(self):
		self.suppress      = False
		self.display_only  = False
		self.lastfm_key    = getenv('LASTFM_KEY')
		self.twitter_key   = getenv('TWITTER_CONSUMER_KEY')
		self.twitter_srt   = getenv('TWITTER_CONSUMER_SECRET')
		self.twitter_users = None

	def main(self, argv):
		try:
			opts, args = getopt.getopt(
				argv[1::],
				"hvpsdhf:",
				["help", "version", "period", "silent", "display", "file="]
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
			elif opt in ('-d', '--display'):
				self.display_only = True
			elif opt in ("-f", "--file"):
				confile = realpath( arg )
				if exists( confile ):
					self.read_config( confile )
				else:
					print("The configuration file specified could not be found.")
					exit(3)

		if self.twitter_users is None:
			print("Participant collection is empty. Please specify a configuration file per-user (array object we look for is 'clients').")
			print("Run with -h/--help to see the help documentation.")
			exit(4)

		success_count = 0
		failure_count = 0
		for item in self.twitter_users:
			if not self.suppress:
				print("Processing %s" % item['lastfmUsername'])
				print("- Scraping from last.fm...")
			artists = lfm(self.lastfm_key).get_top_artists(item['lastfmUsername'])

			if not self.suppress:
				print("- Generating collage...")
			colgen = collage()
			pic    = colgen.new(artists)

			if not self.suppress:
				print("- Composing tweet...")
			tweet = compose_tweet(artists, item['lastfmUsername'])
			
			if self.display_only:
				print(tweet)
				print("---")
				print("Collage: %s" % pic)
				success_count += 1
			else:
				if not self.suppress:
					print("- Posting to Twitter...")
				
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
			print("Processing complete - %s successful, %s failures." % (success_count, failure_count))

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
		print("Run without arguments to process last.fm & Twitter using environmental variables.")
		print("Script will also check and use environment variables stored in '.env'.")
		print("")
		print("Options:")
		print("-f, --file         Load in a config.json file from a different location.")
		#print("-p, --period       Time period to post. If unspecified, the default is 1 week.")
		print("                   Options are 'week' (default), 'month', 'quarter', 'halfyear', 'year' and 'all'.")
		print("-s, --silent       Script does not output anything, just success/fail code.")
		print("-d, --display      Displays tweet, but does not post to Twitter.")
		print("")
		print("-v, --version      Display script version.")
		print("-h, --help         Display help information.")

	def print_version(self):
		print("Last.fm Twitter bot by soup-bowl - pre-alpha.")
		print("https://github.com/soup-bowl/lastfm-twitter/")

