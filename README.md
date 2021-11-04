# 🔥🎶 Hot this Week on Last.fm Twitter Bot
![image](https://user-images.githubusercontent.com/11209477/140189390-22aef5bd-17cf-4944-95a5-38f8c84ec898.png)


An experimental bot that posts a rundown of your musical week on Twitter.

## 🤔 What does this do?
This clever bot does the following:
* Phones the last.fm API 📲, exchanges pleasantries, asks how their cat is doing 🐈...
* Oh yeah, "can you tell me what <user> has listened to this week?" 🎶
* API kindly hands over the info (or gives us a whack of the handbag if we have no API key).
* We sneakily scrape the last.fm website for the artist pictures 🤫 (better solutions welcome).
* We do some arts and crafts wizardary 🪄 to formulate a collage picture.
* Lastly, the app phones up Twitter 📞, asks how their turtle is hanging 🐢, and posts the info and picture.

⭐ Collage is made using the power of [Collage][tzsk/collage] and [Intervention][intervention], and connections made using [Dandelionmood's Lastfm][dandelionmood/lastfm], and the [Twitter oAuth][abraham/twitteroauth] libaries.

## 🚀 Set-up
**There's no official service or method of usage yet!** Watch this space 👀

The project depends on having the **GD** library and PHP extension installed, and don't forget to run `composer install` to grab the project dependencies.

The following enviroment configuration is **required** for this to work in your **own environment**:

* last.fm: `LASTFM_KEY`, `LASTFM_SECRET`, and `LASTFM_SCAN_USER_NAME`.
  * You can [register an API key here](https://www.last.fm/api/account/create).
* Twitter: `TWITTER_CONSUMER_KEY`, `TWITTER_CONSUMER_SECRET`, `TWITTER_ACCESS_TOKEN`, and `TWITTER_ACCESS_TOKEN`.
  * You can [register for Twitter API keys here](https://developer.twitter.com/en/portal/dashboard).
  * Ensure your app is given **read and write** capabilities (default is read only).

With everything set, you can just run `php main.php` from CLI, and all the magic should happen.

[dandelionmood/lastfm]: https://github.com/dandelionmood/php-lastfm
[abraham/twitteroauth]: https://twitteroauth.com/
[tzsk/collage]: https://github.com/tzsk/collage
[intervention]: http://image.intervention.io/
