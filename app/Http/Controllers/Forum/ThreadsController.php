<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThreadRequest;
use App\Jobs\CreateThread;
use App\Jobs\DeleteThread;
use App\Jobs\MarkThreadSolution;
use App\Jobs\SubscribeToSubscriptionAble;
use App\Jobs\UnmarkThreadSolution;
use App\Jobs\UnsubscribeFromSubscriptionAble;
use App\Jobs\UpdateThread;
use App\Models\Reply;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Policies\ThreadPolicy;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;

class ThreadsController extends Controller
{
    public function __construct()
    {
        $this->middleware([Authenticate::class, EnsureEmailIsVerified::class], ['except' => ['overview', 'show']]);
    }

    public function overview()
    {
        $filter = (string) request('filter') ?: 'recent';

        if ($filter === 'recent') {
            $threads = Thread::feedPaginated();
        }

        if ($filter === 'resolved') {
            $threads = Thread::feedQuery()
                ->resolved()
                ->paginate();
        }

        if ($filter === 'active') {
            $threads = Thread::feedQuery()
                ->active()
                ->paginate();
        }

        $mostSolutions = User::mostSolutions()->take(3)->get();

        return view('forum.overview', compact('threads', 'filter', 'mostSolutions'));
    }

    public function show(Thread $thread)
    {
        return view('forum.threads.show', compact('thread'));
    }

    public function create()
    {
        $tags = Tag::all();
        $selectedTags = old('tags') ?: [];

        return view('forum.threads.create', ['tags' => $tags, 'selectedTags' => $selectedTags]);
    }

    public function store(ThreadRequest $request)
    {
        $thread = $this->dispatchNow(CreateThread::fromRequest($request));

        $this->success('forum.threads.created');

        return redirect()->route('thread', $thread->slug());
    }

    public function edit(Thread $thread)
    {
        $this->authorize(ThreadPolicy::UPDATE, $thread);
        $selectedTags = $thread->tags()->pluck('id')->toArray();

        return view('forum.threads.edit', ['thread' => $thread, 'tags' => Tag::all(), 'selectedTags' => $selectedTags]);
    }

    public function update(ThreadRequest $request, Thread $thread)
    {
        $this->authorize(ThreadPolicy::UPDATE, $thread);

        $thread = $this->dispatchNow(UpdateThread::fromRequest($thread, $request));

        $this->success('forum.threads.updated');

        return redirect()->route('thread', $thread->slug());
    }

    public function delete(Thread $thread)
    {
        $this->authorize(ThreadPolicy::DELETE, $thread);

        $this->dispatchNow(new DeleteThread($thread));

        $this->success('forum.threads.deleted');

        return redirect()->route('forum');
    }

    public function markSolution(Thread $thread, Reply $reply)
    {
        $this->authorize(ThreadPolicy::UPDATE, $thread);

        $this->dispatchNow(new MarkThreadSolution($thread, $reply));

        return redirect()->route('thread', $thread->slug());
    }

    public function unmarkSolution(Thread $thread)
    {
        $this->authorize(ThreadPolicy::UPDATE, $thread);

        $this->dispatchNow(new UnmarkThreadSolution($thread));

        return redirect()->route('thread', $thread->slug());
    }

    public function subscribe(Request $request, Thread $thread)
    {
        $this->authorize(ThreadPolicy::SUBSCRIBE, $thread);

        $this->dispatchNow(new SubscribeToSubscriptionAble($request->user(), $thread));

        $this->success("You're now subscribed to this thread.");

        return redirect()->route('thread', $thread->slug());
    }

    public function unsubscribe(Request $request, Thread $thread)
    {
        $this->authorize(ThreadPolicy::UNSUBSCRIBE, $thread);

        $this->dispatchNow(new UnsubscribeFromSubscriptionAble($request->user(), $thread));

        $this->success("You're now unsubscribed from this thread.");

        return redirect()->route('thread', $thread->slug());
    }
}
