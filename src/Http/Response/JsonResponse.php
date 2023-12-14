<?php declare(strict_types=1);
namespace Phx\Http\Response;

use InvalidArgumentException;
use Phx\Http\Message\Response;
use RuntimeException;

/**
 * JSON response.
 */
class JsonResponse extends Response
{
    public function __construct(
        int $statusCode = 200,
        mixed $data = null,
        array $headers = [],
        int $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    ) {
        parent::__construct(
            $statusCode,
            $this->jsonEncode($data, $flags),
            array_merge(['Content-Type' => 'application/json'], $headers)
        );
    }

    protected function jsonEncode($data, int $flags): string
    {
        if (is_resource($data)) {
            throw new InvalidArgumentException('Cannot JSON encode resources');
        }

        $data = json_encode($data, $flags);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                "Failed to encode data as JSON. '%s'.", json_last_error_msg()
            ));
        }
        return $data;
    }
}