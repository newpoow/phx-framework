<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Represents values of a file uploaded through an HTTP Request.
 */
class UploadedFile implements UploadedFileInterface
{
    protected const ERROR_MESSAGES = array(
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
    );

    protected int $error;
    protected int $size;

    public function __construct(
        protected ?StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        protected ?string $filename = null,
        protected ?string $mediaType = null
    ) {
        if (0 > $error || 8 < $error) {
            throw new InvalidArgumentException(
                "Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant."
            );
        }

        $this->error = $error;
        $this->size = $size ?: $stream->getSize();
    }

    public function getClientFilename(): ?string
    {
        return $this->filename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getStream(): StreamInterface
    {
        if (!$this->stream instanceof StreamInterface) {
            throw new RuntimeException(
                "Cannot retrieve stream after it has already been moved."
            );
        }
        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if (!($this->stream instanceof StreamInterface) || UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException(
                self::ERROR_MESSAGES[$this->error] ??
                "The uploaded file cannot be moved due to an error or already moved."
            );
        }

        $folder = dirname($targetPath);
        if (!is_dir($folder) || !is_writable($folder)) {
            throw new InvalidArgumentException(sprintf(
                "The directory '%s' does not exists or is not writable.", $folder
            ));
        }

        set_error_handler(function () use ($targetPath) {
            throw new RuntimeException(sprintf(
                "Uploaded file could not be moved to '%s'.", $targetPath
            ));
        });
        $resource = fopen($targetPath, 'wb+');
        restore_error_handler();

        $target = new Stream($resource);
        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $target->write($this->stream->read(4096));
        }

        $this->stream->close();
        $this->stream = null;
        $target->close();
    }
}