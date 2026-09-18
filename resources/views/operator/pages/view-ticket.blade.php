<x-filament-panels::page>
    {{-- Ticket Infolist --}}
    <div class="fi-hd-page-content">
        {{ $this->infolist }}
    </div>

    {{-- Ticket Attachments (uploaded at creation) --}}
    @if ($this->record->attachments()->whereNull('comment_id')->exists())
        <x-filament::section
            :heading="__('filament-help-desk::filament-help-desk.sections.attachments')"
            icon="heroicon-o-paper-clip"
        >
            <div class="fi-hd-attachments-grid">
                @foreach ($this->record->attachments()->whereNull('comment_id')->get() as $attachment)
                    <a
                        href="{{ $attachment->getUrl() }}"
                        target="_blank"
                        class="fi-hd-attachment-link"
                    >
                        <x-heroicon-m-paper-clip class="fi-hd-attachment-icon" />
                        {{ $attachment->file_name }}
                        <span class="fi-hd-attachment-size">({{ $attachment->getFileSizeForHumans() }})</span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <div class="fi-hd-view-ticket-grid">
        {{-- Main column: comments timeline + reply form --}}
        <div class="fi-hd-view-ticket-main">
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

                        <div class="fi-hd-form-actions">
                            <x-filament::button
                                type="submit"
                                :color="$this->isInternalNote() ? 'warning' : 'primary'"
                            >
                                {{ __($this->isInternalNote()
                                    ? 'filament-help-desk::filament-help-desk.actions.submit_note'
                                    : 'filament-help-desk::filament-help-desk.actions.submit_reply') }}
                            </x-filament::button>
                        </div>
                    </form>
                </x-filament::section>
            @else
                <x-filament::section>
                    <div class="fi-hd-ticket-closed-message">
                        {{ __('filament-help-desk::filament-help-desk.comments.ticket_closed_message', [
                            'status' => $this->record->status->label(),
                        ]) }}
                    </div>
                </x-filament::section>
            @endif
        </div>

        {{-- Sidebar: requester context + ticket metadata, at a glance while replying --}}
        <div class="fi-hd-view-ticket-sidebar">
            <x-filament::section
                collapsible
                :heading="__('filament-help-desk::filament-help-desk.sections.requester')"
                icon="heroicon-o-user"
            >
                <dl class="fi-hd-sidebar-list">
                    <div class="fi-hd-sidebar-list-item">
                        <dt>{{ __('filament-help-desk::filament-help-desk.fields.requester') }}</dt>
                        <dd>{{ $this->record->requester_name }}</dd>
                    </div>
                    <div class="fi-hd-sidebar-list-item">
                        <dt>{{ __('filament-help-desk::filament-help-desk.fields.other_open_tickets') }}</dt>
                        <dd>{{ $this->getOtherOpenTicketsCount() }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section
                collapsible
                :heading="__('filament-help-desk::filament-help-desk.sections.ticket_metadata')"
                icon="heroicon-o-tag"
            >
                <dl class="fi-hd-sidebar-list">
                    <div class="fi-hd-sidebar-list-item">
                        <dt>{{ __('filament-help-desk::filament-help-desk.fields.department') }}</dt>
                        <dd>{{ $this->record->department?->name ?? __('filament-help-desk::filament-help-desk.placeholders.na') }}</dd>
                    </div>
                    <div class="fi-hd-sidebar-list-item">
                        <dt>{{ __('filament-help-desk::filament-help-desk.fields.category') }}</dt>
                        <dd>{{ $this->record->category?->name ?? __('filament-help-desk::filament-help-desk.placeholders.na') }}</dd>
                    </div>
                    @if ($this->record->app_key)
                        <div class="fi-hd-sidebar-list-item">
                            <dt>{{ __('filament-help-desk::filament-help-desk.fields.application') }}</dt>
                            <dd>{{ $this->record->app_name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
