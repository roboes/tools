## File Management
# Last update: 2026-09-16


# Start Bash (Unix Shell)
[ -z "${BASH}" ] && exec bash


# Install rename
# sudo apt install -y rename


# Settings
if grep -qi microsoft /proc/version; then
    cd "/mnt/c/Users/${USER}/Downloads"
else
    cd "${HOME}/Downloads"
fi


## find
# To disable recursive: add "-maxdepth 0" after "find ."


# List hidden files (recursive)
find . -type f -iname ".*" -print # -delete

# List Thumbs.db files (recursive)
find . -type f -iname "Thumbs.db" -print # -delete

# List empty folders and subfolders (recursive)
find . -type d -empty -print # -delete

# Move files from folders and subfolders to new folder
# find . -type f -exec mv --backup=numbered --target-directory="Output Folder" {} +


# Rename files and folders

## Define an array of patterns and replacements
patterns=(
    '\xC2\xA0' ' ' # Remove UTF-8-encoded non-breaking space
    '\xA0' ' ' # Remove non-breaking space (raw byte, rarer case)
    '[\\/*?"<>|]' ' ' # Remove Windows-forbidden characters
    '\s{2,}' ' ' # Replace multiple spaces with a single space
    '^ ' '' # Remove leading spaces
    ' $' '' # Remove trailing space
    '\.$' '' # Remove trailing dots
    ' (\..*$)' '$1' # Remove spaces before file extension
)

# patterns=(
    # '([0-9]{4})\.([0-9]{2})\.([0-9]{2})' '${1}-${2}-${3}'  # Rename from YYYY.MM.DD to YYYY-MM-DD
    # '([0-9]{2})\.([0-9]{2})\.([0-9]{4})' '${3}-${2}-${1}'  # Rename from DD.MM.YYYY to YYYY-MM-DD
    # '([0-9]{4})\.([0-9]{2})' '${1}-${2}'  # Rename from YYYY.MM to YYYY-MM
    # '([0-9]{4})([0-9]{2})([0-9]{2})' '${1}-${2}-${3}'  # Rename from YYYYMMDD to YYYY-MM-DD
# )

# patterns=(
    # '^[0-9]{4}-[0-9]{2}-[0-9]{2}_Kontoauszug_([0-9])_([0-9]{4})_vom_.*(\.pdf)' '${2}-0${1}${3}'  # Extract M_YYYY and rearrange to YYYY-0M
    # '^[0-9]{4}-[0-9]{2}-[0-9]{2}_Kontoauszug_([0-9]{2})_([0-9]{4})_vom_.*(\.pdf)' '${2}-${1}${3}'  # Extract MM_YYYY and rearrange to YYYY-MM
# )


files_rename() {
    local path="." recursive=0 apply=0 entries="f,d" patterns_arrayname="" arg
    local args=("$@") i=0
    while [[ $i -lt ${#args[@]} ]]; do
        arg="${args[$i]}"
        case "$arg" in
            --recursive)  recursive=1 ;;
            --apply)      apply=1 ;;
            --path)       i=$((i + 1)); path="${args[$i]}" ;;
            --entries)    i=$((i + 1)); entries="${args[$i]}" ;;
            --patterns)   i=$((i + 1)); patterns_arrayname="${args[$i]}" ;;
            *)            path="$arg" ;;
        esac
        i=$((i + 1))
    done

    if [[ -z "$patterns_arrayname" ]]; then
        echo "files_rename: --patterns <arrayname> is required (no built-in patterns)" >&2
        return 1
    fi
    if ! declare -p "$patterns_arrayname" &>/dev/null; then
        echo "files_rename: '$patterns_arrayname' is not a defined array" >&2
        return 1
    fi
    local -n _files_rename_ref="$patterns_arrayname"
    local patterns=("${_files_rename_ref[@]}")

    local perl_expr='' j
    for ((j=0; j<${#patterns[@]}; j+=2)); do
        perl_expr+="s#${patterns[j]}#${patterns[j+1]}#g;"
    done

    local entries_norm="" token IFS=','
    for token in $entries; do
        token="${token#"${token%%[![:space:]]*}"}"
        token="${token%"${token##*[![:space:]]}"}"
        case "$token" in
            file)             entries_norm+="f," ;;
            folder|dir|dirs)  entries_norm+="d," ;;
            *)                entries_norm+="${token}," ;;
        esac
    done
    entries_norm="${entries_norm%,}"
    unset IFS

    local type_flags=(-type "$entries_norm")
    local depth_flags=(-maxdepth 1)
    [[ $recursive -eq 1 ]] && depth_flags=(-depth)
    local rename_flags=(--filename --verbose --nono)
    [[ $apply -eq 1 ]] && rename_flags=(--filename --verbose)

    local out_log err_log
    out_log=$(mktemp) || return 1
    err_log=$(mktemp) || return 1

    find "$path" -mindepth 1 "${depth_flags[@]}" "${type_flags[@]}" \
        -exec rename "${rename_flags[@]}" "$perl_expr" {} + \
        > "$out_log" 2> "$err_log"
    local find_status=$?

    _frn_print_clean() {
        if command -v iconv >/dev/null 2>&1; then
            iconv -f UTF-8 -t UTF-8 -c < "$1" 2>/dev/null || cat "$1"
        else
            cat "$1"
        fi
    }

    if [[ -s "$out_log" ]]; then
        echo "----- Renamed -----"
        _frn_print_clean "$out_log"
    fi

    if [[ -s "$err_log" ]]; then
        echo
        echo "----- Skipped / issues (e.g. name already exists) -----"
        _frn_print_clean "$err_log"
    fi

    rm -f "$out_log" "$err_log"
    unset -f _frn_print_clean

    return "$find_status"
}

files_rename --patterns patterns --path . --recursive # --apply
