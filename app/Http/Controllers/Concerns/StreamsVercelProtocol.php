<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamsVercelProtocol
{
    /**
     * Send an already-computed reply to the frontend as a single-chunk
     * Vercel AI SDK UI Message Stream (the protocol useChat expects).
     *
     * laravel/ai's own ->stream() does support real token-by-token
     * streaming, but its tool-call event parsing does not currently work
     * against our gateway (LiteLLM): the stream ends after the tool-call
     * step instead of continuing to the final answer. ->prompt() runs the
     * same tool-calling loop synchronously and does return the correct
     * final text, so we compute the answer that way and just re-encode it
     * in the wire format the frontend already expects.
     */
    protected function streamText(string $text): StreamedResponse
    {
        return response()->stream(function () use ($text) {
            $id = (string) Str::uuid();

            $send = function (array $event) {
                echo 'data: '.json_encode($event)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            $send(['type' => 'start']);
            $send(['type' => 'text-start', 'id' => $id]);
            $send(['type' => 'text-delta', 'id' => $id, 'delta' => $text]);
            $send(['type' => 'text-end', 'id' => $id]);
            $send(['type' => 'finish']);

            echo "data: [DONE]\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
