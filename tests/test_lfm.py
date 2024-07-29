"""
Checks the Last.fm interactions.
"""

import unittest

from htw.providers.lfm import LFM

class StatChecks(unittest.TestCase):
	"""Checks the Last.fm interactions.
	"""

	def test_artist_picture_scan(self):
		"""Tests out the artist picture scraper.
		"""
		pic_good = LFM("abc").get_artist_picture("https://www.last.fm/music/Lady+Gaga")
		self.assertRegex(
			pic_good,
			"https://lastfm(.*).(jpg|png|webp)",
			"Expected a lastfm (fastly) image URL response, but did not match expected criteria."
		)

		pic_bad = LFM("abc").get_artist_picture("https://www.last.fm/music/Ladeh+dada")
		self.assertIsNone(
			pic_bad,
			"A non-existent artsist URL returned a non-null value."
		)
