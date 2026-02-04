<x-filament-panels::page>
    {{-- Ticket Infolist --}}
    <div class="space-y-6">
        {{ $this->infolist }}
    </div>

    {{-- Comments Timeline (operators can see internal notes) --}}
    <x-filament::section
        :heading="__('filament-help-desk::filament-help-desk.comments.reply')"
        icon="heroicon-o-chat-bubble-left-right"
    >
        @include('filament-help-desk::ticket.timeline', [
            'comments' => $this->getComments(),
            'showInternal' => true,
        ])
    </x-filament::section>

    {{-- Reply / Note Form --}}
    @if ($this->record->isOpen())
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
                {{ __('filament-help-desk::filament-help-desk.comments.reply') }}
                &mdash;
                {{ $this->record->status->label() }}
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
