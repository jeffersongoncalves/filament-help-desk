<x-filament-panels::page>
    {{-- Ticket Infolist --}}
    <div class="space-y-6">
        {{ $this->infolist }}
    </div>

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
