<?php

namespace Tests\Feature;

use App\Support\ErrorClassifier;
use App\Support\ErrorLogger;
use App\Support\Pii;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F7: what reaches the logs and the error pages. Logs are copied to CloudWatch and read by support staff for months,
 * so phone numbers, tokens, e-mails and links must be masked, and a failure must never break the page that reports it.
 */
class LoggingSafetyTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    // ---- Pii -----------------------------------------------------------------------------------------------

    public static function phones(): array
    {
        return [
            'sri lanka mobile' => ['+94712345678', '+94******678'],
            'short number'     => ['12345', '123**'],
            'six digits'       => ['123456', '123***'],
            'empty'            => ['', ''],
            'null'             => [null, ''],
        ];
    }

    #[DataProvider('phones')]
    public function test_a_phone_number_is_masked_but_still_recognisable(?string $in, string $out): void
    {
        $this->assertSame($out, Pii::phone($in));
    }

    public function test_a_masked_phone_never_contains_the_middle_digits(): void
    {
        $this->assertStringNotContainsString('4567', Pii::phone('+94771234567'));
        $this->assertSame(strlen('+94771234567'), strlen(Pii::phone('+94771234567')));
    }

    public function test_a_user_id_becomes_a_short_stable_non_reversible_code(): void
    {
        $a = Pii::id('firebase-uid-123');

        $this->assertSame($a, Pii::id('firebase-uid-123'));
        $this->assertNotSame($a, Pii::id('firebase-uid-124'));
        $this->assertSame(10, strlen($a));
        $this->assertStringNotContainsString('firebase', $a);
        $this->assertSame('', Pii::id(null));
        $this->assertSame('', Pii::id(''));
    }

    public function test_an_api_error_body_is_cut_down_to_status_fields_only(): void
    {
        $body = [
            'message' => 'Validation failed', 'code' => 'E42', 'status' => 400, 'title' => 'Bad', 'error' => 'x', 'errorCode' => 7,
            'errors' => ['email' => ['nimal@example.com is taken']], 'nic' => '991234567V', 'address' => '1 Galle Road',
            'nested' => ['message' => 'inner'], 'message2' => 'x',
        ];

        $out = Pii::apiBody($body);

        $this->assertSame(['code', 'errorCode', 'error', 'message', 'title', 'status'], array_keys($out));
        $this->assertStringNotContainsString('nimal', json_encode($out));
        $this->assertStringNotContainsString('991234567V', json_encode($out));
    }

    public function test_api_body_values_are_cut_to_200_characters_and_non_scalars_are_dropped(): void
    {
        $out = Pii::apiBody(['message' => str_repeat('a', 500), 'code' => ['not', 'scalar'], 'title' => null]);

        $this->assertSame(200, strlen($out['message']));
        $this->assertArrayNotHasKey('code', $out);
        $this->assertArrayNotHasKey('title', $out);
    }

    #[DataProvider('notArrays')]
    public function test_an_api_body_that_is_not_an_object_gives_nothing(mixed $body): void
    {
        $this->assertSame([], Pii::apiBody($body));
    }

    public static function notArrays(): array
    {
        return [[null], ['plain text'], [12], [false]];
    }

    // ---- ErrorLogger::scrub ---------------------------------------------------------------------------------

    public static function secrets(): array
    {
        return [
            'bearer token' => ['Authorization: Bearer abc.DEF-123_xyz~+/=', 'abc.DEF'],
            'jwt'          => ['token eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjMifQ.c2lnbmF0dXJl failed', 'eyJhbGci'],
            'long id/link' => ['No query results for /live/AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_', 'AbCdEfGh'],
            'email'        => ['user nimal.perera+test@example.co.lk not found', 'nimal'],
            'phone number' => ['phone +94 77 123 4567 rejected', '123 4567'],
            'plain digits' => ['called 0771234567 twice', '0771234567'],
        ];
    }

    #[DataProvider('secrets')]
    public function test_secrets_and_personal_data_are_masked_in_exception_text(string $in, string $mustNotRemain): void
    {
        $out = ErrorLogger::scrub($in);

        $this->assertStringNotContainsString($mustNotRemain, $out);
    }

    public function test_masking_uses_visible_placeholders_so_support_can_still_read_the_line(): void
    {
        $out = ErrorLogger::scrub('Bearer abc123 for a@b.co and +94771234567');

        $this->assertStringContainsString('Bearer [redacted]', $out);
        $this->assertStringContainsString('[email]', $out);
        $this->assertStringContainsString('[number]', $out);
    }

    public function test_ordinary_error_text_is_left_alone(): void
    {
        $this->assertSame('Undefined array key "name"', ErrorLogger::scrub('Undefined array key "name"'));
    }

    public function test_scrubbed_text_is_cut_to_500_characters(): void
    {
        $this->assertSame(500, mb_strlen(ErrorLogger::scrub(str_repeat('word ', 400))));
    }

    #[DataProvider('paths')]
    public function test_share_links_and_ids_never_reach_the_log_path(?string $in, string $out): void
    {
        $this->assertSame($out, ErrorLogger::safePath($in));
    }

    public static function paths(): array
    {
        return [
            'plain page'        => ['vehicles', '/vehicles'],
            'with slash'        => ['/trips', '/trips'],
            'live link token'   => ['live/AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_/data', '/live/{id}/data'],
            'uuid in path'      => ['vehicles/11111111-1111-4111-8111-111111111111/health', '/vehicles/{id}/health'],
            'short id is kept'  => ['vehicles/abc123', '/vehicles/abc123'],
            'empty'             => ['', '/'],
            'null'              => [null, '/'],
        ];
    }

    // ---- ErrorClassifier -----------------------------------------------------------------------------------

    public static function failures(): array
    {
        $req = new \Illuminate\Http\Client\Request(new \GuzzleHttp\Psr7\Request('GET', 'https://x'));
        $res = new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(500));

        return [
            'missing app key'  => [fn () => new \Illuminate\Encryption\MissingAppKeyException(), 'Configuration error'],
            'own config error' => [fn () => new \App\Exceptions\ConfigurationException('X'), 'Configuration error'],
            'database'         => [fn () => new \Illuminate\Database\QueryException('pgsql', 'select * from secret_table', [], new \Exception('boom')), 'Database error'],
            'api unreachable'  => [fn () => new \Illuminate\Http\Client\ConnectionException('cURL error 7'), 'API — connection failed'],
            'api error status' => [fn () => new \Illuminate\Http\Client\RequestException($res), 'API — bad response'],
            'csrf'             => [fn () => new \Illuminate\Session\TokenMismatchException(), 'Session — CSRF mismatch'],
            'route name'       => [fn () => new \Symfony\Component\Routing\Exception\RouteNotFoundException('x'), 'Routing — missing route name'],
            'not found'        => [fn () => new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(), 'Routing — route not matched'],
            'access denied'    => [fn () => new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(), 'Authorisation denied'],
            'wrong method'     => [fn () => new \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException(['GET']), 'Routing — wrong HTTP method'],
            'rate limited'     => [fn () => new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(), 'Rate limited'],
            'other http'       => [fn () => new \Symfony\Component\HttpKernel\Exception\HttpException(502), 'HTTP 502'],
            'type error'       => [fn () => new \TypeError('x'), 'Code bug — PHP runtime error'],
            'php error'        => [fn () => new \ErrorException('x'), 'Code bug — PHP runtime error'],
            'validation'       => [fn () => \Illuminate\Validation\ValidationException::withMessages(['a' => 'b']), 'Validation'],
            'anything else'    => [fn () => new \RuntimeException('x'), 'Unclassified'],
        ];
    }

    #[DataProvider('failures')]
    public function test_each_kind_of_failure_gets_its_own_plain_category(\Closure $make, string $category): void
    {
        $e = $make();
        $c = ErrorClassifier::classify($e);

        $this->assertSame($category, $c['category']);
        $this->assertNotSame('', $c['summary']);
    }

    public function test_a_firebase_named_exception_is_classified_by_its_class_name(): void
    {
        $e = new class('x') extends \RuntimeException {};
        // anonymous classes carry their parent in the name; use a real named class
        $named = new \Tests\Feature\FakeFirebaseAuthException('x');

        $this->assertSame('Authentication — Firebase', ErrorClassifier::classify($named)['category']);
        $this->assertSame('Unclassified', ErrorClassifier::classify($e)['category']);
    }

    public function test_classifier_output_never_contains_the_message_sql_or_paths(): void
    {
        $e = new \Illuminate\Database\QueryException('pgsql', 'select * from customers where phone = ?', ['+94771234567'], new \Exception('relation "customers" does not exist at /var/www/app/Repo.php'));

        $json = json_encode(ErrorClassifier::classify($e));

        foreach (['customers', '+94771234567', '/var/www', 'select'] as $leak) {
            $this->assertStringNotContainsString($leak, $json);
        }
    }

    // ---- ErrorLogger::classify / status --------------------------------------------------------------------

    public function test_a_419_wrapping_a_csrf_error_is_classified_by_the_real_cause(): void
    {
        $inner = new \Illuminate\Session\TokenMismatchException();
        $wrapped = new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'Page Expired', $inner);

        $this->assertSame('Session — CSRF mismatch', ErrorLogger::classify($wrapped)['category']);
    }

    public function test_a_wrapper_with_an_unknown_inner_cause_keeps_its_own_category(): void
    {
        $wrapped = new \Symfony\Component\HttpKernel\Exception\HttpException(503, 'x', new \RuntimeException('inner'));

        $this->assertSame('HTTP 503', ErrorLogger::classify($wrapped)['category']);
    }

    public function test_the_http_status_follows_the_exception(): void
    {
        $this->assertSame(404, ErrorLogger::status(new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException()));
        $this->assertSame(404, ErrorLogger::status(new \Symfony\Component\Routing\Exception\RouteNotFoundException('x')));
        $this->assertSame(429, ErrorLogger::status(new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException()));
        $this->assertSame(500, ErrorLogger::status(new \RuntimeException('x')));
    }

    // ---- the JSON contract ---------------------------------------------------------------------------------

    public static function jsonCases(): array
    {
        return [
            '419' => [419, 'PAGE_EXPIRED'], '429' => [429, 'RATE_LIMITED'], '403' => [403, 'FORBIDDEN'], '404' => [404, 'NOT_FOUND'],
            '405' => [405, 'METHOD_NOT_ALLOWED'], '413' => [413, 'TOO_LARGE'], '500' => [500, 'SERVER_ERROR'], '503' => [503, 'SERVER_ERROR'],
            '400' => [400, 'REQUEST_FAILED'], '422' => [422, 'REQUEST_FAILED'],
        ];
    }

    #[DataProvider('jsonCases')]
    public function test_every_failure_has_a_stable_code_and_a_customer_safe_message(int $status, string $code): void
    {
        config(['app.error_details' => false]);

        $res = ErrorLogger::json(new \Symfony\Component\HttpKernel\Exception\HttpException($status, 'secret internal text /var/www/x.php'));
        $body = $res->getData(true);

        $this->assertSame($status, $res->getStatusCode());
        $this->assertSame(['success' => false, 'code' => $code], array_intersect_key($body, ['success' => 1, 'code' => 1]));
        $this->assertMatchesRegularExpression('/^\d{8}-[0-9A-F]{8}$/', $body['reference']);
        $this->assertArrayNotHasKey('technical', $body);
        $this->assertStringNotContainsString('/var/www', $res->getContent());
    }

    public function test_technical_detail_appears_only_when_the_support_switch_is_on(): void
    {
        $e = new \RuntimeException('phone +94771234567 failed');

        config(['app.error_details' => false]);
        $this->assertArrayNotHasKey('technical', ErrorLogger::json($e)->getData(true));

        config(['app.error_details' => true]);
        $t = ErrorLogger::json($e)->getData(true)['technical'];
        $this->assertSame('Unclassified', $t['category']);
        $this->assertStringNotContainsString('94771234567', $t['message'], 'even support detail is masked');
        $this->assertStringContainsString('LoggingSafetyTest.php', $t['file']);
    }

    public function test_response_headers_of_the_original_exception_are_kept(): void
    {
        $res = ErrorLogger::json(new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(30));

        $this->assertSame('30', $res->headers->get('Retry-After'));
    }

    public function test_the_error_page_data_hides_causes_unless_the_switch_is_on(): void
    {
        $e = new \RuntimeException('secret thing in /var/www/app/X.php');

        config(['app.error_details' => false]);
        $data = ErrorLogger::viewData($e);
        $this->assertSame(['referenceId', 'statusCode', 'technicalCategory'], array_keys($data));
        $this->assertSame(500, $data['statusCode']);

        config(['app.error_details' => true]);
        $data = ErrorLogger::viewData($e);
        $this->assertSame(['referenceId', 'statusCode', 'technicalCategory', 'technicalSummary', 'technicalMessage', 'technicalFile'], array_keys($data));
    }

    public function test_one_reference_id_is_shared_by_the_log_line_and_the_page_within_a_request(): void
    {
        $this->assertSame(app('error.reference_id'), app('error.reference_id'));
        $this->assertSame(app('error.reference_id'), ErrorLogger::viewData(new \RuntimeException('x'))['referenceId']);
    }

    // ---- ErrorLogger::log ----------------------------------------------------------------------------------

    public function test_a_log_entry_carries_the_reference_masked_text_and_no_raw_ids(): void
    {
        $captured = [];
        $channel = \Mockery::mock();
        $channel->shouldReceive('error')->once()->andReturnUsing(function ($msg, $ctx) use (&$captured) { $captured = [$msg, $ctx]; });
        Log::shouldReceive('channel')->with('app_errors')->andReturn($channel);

        $req = \Illuminate\Http\Request::create('/live/AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_', 'GET');
        $req->setLaravelSession(app('session.store'));
        session()->put('firebase_uid', 'uid-raw-123');
        $this->app->instance('request', $req);

        $ref = ErrorLogger::log(new \RuntimeException('failed for nimal@example.com'), 'error', ['extra' => 'yes']);

        [$msg, $ctx] = $captured;
        $this->assertSame(app('error.reference_id'), $ctx['reference_id']);
        $this->assertSame($ref, $ctx['reference_id']);
        $this->assertStringNotContainsString('nimal', $msg);
        $this->assertSame('yes', $ctx['extra']);
        $this->assertSame('Unclassified', $ctx['category']);
        $this->assertStringNotContainsString('uid-raw-123', json_encode($ctx));
        $this->assertSame(Pii::id('uid-raw-123'), $ctx['user']);
        $this->assertSame('/live/{id}', $ctx['path']);
        $this->assertStringNotContainsString('AbCdEfGh', json_encode($ctx));
    }

    public function test_logging_never_throws_even_when_the_log_channel_is_broken(): void
    {
        Log::shouldReceive('channel')->andThrow(new \RuntimeException('log disk full'));

        $ref = ErrorLogger::log(new \RuntimeException('x'));

        $this->assertMatchesRegularExpression('/^\d{8}-[0-9A-F]{8}$/', $ref);
    }

    public function test_a_real_failing_request_gets_the_friendly_json_with_a_reference(): void
    {
        $res = $this->getJson('/api/this-route-does-not-exist');

        $res->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND')->assertJsonPath('success', false);
        $this->assertMatchesRegularExpression('/^\d{8}-[0-9A-F]{8}$/', $res->json('reference'));
    }

    public function test_a_real_missing_page_shows_the_error_page_with_a_reference_and_no_internals(): void
    {
        config(['app.error_details' => false]);

        $res = $this->get('/no-such-page-at-all')->assertStatus(404);

        $this->assertMatchesRegularExpression('/\d{8}-[0-9A-F]{8}/', $res->getContent());
        $this->assertStringNotContainsString('/var/www', $res->getContent());
        $this->assertStringNotContainsString('routes/web.php', $res->getContent());
    }
}

/** A real, named class whose name contains "Firebase", as the classifier looks at the class name. */
class FakeFirebaseAuthException extends \RuntimeException
{
}
