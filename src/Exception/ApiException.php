<?php

declare(strict_types=1);

namespace Verifaid\Exception;

use RuntimeException;
use Verifaid\Http\Response;

/**
 * API VerifAID membalas dengan error. Kode exception berisi status HTTP, dan
 * pesannya diambil dari field "message" pada respons.
 */
class ApiException extends RuntimeException implements VerifaidException
{
    private const STATUS_MAP = [
        400 => BadRequestException::class,
        401 => AuthenticationException::class,
        402 => QuotaExceededException::class,
        403 => PermissionDeniedException::class,
        404 => NotFoundException::class,
        422 => UnprocessableEntityException::class,
        429 => RateLimitException::class,
    ];

    private Response $response;

    final public function __construct(string $message, Response $response)
    {
        parent::__construct($message, $response->getStatusCode());
        $this->response = $response;
    }

    /**
     * Buat exception yang sesuai dengan status HTTP respons.
     */
    public static function fromResponse(Response $response): self
    {
        $status = $response->getStatusCode();

        $message = $response->getMessage();
        if ($message === '') {
            $message = $response->json() === null
                ? sprintf('API VerifAID membalas HTTP %d tanpa body JSON yang valid.', $status)
                : sprintf('Request ke API VerifAID gagal (HTTP %d).', $status);
        }

        if (isset(self::STATUS_MAP[$status])) {
            $class = self::STATUS_MAP[$status];

            return new $class($message, $response);
        }

        if ($status >= 500) {
            return new ServerException($message, $response);
        }

        return new self($message, $response);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Data tambahan yang menyertai error, bila ada.
     *
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->response->getData();
    }
}
