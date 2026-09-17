<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Exceptions;

use RuntimeException;

class UnsupportedDriverException extends RuntimeException
{
    /**
     * A satellite has no operator, department or canned response data, and no
     * endpoint serves any of it. Failing at registration says so once, where a
     * developer is looking; letting the panel register would say it later, one
     * empty page at a time, to whoever opened it.
     */
    public static function operatorPanel(string $panel): self
    {
        return new self(
            "The {$panel} panel cannot run on the [api] help desk driver. It reads operator, "
            .'department and canned response data that a satellite application does not have and '
            .'the API does not serve. Register it on an application using the [database] driver, '
            .'and give the satellite FilamentHelpDeskUserPlugin only.'
        );
    }
}
