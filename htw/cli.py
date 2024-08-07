"""
Command line handler for the Hot-this-Week toolset.
"""

import sys
import getopt
import json
from os import getenv
from os.path import realpath, exists
from pathlib import Path

from htw.collage import Collage
from htw.compose import compose_tweet
from htw.providers.lfm import LFM, LFMPeriod
from htw.providers.mastodon import Mastodon
from htw.providers.bluesky import Bluesky

class CLI():
	"""Command line handler for the Hot-this-Week toolset.
	"""
	def __init__(self):
		self.suppress = False
		self.display_only = False
		self.keep_pic = False
		self.conf_path = "config.json"
		self.lfm_period = LFMPeriod.WEEK
		self.lastfm_key = getenv('LASTFM_KEY')
		self.mastodon_key = getenv('MASTODON_KEY')
		self.mastodon_srt = getenv('MASTODON_SECRET')
		self.mastodon_url = getenv('MASTODON_URL')
		self.htw_users = None

	def main(self, argv):
		"""Main initator of the command-line response sequence.

		Args:
			argv: Input arguments.
		"""

		try:
			opts, args = getopt.getopt(
				argv[1::],
				"hvsdhkf:p:",
				["help", "version", "silent", "display", "keep", "file=", "period="]
			)
		except getopt.GetoptError:
			print("Invalid command(s).\n")
			self.print_help()
			sys.exit(2)

		for opt, arg in opts:
			if opt in ('-h', '--help'):
				self.print_help()
				sys.exit()
			elif opt in ('-v', '--version'):
				self.print_version()
				sys.exit()
			elif opt in ('-s', '--silent'):
				self.suppress = True
			elif opt in ('-k', '--keep'):
				self.keep_pic = True
			elif opt in ('-d', '--display'):
				self.display_only = True
			elif opt in ('-p', '--period'):
				if arg in ('week', 'weekly'):
					self.lfm_period = LFMPeriod.WEEK
				if arg in ('month', 'monthly'):
					self.lfm_period = LFMPeriod.MONTH
				elif arg == 'quarter':
					self.lfm_period = LFMPeriod.QUARTER
				elif arg == 'halfyear':
					self.lfm_period = LFMPeriod.HALFYEAR
				elif arg == 'year':
					self.lfm_period = LFMPeriod.YEAR
				elif arg == 'all':
					self.lfm_period = LFMPeriod.ALL
				else:
					if not self.suppress:
						print(f"Invalid period specifier '{arg}' - defaulting to weekly.")
					self.lfm_period = LFMPeriod.WEEK
			elif opt in ("-f", "--file"):
				self.conf_path = realpath( arg )

		if exists( self.conf_path ):
			self.read_config( self.conf_path )
		else:
			if not self.suppress:
				print("The configuration file could not be found.")
				print("Run with -h/--help to see the help documentation.")
			sys.exit(3)

		if self.htw_users is None:
			if not self.suppress:
				print("Participant collection is empty. Please specify a configuration file per-user ", end='')
				print("(array object we look for is 'clients').")
				print("Run with -h/--help to see the help documentation.")
			sys.exit(4)

		step_count = [0,0]
		for item in self.htw_users:
			state = self.process_user(item)
			if state:
				step_count[0] += 1
			else:
				step_count[1] += 1
		if not self.suppress:
			step_calc = step_count[0] + step_count[1]
			print(f'Processing {step_calc} complete - ', end='')
			print(f'\033[92m{step_count[0]}\033[00m successful, ', end='')
			print(f'\033[91m{step_count[1]}\033[00m failures.')

	def process_user(self, user_conf):
		"""Process individual user of a sequence.

		Args:
			user_conf: The individual user configurations.
		"""

		if not self.suppress:
			print(f"Processing \033[92m{user_conf['lastfmUsername']}\033[00m")
			print("- Scraping from \033[91mlast.fm\033[00m...")
		try:
			artists = LFM(self.lastfm_key).get_top_artists(user_conf['lastfmUsername'], self.lfm_period)
		except Exception as error:
			print(f"\033[91mError\033[00m: {error}")
			return False

		if len(artists) < 5:
			if not self.suppress:
				print("\033[91mError\033[00m: Not enough data to process. Skipping.")
			return False

		if not self.suppress:
			print("- Generating collage...")
		colgen = Collage()
		pic = colgen.new(artists, self.keep_pic)

		if not self.suppress:
			print("- Composing message...")
		tweet = compose_tweet(artists, user_conf['lastfmUsername'], self.lfm_period)

		if self.display_only:
			print(tweet)
			print("---")
			print(f"\033[92mCollage\033[00m: {pic}")
			colgen.cleanup()
			return True
		
		if 'BlueskyHandle' in user_conf:
			if not self.suppress:
				print("- Posting to \033[96mBluesky\033[00m...")

			try:
				bsky = Bluesky(
					url=user_conf['BlueskyInstance'],
					handle=user_conf['BlueskyHandle'],
					application_password=user_conf['BlueskyPassword']
				)

				bsky.post(tweet, pic)
			except Exception as error:
				print("\033[91mError\033[00m: " + str(error) + ".")
				return False
			finally:
				colgen.cleanup()

		if 'mastodonAccessToken' in user_conf:
			if not self.suppress:
				print("- Posting to \033[95mMastodon\033[00m...")

			try:
				Mastodon(
					api_url=self.mastodon_url,
					access_key=self.mastodon_key,
					access_secret=self.mastodon_srt,
					user_name=user_conf['mastodonUsername'],
					user_token=user_conf['mastodonAccessToken']
				).post_to_mastodon(tweet, pic)
				return True
			except Exception as error:
				print("\033[91mError\033[00m: " + str(error) + ".")
				return False
			finally:
				colgen.cleanup()

	def read_config(self, location):
		"""Loads in the configurations from the Hot this Week configuration file.

		Args:
			location (str): Where the configuration file is found.
		"""

		conf = json.loads( Path( location ).read_text('UTF-8') )
		if 'config' not in conf:
			return

		if 'clients' in conf:
			self.htw_users = conf['clients']

		if 'lastfmKey' in conf['config']:
			self.lastfm_key = conf['config']['lastfmKey'] if self.lastfm_key is None else self.lastfm_key

		if 'mastodonURL' in conf['config']:
			self.mastodon_url = conf['config']['mastodonURL'] if self.mastodon_url is None else self.mastodon_url
		if 'mastodonKey' in conf['config']:
			self.mastodon_key = conf['config']['mastodonKey'] if self.mastodon_key is None else self.mastodon_key
		if 'mastodonSecret' in conf['config']:
			self.mastodon_srt = conf['config']['mastodonSecret'] if self.mastodon_srt is None else self.mastodon_srt

	def print_help(self):
		"""Prints help text to the screen.
		"""

		if self.suppress:
			return

		print("Run without arguments to process last.fm & Mastodon using environmental variables.")
		print("Script will also check and use environment variables stored in '.env'.")
		print("")
		print("\033[93mOptions:\033[00m")
		print("\033[92m-f, --file         \033[00mLoad in a config.json file from a different location.")
		print("                   Default is \033[93mconfig.json\033[00m in the current directory.")
		print("\033[92m-p, --period       \033[00mTime period to post. If unspecified, the default is 1 week.")
		print("                   Options are \033[93m'week' (default)\033[00m, ", end='')
		print("'month', 'quarter', 'halfyear', 'year' and 'all'.")
		print("\033[92m-k, --keep         \033[00mThe collage is dumped into the working directory instead ", end='')
		print("of a temporary disposable directory.")
		print("\033[92m-s, --silent       \033[00mScript does not output anything, just success/fail code.")
		print("\033[92m-d, --display      \033[00mDisplays tweet, but does not post to Mastodon.")
		print("")
		print("\033[92m-v, --version      \033[00mDisplay script version.")
		print("\033[92m-h, --help         \033[00mDisplay help information.")

	def print_version(self):
		"""Prints version text to the screen.
		"""

		if self.suppress:
			return

		print("Hot this Week by soup-bowl - \033[93mpre-alpha\033[00m.")
		print("https://github.com/soup-bowl/hot-this-week")
