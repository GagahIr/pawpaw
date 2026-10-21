<?php

namespace App\DTOs;

readonly class GoogleOAuthContextData
{
    public function __construct(
        public string $state,
        public string $verifier,
        public int $startedAt,
        public bool $remember = false,
        public bool $registration = false,
        public bool $termsAccepted = false,
    ) {}

    public static function fromSession(mixed $context): ?self
    {
        if (! is_array($context)
            || ! is_string($context['state'] ?? null)
            || ! is_string($context['verifier'] ?? null)
            || ! is_int($context['started_at'] ?? null)) {
            return null;
        }

        return new self(
            state: $context['state'],
            verifier: $context['verifier'],
            startedAt: $context['started_at'],
            remember: (bool) ($context['remember'] ?? false),
            registration: ($context['intent'] ?? null) === 'register',
            termsAccepted: (bool) ($context['terms_accepted'] ?? false),
        );
    }

    /**
     * @return array{state: string, verifier: string, started_at: int, remember: bool, intent: string, terms_accepted: bool}
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'verifier' => $this->verifier,
            'started_at' => $this->startedAt,
            'remember' => $this->remember,
            'intent' => $this->registration ? 'register' : 'login',
            'terms_accepted' => $this->termsAccepted,
        ];
    }
}
