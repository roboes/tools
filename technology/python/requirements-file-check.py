## Requirements Update Packages
# Last update: 2025-05-27


"""About: Script to check and optionally update installed packages based on requirements.txt."""


###############
# Initial Setup
###############

# Erase all declared global variables
# globals().clear()

# Import packages
import os
import requests
import re


def get_latest_version(*, package_name):
    try:
        response = requests.get(f'https://pypi.org/pypi/{package_name}/json', timeout=5)
        response.raise_for_status()
        return response.json()['info']['version']
    except Exception as error:
        print(f'Could not fetch latest version for {package_name}: {error}')
        return None


def parse_package_and_version(*, line):
    line = line.strip()
    if not line or line.startswith('#'):
        return None, None

    # Regex to extract package and optional version specifier (==,>=,<=,!=,~=,>)
    match = re.match(pattern=r'^([A-Za-z0-9_.\-]+)\s*([<>=!~]{1,2}\s*.+)?$', string=line)
    if match:
        package = match.group(1)
        version = match.group(2)
        if version:
            version = version.replace(' ', '')
        return package, version
    else:
        return line, None


def version_to_compare(*, version_specifier):
    if version_specifier is None:
        return None
    version_number = re.sub(r'^[<>=!~]+', '', version_specifier)
    return version_number


def requirements_file_check(*, requirements_file_path, update_requirements_file=False):
    with open(file=requirements_file_path, encoding='utf-8') as file:
        lines = file.readlines()

    updated_lines = []
    for line in lines:
        stripped = line.strip()
        if not stripped or stripped.startswith('#'):
            updated_lines.append(line)
            continue

        package, version_specifier = parse_package_and_version(line=line)
        if not package:
            updated_lines.append(line)
            continue

        latest_version = get_latest_version(package_name=package)
        if latest_version is None:
            print(f'[{package}] Could not get latest version info.')
            updated_lines.append(line)
            continue

        specified_version = version_to_compare(version_specifier=version_specifier)
        if specified_version is None:
            print(f'[{package}] No version specified in requirements, latest is {latest_version}.')
            updated_lines.append(line)
        elif specified_version != latest_version:
            print(f'[{package}] Outdated in requirements: specified {specified_version} → latest {latest_version}')
            if update_requirements_file:
                updated_line = f'{package}=={latest_version}\n'
                updated_lines.append(updated_line)
            else:
                updated_lines.append(line)
        else:
            # print(f"[{package}] Up to date in requirements ({specified_version})")
            updated_lines.append(line)

    if update_requirements_file:
        with open(file=requirements_file_path, mode='w', encoding='utf-8') as file:
            file.writelines(updated_lines)
        print(f'Requirements file updated at: {requirements_file_path}')


requirements_file_check(requirements_file_path=os.path.join(os.path.expanduser('~'), 'Downloads', 'requirements.txt'), update_requirements_file=True)
