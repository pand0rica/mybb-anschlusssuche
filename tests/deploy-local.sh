#!/usr/bin/env bash
# Copies the plugin files into the local test forum (D:\h4w\forum). Dev only.
set -euo pipefail
SRC="$(cd "$(dirname "$0")/.." && pwd)"
DST="${1:-/d/h4w/forum}"

cp "$SRC/anschluss.php" "$DST/anschluss.php"
cp "$SRC/inc/plugins/anschlusssuche.php" "$DST/inc/plugins/"
mkdir -p "$DST/inc/plugins/anschlusssuche"
cp -r "$SRC/inc/plugins/anschlusssuche/." "$DST/inc/plugins/anschlusssuche/"
cp "$SRC/admin/modules/tools/anschlusssuche.php" "$DST/admin/modules/tools/"
for lang in english deutsch_du; do
	cp "$SRC/inc/languages/$lang/anschlusssuche.lang.php" "$DST/inc/languages/$lang/"
	cp "$SRC/inc/languages/$lang/admin/anschlusssuche.lang.php" "$DST/inc/languages/$lang/admin/"
done
cp "$SRC/jscripts/anschlusssuche.js" "$DST/jscripts/"
mkdir -p "$DST/tests"
cp "$SRC/tests/cli-plugin.php" "$SRC/tests/cli-selftest.php" "$DST/tests/" 2>/dev/null || cp "$SRC/tests/cli-plugin.php" "$DST/tests/"
echo "deployed to $DST"
