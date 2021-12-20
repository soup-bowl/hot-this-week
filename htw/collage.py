from PIL import Image, ImageEnhance
from urllib3 import PoolManager
from os.path import realpath
import io, string, random

class collage(object):
	def __init__(self):
		self.pm = PoolManager()

	def new(self, lfm_collection):
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

		rangen   = ''.join(random.choices(string.ascii_letters + string.digits, k=5))
		filename = 'sbimg_%s.png' % rangen
		main.save(filename)

		return realpath(filename)
