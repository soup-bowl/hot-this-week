from urllib3 import PoolManager

class lfm(object):
    def __init__(self, key, secret = ''):
        self.key    = key
        self.secret = secret

        self.url    = "http://ws.audioscrobbler.com/2.0/?api_key=%s&format=%s&method=%s&user=%s"
        self.pm     = PoolManager()

    def get_top_artists(self, username):
        resp = http.request( 'GET', self.craft_request_url( 'user.gettopartists', username ) )
        print(resp.status)
    
    def craft_request_url(self, endpoint, user):
        return self.url % ( self.key, 'json', endpoint, user ) 
