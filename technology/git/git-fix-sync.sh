## Git fix sync
# Last update: 2026-07-10

# Syncs line endings and file permissions of locally modified files to match a remote branch (default: origin/main). Useful when working across platforms (e.g., Windows/Linux) where git may not catch chmod or CRLF drift.

set -euo pipefail

settings_origin="${1:-origin/main}"

echo "=== Fixing line endings and permissions to match $settings_origin ==="

git diff "$settings_origin" --name-only | while IFS= read -r file; do
    [[ -f "$file" ]] || continue

    # Fix line endings
    dos2unix -q "$file" 2>/dev/null || true

    # Fix permissions to match origin
    case "$(git ls-tree "$settings_origin" "$file" 2>/dev/null | awk '{print $1}')" in
        100755) chmod +x "$file" ;;
        100644) chmod -x "$file" ;;
    esac
done

echo ""
echo "=== Final diff ==="
git diff "$settings_origin" --name-only
