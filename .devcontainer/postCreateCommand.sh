#! /bin/bash
pipx install poetry
poetry install
echo ${HTW_CONFIG} | jq > config.json
echo "Run the command by doing executing 'poetry run python -m htw -h'."
