<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\Response;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\HelpDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an attachment through the repository instead of the disk.
 *
 * A satellite has no access to the disk the file sits on, so `getUrl()` throws
 * there on purpose. This route asks the repository for the bytes, which means
 * the central application streams them over the signed API on one transport
 * and the local disk answers on the other — with the same URL either way.
 */
final class DownloadTicketAttachmentController
{
    public function __invoke(string $ticket, string $attachment): StreamedResponse
    {
        try {
            $record = HelpDesk::tickets()->findByUuid($ticket);
        } catch (TicketNotFoundException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Under the API driver the endpoint is scoped to the caller, so a
        // ticket belonging to someone else has already come back as a 404 —
        // and the payload carries no requester columns to check anyway. The
        // database driver has no such scope: findByUuid() finds any ticket,
        // so ownership is this route's to enforce.
        if (! Driver::isApi()) {
            $user = Filament::auth()->user();

            abort_unless(
                $record->user_type === $user->getMorphClass()
                    && (string) $record->user_id === (string) $user->getAuthIdentifier(),
                Response::HTTP_NOT_FOUND,
            );
        }

        $file = $record->attachments->firstWhere('uuid', $attachment);

        if ($file === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $contents = HelpDesk::attachments()->contents($file, $ticket);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $file->file_name,
            [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',
                // The bytes and the type both came from whoever uploaded the
                // file, so the browser is told not to look for a second
                // opinion in the content.
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
