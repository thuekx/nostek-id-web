#!/bin/bash
# Copy the Nostek theme and plugin into the WordPress install that matches
# the checked-out branch. Run by cPanel (.cpanel.yml) from the repo root.
set -euo pipefail

branch="$(git rev-parse --abbrev-ref HEAD)"
case "$branch" in
	main)       target="${NOSTEK_STAGING_ROOT:-$HOME/staging.nostek.id}" ;;
	production) target="${NOSTEK_PRODUCTION_ROOT:-$HOME/public_html}" ;;
	*)
		echo "REFUSE: branch '$branch' has no deploy target (use main or production); nothing done" >&2
		exit 1
		;;
esac

if [ ! -f "$target/wp-config.php" ] || [ ! -d "$target/wp-content" ]; then
	echo "REFUSE: $target is not a WordPress root (no wp-config.php); nothing done" >&2
	exit 1
fi

stamp="$(date +%Y%m%d-%H%M%S)"
keep="$HOME/nostek-deploy-previous/$stamp"
mkdir -p "$keep"

for item in themes/nostek plugins/nostek-core; do
	dest="$target/wp-content/$item"
	if [ -d "$dest" ]; then
		# Keep the previous version for rollback instead of deleting it.
		mkdir -p "$keep/$(dirname "$item")"
		cp -a "$dest" "$keep/$item"
	fi
	mkdir -p "$dest"
	/bin/rsync -a --delete "$item/" "$dest/"
	echo "DEPLOYED $item -> $dest"
done

echo "PASS deploy $branch $(git rev-parse --short HEAD) -> $target (previous copy: $keep)"
