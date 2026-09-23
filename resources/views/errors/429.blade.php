@include('errors.partials.card', [
    'statusCode' => 429,
    'title'      => 'Too many requests',
    'message'    => 'You\'ve sent too many requests in a short time and have been temporarily rate-limited. Wait a minute or two, then try again. If this keeps happening, contact your fleet administrator.',
])