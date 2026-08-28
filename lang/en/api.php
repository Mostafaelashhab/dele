<?php

return [
    'errors' => [
        'missing_key' => 'No API key was provided. Send it in the Authorization header.',
        'invalid_key' => 'The API key is invalid or has been revoked.',
        'client_suspended' => 'This API client has been suspended.',
        'rate_limited' => 'Rate limit exceeded. Try again in :seconds seconds.',
        'idempotency_mismatch' => 'This Idempotency-Key was already used with a different request body.',
        'idempotency_in_progress' => 'A request with this Idempotency-Key is still being processed.',
        'not_found' => 'The requested resource could not be found.',
        'forbidden' => 'You do not have access to this resource.',
        'business_inactive' => 'This business account is not active.',
        'not_cancellable' => 'This delivery cannot be cancelled in its current state.',
    ],
];
