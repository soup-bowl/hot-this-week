"""
Contains the communication class for composing messages.
"""

from htw.providers.lfm import LFMPeriod

def compose_tweet(lfm_collection, name, period):
	"""Compose tweet message contents.

	Args:
		lfm_collection ([type]): Last.fm user data collection.
		name (str): Last.fm username.
		period (LFMPeriod): The period to be displayed.

	Returns:
		[str]: Message contents.
	"""
	message = f"\U0001F4BF {_first_label(period)}\n"
	for artist in lfm_collection:
		message = message + f"{artist['name']} ({artist['plays']})\n"
	message = message + f"https://www.last.fm/user/{name}"
	return message

def _first_label(period):
	"""Produces the introductionary label.

	Args:
		period (LFMPeriod): The period to be displayed.

	Returns:
		[str]: The introductionary label.
	"""
	if period is LFMPeriod.ALL:
		return "My entire #lastfm:"

	period_string = ""
	if period is LFMPeriod.MONTH:
		period_string = "month"
	elif period is LFMPeriod.QUARTER:
		period_string = "quarter"
	elif period is LFMPeriod.HALFYEAR:
		period_string = "half-year"
	elif period is LFMPeriod.YEAR:
		period_string = "year"
	else:
		period_string = "week"

	return f"My {period_string} with #lastfm:"
