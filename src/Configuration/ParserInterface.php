<?php declare(strict_types=1);
namespace Phx\Configuration;

/**
 * Standardization of a file parser.
 */
interface ParserInterface
{
    public function parse(string $file): array;
}