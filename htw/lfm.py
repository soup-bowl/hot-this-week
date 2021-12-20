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
        resp = self.pm.request( 'GET', self.craft_request_url( 'user.gettopartists', username ) )

        if resp.status == 200:
            data = json.loads( resp.data )
            self.get_artist_picture(data['topartists']['artist'][0]['url'])
    
    def get_artist_picture(self, url):
        resp = self.pm.request( 'GET', url )

        if resp.status == 200:
            content = html.fromstring( resp.data.decode('utf-8') )
            image   = content.xpath('//div[contains(@class,"header-new-background-image")]')
        
            return image[0].attrib['content']
        else:
            return None
    
    def craft_request_url(self, endpoint, user):
        return self.url % ( self.key, 'json', endpoint, user, 'weekly', '5' ) 
