<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stale Claim TTL
    |--------------------------------------------------------------------------
    |
    | Commands that have been claimed but not finished within this many minutes
    | will be automatically released back to 'pending' status. This prevents
    | commands from being stuck when an agent crashes after claiming.
    |
    | Set to 0 to disable stale claim release.
    |
    */
    'stale_claim_minutes' => (int) env('AGENT_STALE_CLAIM_MINUTES', 2),

    /*
    |--------------------------------------------------------------------------
    | Command Pull Limit
    |--------------------------------------------------------------------------
    |
    | Maximum commands an agent can claim in a single request.
    |
    */
    'max_command_pull_limit' => (int) env('AGENT_MAX_COMMAND_PULL_LIMIT', 50),
];
