<div class="chat-message {{ $isMe ? 'user-message' : 'other-message' }}">
    <div class="message-content">
        <div class="message-bubble">
            @if($msg->body)
                {!! nl2br(e($msg->body)) !!}
            @endif
            @if($msg->attachments && $msg->attachments->count())
                @foreach($msg->attachments as $att)
                    @if($att->is_image)
                        <img src="{{ $att->url }}" class="attachment-img" onclick="window.open(this.src)" alt="{{ $att->file_name }}">
                    @else
                        <a href="{{ $att->url }}" target="_blank" class="attachment-item text-decoration-none {{ $isMe ? 'text-white' : 'text-dark' }}">
                            <i class="fa-solid fa-file"></i> {{ $att->file_name }}
                            <small>({{ $att->formatted_size }})</small>
                        </a>
                    @endif
                @endforeach
            @endif
        </div>
        <div class="message-time text-muted small mt-1">
            {{ $msg->created_at->format('M d, H:i') }}
        </div>
    </div>
</div>
