<?php

namespace App\Integrations\Zkteco\Exceptions;

use RuntimeException;

class ZktecoIntegrationException extends RuntimeException
{
    public const INVALID_CREDENTIALS = 'invalid_credentials';
    public const HOST_UNREACHABLE = 'host_unreachable';
    public const SSL_ERROR = 'ssl_error';
    public const UNSUPPORTED_API = 'unsupported_api';
    public const RATE_LIMITED = 'rate_limited';
    public const REMOTE_ERROR = 'remote_error';
    public const UNKNOWN = 'unknown';

    public function __construct(
        string $reason,
        string $message,
        public readonly array $context = [],
        int $code = 0,
    ) {
        parent::__construct($message, $code);

        $this->reason = $reason;
    }

    public readonly string $reason;

    public static function invalidCredentials(string $message = 'Invalid ZKBio credentials.'): self
    {
        return new self(self::INVALID_CREDENTIALS, $message);
    }

    public static function hostUnreachable(string $message = 'Unable to reach the ZKBio host.'): self
    {
        return new self(self::HOST_UNREACHABLE, $message);
    }

    public static function sslError(string $message = 'TLS certificate validation failed.'): self
    {
        return new self(self::SSL_ERROR, $message);
    }

    public static function unsupportedApi(string $message = 'This ZKBio installation does not expose a supported API mode.'): self
    {
        return new self(self::UNSUPPORTED_API, $message);
    }

    public static function rateLimited(string $message = 'ZKBio API rate limit reached.'): self
    {
        return new self(self::RATE_LIMITED, $message);
    }

    public static function remoteError(string $message = 'ZKBio API request failed.', array $context = []): self
    {
        return new self(self::REMOTE_ERROR, $message, $context);
    }

    public static function unknown(string $message = 'Unexpected ZKTeco integration error.', array $context = []): self
    {
        return new self(self::UNKNOWN, $message, $context);
    }
}

