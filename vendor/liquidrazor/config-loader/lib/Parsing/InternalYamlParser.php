<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Lib\Parsing;

use LiquidRazor\ConfigLoader\Contract\ConfigParserInterface;
use LiquidRazor\ConfigLoader\Exception\InvalidConfigSyntaxException;

final class InternalYamlParser implements ConfigParserInterface
{
    /**
     * @var list<array{indent: int, content: string, line: int}>
     */
    private array $tokens = [];

    private int $index = 0;

    private string $source = '';

    public function parse(string $contents, string $source): array
    {
        $this->tokens = $this->tokenize($contents, $source);
        $this->index = 0;
        $this->source = $source;

        if ($this->tokens === []) {
            return [];
        }

        if ($this->tokens[0]['indent'] !== 0) {
            throw $this->syntaxError($this->tokens[0]['line'], 'Top-level content must start at indentation level 0.');
        }

        $parsed = $this->parseBlock(0);

        if ($this->currentToken() !== null) {
            $token = $this->currentToken();
            throw $this->syntaxError($token['line'], 'Unexpected trailing content.');
        }

        return $parsed;
    }

    /**
     * @return list<array{indent: int, content: string, line: int}>
     */
    private function tokenize(string $contents, string $source): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $contents);

        if ($lines === false) {
            throw InvalidConfigSyntaxException::forSource($source, 'Unable to split YAML input into lines.');
        }

        $tokens = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (str_contains($line, "\t")) {
                throw InvalidConfigSyntaxException::forSource($source, sprintf('Tabs are not allowed for indentation on line %d.', $lineNumber));
            }

            $cleanLine = rtrim($this->stripComment($line));

            if ($cleanLine === '') {
                continue;
            }

            preg_match('/^ */', $cleanLine, $matches);
            $indentation = strlen($matches[0] ?? '');
            $content = substr($cleanLine, $indentation);

            if ($content === '' || $content === false) {
                continue;
            }

            $tokens[] = [
                'indent' => $indentation,
                'content' => $content,
                'line' => $lineNumber,
            ];
        }

        return $tokens;
    }

    /**
     * @return array<mixed>
     */
    private function parseBlock(int $indent): array
    {
        $token = $this->currentToken();

        if ($token === null) {
            return [];
        }

        if ($token['indent'] !== $indent) {
            throw $this->syntaxError($token['line'], sprintf('Unexpected indentation level %d, expected %d.', $token['indent'], $indent));
        }

        if ($this->isSequenceToken($token['content'])) {
            return $this->parseSequence($indent);
        }

        return $this->parseMapping($indent);
    }

    /**
     * @return list<mixed>
     */
    private function parseSequence(int $indent): array
    {
        $sequence = [];

        while (($token = $this->currentToken()) !== null && $token['indent'] === $indent) {
            if (!$this->isSequenceToken($token['content'])) {
                throw $this->syntaxError($token['line'], 'Cannot mix mapping and sequence entries at the same indentation level.');
            }

            $rest = ltrim(substr($token['content'], 1));
            ++$this->index;

            if ($rest === '') {
                $sequence[] = $this->parseNestedValueOrNull($indent, $token['line']);
                continue;
            }

            if ($this->isMappingEntry($rest)) {
                array_splice($this->tokens, $this->index, 0, [[
                    'indent' => $indent + 2,
                    'content' => $rest,
                    'line' => $token['line'],
                ]]);

                $sequence[] = $this->parseBlock($indent + 2);
                continue;
            }

            $sequence[] = $this->parseScalar($rest, $token['line']);
            $this->assertNoUnexpectedNestedContent($indent, $token['line']);
        }

        return $sequence;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseMapping(int $indent): array
    {
        $mapping = [];

        while (($token = $this->currentToken()) !== null && $token['indent'] === $indent) {
            if ($this->isSequenceToken($token['content'])) {
                throw $this->syntaxError($token['line'], 'Cannot mix sequence and mapping entries at the same indentation level.');
            }

            [$key, $value] = $this->splitMappingEntry($token['content'], $token['line']);
            ++$this->index;

            if ($value === null) {
                $mapping[$key] = $this->parseNestedValueOrNull($indent, $token['line']);
                continue;
            }

            $mapping[$key] = $this->parseScalar($value, $token['line']);
            $this->assertNoUnexpectedNestedContent($indent, $token['line']);
        }

        return $mapping;
    }

    private function parseNestedValueOrNull(int $indent, int $line): mixed
    {
        $nextToken = $this->currentToken();

        if ($nextToken === null || $nextToken['indent'] <= $indent) {
            return null;
        }

        return $this->parseBlock($nextToken['indent']);
    }

    private function assertNoUnexpectedNestedContent(int $indent, int $line): void
    {
        $nextToken = $this->currentToken();

        if ($nextToken !== null && $nextToken['indent'] > $indent) {
            throw $this->syntaxError($line, 'Unexpected nested block after an inline scalar value.');
        }
    }

    private function isSequenceToken(string $content): bool
    {
        return str_starts_with($content, '- ')
            || $content === '-';
    }

    private function isMappingEntry(string $content): bool
    {
        return $this->findMappingSeparator($content) !== null;
    }

    /**
     * @return array{string, ?string}
     */
    private function splitMappingEntry(string $content, int $line): array
    {
        $separatorPosition = $this->findMappingSeparator($content);

        if ($separatorPosition === null) {
            throw $this->syntaxError($line, 'Expected a YAML mapping entry in the form "key: value".');
        }

        $rawKey = trim(substr($content, 0, $separatorPosition));

        if ($rawKey === '') {
            throw $this->syntaxError($line, 'YAML mapping keys must not be empty.');
        }

        $key = $this->parseKey($rawKey, $line);
        $rawValue = ltrim(substr($content, $separatorPosition + 1));

        return [$key, $rawValue === '' ? null : $rawValue];
    }

    private function parseKey(string $rawKey, int $line): string
    {
        if (
            (str_starts_with($rawKey, '"') && str_ends_with($rawKey, '"'))
            || (str_starts_with($rawKey, '\'') && str_ends_with($rawKey, '\''))
        ) {
            $parsed = $this->parseScalar($rawKey, $line);

            if (!is_string($parsed)) {
                throw $this->syntaxError($line, 'Quoted YAML mapping keys must resolve to strings.');
            }

            return $parsed;
        }

        return $rawKey;
    }

    private function findMappingSeparator(string $content): ?int
    {
        $inSingleQuotes = false;
        $inDoubleQuotes = false;
        $length = strlen($content);

        for ($index = 0; $index < $length; ++$index) {
            $character = $content[$index];

            if ($character === '\'' && !$inDoubleQuotes) {
                if ($inSingleQuotes && ($content[$index + 1] ?? null) === '\'') {
                    ++$index;
                    continue;
                }

                $inSingleQuotes = !$inSingleQuotes;
                continue;
            }

            if ($character === '"' && !$inSingleQuotes) {
                $escaped = $index > 0 && $content[$index - 1] === '\\';

                if (!$escaped) {
                    $inDoubleQuotes = !$inDoubleQuotes;
                }

                continue;
            }

            if ($character !== ':' || $inSingleQuotes || $inDoubleQuotes) {
                continue;
            }

            $nextCharacter = $content[$index + 1] ?? null;

            if ($nextCharacter === null || $nextCharacter === ' ') {
                return $index;
            }
        }

        return null;
    }

    private function parseScalar(string $value, int $line): mixed
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with($trimmed, '\'') && str_ends_with($trimmed, '\'')) {
            return str_replace('\'\'', '\'', substr($trimmed, 1, -1));
        }

        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            $decoded = stripcslashes(substr($trimmed, 1, -1));

            if ($decoded === false) {
                throw $this->syntaxError($line, 'Invalid double-quoted YAML string.');
            }

            return $decoded;
        }

        return match (strtolower($trimmed)) {
            'null', '~' => null,
            'true' => true,
            'false' => false,
            default => $this->parseNumericOrString($trimmed),
        };
    }

    private function parseNumericOrString(string $value): mixed
    {
        if (preg_match('/^-?(?:0|[1-9][0-9]*)$/', $value) === 1) {
            return (int) $value;
        }

        if (preg_match('/^-?(?:0|[1-9][0-9]*)\\.[0-9]+$/', $value) === 1) {
            return (float) $value;
        }

        return $value;
    }

    private function stripComment(string $line): string
    {
        $inSingleQuotes = false;
        $inDoubleQuotes = false;
        $length = strlen($line);

        for ($index = 0; $index < $length; ++$index) {
            $character = $line[$index];

            if ($character === '\'' && !$inDoubleQuotes) {
                if ($inSingleQuotes && ($line[$index + 1] ?? null) === '\'') {
                    ++$index;
                    continue;
                }

                $inSingleQuotes = !$inSingleQuotes;
                continue;
            }

            if ($character === '"' && !$inSingleQuotes) {
                $escaped = $index > 0 && $line[$index - 1] === '\\';

                if (!$escaped) {
                    $inDoubleQuotes = !$inDoubleQuotes;
                }

                continue;
            }

            if ($character === '#' && !$inSingleQuotes && !$inDoubleQuotes) {
                return substr($line, 0, $index);
            }
        }

        return $line;
    }

    /**
     * @return array{indent: int, content: string, line: int}|null
     */
    private function currentToken(): ?array
    {
        return $this->tokens[$this->index] ?? null;
    }

    private function syntaxError(int $line, string $reason): InvalidConfigSyntaxException
    {
        return InvalidConfigSyntaxException::forSource($this->source, sprintf('%s (line %d)', $reason, $line));
    }
}
