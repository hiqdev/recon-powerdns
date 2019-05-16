<?php

namespace hiqdev\recon\powerdns\Service;

use hiqdev\recon\core\Exception\ReconException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class PowerDnsResponseException represents an error received from the remote
 * PowerDNS service.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class PowerDnsResponseException extends ReconException
{
    /** @var ResponseInterface */
    protected $response;

    private function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(ResponseInterface $response, string $message): self
    {
        $exception = new self("$message: {$response->getStatusCode()} ({$response->getReasonPhrase()})");
        $exception->response = $response;

        return $exception;
    }

    public static function fromException(\Throwable $previousException, string $message): self
    {
        $exception = new self("$message: {$previousException->getMessage()}", 0, $previousException);

        return $exception;
    }

    public function getResponseBody(): ?string
    {
        return $this->response->getBody()->getContents();
    }
}
