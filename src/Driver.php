<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

/**
 * Which transport the help desk is running on.
 *
 * Read from configuration on every call rather than cached, because the base
 * package binds its repositories the same way: deferred, so a test can switch
 * drivers between examples and a cached config still wins.
 */
final class Driver
{
    /**
     * A satellite reaching a central help desk over the signed API, with no
     * help desk tables of its own.
     */
    public static function isApi(): bool
    {
        return config('help-desk.driver', 'database') === 'api';
    }

    /**
     * The attachment cap the current transport can actually carry, in KB.
     *
     * Over the API a file travels base64 encoded inside the JSON body, so it
     * grows by a third and is held in memory on both ends — the cap is lower
     * there on purpose. The central application enforces it too, but failing
     * in the browser beats failing after the upload.
     */
    public static function maxAttachmentSize(): int
    {
        $size = self::isApi()
            ? config('help-desk.api.max_inline_attachment', 2048)
            : config('help-desk.ticket.max_file_size', 10240);

        return is_numeric($size) ? (int) $size : 10240;
    }
}
