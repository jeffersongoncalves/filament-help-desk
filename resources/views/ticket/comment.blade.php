<div class="fi-hd-comment {{ $comment->is_internal ? 'fi-hd-comment--internal' : '' }}">
    <div class="fi-hd-comment-avatar-wrap">
        <div class="fi-hd-comment-avatar {{ $comment->is_internal ? 'fi-hd-comment-avatar--warning' : 'fi-hd-comment-avatar--primary' }}">
            @if ($comment->isSystem())
                <x-heroicon-m-cog-6-tooth class="fi-hd-comment-avatar-icon fi-hd-comment-avatar-icon--gray" />
            @elseif ($comment->is_internal)
                <x-heroicon-m-lock-closed class="fi-hd-comment-avatar-icon fi-hd-comment-avatar-icon--warning" />
            @else
                <x-heroicon-m-user class="fi-hd-comment-avatar-icon fi-hd-comment-avatar-icon--primary" />
            @endif
        </div>
    </div>

    <div class="fi-hd-comment-body">
        <div class="fi-hd-comment-header">
            <span class="fi-hd-comment-author">
                {{ $comment->author?->name ?? __('filament-help-desk::filament-help-desk.comments.system') }}
            </span>

            @if ($comment->is_internal)
                <span class="fi-hd-comment-badge fi-hd-comment-badge--warning">
                    {{ __('filament-help-desk::filament-help-desk.comments.internal_note') }}
                </span>
            @endif

            @if ($comment->isNote() && ! $comment->is_internal)
                <span class="fi-hd-comment-badge fi-hd-comment-badge--gray">
                    {{ __('filament-help-desk::filament-help-desk.comments.note') }}
                </span>
            @endif

            <span class="fi-hd-comment-time">
                {{ $comment->created_at->diffForHumans() }}
            </span>
        </div>

        <div class="fi-hd-comment-content prose prose-sm dark:prose-invert">
            {!! $comment->body !!}
        </div>

        @if ($comment->attachments->isNotEmpty())
            <div class="fi-hd-comment-attachments">
                @foreach ($comment->attachments as $attachment)
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
        @endif
    </div>
</div>
