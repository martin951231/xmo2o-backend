<?php
return [
    'enabled' => env('community_agent.enabled', false),
    // Exact internal intent endpoint; no user-controlled URL or redirect.
    'intent_url' => env('community_agent.intent_url', 'http://127.0.0.1:8000/api/internal/community/intent'),
    'service_key' => env('community_agent.service_key', ''),
];
