"""
Contains the communication class for interacting with Bluesky.

A lot of this has been derrived from the Bluesky Cookbook:
https://github.com/bluesky-social/cookbook/blob/main/python-bsky-post/create_bsky_post.py#L326
"""

from datetime import datetime, timezone
import requests

class Bluesky():
	"""Contains the communication class for interacting with Bluesky.

	Args:
		url (str): The Bluesky instance (https://bsky.app).
		handle (str): The username.
		application_password (str): The users' app password.
	"""
	def __init__(self, url, handle, application_password):
		self._api_url = url
		self._handle = handle
		self._app_pass = application_password
		self._authentication = None

	def login(self):
		"""Login to the Bluesky instance.
		"""
		resp = requests.post(
			f"{self._api_url}/xrpc/com.atproto.server.createSession",
			timeout=10,
			json={"identifier": self._handle, "password": self._app_pass},
		)
		resp.raise_for_status()

		if resp.status_code == 200:
			self._authentication = resp.json()
			return True

		return False

	def upload_file(self, filename, img_bytes):
		"""Upload file to Bluesky.

		Args:
			filename (str): Filename.
			img_bytes (int): Filesize.
		"""
		suffix = filename.split(".")[-1].lower()
		mimetype = "application/octet-stream"
		if suffix in ["png"]:
			mimetype = "image/png"
		elif suffix in ["jpeg", "jpg"]:
			mimetype = "image/jpeg"
		elif suffix in ["webp"]:
			mimetype = "image/webp"

		# WARNING: a non-naive implementation would strip EXIF metadata from JPEG files here by default
		resp = requests.post(
			f"{self._api_url}/xrpc/com.atproto.repo.uploadBlob",
			timeout=10,
			headers={
				"Content-Type": mimetype,
				"Authorization": "Bearer " + self._authentication["accessJwt"],
			},
			data=img_bytes,
		)
		resp.raise_for_status()
		return resp.json()["blob"]

	def upload_image(self, image):
		"""Upload file to Bluesky.

		Args:
			image (str): Image to upload.
		"""
		images = []
		with open(image, "rb") as f:
			img_bytes = f.read()

		blob = self.upload_file(image, img_bytes)
		images.append({"alt": "", "image": blob})
		return {
			"$type": "app.bsky.embed.images",
			"images": images,
		}

	def post(self, message, image):
		"""Posts a message to the Bluesky.

		Args:
			message (str): Message contents.
			image (str): Filesystem location of the collage to attach.
		"""
		self.login()

		file = self.upload_image(image)

		post = {
			"$type": "app.bsky.feed.post",
			"text": message,
			"createdAt": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
			"embed": file,
		}

		resp = requests.post(
			f"{self._api_url}/xrpc/com.atproto.repo.createRecord",
			timeout=10,
			headers={"Authorization": "Bearer " + self._authentication["accessJwt"]},
			json={
				"repo": self._authentication["did"],
				"collection": "app.bsky.feed.post",
				"record": post,
			},
		)
		resp.raise_for_status()

		return True
