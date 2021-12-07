# 🔥🎶🐦 soup-bowl's Hot This Week
<p align="center">
 <a href="https://www.codefactor.io/repository/github/soup-bowl/hot-this-week">
  <img src="https://www.codefactor.io/repository/github/soup-bowl/hot-this-week/badge" alt="CodeFactor"/>
 </a>
 <a href="https://github.com/soup-bowl/hot-this-week/actions/workflows/docker-image-main.yml">
  <img src="https://github.com/soup-bowl/hot-this-week/actions/workflows/docker-image-main.yml/badge.svg" alt="Compile mainline container"/>
 </a>
</p>

<p align="center">
 <img src="https://user-images.githubusercontent.com/11209477/145074448-0894535e-5438-4fed-98d3-975584994590.png" alt="A view of a tweet showing a 5-picture collage, 1 larger image on the left and 4 small images in a grid orientation"/>
</p>

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
 
### 🐋 Docker/Podman
```
docker run -v /path/to/localsystem/config.json:/opt/app/config.json ghcr.io/soup-bowl/hot-this-week:latest
```
Append `-h` right at the end to see usage instructions. Change 'latest' for 'edge' to get the latest development version (possibly unstable).

### Natively

The project depends on having the **GD** library and PHP extension installed, and don't forget to run `composer install` to grab the project dependencies.
 
See the [configuration example](/config.json.example) to see how to setup the tool. The following configurations are **required** for this to work in your **own environment**:

* last.fm: global `LASTFM_KEY`, `LASTFM_SECRET`, and per-user `LASTFM_SCAN_USER_NAME`.
  * You can [register an API key here](https://www.last.fm/api/account/create).
* Twitter: global `TWITTER_CONSUMER_KEY`, `TWITTER_CONSUMER_SECRET`, and per-user `TWITTER_ACCESS_TOKEN`, and `TWITTER_ACCESS_TOKEN`.
  * You can [register for Twitter API keys here](https://developer.twitter.com/en/portal/dashboard).
  * Ensure your access token has **read and write** capabilities (default is read only).

With everything set, you can just run `php main.php` from CLI, and all the magic should happen. You can see the optional arguments by running `php main.php --help`.

[dandelionmood/lastfm]: https://github.com/dandelionmood/php-lastfm
[abraham/twitteroauth]: https://twitteroauth.com/
[tzsk/collage]: https://github.com/tzsk/collage
[intervention]: http://image.intervention.io/
