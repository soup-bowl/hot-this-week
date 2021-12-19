from sys import exit
from os import getenv
from os.path import realpath, exists
from pathlib import Path
import getopt, json

class cli(object):
	def __init__(self):
		self.suppress = False
		self.display = False
		self.lastfm_key  = getenv('LASTFM_KEY')
		self.twitter_key = getenv('TWITTER_CONSUMER_KEY')
		self.twitter_srt = getenv('TWITTER_CONSUMER_SECRET')

	def main(self, argv):
		suppress    = False
		display     = False
		lastfm_key  = getenv('LASTFM_KEY')
		twitter_key = getenv('TWITTER_CONSUMER_KEY')
		twitter_srt = getenv('TWITTER_CONSUMER_SECRET')

		try:
			opts, args = getopt.getopt(
				argv[1::],
				"hvpsdhf:",
				["help", "version", "period", "silent", "display", "file="]
			)
		except getopt.GetoptError:
			print("Invalid command(s).")
			self.print_help()
			exit(2)

		for opt, arg in opts:
			if opt in ('-h', '--help'):
				self.print_help()
				exit()
			elif opt in ('-v', '--version'):
				self.print_version()
				exit()
			elif opt in ("-f", "--file"):
				confile = realpath( arg )
				if exists( confile ):
					furrr = self.read_config( confile )
				else:
					print("The configuration file specified could not be found.")
					exit(3)

		success_count = 0
		failure_count = 0

		print(self.lastfm_key)

	def read_config(self, location):
		conf = json.loads( Path( location ).read_text() )
		self.lastfm_key  = conf['config']['lastfmKey'] if self.lastfm_key is None else self.lastfm_key
		self.twitter_key = conf['config']['twitterConsumerKey'] if self.twitter_key is None else self.twitter_key
		self.twitter_srt = conf['config']['twitterConsumerSecret'] if self.twitter_srt is None else self.twitter_srt

	def print_help(self):
		print("Run without arguments to process last.fm & Twitter using environmental variables.")
		print("Script will also check and use environment variables stored in '.env'.")
		print("")
		print("Options:")
		print("-f, --file         Load in a config.json file from a different location.")
		print("-p, --period       Time period to post. If unspecified, the default is 1 week.")
		print("                   Options are 'week' (default), 'month', 'quarter', 'halfyear', 'year' and 'all'.")
		print("-s, --silent       Script does not output anything, just success/fail code.")
		print("-d, --display      Displays tweet, but does not post to Twitter.")
		print("")
		print("-v, --version      Display script version.")
		print("-h, --help         Display help information.")

	def print_version(self):
		print("Last.fm Twitter bot by soup-bowl - pre-alpha.")
		print("https://github.com/soup-bowl/lastfm-twitter/")

def main(argv):
	cli().main(argv)
