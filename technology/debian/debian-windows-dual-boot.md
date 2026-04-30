# Windows Dual-boot Debian

## On Windows 11

`Win + R` → `msinfo32`. Look for:

- BIOS Mode.
- Secure Boot State.

Disable Fast Startup: `Control Panel` → `Power Options` → `Choose what the power buttons do` → Disable `Turn on fast startup`.

Shrink Windows partition first: `Win + R` → `diskmgmt.msc` → Right-click on the C: drive → Shrink Volume.

## Debian

[Download Debian](https://www.debian.org/distrib/) → "iso-cd" → "netinst.iso".

Rufus:

- Partition scheme: `GPT`.
- Target system: `UEFI`
- File system: `FAT32`.
