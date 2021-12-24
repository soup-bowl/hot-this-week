FROM python:3

WORKDIR /opt/app

COPY htw                htw
COPY assets             assets
COPY requirements.txt   requirements.txt

RUN pip install --no-cache-dir -r requirements.txt

ENTRYPOINT [ "python", "-m", "htw" ]
