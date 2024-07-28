"""
Contains the communication class for composing messages.
"""

class Compose():
	"""Contains the communication class for composing messages.
	"""

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
