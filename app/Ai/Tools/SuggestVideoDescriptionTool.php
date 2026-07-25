<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SuggestVideoDescriptionTool implements Tool
{
    public ?string $suggestedDescription = null;

    public function description(): Stringable|string
    {
        return <<<'EOT'
        Propose a new YouTube description for this video. Call this whenever the
        creator asks you to write, rewrite, or tweak the description (e.g. "make
        it funnier", "add a call to action") instead of just replying with the
        text inline. The proposal is shown to the creator to review and publish
        themselves — calling this tool does not publish anything. Your reply
        after calling it should be a short comment, not a restatement of the
        full description.
        EOT;
    }

    public function handle(Request $request): Stringable|string
    {
        $this->suggestedDescription = (string) $request['description'];

        return 'Suggestion recorded and shown to the creator for review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->description('The full proposed new video description text.'),
        ];
    }
}
