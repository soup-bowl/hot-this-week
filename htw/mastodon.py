from mastodon import Mastodon as MastAPI

class Mastodon():
	def __init__(self, api_url = '', access_key = '', access_secret = '', user_name = '', user_token = ''):
		self._api_url = api_url
		self._access_key = access_key
		self._access_secret = access_secret
		self._user_name = user_name
		self._user_token = user_token
	
	def post_to_mastodon(self, message):
		elephant = MastAPI(
			api_base_url=self._api_url,
			client_id=self._access_key,
			client_secret=self._access_secret,
			access_token=self._user_token,
		)

		elephant.toot(message)
