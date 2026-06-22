<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEmailDomain implements ValidationRule
{
    public static function isAllowed(string $email): bool
    {
        $allowedDomains = config('security.allowed_email_domains', []);

        if ($allowedDomains === []) {
            return true;
        }

        $domain = strtolower(substr(strrchr(strtolower($email), '@') ?: '', 1));

        return $domain !== '' && in_array($domain, array_map('strtolower', $allowedDomains), true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isAllowed((string) $value)) {
            $fail(self::rejectionMessage());
        }
    }

    public static function rejectionMessage(): string
    {
        $domains = config('security.allowed_email_domains', []);

        if (count($domains) === 1) {
            return 'Only @'.$domains[0].' email addresses are allowed.';
        }

        return 'Only approved company email addresses are allowed.';
    }
}
