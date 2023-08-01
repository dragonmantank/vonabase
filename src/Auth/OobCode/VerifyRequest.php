<?php
declare(strict_types=1);

namespace Vonage\Vonabase\Auth\OobCode;

use Vonage\Verify2\Request\BaseVerifyRequest;
use Vonage\Verify2\VerifyObjects\VerificationLocale;

class VerifyRequest extends BaseVerifyRequest
{
    public function __construct()
    {
        $this->setLocale(new VerificationLocale());
    }
    
    public function toArray(): array
    {
        return $this->getBaseVerifyUniversalOutputArray();
    }
}
