from PIL import Image, ImageEnhance, ImageDraw, ImageFont
from urllib3 import PoolManager
from os.path import realpath
import io, string, random, tempfile, shutil

class collage(object):
	def __init__(self):
		self.pm         = PoolManager()
		self.namelength = 15
		self.tempdir    = tempfile.mkdtemp()

	def new(self, lfm_collection, keep_collage = False):
		"""Creates a new collage image.

		Args:
			lfm_collection ([type]): Last.fm user data collection.

		Returns:
			[str]: Collage image path.
		"""
		main  = Image.new("RGB", (800, 400))
		coll1 = Image.new("RGB", (400, 400))

		img0 = Image.open( io.BytesIO( self.pm.request( 'GET', lfm_collection[0]['image'] ).data ) ).resize((400,400), Image.ANTIALIAS)
		img1 = Image.open( io.BytesIO( self.pm.request( 'GET', lfm_collection[1]['image'] ).data ) ).resize((200,200), Image.ANTIALIAS)
		img2 = Image.open( io.BytesIO( self.pm.request( 'GET', lfm_collection[2]['image'] ).data ) ).resize((200,200), Image.ANTIALIAS)
		img3 = Image.open( io.BytesIO( self.pm.request( 'GET', lfm_collection[3]['image'] ).data ) ).resize((200,200), Image.ANTIALIAS)
		img4 = Image.open( io.BytesIO( self.pm.request( 'GET', lfm_collection[4]['image'] ).data ) ).resize((200,200), Image.ANTIALIAS)

		coll1.paste(img1, (0,0))
		coll1.paste(img2, (200,0))
		coll1.paste(img3, (0,200))
		coll1.paste(img4, (200,200))

		main.paste(img0, (0,0))
		main.paste(coll1, (400,0))

		draw  = ImageDraw.Draw(main)
		mfont = ImageFont.truetype("ubuntu.ttf", 42)
		sfont = ImageFont.truetype("ubuntu.ttf", 24)
		self.render_text(draw, (395,395), lfm_collection[0]['name'], mfont)
		self.render_text(draw, (595,195), lfm_collection[1]['name'], sfont)
		self.render_text(draw, (795,195), lfm_collection[2]['name'], sfont)
		self.render_text(draw, (595,395), lfm_collection[3]['name'], sfont)
		self.render_text(draw, (795,395), lfm_collection[4]['name'], sfont)

		rangen   = ''.join(random.choices(string.ascii_letters + string.digits, k=5))
		filename = 'sbimg_%s.png' % rangen
		storage  = ''
		if keep_collage:
			storage = filename
		else:
			storage = self.tempdir + '/' + filename

		main.save(storage)
		return realpath(storage)

	def render_text(self, draw, pos, content, font):
		"""Renders text over the image.

		Args:
			draw (ImageDraw): ImageDraw instance to add to.
			pos (tuple): Position to draw text.
			content (str): Text to be written to the image.
			font (ImageFont): Font to utilise.
		"""
		draw.text(pos, self.cut_long_artist_name( content ), font=font, align='right', fill='black', anchor='rb' )
		draw.text(((pos[0] - 1),(pos[1] - 1)), self.cut_long_artist_name( content ), font=font, align='right', fill='white', anchor='rb' )

	def cut_long_artist_name(self, name):
		"""Concatenates a long artist name to avoid image overlapping (dictated by self.namelength).

		Args:
			name (str): Artist name.

		Returns:
			[str]: Artist name, concatenated with an elipsis if over a predetermined length.
		"""
		if len(name) > self.namelength:
			return name[0:(self.namelength - 3)] + '...'
		else:
			return name

	def cleanup(self):
		"""Cleans up the temporary directory used for generating the image.
		"""
		shutil.rmtree(self.tempdir)
