"""
Contains the communication class for interacting with Mastodon.
"""

from time import sleep
from mastodon import Mastodon as MastAPI

class Mastodon():
	"""Contains the communication class for interacting with Mastodon servers.

	Args:
		api_url (str): The Mastodon server main URL (https://example.social).
		access_key (str): The servers' API Access Key.
		access_secret (str): The servers' API Access Secret.
		user_name (str): Username of the account to post about.
		user_token (str): Users individual API token.
	"""
	def __init__(self, api_url = '', access_key = '', access_secret = '', user_name = '', user_token = ''):
		self._api_url = api_url
		self._access_key = access_key
		self._access_secret = access_secret
		self._user_name = user_name
		self._user_token = user_token

	def post_to_mastodon(self, message, image):
		"""Posts a message to the Mastodon platform.

		Args:
			message (str): Message contents.
			image (str): Filesystem location of the collage to attach.
		"""

		elephant = MastAPI(
			api_base_url=self._api_url,
			client_id=self._access_key,
			client_secret=self._access_secret,
			access_token=self._user_token,
		)

		medias = elephant.media_post(
			media_file=image,
		)

		uploaded = False
		while not uploaded:
			media = elephant.media(medias['id'])
			if media.url is not None:
				uploaded = True
			else:
				sleep(0.5)

		elephant.status_post(
			status=message,
			media_ids=elephant.media(medias['id']),
		)
