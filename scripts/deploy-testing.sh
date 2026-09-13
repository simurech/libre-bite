#!/usr/bin/env bash
# Synchronisiert den Arbeitsbaum auf das Testsystem testing.libre-bite.org.
# Nur für Entwicklung – die Datei ist über .distignore vom Release-ZIP ausgeschlossen.
set -euo pipefail

SSH_HOST="u751775594@89.116.147.16"
SSH_PORT="65002"
WP_PATH="/home/u751775594/domains/testing.libre-bite.org/public_html"
PLUGIN_DIR="$WP_PATH/wp-content/plugins/librebite-pro"
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "→ Synchronisiere $REPO_DIR → $PLUGIN_DIR"
rsync -az --delete \
	--exclude-from="$REPO_DIR/.distignore" \
	--exclude='scripts' \
	--exclude='.git' \
	-e "ssh -p $SSH_PORT" \
	"$REPO_DIR/" "$SSH_HOST:$PLUGIN_DIR/"

echo "→ Leere Objekt-Cache und lade Plugin-Version"
ssh -p "$SSH_PORT" "$SSH_HOST" "cd $WP_PATH && wp cache flush --allow-root >/dev/null 2>&1; wp eval 'echo \"LBITE_VERSION=\".LBITE_VERSION.\"\n\";' --allow-root 2>&1 | tail -1"

echo "✓ Deploy abgeschlossen"
