<?php
declare(strict_types=1);

namespace Vonage\Vonabase\Auth;

use Vonage\Client\Exception\Request;
use Vonage\Users\Client as UsersClient;
use Vonage\Users\User;
use Vonage\Verify2\Request\SMSRequest;
use Vonage\Verify2\VerifyObjects\VerificationWorkflow;
use Vonage\Vonabase\Auth\OobCode\VerifyRequest;

class Client
{
    public function __construct(protected UsersClient $client)
    {

    }

    public function createUserWithEmailAndPassword(string $email, string $password, array $metadata = []): User
    {
        $metadata = array_merge(
            $metadata,
            ['password' => password_hash($password, PASSWORD_BCRYPT)]
        );

        $user = new User();
        $user->setName($email);
        $user->setProperties(['custom_data' => $metadata]);
        $user = $this->client->createUser($user);
        return $user;
    }

    public function createUserWithEmailAndSMS(string $email, string $mobile, array $metadata = []): User
    {
        $user = new User();
        $user->setName($email);
        $user->setProperties(['custom_data' => $metadata]);
        $user->setChannels([
            'sms' => [['number' => $mobile]]
        ]);

        $user = $this->client->createUser($user);
        
        return $user;
    }

    public function deleteUser(string $email)
    {
        try {
            $user = $this->client->getUser($email);    
        } catch (Request $e) {
            throw new \RuntimeException('Could not delete requested user');
        }
        
        $this->client->deleteUser($user->getId());
    }

    public function getUser(string $email): User
    {
        try {
            return $this->client->getUser($email);
        } catch (Request $e) {
            throw new \RuntimeException('User not found');
        }
    }

    public function sendOobCode(string $email, string $channel = null): string
    {
        $allowedChannels = [
            VerificationWorkflow::WORKFLOW_SMS => SMSRequest::class,
        ];

        $user = $this->getUser($email);
        $channels = $user->getChannels();

        if (count($channels) === 0) {
            throw new \RuntimeException('No OOB Channels registered for user');
        }

        if (!is_null($channel) && !isset($channels[$channel])) {
            throw new \RuntimeException('Requested OOB Channel ' . $channel . ' is not registered for user');
        }

        if (!is_null($channel) && isset($channels[$channel])) {
            /** @var class-string<BaseVerifyRequest> */
            $className = $allowedChannels[$channel];
            return $this->client->getClient()->verify2()->startVerification(new $className($channels[$channel]['to'], 'Vonabase'))['request_id'];
        }

        if (is_null($channel)) {
            $request = new VerifyRequest();
            foreach ($allowedChannels as $key => $className) {
                if (isset($channels[$key])) {
                    foreach ($channels[$key] as $number) {
                        $request->addWorkflow(new VerificationWorkflow($key, $number['number'], 'Vonabase'));
                    }
                }
            }
            return $this->client->getClient()->verify2()->startVerification($request)['request_id'];
        }
    }

    public function signInWithEmailAndOobCode(string $email, string $requestId, string $code): User
    {
        $user = $this->getUser($email);
        if ($this->client->getClient()->verify2()->check($requestId, $code)) {
            return $user;
        }

        throw new \RuntimeException('Invalid code supplied');
    }

    public function signInWithEmailAndPassword(string $email, string $password): bool
    {
        try {
            $user = $this->client->getUser($email);
            
            if (!isset($user->getProperties()['custom_data'])) {
                throw new \RuntimeException('User has not registered a password');
            }

            if (!isset($user->getProperties()['custom_data']['password'])) {
                throw new \RuntimeException('User has not registered a password');
            }

            return password_verify($password, $user->getProperties()['custom_data']['password']);
        } catch (Request $e) {
            if ($e->getCode() === 404) {
                throw new \RuntimeException('User not found');
            }
        }
    }

    public function updateUser(string $email = null, string $password = null, array $metadata = []): User
    {
        $user = $this->client->getUser($email);
        $customData = $user->getProperties()['custom_data'] ?? [];

        if (!is_null($email)) {
            $user->setName($email);
        }

        if (!is_null($password)) {
            $metadata['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $user->setProperties(['custom_data' => array_merge($customData, $metadata)]);

        return $this->client->updateUser($user);
    }
}