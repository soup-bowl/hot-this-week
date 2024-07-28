"""
Unit tests for the Collage generation capability.
"""

import unittest
import os

from htw.collage import Collage

class StatChecks(unittest.TestCase):
	"""Unit tests for the Collage generation capability.
	"""

	def test_collage_generation(self):
		"""Generates a collage using dummy data.
		"""
		dummy_lfm = []
		for i in range(5):
			dummy_lfm.append({
				"name": "Dummy",
				"image": "https://picsum.photos/2048",
				"plays": i,
			})

		self.assertTrue(os.path.exists(
			Collage().new(dummy_lfm, True)
		))

	def test_name_concatenation(self):
		"""Tests the concatentation function returns expected results for okay and too-long names.
		"""
		name_okay = "a" * 15
		name_conc = name_okay + "a"

		self.assertEqual(Collage().cut_long_artist_name(name_okay), name_okay)
		self.assertEqual(Collage().cut_long_artist_name(name_conc), "aaaaaaaaaaaa...")
