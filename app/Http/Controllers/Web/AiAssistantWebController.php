<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiAssistantWebController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'messages'            => 'required|array|min:1|max:20',
            'messages.*.role'     => 'required|in:user,assistant',
            'messages.*.content'  => 'required|string|max:4000',
        ]);

        $messages = [
            ['role' => 'system', 'content' => config('ai_assistant.system_prompt')],
            ...$request->messages,
        ];

        $response = Http::timeout(90)->post('http://127.0.0.1:11434/api/chat', [
            'model'    => 'llama3.2:3b',
            'messages' => $messages,
            'stream'   => false,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'AI Assistant sedang tidak tersedia.'], 502);
        }

        return response()->json([
            'reply' => $response->json('message.content', ''),
        ]);
    }
}
