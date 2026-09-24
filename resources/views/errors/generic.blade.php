{{--
    Catch-all error view — Laravel routes here for any unhandled HTTP exception
    that doesn't have a dedicated status-code view (e.g. 408, 422, 502 …).

    The card partial auto-detects the status code from $exception (via
    $exception->getStatusCode()) and selects the correct icon/colour,
    falling back to the navy generic variant for unknown codes.

    No statusCode, title, or message is passed here deliberately — the card
    reads everything from the $exception and the ErrorClassifier output that
    the withExceptions() pipeline injects as view variables.
--}}
@include('errors.partials.card')