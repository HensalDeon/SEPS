#!/usr/bin/env bash
#
# Copy this repo's theme and plugin into a LocalWP site.
#
# Real copies (not symlinks) are required: LocalWP's "Push to WP Engine" does
# not follow symlinks, so symlinked folders never reach the server.
# Re-run after every change you want to see in Local or push to WP Engine.
#
# Usage: bin/sync-local.sh <local-site-folder-name>
#   e.g. bin/sync-local.sh seps   →   ~/Local Sites/seps/app/public/wp-content
#
set -euo pipefail

site="${1:-}"
if [[ -z "$site" ]]; then
	echo "Usage: $0 <local-site-folder-name>" >&2
	exit 1
fi

repo="$(cd "$(dirname "$0")/.." && pwd)"
target="${LOCAL_SITES_DIR:-$HOME/Local Sites}/$site/app/public/wp-content"

if [[ ! -d "$target" ]]; then
	echo "Not found: $target" >&2
	echo "Create the site in LocalWP first (or set LOCAL_SITES_DIR)." >&2
	exit 1
fi

# Development-only files that must never reach a server.
excludes=(
	--exclude=node_modules/
	--exclude=assets/src/
	--exclude=.*
	--exclude=package.json
	--exclude=package-lock.json
	--exclude=eslint.config.mjs
	--exclude=composer.json
	--exclude=composer.lock
	--exclude=phpcs.xml.dist
)

sync() {
	local src="$1" dest="$2"

	# Replace a symlink from the old link-based setup. (rsync into a symlink
	# would write through it, back into this repo.)
	if [[ -L "$dest" ]]; then
		rm "$dest"
		echo "Removed old symlink: $dest"
	fi

	mkdir -p "$dest"
	rsync -a --delete "${excludes[@]}" "$src/" "$dest/"
	echo "Synced: $dest ($(du -sh "$dest" | cut -f1))"
}

sync "$repo/wp-content/themes/seps" "$target/themes/seps"
sync "$repo/wp-content/plugins/seps-core" "$target/plugins/seps-core"

cat <<'EOF'

First-time setup only: in LocalWP click "Open site shell" and run
  wp plugin install secure-custom-fields --activate
  wp theme activate seps
  wp plugin activate seps-core
  wp seps seed
EOF
