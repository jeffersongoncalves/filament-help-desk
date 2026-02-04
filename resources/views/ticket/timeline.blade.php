<div class="space-y-6">
    @forelse ($comments as $comment)
        @if ($comment->is_internal && ! ($showInternal ?? false))
            @continue
        @endif

        <div @class([
            'relative pl-6',
            'border-l-2 border-warning-300 dark:border-warning-700' => $comment->is_internal,
            'border-l-2 border-gray-200 dark:border-gray-700' => ! $comment->is_internal,
        ])>
            @include('filament-help-desk::ticket.comment', ['comment' => $comment])
        </div>
    @empty
        <div class="text-center py-6">
            <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-help-desk::filament-help-desk.comments.empty') }}
            </p>
        </div>
    @endforelse
</div>
