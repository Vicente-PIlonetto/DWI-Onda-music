#!/bin/sh
# CSV parsing lives in Python so quoted commas and UTF-8 are handled correctly.
set -eu
cd "$(dirname "$0")/.."
exec python3 scripts/download_audio.py "$@"
