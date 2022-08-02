"""
Creates and modifies collage images.
"""

import io
import string
import random
import tempfile
import shutil
from os.path import realpath
from PIL import Image, ImageDraw, ImageFont
from urllib3 import PoolManager

class Collage():
	"""Creates and modifies collage images.
	"""

	def __init__(self):
		self.pool = PoolManager()
		self.fontface = "assets/ubuntu.ttf"
		self.namelength = 15
		self.tempdir = tempfile.mkdtemp()

	def new(self, lfm_collection, keep_collage = False):
		"""Creates a new collage image.

		Args:
			lfm_collection ([type]): Last.fm user data collection.

		Returns:
			[str]: Collage image path.
		"""
		main = Image.new("RGB", (800, 400))
		coll = Image.new("RGB", (400, 400))

		img = [
			Image.open( self.obtain_picture( lfm_collection[0]['image'] ) ).resize((400,400), Image.ANTIALIAS),
			Image.open( self.obtain_picture( lfm_collection[1]['image'] ) ).resize((200,200), Image.ANTIALIAS),
			Image.open( self.obtain_picture( lfm_collection[2]['image'] ) ).resize((200,200), Image.ANTIALIAS),
			Image.open( self.obtain_picture( lfm_collection[3]['image'] ) ).resize((200,200), Image.ANTIALIAS),
			Image.open( self.obtain_picture( lfm_collection[4]['image'] ) ).resize((200,200), Image.ANTIALIAS)
		]

		vectors = [(0,0), (200, 0), (0,200), (200,200)]
		for index in range(4):
			coll.paste(img[(index + 1)], vectors[index])

		main.paste(img[0], (0,0))
		main.paste(coll, (400,0))

		draw = ImageDraw.Draw(main)
		mfont = ImageFont.truetype(self.fontface, 42)
		sfont = ImageFont.truetype(self.fontface, 24)
		self.render_text(draw, (395,395), lfm_collection[0]['name'], mfont)
		self.render_text(draw, (595,195), lfm_collection[1]['name'], sfont)
		self.render_text(draw, (795,195), lfm_collection[2]['name'], sfont)
		self.render_text(draw, (595,395), lfm_collection[3]['name'], sfont)
		self.render_text(draw, (795,395), lfm_collection[4]['name'], sfont)

		rangen = ''.join(random.choices(string.ascii_letters + string.digits, k=5))
		filename = f'sbimg_{rangen}.png'
		storage = f'{self.tempdir}/{filename}'

		if keep_collage:
			storage = filename

		main.save(storage)
		return realpath(storage)

	def obtain_picture(self, url):
		"""Requests the image from the Last.fm server.

		Args:
			url (str): the URL to ping the request to.

		Returns:
			[str]: Image contents, or location of default image.
		"""
		pic = self.pool.request( 'GET', url )

		if pic.status == 200:
			return io.BytesIO( pic.data )

		return "assets/blank.png"

	def render_text(self, draw, pos, content, font):
		"""Renders text over the image.

		Args:
			draw (ImageDraw): ImageDraw instance to add to.
			pos (tuple): Position to draw text.
			content (str): Text to be written to the image.
			font (ImageFont): Font to utilise.
		"""
		draw.text(
			pos,
			self.cut_long_artist_name( content ),
			font=font,
			align='right',
			fill='black',
			anchor='rb'
		)

		draw.text(
			((pos[0] - 1),(pos[1] - 1)),
			self.cut_long_artist_name( content ),
			font=font,
			align='right',
			fill='white',
			anchor='rb' )

	def cut_long_artist_name(self, name):
		"""Concatenates a long artist name to avoid image overlapping (dictated by self.namelength).

		Args:
			name (str): Artist name.

		Returns:
			[str]: Artist name, concatenated with an elipsis if over a predetermined length.
		"""
		if len(name) > self.namelength:
			return name[0:(self.namelength - 3)] + '...'
		return name

	def cleanup(self):
		"""Cleans up the temporary directory used for generating the image.
		"""
		shutil.rmtree(self.tempdir)
