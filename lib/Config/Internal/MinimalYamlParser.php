<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Config\Internal;

use LiquidRazor\FileLocator\Exception\YamlParseException;

final class MinimalYamlParser
{
    public function parseFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new YamlParseException($path, 'Unable to read file.');
        }

        return $this->parse($contents, $path);
    }

    public function parse(string $yaml, string $path = '<memory>'): array
    {
        $lines = $this->tokenize($yaml, $path);

        if ($lines === []) {
            return [];
        }

        $index = 0;
        $data = $this->parseMapping($lines, $index, 0, $path);

        if (isset($lines[$index])) {
            throw $this->error($path, $lines[$index]['line'], 'Unexpected trailing content.');
        }

        return $data;
    }

    /**
     * @return list<array{indent:int, content:string, line:int}>
     */
    private function tokenize(string $yaml, string $path): array
    {
        $yaml = str_replace("\r\n", "\n", $yaml);
        $yaml = str_replace("\r", "\n", $yaml);

        $lines = [];

        foreach (explode("\n", $yaml) as $lineNumber => $rawLine) {
            if ($rawLine === '') {
                continue;
            }

            if (preg_match('/^[ ]*\t/', $rawLine) === 1) {
                throw $this->error($path, $lineNumber + 1, 'Tabs are not supported for indentation.');
            }

            if (preg_match('/^ +$/', $rawLine) === 1) {
                continue;
            }

            preg_match('/^ */', $rawLine, $matches);
            $indentation = strlen($matches[0]);

            if ($indentation % 2 !== 0) {
                throw $this->error($path, $lineNumber + 1, 'Indentation must use multiples of two spaces.');
            }

            $content = substr($rawLine, $indentation);

            if ($content === '' || str_starts_with($content, '#')) {
                continue;
            }

            $lines[] = [
                'indent' => $indentation,
                'content' => $content,
                'line' => $lineNumber + 1,
            ];
        }

        return $lines;
    }

    /**
     * @param list<array{indent:int, content:string, line:int}> $lines
     * @return array<string, mixed>
     */
    private function parseMapping(array $lines, int &$index, int $indent, string $path): array
    {
        $result = [];

        while (isset($lines[$index])) {
            $line = $lines[$index];

            if ($line['indent'] < $indent) {
                break;
            }

            if ($line['indent'] > $indent) {
                throw $this->error($path, $line['line'], 'Unexpected indentation.');
            }

            if (str_starts_with($line['content'], '- ')) {
                throw $this->error($path, $line['line'], 'Unexpected list item in mapping context.');
            }

            if (!preg_match('/^(<<|[A-Za-z0-9_.-]+):(.*)$/', $line['content'], $matches)) {
                throw $this->error($path, $line['line'], 'Invalid mapping entry.');
            }

            $key = $matches[1];
            $rawValue = ltrim($matches[2], ' ');

            if ($key === '<<') {
                throw $this->error($path, $line['line'], 'YAML merge keys are not supported.');
            }

            ++$index;

            if ($rawValue === '') {
                if (!isset($lines[$index])) {
                    throw $this->error($path, $line['line'], sprintf('Missing indented block for key "%s".', $key));
                }

                $nextLine = $lines[$index];

                if ($nextLine['indent'] <= $indent) {
                    throw $this->error($path, $line['line'], sprintf('Missing indented block for key "%s".', $key));
                }

                if ($nextLine['indent'] !== $indent + 2) {
                    throw $this->error($path, $nextLine['line'], 'Nested indentation must increase by two spaces.');
                }

                if (str_starts_with($nextLine['content'], '- ')) {
                    $result[$key] = $this->parseList($lines, $index, $nextLine['indent'], $path);
                    continue;
                }

                $result[$key] = $this->parseMapping($lines, $index, $nextLine['indent'], $path);
                continue;
            }

            $result[$key] = $this->parseValue($rawValue, $path, $line['line']);
        }

        return $result;
    }

    /**
     * @param list<array{indent:int, content:string, line:int}> $lines
     * @return list<bool|string>
     */
    private function parseList(array $lines, int &$index, int $indent, string $path): array
    {
        $result = [];

        while (isset($lines[$index])) {
            $line = $lines[$index];

            if ($line['indent'] < $indent) {
                break;
            }

            if ($line['indent'] > $indent) {
                throw $this->error($path, $line['line'], 'Nested list items are not supported.');
            }

            if (!str_starts_with($line['content'], '- ')) {
                throw $this->error($path, $line['line'], 'Mixed mappings and lists are not supported.');
            }

            $rawValue = substr($line['content'], 2);

            if ($rawValue === '') {
                throw $this->error($path, $line['line'], 'Empty list items are not supported.');
            }

            $value = $this->parseValue($rawValue, $path, $line['line']);

            if (is_array($value)) {
                throw $this->error($path, $line['line'], 'Nested lists or inline mappings are not supported.');
            }

            $result[] = $value;
            ++$index;
        }

        return $result;
    }

    private function parseValue(string $value, string $path, int $line): bool|string|array
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value === '[]') {
            return [];
        }

        if ($value[0] === '[') {
            if (!str_ends_with($value, ']')) {
                throw $this->error($path, $line, 'Invalid inline list.');
            }

            return $this->parseInlineList(substr($value, 1, -1), $path, $line);
        }

        if ($value[0] === '{') {
            throw $this->error($path, $line, 'Inline mappings are not supported.');
        }

        if ($value[0] === '&' || $value[0] === '*') {
            throw $this->error($path, $line, 'Anchors and aliases are not supported.');
        }

        if ($value[0] === '|' || $value[0] === '>') {
            throw $this->error($path, $line, 'Multiline scalars are not supported.');
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === '\'' && str_ends_with($value, '\'')))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * @return list<bool|string>
     */
    private function parseInlineList(string $value, string $path, int $line): array
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return [];
        }

        $items = [];

        foreach (explode(',', $trimmed) as $item) {
            $item = trim($item);

            if ($item === '') {
                throw $this->error($path, $line, 'Inline lists cannot contain empty items.');
            }

            $parsedItem = $this->parseValue($item, $path, $line);

            if (is_array($parsedItem)) {
                throw $this->error($path, $line, 'Nested lists or inline mappings are not supported.');
            }

            $items[] = $parsedItem;
        }

        return $items;
    }

    private function error(string $path, int $line, string $reason): YamlParseException
    {
        return new YamlParseException($path, sprintf('Line %d: %s', $line, $reason));
    }
}
