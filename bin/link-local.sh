#!/usr/bin/env bash
#
# Symlink this repo's theme and plugin into a LocalWP site, so edits here are
# live in the browser without copying files.
#
# Usage: bin/link-local.sh <local-site-folder-name>
#   e.g. bin/link-local.sh seps   →   ~/Local Sites/seps/app/public/wp-content
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

link() {
	local src="$1" dest="$2"

	if [[ -L "$dest" ]]; then
		ln -sfn "$src" "$dest"
		echo "Updated link: $dest"
	elif [[ -e "$dest" ]]; then
		echo "Refusing to overwrite existing folder: $dest" >&2
		echo "Move or delete it, then re-run." >&2
		exit 1
	else
		ln -s "$src" "$dest"
		echo "Linked: $dest"
	fi
}

link "$repo/wp-content/themes/seps" "$target/themes/seps"
link "$repo/wp-content/plugins/seps-core" "$target/plugins/seps-core"

cat <<'EOF'

Next, in LocalWP click "Open site shell" and run:

  wp plugin install secure-custom-fields --activate
  wp theme activate seps
  wp plugin activate seps-core
  wp seps seed
EOF
