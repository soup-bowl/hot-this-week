from htw.collage import collage

import unittest, os

class StatChecks(unittest.TestCase):
	@classmethod
	def setUpClass(self):
		self.collage   = collage()
		self.dummy_lfm = []
		for i in range(5):
			self.dummy_lfm.append({
				"name": "Dummy",
				"image": "https://source.unsplash.com/featured/?face,person",
				"plays": "123",
			})

	def test_collage_generation(self):
		"""Generates a collage using dummy data.
		"""
		collage = self.collage.new(self.dummy_lfm, True)
		self.assertTrue(os.path.exists(collage))

	def test_name_concatenation(self):
		"""Tests the concatentation function returns expected results for okay and too-long names.
		"""
		name_okay = "a" * 15
		name_conc = name_okay + "a"

		self.assertEqual(self.collage.cut_long_artist_name(name_okay), name_okay)
		self.assertEqual(self.collage.cut_long_artist_name(name_conc), "aaaaaaaaaaaa...")
