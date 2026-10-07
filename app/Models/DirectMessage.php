<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DirectMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_id',
        'body',
        'edited_at',
        'is_pinned',
        'pinned_by',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DirectMessage::class, 'parent_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(DirectMessageRead::class, 'message_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DirectMessageAttachment::class, 'message_id');
    }
}
