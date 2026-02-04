<div class="flex gap-x-3 {{ $comment->is_internal ? 'opacity-75' : '' }}">
    <div class="flex-shrink-0">
        <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $comment->is_internal ? 'bg-warning-100 dark:bg-warning-900' : 'bg-primary-100 dark:bg-primary-900' }}">
            @if ($comment->isSystem())
                <x-heroicon-m-cog-6-tooth class="h-5 w-5 text-gray-500 dark:text-gray-400" />
            @elseif ($comment->is_internal)
                <x-heroicon-m-lock-closed class="h-5 w-5 text-warning-600 dark:text-warning-400" />
            @else
                <x-heroicon-m-user class="h-5 w-5 text-primary-600 dark:text-primary-400" />
            @endif
        </div>
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-x-2">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $comment->author?->name ?? __('filament-help-desk::filament-help-desk.comments.system') }}
            </span>

            @if ($comment->is_internal)
                <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                    {{ __('filament-help-desk::filament-help-desk.comments.internal_note') }}
                </span>
            @endif

            @if ($comment->isNote() && ! $comment->is_internal)
                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                    {{ __('filament-help-desk::filament-help-desk.comments.note') }}
                </span>
            @endif

            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $comment->created_at->diffForHumans() }}
            </span>
        </div>

        <div class="mt-1 prose prose-sm max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">
            {!! $comment->body !!}
        </div>

        @if ($comment->attachments->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($comment->attachments as $attachment)
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
        @endif
    </div>
</div>
