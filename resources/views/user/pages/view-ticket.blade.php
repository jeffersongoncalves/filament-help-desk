<x-filament-panels::page>
    {{-- Ticket Infolist --}}
    <div class="space-y-6">
        {{ $this->infolist }}
    </div>

    {{-- Ticket Attachments (uploaded at creation) --}}
    @if ($this->record->attachments()->whereNull('comment_id')->exists())
        <x-filament::section
            :heading="__('filament-help-desk::filament-help-desk.sections.attachments')"
            icon="heroicon-o-paper-clip"
        >
            <div class="flex flex-wrap gap-2">
                @foreach ($this->record->attachments()->whereNull('comment_id')->get() as $attachment)
                    <a
                        href="{{ $attachment->getUrl() }}"
                        target="_blank"
                        class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10"
                    >
                        <x-heroicon-m-paper-clip class="h-3.5 w-3.5 text-gray-400" />
                        {{ $attachment->file_name }}
                        <span class="text-gray-400">({{ $attachment->getFileSizeForHumans() }})</span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Comments Timeline --}}
    <x-filament::section
        :heading="__('filament-help-desk::filament-help-desk.comments.reply')"
        icon="heroicon-o-chat-bubble-left-right"
    >
        @include('filament-help-desk::ticket.timeline', [
            'comments' => $this->getComments(),
            'showInternal' => false,
        ])
    </x-filament::section>

    {{-- Reply Form --}}
    @if (in_array($this->record->status, [
        \JeffersonGoncalves\HelpDesk\Enums\TicketStatus::Open,
        \JeffersonGoncalves\HelpDesk\Enums\TicketStatus::Pending,
        \JeffersonGoncalves\HelpDesk\Enums\TicketStatus::InProgress,
        \JeffersonGoncalves\HelpDesk\Enums\TicketStatus::OnHold,
    ]))
        <x-filament::section
            :heading="__('filament-help-desk::filament-help-desk.actions.add_comment')"
            icon="heroicon-o-paper-airplane"
        >
            <form wire:submit="submitComment">
                {{ $this->commentForm }}

                <div class="mt-4 flex justify-end">
                    <x-filament::button type="submit">
                        {{ __('filament-help-desk::filament-help-desk.actions.submit_reply') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-help-desk::filament-help-desk.comments.ticket_closed_message', [
                    'status' => $this->record->status->label(),
                ]) }}
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
