@include('errors.partials.card', [
    'statusCode' => 403,
    'title'      => 'Access denied',
    'message'    => 'You don\'t have permission to view this page. If you believe you should have access, contact your fleet administrator and quote the reference ID below.',
])