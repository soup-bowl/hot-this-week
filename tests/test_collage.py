from htw.collage import collage

import unittest

class StatChecks(unittest.TestCase):
	@classmethod
	def setUpClass(self):
		self.stats = collage()

	def test_name_concatenation(self):
		"""Tests the concatentation function returns expected results for okay and too-long names.
		"""
		name_okay = "a" * 15
		name_conc = name_okay + "a"

		self.assertEqual(self.stats.cut_long_artist_name(name_okay), name_okay)
		self.assertEqual(self.stats.cut_long_artist_name(name_conc), "aaaaaaaaaaaa...")
