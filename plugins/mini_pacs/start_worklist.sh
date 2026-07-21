#!/usr/bin/env bash

# Modality Worklist (MWL) SCP for Mini PACS mLITE
# AE Title: MINIPACS (Default)
# Port: 104 (Default)

# Get current script directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE_DIR="$(dirname "$(dirname "$DIR")")"
WL_DIR="$BASE_DIR/uploads/pacs/worklist"

# Create directory if not exists
mkdir -p "$WL_DIR"

# AE Title and Port from argument or default
AET=${1:-MINIPACS}
PORT=${2:-10104}

# Ensure DCMTK is in path (common paths)
export PATH=$PATH:/usr/local/bin:/usr/bin:/opt/homebrew/bin

echo "Starting DICOM Modality Worklist (MWL) SCP..."
echo "AE Title: $AET"
echo "Port: $PORT"
echo "Worklist Directory: $WL_DIR"

# Run wlmscpfs in the background
# -v: verbose, -df: data files directory
# --request-file-path: log requests for status tracking
mkdir -p "$WL_DIR/requests"
wlmscpfs -v -df "$WL_DIR" "$PORT" -aet "$AET" --request-file-path "$WL_DIR/requests"
