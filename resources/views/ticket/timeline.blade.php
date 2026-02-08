<div class="fi-hd-timeline">
    @forelse ($comments as $comment)
        @if ($comment->is_internal && ! ($showInternal ?? false))
            @continue
        @endif

        <div class="fi-hd-timeline-item {{ $comment->is_internal ? 'fi-hd-timeline-item--internal' : '' }}">
            @include('filament-help-desk::ticket.comment', ['comment' => $comment])
        </div>
    @empty
        <div class="fi-hd-timeline-empty">
            <x-heroicon-o-chat-bubble-left-right class="fi-hd-timeline-empty-icon" />
            <p class="fi-hd-timeline-empty-text">
                {{ __('filament-help-desk::filament-help-desk.comments.empty') }}
            </p>
        </div>
    @endforelse
</div>
