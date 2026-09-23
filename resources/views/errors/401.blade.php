@include('errors.partials.card', [
    'statusCode' => 401,
    'title'      => 'Session expired',
    'message'    => 'Your session is no longer active — this usually happens after a period of inactivity. Please log in again to continue where you left off.',
])