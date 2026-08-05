<?php
/**
 * Rosetta reference adapter: PHP.
 *
 * Implements the stdin/stdout JSON contract documented in
 * docs/ADAPTER_CONTRACT.md. Reads a single JSON request object from stdin,
 * writes a single JSON response object to stdout, and exits 0 even when the
 * request itself is invalid (invalid input is reported via the `error`
 * field, not a crash/non-zero exit).
 *
 * Supported operation: "convert" - converts an identifier between
 * snake_case, camelCase, PascalCase and kebab-case.
 */

declare(strict_types=1);

const VALID_STYLES = ['snake', 'camel', 'pascal', 'kebab'];

/**
 * Break an identifier into lowercase word tokens, regardless of its
 * original case style.
 *
 * @return string[]
 */
function tokenize(string $identifier): array
{
    $identifier = trim($identifier);

    if (strpos($identifier, '_') !== false) {
        $parts = explode('_', $identifier);
    } elseif (strpos($identifier, '-') !== false) {
        $parts = explode('-', $identifier);
    } else {
        // camelCase / PascalCase: insert a space before every interior
        // capital letter, then split on whitespace.
        $spaced = preg_replace('/(?<!^)([A-Z])/', ' $1', $identifier);
        $parts = explode(' ', $spaced);
    }

    $tokens = [];
    foreach ($parts as $part) {
        if ($part !== '') {
            $tokens[] = strtolower($part);
        }
    }

    return $tokens;
}

function joinTokens(array $tokens, string $style): string
{
    switch ($style) {
        case 'snake':
            return implode('_', $tokens);
        case 'kebab':
            return implode('-', $tokens);
        case 'camel':
            $result = '';
            foreach ($tokens as $i => $token) {
                $result .= $i === 0 ? $token : ucfirst($token);
            }
            return $result;
        case 'pascal':
            $result = '';
            foreach ($tokens as $token) {
                $result .= ucfirst($token);
            }
            return $result;
        default:
            throw new InvalidArgumentException("Unknown case style: {$style}");
    }
}

function handle(array $request): array
{
    $operation = $request['operation'] ?? null;
    if ($operation !== 'convert') {
        throw new InvalidArgumentException(
            'Unsupported operation: ' . ($operation === null ? 'null' : (string) $operation)
        );
    }

    $input = $request['input'] ?? null;
    if (!is_string($input) || $input === '') {
        throw new InvalidArgumentException('Input must be a non-empty string.');
    }

    $options = $request['options'] ?? [];
    $to = is_array($options) ? ($options['to'] ?? null) : null;
    $from = is_array($options) ? ($options['from'] ?? null) : null;

    if (!in_array($to, VALID_STYLES, true)) {
        throw new InvalidArgumentException(
            'Unsupported target case: ' . ($to === null ? 'null' : (string) $to)
        );
    }
    if ($from !== null && !in_array($from, VALID_STYLES, true)) {
        throw new InvalidArgumentException("Unsupported source case: {$from}");
    }

    $tokens = tokenize($input);
    if (count($tokens) === 0) {
        throw new InvalidArgumentException('Could not tokenize input.');
    }

    return ['output' => joinTokens($tokens, $to), 'error' => null];
}

$raw = stream_get_contents(STDIN);
$response = ['output' => null, 'error' => null];

try {
    $request = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($request)) {
        throw new InvalidArgumentException('Request payload must be a JSON object.');
    }
    $response = handle($request);
} catch (Throwable $e) {
    $response = ['output' => null, 'error' => $e->getMessage()];
}

fwrite(STDOUT, json_encode($response) . "\n");
