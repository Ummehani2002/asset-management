<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedDomains = config('security.allowed_email_domains', []);

        if ($allowedDomains === []) {
            return;
        }

        $email = strtolower((string) $value);
        $domain = substr(strrchr($email, '@') ?: '', 1);

        if ($domain === '' || ! in_array($domain, array_map('strtolower', $allowedDomains), true)) {
            $fail('Registration is restricted to approved company email addresses.');
        }
    }
}
