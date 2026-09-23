@include('errors.partials.card', [
    'statusCode' => 500,
    'title'      => 'Something went wrong',
    'message'    => 'An unexpected error occurred on our end. Our engineering team has been notified automatically. If you need this resolved urgently, call us and quote the reference ID below — it pinpoints exactly what happened.',
])