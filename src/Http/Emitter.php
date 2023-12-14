<?php declare(strict_types=1);
namespace Phx\Http;

use InvalidArgumentException;
use Phx\Http\Exceptions\HeadersAlreadySentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Emits a Response to the PHP Server API.
 */
final class Emitter
{
    protected bool $ignoreHeaderSend = false;

    public static function makeOf(ResponseInterface $response): Emitter
    {
        return new Emitter($response);
    }

    public function __construct(
        private readonly ResponseInterface $response, protected int $bufferLength = 4096
    ) {
        if ($this->bufferLength !== null && $this->bufferLength < 1) {
            throw new InvalidArgumentException(sprintf(
                'Buffer length for `%s` must be greater than zero; received `%d`.',
                self::class,
                $this->bufferLength
            ));
        }
    }

    public function getBufferLength(): int
    {
        return $this->bufferLength;
    }

    public function isIgnoreHeaderSend(): bool
    {
        return $this->ignoreHeaderSend;
    }

    public function setBufferLength(int $bufferLength): self
    {
        $this->bufferLength = $bufferLength;
        return $this;
    }

    public function setIgnoreHeaderSend(bool $ignoreHeaderSend): self
    {
        $this->ignoreHeaderSend = $ignoreHeaderSend;
        return $this;
    }

    public function send(bool $withoutBody = false): ResponseInterface
    {
        if ($this->assertNoPreviousOutput()) {
            $this->sendHeaders($this->response);
            $this->sendStatusLine($this->response);
        }

        if (!$withoutBody && $this->response->getBody()->isReadable()) {
            $this->sendBody($this->response);
        }
        return $this->response;
    }

    protected function assertNoPreviousOutput(): bool
    {
        $file = $line = null;
        $sent = headers_sent($file, $line);

        if ($sent && !$this->isIgnoreHeaderSend()) {
            throw new HeadersAlreadySentException(sprintf(
                "Unable to emit response: Headers already sent in file '%s' on line '%s'.", $file, $line
            ));
        }
        return !$sent;
    }

    protected function sendBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $buffer = !$response->getHeaderLine('Content-Length') ?
            $body->getSize() : $response->getHeaderLine('Content-Length');
        if (isset($buffer)) {
            while ($buffer > 0 && !$body->eof()) {
                $data = $body->read(min($this->bufferLength, $buffer));
                echo $data;
                $buffer -= strlen($data);
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($this->bufferLength);
            }
        }
    }

    protected function sendHeaders(ResponseInterface $response): void
    {
        $code = $response->getStatusCode();
        foreach ($response->getHeaders() as $header => $values) {
            $header = ucwords($header, '-');

            array_map(function ($value) use ($header, $code) {
                header(sprintf(
                    "%s: %s", $header, $value
                ), stripos($header, 'Set-Cookie') == 0, $code);
            }, $values);
        }
    }

    protected function sendStatusLine(ResponseInterface $response): void
    {
        $code = $response->getStatusCode();
        header(sprintf(
            "HTTP/%s %d %s", $response->getProtocolVersion(), $code, $response->getReasonPhrase()
        ), true, $code);
    }
}