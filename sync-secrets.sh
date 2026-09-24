#!/bin/bash
# Materializes the Brevo API key from the centralized store
# (/home/dave/secrets/) into .brevo-api-key -- the copy submit-event.php
# actually reads. PHP runs as www-data, which can't read /home/dave/secrets/,
# so this is the deliberate exception, run manually whenever the key rotates.
# Same pattern as whatsapp-claude-bot's sync-secrets.sh.
set -euo pipefail

DEST="$(dirname "$0")/.brevo-api-key"

sudo cat /home/dave/secrets/ipa_brevo_api_key | sudo tee "$DEST" >/dev/null
sudo chown www-data:www-data "$DEST"
sudo chmod 400 "$DEST"

echo "Synced $DEST from /home/dave/secrets/ipa_brevo_api_key."
