<?php

namespace App\Support;

class RequestedDocuments
{
    /**
     * @return list<string>
     */
    public static function fromText(?string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/u', (string) $value) ?: [];
        $names = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || in_array($line, $names, true)) {
                continue;
            }

            $names[] = mb_substr($line, 0, 120);
        }

        return array_slice($names, 0, 8);
    }
}
