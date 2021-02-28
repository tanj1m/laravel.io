<?php

namespace App\Models;

use App\Helpers\HasTimestamps;
use App\Helpers\ModelHelpers;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

final class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasTimestamps;
    use ModelHelpers;
    use Notifiable;

    const TABLE = 'users';

    const DEFAULT = 1;
    const MODERATOR = 2;
    const ADMIN = 3;

    /**
     * {@inheritdoc}
     */
    protected $table = 'users';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'email',
        'twitter',
        'username',
        'password',
        'ip',
        'github_id',
        'github_username',
        'type',
        'remember_token',
        'bio',
    ];

    /**
     * {@inheritdoc}
     */
    protected $hidden = ['password', 'remember_token'];

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function emailAddress(): string
    {
        return $this->email;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function bio(): string
    {
        return $this->bio;
    }

    public function githubUsername(): string
    {
        return $this->github_username;
    }

    public function twitter(): ?string
    {
        return $this->twitter;
    }

    public function hasTwitterAccount(): bool
    {
        return ! empty($this->twitter());
    }

    public function isBanned(): bool
    {
        return ! is_null($this->banned_at);
    }

    public function type(): int
    {
        return (int) $this->type;
    }

    public function isModerator(): bool
    {
        return $this->type() === self::MODERATOR;
    }

    public function isAdmin(): bool
    {
        return $this->type() === self::ADMIN;
    }

    public function isLoggedInUser(): bool
    {
        return $this->id() === Auth::id();
    }

    public function hasPassword(): bool
    {
        $password = $this->getAuthPassword();

        return $password !== '' && $password !== null;
    }

    /**
     * @return \App\Models\Thread[]
     */
    public function threads()
    {
        return $this->threadsRelation;
    }

    /**
     * @return \App\Models\Thread[]
     */
    public function latestThreads(int $amount = 5)
    {
        return $this->threadsRelation()->latest()->limit($amount)->get();
    }

    public function deleteThreads()
    {
        // We need to explicitly iterate over the threads and delete them
        // separately because all related models need to be deleted.
        foreach ($this->threads() as $thread) {
            $thread->delete();
        }
    }

    public function threadsRelation(): HasMany
    {
        return $this->hasMany(Thread::class, 'author_id');
    }

    public function countThreads(): int
    {
        return $this->threadsRelation()->count();
    }

    /**
     * @return \App\Models\Reply[]
     */
    public function replies()
    {
        return $this->replyAble;
    }

    /**
     * @return \App\Models\Reply[]
     */
    public function latestReplies(int $amount = 10)
    {
        return $this->replyAble()->latest()->limit($amount)->get();
    }

    public function deleteReplies()
    {
        // We need to explicitly iterate over the replies and delete them
        // separately because all related models need to be deleted.
        foreach ($this->replyAble()->get() as $reply) {
            $reply->delete();
        }
    }

    public function countReplies(): int
    {
        return $this->replyAble()->count();
    }

    public function replyAble(): HasMany
    {
        return $this->hasMany(Reply::class, 'author_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function latestArticles(int $amount = 10)
    {
        return $this->articles()->latest()->limit($amount)->get();
    }

    public function countArticles(): int
    {
        return $this->articles()->count();
    }

    public function series(): HasMany
    {
        return $this->hasMany(Series::class, 'author_id');
    }

    /**
     * @todo Make this work with Eloquent instead of a collection
     */
    public function countSolutions(): int
    {
        return $this->replies()->filter(function (Reply $reply) {
            if ($reply->replyAble() instanceof Thread) {
                return $reply->replyAble()->isSolutionReply($reply);
            }

            return false;
        })->count();
    }

    public static function findByUsername(string $username): self
    {
        return static::where('username', $username)->firstOrFail();
    }

    public static function findByEmailAddress(string $emailAddress): self
    {
        return static::where('email', $emailAddress)->firstOrFail();
    }

    public static function findByGithubId(string $githubId): self
    {
        return static::where('github_id', $githubId)->firstOrFail();
    }

    public function delete()
    {
        $this->deleteThreads();
        $this->deleteReplies();

        parent::delete();
    }

    public function scopeMostSolutions(Builder $query)
    {
        return $query->withCount(['replyAble as most_solutions' => function ($query) {
            return $query->join('threads', 'threads.solution_reply_id', '=', 'replies.id')
                ->where('replyable_type', 'threads');
        }])->orderBy('most_solutions', 'desc');
    }
}
