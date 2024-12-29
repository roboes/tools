## Rename paths
# Last update: 2024-12-29


# Function to rename files and folders
rename_paths() {
    local directory="./" targets="files folders" recursive=true pattern="" replacement="" preview=true

    # Parse named arguments
    for arg in "$@"; do
        case $arg in
            directory=*) directory="${arg#*=}" ;;
            targets=*) targets=("${arg#*=}") ;;
            recursive=*) recursive="${arg#*=}" ;;
            pattern=*) pattern="${arg#*=}" ;;
            replacement=*) replacement="${arg#*=}" ;;
            preview=*) preview="${arg#*=}" ;;
        esac
    done

    # Determine the mode (Preview or Execute)
    echo "Mode: $([ "$preview" == "true" ] && echo "Preview" || echo "Execute")"

    # Function to collect and rename paths (files or folders)
    collect_and_rename() {
        local type="$1"
        local label=""
        [[ "$type" == "f" ]] && label="Files"
        [[ "$type" == "d" ]] && label="Folders"

        local paths=()
        local find_opts=("-maxdepth 1")
        [[ "$recursive" == "true" ]] && find_opts=()  # Adjust for recursive or non-recursive

        while IFS= read -r path; do
            local base_name=$(basename "$path")
            local dir_name=$(dirname "$path")

            # Skip the current directory when processing folders
            if [[ "$type" == "d" && "$path" == "./" ]]; then
                continue
            fi

            # Split the file name into name and extension
            local name="${base_name%.*}"
            local extension="${base_name##*.}"
            if [[ "$name" == "$base_name" ]]; then
                extension="" # No extension case
            fi

            # Apply pattern replacement only to the name part
            local new_name=$(echo "$name" | sed -E "s/$pattern/$replacement/g")
            local new_base_name="$new_name${extension:+.$extension}"

            # Skip if the new name is the same as the old name
            if [[ "$base_name" != "$new_base_name" ]]; then
                paths+=("$path -> $dir_name/$new_base_name")
            fi
        done < <(find "$directory" "${find_opts[@]}" -type "$type" -name "*.*")

        # Output and rename paths if necessary
        if [[ ${#paths[@]} -gt 0 ]]; then
            local count=${#paths[@]}
            echo "$label to be renamed: $count"
            for path in "${paths[@]}"; do
                echo "$path"
            done
            [[ "$preview" == "false" ]] && rename_paths_helper "${paths[@]}"
        else
            echo "No ${label,,} to rename."
        fi
    }

    # Helper function to perform renaming
    rename_paths_helper() {
        for path in "$@"; do
            local old_path=$(echo "$path" | awk -F' -> ' '{print $1}')
            local new_path=$(echo "$path" | awk -F' -> ' '{print $2}')
            mv -v "$old_path" "$new_path"
        done
    }

    # Collect and rename files and folders
    [[ " ${targets[@]} " =~ " files " ]] && collect_and_rename "f"
    [[ " ${targets[@]} " =~ " folders " ]] && collect_and_rename "d"
}


# Remove leading, trailing, non-breaking and double spaces from files and folders
rename_paths directory="./" targets="files folders" recursive=true pattern="^ | $" replacement="" preview=true
rename_paths directory="./" targets="files folders" recursive=true pattern="\xA0" replacement="" preview=true
rename_paths directory="./" targets="files folders" recursive=true pattern="  " replacement=" " preview=true


# Rename files and folders from "YYYY.MM.DD" to "YYYY-MM-DD"
rename_paths directory="./" targets="files folders" recursive=true pattern="([0-9]{4})\.([0-9]{2})\.([0-9]{2})" replacement="\1-\2-\3" preview=true


# Rename files and folders from "YYYY.MM" to "YYYY-MM"
rename_paths directory="./" targets="files folders" recursive=true pattern="([0-9]{4})\.([0-9]{2})" replacement="\1-\2" preview=true


# Rename files and folders from "DD.MM.YYYY" to "YYYY-MM-DD"
rename_paths directory="./" targets="files folders" recursive=true pattern="([0-9]{2})\.([0-9]{2})\.([0-9]{4})" replacement="\3-\2-\1" preview=true


# Rename files and folders from "YYYYMMDD" to "YYYY-MM-DD"
rename_paths directory="./" targets="files folders" recursive=true pattern="([0-9]{4})([0-9]{2})([0-9]{2})" replacement="\1-\2-\3" preview=true
