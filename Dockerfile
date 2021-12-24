FROM python:3

WORKDIR /opt/app

COPY htw                htw
COPY requirements.txt   requirements.txt
COPY ubuntu.ttf         ubuntu.ttf
COPY blank.png          blank.png

RUN pip install --no-cache-dir -r requirements.txt

ENTRYPOINT [ "python", "-m", "htw" ]
