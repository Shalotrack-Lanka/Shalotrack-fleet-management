<?php
// Builds a signed-in session for the portal WITHOUT any login: writes the session file the way Laravel does and prints the encrypted cookie.
require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$store = $app['session']->driver();
$store->setId($id = Illuminate\Support\Str::random(40));
$store->put('firebase_token', 'tok'); $store->put('firebase_uid', 'uid-browser'); $store->put('firebase_token_expires_at', time() + 3000);
$store->put('customer_id', '22222222-2222-4222-8222-222222222222');
$store->save();
$enc = $app['encrypter'];
$name = config('session.cookie');
echo $enc->encrypt(Illuminate\Cookie\CookieValuePrefix::create($name, $enc->getKey()) . $id, false);
