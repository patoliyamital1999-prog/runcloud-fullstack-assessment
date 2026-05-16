<?php

namespace App\Http\Controllers;

use App\Models\PostVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VoteController extends Controller
{
    public function vote(Request $request): JsonResponse
    {
        $request->validate([
            'post_id' => 'required|integer',
            'vote_type' => 'required|in:upvote,downvote',
        ]);

        $user = auth()->user();

        $existingVote = PostVote::where('user_id', $user->id)
            ->where('post_id', $request->post_id)
            ->first();

        if (!$existingVote) {
            PostVote::create([
                'user_id' => $user->id,
                'post_id' => $request->post_id,
                'vote_type' => $request->vote_type,
            ]);

            return $this->respondFromWordPress(
                $this->sendVoteToWordPress($request->post_id, $request->vote_type, 'increment')
            );
        }

        if ($existingVote->vote_type === $request->vote_type) {
            $existingVote->delete();

            return $this->respondFromWordPress(
                $this->sendVoteToWordPress($request->post_id, $request->vote_type, 'decrement')
            );
        }

        $oldVoteType = $existingVote->vote_type;

        $existingVote->update([
            'vote_type' => $request->vote_type,
        ]);

        $decrementResponse = $this->sendVoteToWordPress(
            $request->post_id,
            $oldVoteType,
            'decrement'
        );

        if ($decrementResponse->failed()) {
            return $this->respondFromWordPress($decrementResponse);
        }

        return $this->respondFromWordPress(
            $this->sendVoteToWordPress($request->post_id, $request->vote_type, 'increment')
        );
    }

    private function respondFromWordPress($response): JsonResponse
    {
        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to register vote with WordPress.',
            ], $response->status() ?: 502);
        }

        $data = $response->json();

        if (empty($data['success'])) {
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'WordPress rejected the vote request.',
            ], 422);
        }

        return response()->json($data);
    }

    private function sendVoteToWordPress(int $postId, string $voteType, string $action)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.wordpress.secret_token'),
        ])->post(config('services.wordpress.vote_url'), [
            'post_id' => $postId,
            'vote_type' => $voteType,
            'action' => $action,
        ]);
    }
}
