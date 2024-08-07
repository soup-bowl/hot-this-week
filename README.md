# 🔥 soup-bowl's Hot This Week

<p align="center">
 <a href="https://hub.docker.com/r/soupbowl/hot-this-week">
  <img src="https://img.shields.io/docker/pulls/soupbowl/hot-this-week?logo=docker&logoColor=white"/>
 </a>
 <a href="https://www.codefactor.io/repository/github/soup-bowl/hot-this-week">
  <img src="https://www.codefactor.io/repository/github/soup-bowl/hot-this-week/badge" alt="CodeFactor"/>
 </a>
</p>

<p align="center">
 <img src="https://user-images.githubusercontent.com/11209477/214368280-532459b4-eb5d-46f7-82cd-2913d4da1633.png" alt="A view of a Mastodon post showing a 5-picture collage, 1 larger image on the left and 4 small images in a grid orientation"/>
</p>

An experimental bot that posts a rundown of your musical week on Mastodon and/or Bluesky.

## 🤔 What does this do?

This clever bot does the following:
* Phones the last.fm API 📲, exchanges pleasantries, asks how their cat is doing 🐈...
* Oh yeah, "can you tell me what <user> has listened to this week?" 🎶
* API kindly hands over the info (or gives us a whack of the handbag if we have no API key).
* We sneakily scrape the last.fm website for the artist pictures 🤫 (better solutions welcome).
* We do some arts and crafts wizardary 🪄 to formulate a collage picture.
* Lastly, the app phones up Mastodon/Bluesky 📞, asks how their turtle is hanging 🐢, and posts the info and picture.

⭐ Collage is made using the power of Python using [Pillow][p-pillow] for image manipulation, [Mastodon][p-mstdn] and [urllib3][p-urllib3] for API communication, and [lxml][p-lxml] for scraping the internet.

## 🚀 Set-up

**There's no official service or method of usage yet!** Watch this space 👀
 
### 🐋 Docker/Podman

```
docker run -v ${PWD}/config.json:/opt/app/config.json soupbowl/hot-this-week:latest
```

*The command above [uses the Dockerhub image](https://hub.docker.com/r/soupbowl/hot-this-week). You can swap `soupbowl` out for `ghcr.io/soup-bowl` for GitHub container registry.*

Append `-h` right at the end to see usage instructions. Change 'latest' for 'edge' to get the latest development version (possibly unstable).

### Natively

The project depends on having Python 3 installed, and don't forget to run `poetry install` to grab the project dependencies.
 
See the [configuration example](/config.json.example) to see how to setup the tool. The following configurations are **required** for this to work in your **own environment**:

* last.fm: global `LASTFM_KEY`, `LASTFM_SECRET`, and per-user `LASTFM_SCAN_USER_NAME`.
  * You can [register an API key here](https://www.last.fm/api/account/create).
* Mastodon: global `MASTODON_URL`, `MASTODON_KEY` and `MASTODON_SECRET`.
  * Easily register an application in **Preferences**, then **Development**.
  * Permissions needed are **write:media** and **write:statuses**.

With everything set, you can just run `poetry run python -m htw` from CLI, and all the magic should happen. You can see the optional arguments by running `poetry run python -m htw --help`.

This uses **[pytest](https://docs.pytest.org/en/6.2.x/)** for Unit Testing, and **[pylint](https://pypi.org/project/pylint/)** for linting.

[p-pillow]: https://pypi.org/project/Pillow/
[p-mstdn]: https://github.com/halcy/Mastodon.py
[p-urllib3]: https://pypi.org/project/urllib3/
[p-lxml]: https://pypi.org/project/lxml/
