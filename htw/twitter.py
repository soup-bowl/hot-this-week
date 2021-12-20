from twython import Twython

def compose_tweet(lfm_collection):
    """Compose tweet message contents.

    Args:
        lfm_collection ([type]): Last.fm user data collection.

    Returns:
        [str]: Message contents.
    """
    message = "\u1F4BF my week with #lastfm:\n"
    for artist in lfm_collection:
        message = message + "%s (%s)\n" % (artist['name'], artist['plays'])
    return message

def post_to_twitter(tweet, picture, consumer_key, consumer_secret, access_key, access_secret):
    """Posts a message to the Twitter platform.

    Args:
        tweet (str): Message contents.
        picture (str): Filesystem location of the collage to attach.
        consumer_key (str): Twitter application credentials.
        consumer_secret (str): Twitter application credentials.
        access_key (str): Individual user Twitter credentials.
        access_secret (str): Individual user Twitter credentials.
    """
    twitter  = Twython(consumer_key, consumer_secret, access_key, access_secret)
    collage  = open(picture, 'rb')

    response = twitter.upload_media(media=collage)
    twitter.update_status(status=tweet, media_ids=[response['media_id']])
