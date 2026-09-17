<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Exceptions;

use RuntimeException;

class UnsupportedDriverException extends RuntimeException
{
    /**
     * A satellite has none of the operator-side data these panels read, and
     * the API serves the requester's own view only. Failing at registration
     * says so once, where a developer is looking; letting the panel register
     * would say it later, one empty page at a time, to whoever opened it.
     */
    public static function operatorPanel(string $panel): self
    {
        return new self(
            "The {$panel} panel cannot run on the [api] help desk driver. It reads operators, "
            .'canned responses, ticket history and every ticket in a department, and works on '
            .'assignment, internal notes and arbitrary status changes — none of which the API '
            .'exposes to a satellite. Register it on an application using the [database] driver, '
            .'and give the satellite FilamentHelpDeskUserPlugin only.'
        );
    }
}
