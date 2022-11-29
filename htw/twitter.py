"""
Contains the communication class for interacting with Twitter.
"""

from twython import Twython, TwythonError

class Twitter():
	"""Contains the communication class for interacting with Twitter.

	Args:
		consumer_key (str): Twitter application credentials.
		consumer_secret (str): Twitter application credentials.
		access_key (str): Individual user Twitter credentials.
		access_secret (str): Individual user Twitter credentials.
	"""
	def __init__(self, consumer_key = '', consumer_secret = '', access_key = '', access_secret = ''):
		self._consumer_key = consumer_key
		self._consumer_secret = consumer_secret
		self._access_key = access_key
		self._access_secret = access_secret

	def compose_tweet(self, lfm_collection, name):
		"""Compose tweet message contents.

		Args:
			lfm_collection ([type]): Last.fm user data collection.
			name (str): Last.fm username.

		Returns:
			[str]: Message contents.
		"""
		message = "\U0001F4BF my week with #lastfm:\n"
		for artist in lfm_collection:
			message = message + f"{artist['name']} ({artist['plays']})\n"
		message = message + f"https://www.last.fm/user/{name}"
		return message

	def post_to_twitter(self, tweet, picture):
		"""Posts a message to the Twitter platform.

		Args:
			tweet (str): Message contents.
			picture (str): Filesystem location of the collage to attach.
		"""

		twitter = Twython(
			self._consumer_key,
			self._consumer_secret,
			self._access_key,
			self._access_secret
		)
		collage = open(picture, 'rb')

		try:
			response = twitter.upload_media(media=collage)
			twitter.update_status(status=tweet, media_ids=[response['media_id']])
		except TwythonError as error:
			raise Exception("Twitter responded with an error code: " + str(error.error_code)) from error
