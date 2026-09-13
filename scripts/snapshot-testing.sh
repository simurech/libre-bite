#!/usr/bin/env bash
# Datenbank-Schnappschüsse für das Testsystem.
#   ./snapshot-testing.sh save <name>     – Schnappschuss anlegen
#   ./snapshot-testing.sh restore <name>  – Schnappschuss zurückspielen
#   ./snapshot-testing.sh list            – vorhandene Schnappschüsse
# Nutzt mysqldump direkt: `wp db export` scheitert auf diesem Host am
# fehlenden PROCESS-Privileg (exit 255 ohne Ausgabe).
set -euo pipefail

SSH_HOST="u751775594@89.116.147.16"
SSH_PORT="65002"
WP_PATH="/home/u751775594/domains/testing.libre-bite.org/public_html"
SNAP_DIR="/home/u751775594/lbite-snapshots"

ACTION="${1:-}"
NAME="${2:-}"

remote() { ssh -p "$SSH_PORT" "$SSH_HOST" "$@"; }

case "$ACTION" in
	save)
		[ -n "$NAME" ] || { echo "Name fehlt" >&2; exit 1; }
		remote "mkdir -p $SNAP_DIR && cd $WP_PATH && \
			DB=\$(wp config get DB_NAME --allow-root) && \
			U=\$(wp config get DB_USER --allow-root) && \
			P=\$(wp config get DB_PASSWORD --allow-root) && \
			H=\$(wp config get DB_HOST --allow-root) && \
			mysqldump --no-tablespaces --single-transaction -h\"\$H\" -u\"\$U\" -p\"\$P\" \"\$DB\" > $SNAP_DIR/$NAME.sql && \
			ls -lh $SNAP_DIR/$NAME.sql"
		echo "✓ Schnappschuss '$NAME' gespeichert"
		;;
	restore)
		[ -n "$NAME" ] || { echo "Name fehlt" >&2; exit 1; }
		remote "cd $WP_PATH && \
			DB=\$(wp config get DB_NAME --allow-root) && \
			U=\$(wp config get DB_USER --allow-root) && \
			P=\$(wp config get DB_PASSWORD --allow-root) && \
			H=\$(wp config get DB_HOST --allow-root) && \
			mysql -h\"\$H\" -u\"\$U\" -p\"\$P\" \"\$DB\" < $SNAP_DIR/$NAME.sql && \
			wp cache flush --allow-root >/dev/null 2>&1; echo restored"
		echo "✓ Schnappschuss '$NAME' zurückgespielt"
		;;
	list)
		remote "ls -lh $SNAP_DIR/ 2>/dev/null || echo 'noch keine Schnappschüsse'"
		;;
	*)
		echo "Aufruf: $0 {save|restore|list} [name]" >&2
		exit 1
		;;
esac
