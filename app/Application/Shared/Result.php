<?php

namespace App\Application\Shared;

class Result
{
    public function __construct(
        private bool $success,
        private mixed $payload = null,
        private ?string $errorCode = null,
        private ?string $errorMessage = null,
        private array $context = []
    ) {}

    public static function ok(mixed $payload = null): self
    {
        return new self(true, $payload);
    }

    public static function error(string $code, string $message, array $context = []): self
    {
        return new self(false, null, $code, $message, $context);
    }

    public function isOk(): bool
    {
        return $this->success;
    }

    public function isError(): bool
    {
        return !$this->success;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
