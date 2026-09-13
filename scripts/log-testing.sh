#!/usr/bin/env bash
# Zeigt nur die plugin-relevanten Zeilen aus debug.log.
# Das WP-CLI-Phar erzeugt auf PHP 8.5 massenhaft eigene Deprecations,
# die nichts mit Libre Bite zu tun haben und sonst alles zudecken.
set -euo pipefail
SSH_HOST="u751775594@89.116.147.16"; SSH_PORT="65002"
WP_PATH="/home/u751775594/domains/testing.libre-bite.org/public_html"
case "${1:-show}" in
	clear) ssh -p "$SSH_PORT" "$SSH_HOST" ": > $WP_PATH/wp-content/debug.log" && echo "✓ debug.log geleert" ;;
	show)  ssh -p "$SSH_PORT" "$SSH_HOST" "grep -v 'wp-cli-2\|php-cli-tools' $WP_PATH/wp-content/debug.log 2>/dev/null | tail -${2:-40}" ;;
	count) ssh -p "$SSH_PORT" "$SSH_HOST" "grep -v 'wp-cli-2\|php-cli-tools' $WP_PATH/wp-content/debug.log 2>/dev/null | wc -l" ;;
esac
