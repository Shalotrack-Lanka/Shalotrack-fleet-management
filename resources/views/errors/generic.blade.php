{{--
  Catch-all error view — Laravel routes here for any unhandled HTTP exception
  that doesn't have a dedicated status-code view (e.g. 408, 422, 502 …).
  The card partial auto-detects the status code from $exception and selects
  the correct icon / colour, falling back to the navy generic variant.
--}}
@include('errors.partials.card', [
    {{-- No statusCode passed — card.blade.php reads it from $exception->getStatusCode() --}}
    {{-- title / message also omitted — card falls back to its per-code defaults --}}
])