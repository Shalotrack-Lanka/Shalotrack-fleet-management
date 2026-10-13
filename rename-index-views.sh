#!/usr/bin/env bash
# Renames resources/views/<folder>/index.blade.php -> <folder>/<folder>.blade.php
# and updates every view('<folder>.index') reference to view('<folder>.<folder>').
# Run from the Fleet repo root. Safe to re-run. Does NOT commit.
# Route names (route('trips.index'), ->name('...')) are NOT views and are left alone.
set -euo pipefail

cd "$(dirname "$0")"
[ -f artisan ] || { echo "Run this from the Fleet repo root (where artisan is)."; exit 1; }

FOLDERS=(complaints dashboard emergency-contacts geofences profile renewals reports saved-places sharing stats trips vehicles)
USE_GIT=0; git rev-parse --is-inside-work-tree >/dev/null 2>&1 && USE_GIT=1

for f in "${FOLDERS[@]}"; do
  old="resources/views/$f/index.blade.php"
  new="resources/views/$f/$f.blade.php"
  if [ -f "$old" ]; then
    [ -e "$new" ] && { echo "ABORT: $new already exists"; exit 1; }
    if [ "$USE_GIT" = 1 ]; then git mv "$old" "$new"; else mv "$old" "$new"; fi
    echo "renamed  $old -> $new"
  fi
  # view('folder.index' ...) / view("folder.index" ...) / assertViewIs / @include / @extends
  for dir in app routes tests resources/views; do
    [ -d "$dir" ] || continue
    grep -rlE "(view|assertViewIs|@include|@extends|@includeIf|@each)\(\s*['\"]$f\.index['\"]" "$dir" 2>/dev/null | while read -r file; do
      sed -i -E "s/((view|assertViewIs|@include|@extends|@includeIf|@each)\(\s*['\"])$f\.index(['\"])/\1$f.$f\3/g" "$file"
      echo "updated  $file"
    done || true
  done
done

# Safety net: nothing may still point at a renamed view.
LEFT=$(grep -rnE "(view|assertViewIs|@include|@extends)\(\s*['\"]($(IFS='|'; echo "${FOLDERS[*]}"))\.index['\"]" app routes tests resources/views 2>/dev/null || true)
if [ -n "$LEFT" ]; then echo "STILL REFERENCED:"; echo "$LEFT"; exit 1; fi

php artisan view:clear >/dev/null 2>&1 || true
echo "Done. Now run: php artisan test   (or vendor/bin/phpunit) and open each page once."
