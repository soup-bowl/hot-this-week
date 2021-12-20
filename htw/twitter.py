
def compose_tweet(lfm_collection):
    message = "\u1F4BF my week with #lastfm:\n"
    for artist in lfm_collection:
        message = message + "%s (%s)\n" % (artist['name'], artist['plays'])
    return message