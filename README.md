# Vonage User Authentication

A sample implemenation of a Firebase-like authentication layer using the Vonage Users API. Users can be created with either Password or SMS two factor authentication using Vonage's Verify v2 API.

## Installation

   composer require dragonmantank/vonabase

You will then need to create a new Auth client, which takes in the Vonage User API object from the Vonage Client. Since the Users API is tied to a Vonage Application, you must use Keypair authentication.

```php
use Vonage\Client;
use Vonage\Client\Credentials\Keypair;
use Vonage\Vonabase\Auth\Client as AuthClient;

require_once __DIR__ . '/vendor/autoload.php';

$vonage = new Client(new Keypair(
    file_get_contents(__DIR__ . '/private.key'),
    'vonage-application-id',
));
$auth = new AuthClient($vonage->users());
```

## Usage

### Email and Password Authentication

```php
$user = $auth->createUserWithEmailAndPassword('chris@ctankersley.com', 'password', ['first_name' => 'chris']);
var_dump($user);

if ($auth->signInWithEmailAndPassword('chris@ctankersley.com', 'password')) {
    echo 'User logged in' . PHP_EOL;
} else {
    echo 'Invalid password' . PHP_EOL;
}
```

### Email and Out Of Band Code Authentication

```php
$email = 'chris@ctankersley.com';
$auth->createUserWithEmailAndSMS($email, '15554441234');
$requestId = $auth->sendOobCode($email);

try {
    $user = $auth->signInWithEmailAndOobCode($mail, $requestId, $codeFromUser);
} catch (\RuntimeException $e) {
    echo 'Invalid pin supplied' . PHP_EOL;
}
```