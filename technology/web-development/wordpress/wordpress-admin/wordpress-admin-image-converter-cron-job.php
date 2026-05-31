<?php

// WordPress Admin - Convert images to AVIF (cron job)
// Last update: 2026-05-28


// Schedule cron job if not already scheduled
add_action(hook_name: 'init', callback: function (): void {

    if (!wp_next_scheduled(hook: 'cron_job_image_converter_avif', args: [])) {

        // Settings
        $start_datetime = new DateTimeImmutable(datetime: 'today 03:30:00', timezone: wp_timezone());

        wp_schedule_event(timestamp: $start_datetime->getTimestamp(), recurrence: 'daily', hook: 'cron_job_image_converter_avif', args: [], wp_error: false);
    }

}, priority: 10, accepted_args: 0);


add_action(hook_name: 'cron_job_image_converter_avif', callback: 'image_converter_avif', priority: 10, accepted_args: 0);


function image_converter_avif(bool $recursive = false, bool $force_all = true, int|bool $limit = false, bool $replace_files = false): void
{
    if (! function_exists('imageavif')) {
        error_log('image_converter_avif: PHP GD library is missing or does not support AVIF processing.');
        return;
    }

    // If running without a limit, raise execution threshold to 15 minutes to avoid execution cuts
    if ($limit === false || $limit > 200) {
        @set_time_limit(900);
    }

    if ($recursive) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(wp_upload_dir()['basedir'], FilesystemIterator::SKIP_DOTS)
        );
    } else {
        $iterator = new DirectoryIterator(wp_upload_dir()['basedir']);
    }

    $converted = 0;

    foreach ($iterator as $file) {
        if ($limit !== false && $converted >= $limit) {
            break;
        }

        if (!$recursive && ($file->isDot() || $file->isDir())) {
            continue;
        }

        $path = $file->getPathname();
        $ext  = strtolower($file->getExtension());

        // Only look at images
        if (! in_array($ext, [ 'jpg', 'jpeg', 'png' ], true)) {
            continue;
        }

        $dest_path = $path . '.avif';

        // Check if file exists, but skip it only if it's healthy OR if $replace_files is false.
        // If the file exists but has 0 bytes, we force a re-run regardless.
        if (file_exists($dest_path) && filesize($dest_path) > 0 && !$replace_files) {
            continue;
        }

        // Only process files from last 48h (catches new uploads since last run)
        if (!$force_all) {
            if (filemtime($path) < time() - 172800) {
                continue;
            }
        }

        $image = null;

        if ($ext === 'png') {
            $image = @imagecreatefrompng(filename: $path);
            if ($image) {
                // Convert palette/indexed PNGs to truecolor - required by imageavif()
                if (!imageistruecolor(image: $image)) {
                    imagepalettetotruecolor(image: $image);
                }
                imagealphablending(image: $image, enable: false);
                imagesavealpha(image: $image, enable: true);
            }
        } else {
            $image = @imagecreatefromjpeg(filename: $path);
        }

        // If PHP successfully opened the file source
        if ($image) {
            // Retain alpha transparency channels stably for AVIF
            if ($ext === 'png') {
                // Keep alphablending ENABLED (true) so GD composites transparency accurately during AVIF compression matrices
                imagealphablending(image: $image, enable: true);
                imagesavealpha(image: $image, enable: true);
            }

            // Convert and write the file natively to disk
            $success = @imageavif(image: $image, file: $dest_path, quality: 70);

            if ($success && file_exists($dest_path) && filesize($dest_path) > 0) {
                error_log("image_converter_avif: Converted {$dest_path}");
                $converted++;
            } else {
                // Clean up any failed 0-byte file attempts so they do not clog Nginx try_files
                if (file_exists($dest_path) && filesize($dest_path) === 0) {
                    @unlink($dest_path);
                    error_log("image_converter_avif: Failed converting PNG to AVIF, deleted 0-byte file: {$path}");
                }
            }

        }
    }

    if ($converted > 0) {
        error_log("image_converter_avif: Converted {$converted} images");
    }

}
