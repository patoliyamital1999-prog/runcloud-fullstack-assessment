@include('layouts.header')

<style>

    body{
        background: #f3f4f9;
        font-family: 'Poppins', sans-serif;
    }

    .posts-page{
        padding: 45px 45px;
    }

    .posts-page h1{
        font-size: 36px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 35px;
        position: relative;
    }

    .posts-page h1::after{
        content: '';
        width: 80px;
        height: 5px;
        background: linear-gradient(to right,#14b8a6,#2563eb);
        display: block;
        margin-top: 10px;
        border-radius: 20px;
    }

    .post-card{
        background: #ffffff;
        border-radius: 18px;
        padding: 32px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        transition: 0.3s ease;
        border: 1px solid #ececec;
        position: relative;
        overflow: hidden;
        width: 100%;
    }

    .post-card::before{
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(to bottom,#14b8a6,#2563eb);
    }

    .post-card:hover{
        transform: translateY(-5px);
        box-shadow: 0 14px 35px rgba(0,0,0,0.12);
    }

    .post-card h2{
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 15px;
    }

    .post-content{
        font-size: 16px;
        line-height: 1.8;
        color: #4b5563;
        margin-bottom: 25px;
    }

    .vote-info{
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .vote-badge{
        background: #f8fafc;
        padding: 12px 18px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }

    .vote-actions{
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .vote-btn,
    .login-vote-link{
        border: none;
        padding: 13px 22px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .upvote{
        background: linear-gradient(to right,#16a34a,#22c55e);
        color: #fff;
    }

    .downvote{
        background: linear-gradient(to right,#dc2626,#ef4444);
        color: #fff;
    }

    .login-vote-link{
        background: linear-gradient(to right,#2563eb,#3b82f6);
        color: #fff;
    }

    .vote-btn:hover,
    .login-vote-link:hover{
        transform: translateY(-2px);
        opacity: 0.95;
        color: #fff;
        text-decoration: none;
    }
    

    @media(max-width:768px){

        .posts-page{
            padding: 25px;
        }

        .post-card{
            padding: 22px;
        }

        .posts-page h1{
            font-size: 28px;
        }

        .post-card h2{
            font-size: 22px;
        }
    }

</style>

<div class="container-fluid page-body-wrapper">
 

    <div class="main-panel">
        <div class="content-wrapper">
            <div class="posts-page">

                <div class="posts-header">
                    <h1>WordPress Posts ({{$totalPosts}})</h1>
                </div>

                @forelse ($posts as $post)

                    <div class="post-card">

                        <h2>{!! $post['title']['rendered'] !!}</h2>

                        <div class="post-content">
                            {!! $post['content']['rendered'] !!}
                        </div>

                        <div class="vote-info">
                            <div class="vote-badge">
                                👍 Upvotes:
                                <span id="upvotes-{{ $post['id'] }}">
                                    {{ $post['votes']['upvotes'] ?? 0 }}
                                </span>
                            </div>

                            <div class="vote-badge">
                                👎 Downvotes:
                                <span id="downvotes-{{ $post['id'] }}">
                                    {{ $post['votes']['downvotes'] ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <div class="vote-actions">
                            @auth

                                <button
                                    class="vote-btn upvote"
                                    onclick="vote({{ $post['id'] }}, 'upvote')"
                                >
                                    👍 Upvote
                                </button>

                                <button
                                    class="vote-btn downvote"
                                    onclick="vote({{ $post['id'] }}, 'downvote')"
                                >
                                    👎 Downvote
                                </button>

                            @else
                                <a href="{{ route('login') }}" class="vote-btn upvote">
                                    👍 Upvote
                                </a>

                                <a href="{{ route('login') }}" class="vote-btn downvote">
                                    👎 Downvote
                                </a>
                            @endauth
                        </div>

                    </div>

                @empty
                    <div class="post-card">
                        <p class="post-content mb-0">No posts found. Create published posts in WordPress first.</p>
                    </div>
                @endforelse

            </div>
        </div>
    </div>
</div>

<script>
    async function vote(postId, voteType) {

        const buttons = document.querySelectorAll('button');

        buttons.forEach(button => {
            button.disabled = true;
        });

        try {

            const response = await fetch('{{ route('vote') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    post_id: postId,
                    vote_type: voteType
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert(data.message || 'Unable to register your vote.');
                return;
            }

            document.getElementById('upvotes-' + postId).innerText = data.votes.upvotes;
            document.getElementById('downvotes-' + postId).innerText = data.votes.downvotes;

        } catch (error) {
            alert('Something went wrong. Please try again.');

        } finally {

            buttons.forEach(button => {
                button.disabled = false;
            });
        }
    }
</script>
</body>
</html>