FROM python:3-slim

WORKDIR /opt/app

COPY pyproject.toml poetry.lock ./

RUN pip install --no-cache-dir poetry
RUN poetry install --no-dev --no-interaction --no-ansi

COPY htw     htw
COPY assets  assets

ENTRYPOINT [ "poetry", "run", "python", "-m", "htw" ]

LABEL org.opencontainers.image.title="Soup-bowl's Hot this Week"
LABEL org.opencontainers.image.authors="code@soupbowl.io"
LABEL org.opencontainers.image.source="https://github.com/soup-bowl/hot-this-week"
LABEL org.opencontainers.image.licenses="MIT"
