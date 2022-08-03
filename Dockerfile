FROM python:3.8-slim

LABEL org.opencontainers.image.title="Soup-bowl's Hot this Week"
LABEL org.opencontainers.image.authors="code@soupbowl.io"
LABEL org.opencontainers.image.source="https://github.com/soup-bowl/hot-this-week"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /opt/app

COPY htw                htw
COPY assets             assets
COPY pyproject.toml     pyproject.toml
COPY poetry.lock        poetry.lock

RUN pip install --no-cache-dir poetry
RUN poetry install --no-dev --no-interaction --no-ansi

ENTRYPOINT [ "poetry", "run", "python", "-m", "htw" ]
