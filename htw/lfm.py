"""
Management object for interacting with the Last.fm APIs.
"""

import json
from enum import Enum

from urllib3 import PoolManager
from lxml import html

class LFM():
	"""Management object for interacting with the Last.fm APIs.

	Args:
		key (str): Last.fm access key.
		secret (str): Last.fm access secret (optional).
	"""

	def __init__(self, key, secret = ''):
		self.key = key
		self.secret = secret

		self.url = "http://ws.audioscrobbler.com/2.0/?api_key=%s&format=%s&method=%s&user=%s&period=%s&limit=%s"
		self.pool_manager = PoolManager()

	def get_top_artists(self, username, period):
		"""Gets the specified users' favourites list.

		Args:
			username (str): last.fm username that we're scannng.

		Returns:
			[dict]: Collection of artists, their logo and user playcount.
		"""
		resp = self.pool_manager.request(
			'GET',
			self.craft_request_url( 'user.gettopartists', username, period )
		)

		if resp.status == 200:
			coll = []
			data = json.loads( resp.data )

			for artist in data['topartists']['artist']:
				coll.append({
					"name": artist['name'],
					"image": self.get_artist_picture( artist['url'] ),
					"plays": artist['playcount']
				})

			return coll
		if resp.status == 403:
			raise Exception("The global Last.fm API key is invalid, blocked or not set.")

		raise Exception("An unknown error has occurred with the Last.fm API.")

	def get_artist_picture(self, url):
		"""Scrapes the last.fm website for the artist image.

		Args:
			url (str): URL to scrape the image from (last.fm artist page).

		Returns:
			[str]: The URL to the artist picture.
		"""
		resp = self.pool_manager.request( 'GET', url )

		if resp.status == 200:
			content = html.fromstring( resp.data.decode('utf-8') )
			image = content.xpath('//div[contains(@class,"header-new-background-image")]')

			return image[0].attrib['content']

		return None

	def craft_request_url(self, endpoint, user, period = None):
		"""Crafts the API URL.

		Args:
			endpoint (str): The last.fm API endpoint to call.
			user (str): the username of the last.fm user we're scanning.

		Returns:
			[str]: The API URL.
		"""
		if period is None:
			period = LFMPeriod.WEEK

		return self.url % ( self.key, 'json', endpoint, user, period.value, '5' )

class LFMPeriod(Enum):
	""" Enum definer to match common words with whatever the Last.fm API expects.
	"""

	WEEK = "7day"
	MONTH = "1month"
	QUARTER = "3month"
	HALFYEAR = "6month"
	YEAR = "12month"
	ALL = "overall"
