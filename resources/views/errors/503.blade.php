@include('errors.partials.card', [
    'statusCode' => 503,
    'title'      => 'Under maintenance',
    'message'    => 'ShaloTrack Fleet is currently undergoing scheduled maintenance. We\'ll be back online shortly. No data has been affected — vehicles are still being tracked in the background.',
])