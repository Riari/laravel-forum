<?php

namespace TeamTeaTime\Forum\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Traits\HasAuthor;
use TeamTeaTime\Forum\Support\Traits\CachesData;

class Thread extends BaseModel
{
    use SoftDeletes, HasAuthor, CachesData;

    protected $table = 'forum_threads';
    protected $dates = ['deleted_at'];
    protected $fillable = ['category_id', 'author_id', 'title', 'locked', 'pinned', 'reply_count', 'first_post_id', 'last_post_id', 'updated_at'];

    const READERS_TABLE = 'forum_threads_read';

    const STATUS_UNREAD = 'unread';
    const STATUS_UPDATED = 'updated';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->perPage = config('forum.general.pagination.threads');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(
            config('forum.integration.user_model'),
            self::READERS_TABLE,
            'thread_id',
            'user_id'
        )->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function firstPost(): HasOne
    {
        return $this->hasOne(Post::class, 'id', 'first_post_id');
    }

    public function lastPost(): HasOne
    {
        return $this->hasOne(Post::class, 'id', 'last_post_id');
    }

    public function scopeRecent(Builder $query): Builder
    {
        $age = strtotime(config('forum.general.old_thread_threshold'), 0);
        $cutoff = time() - $age;

        return $query->where('updated_at', '>', date('Y-m-d H:i:s', $cutoff))->orderBy('updated_at', 'desc');
    }

    public function getIsOldAttribute(): bool
    {
        $age = config('forum.general.old_thread_threshold');
        return (! $age || $this->updated_at->timestamp < (time() - strtotime($age, 0)));
    }

    public function getReaderAttribute()
    {
        if (! auth()->check()) return null;

        $reader = $this->readers()->where('forum_threads_read.user_id', auth()->user()->getKey())->first();

        return (! is_null($reader)) ? $reader->pivot : null;
    }

    public function getUserReadStatusAttribute(): ?string
    {
        if ($this->isOld || ! auth()->check()) return null;

        if (is_null($this->reader)) return trans('forum::general.' . self::STATUS_UNREAD);

        return ($this->updatedSince($this->reader)) ? trans('forum::general.' . self::STATUS_UPDATED) : null;
    }

    public function getPostCountAttribute(): int
    {
        return $this->reply_count + 1;
    }

    public function getLastPost(): Post
    {
        return $this->posts()->orderBy('created_at', 'desc')->first();
    }

    public function markAsRead(int $userId): void
    {
        if ($this->isOld) return;

        if (is_null($this->reader))
        {
            $this->readers()->attach($userId);
        }
        elseif ($this->updatedSince($this->reader))
        {
            $this->reader->touch();
        }
    }
}
