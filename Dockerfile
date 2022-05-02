FROM python:3-alpine

WORKDIR /opt/app

COPY htw                htw
COPY assets             assets
COPY requirements.txt   requirements.txt

RUN apk add python3-dev libc-dev zlib-dev jpeg-dev freetype-dev gcc

RUN pip install --no-cache-dir -r requirements.txt

ENTRYPOINT [ "python", "-m", "htw" ]
