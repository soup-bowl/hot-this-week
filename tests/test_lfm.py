from htw.lfm import lfm

import unittest

class StatChecks(unittest.TestCase):
	@classmethod
	def setUpClass(self):
		self.lfm = lfm("abc")

	def test_artist_picture_scan(self):
		"""Tests out the artist picture scraper.
		"""
		pic_good = self.lfm.get_artist_picture("https://www.last.fm/music/Lady+Gaga")
		self.assertRegex(pic_good, "https://lastfm(.*).(jpg|png|webp)", "Expected a lastfm (fastly) image URL response, but did not match expected criteria.")

		pic_bad = self.lfm.get_artist_picture("https://www.last.fm/music/Ladeh+dada")
		self.assertIsNone(pic_bad, "A non-existent artsist URL returned a non-null value.")