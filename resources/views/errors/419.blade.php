@include('errors.partials.card', [
    'statusCode' => 419,
    'title'      => 'Session expired',
    'message'    => 'Your security token has expired — this usually happens after leaving a page open for too long. Click Go back and try your action again; it should work immediately.',
])