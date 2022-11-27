from time import sleep
from mastodon import Mastodon as MastAPI

class Mastodon():
	def __init__(self, api_url = '', access_key = '', access_secret = '', user_name = '', user_token = ''):
		self._api_url = api_url
		self._access_key = access_key
		self._access_secret = access_secret
		self._user_name = user_name
		self._user_token = user_token
	
	def post_to_mastodon(self, message, image):
		elephant = MastAPI(
			api_base_url=self._api_url,
			client_id=self._access_key,
			client_secret=self._access_secret,
			access_token=self._user_token,
		)

		medias = elephant.media_post(
			media_file=image,
		)

		# Surely there's a better way?!
		sleep(5)

		elephant.status_post(
			status=message,
			media_ids=elephant.media(medias['id']),
		)
