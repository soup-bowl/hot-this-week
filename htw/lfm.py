from urllib3 import PoolManager
from lxml import html
import json

class lfm(object):
	def __init__(self, key, secret = ''):
		self.key    = key
		self.secret = secret

		self.url    = "http://ws.audioscrobbler.com/2.0/?api_key=%s&format=%s&method=%s&user=%s&period=%s&limit=%s"
		self.pm     = PoolManager()

	def get_top_artists(self, username):
		"""Gets the specified users' favourites list.

		Args:
			username (str): last.fm username that we're scannng.

		Returns:
			[dict]: Collection of artists, their logo and user playcount.
		"""
		resp = self.pm.request( 'GET', self.craft_request_url( 'user.gettopartists', username ) )

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
		else:
			return None
	
	def get_artist_picture(self, url):
		"""Scrapes the last.fm website for the artist image.

		Args:
			url (str): URL to scrape the image from (last.fm artist page).

		Returns:
			[str]: The URL to the artist picture.
		"""
		resp = self.pm.request( 'GET', url )

		if resp.status == 200:
			content = html.fromstring( resp.data.decode('utf-8') )
			image   = content.xpath('//div[contains(@class,"header-new-background-image")]')
		
			return image[0].attrib['content']
		else:
			return None
	
	def craft_request_url(self, endpoint, user):
		"""Crafts the API URL.

		Args:
			endpoint (str): The last.fm API endpoint to call.
			user (str): the username of the last.fm user we're scanning.

		Returns:
			[str]: The API URL.
		"""
		return self.url % ( self.key, 'json', endpoint, user, 'weekly', '5' ) 
