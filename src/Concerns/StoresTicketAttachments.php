<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketComment;
use RuntimeException;

/**
 * Hands the files a Filament upload left on disk to the attachment repository.
 *
 * Three copies of this block used to live in CreateTicket, the comment trait
 * and the User ViewTicket, each moving the file and inserting the row by hand.
 * That worked on one transport only: a satellite has no disk to move a file to
 * and no table to insert into, and the repository is what knows the difference.
 */
trait StoresTicketAttachments
{
    /**
     * @param  array<int, string>  $paths  Disk-relative paths, as FileUpload leaves them.
     */
    protected function storeTicketAttachments(Ticket $ticket, array $paths, Model $uploadedBy, ?TicketComment $comment = null): void
    {
        if ($paths === []) {
            return;
        }

        $disk = config('help-desk.ticket.attachment_disk', 'local');
        $storage = Storage::disk($disk);

        foreach ($paths as $path) {
            $contents = $storage->get($path);

            if ($contents === null) {
                continue;
            }

            // storeFromPath() reads a real filesystem path — it has to, since
            // the API driver base64-encodes the bytes into the signed body and
            // the database driver writes them to its own disk. The upload sits
            // on a Laravel disk instead, which may not be local at all, so the
            // bytes are staged through a temporary file rather than assuming
            // Storage::path() means anything.
            //
            // The staged name keeps the original basename because the database
            // driver derives the stored path from it; a tempnam() handle would
            // file every attachment away as a .tmp.
            $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::random(8).'-'.basename($path);

            try {
                // A full temporary filesystem writes what fits and reports it
                // rather than failing, so a short write has to be caught here.
                // Storing the truncated file would hand the requester a
                // corrupted attachment and nothing would ever say so.
                if (file_put_contents($temporaryPath, $contents) !== strlen($contents)) {
                    throw new RuntimeException(
                        "The attachment [{$path}] could not be staged in full for upload."
                    );
                }

                HelpDesk::attachments()->storeFromPath(
                    ticket: $ticket,
                    filePath: $temporaryPath,
                    fileName: basename($path),
                    mimeType: $storage->mimeType($path) ?: 'application/octet-stream',
                    fileSize: strlen($contents),
                    uploadedBy: $uploadedBy,
                    comment: $comment,
                );
            } finally {
                @unlink($temporaryPath);
            }

            // The upload was a staging copy. Leaving it behind would grow the
            // disk by a full copy of every attachment ever sent, which is what
            // the old Storage::move() avoided by never making one.
            $storage->delete($path);
        }
    }
}
