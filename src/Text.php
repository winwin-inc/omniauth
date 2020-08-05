<?php

declare(strict_types=1);

namespace winwin\omniauth;

final class Text
{
    public static function camelize(string $str, ?string $delimiter = null): string
    {
        $sep = "\x00";
        $delimiter = (null === $delimiter ? ['_', '-'] : str_split($delimiter));

        return implode('', array_map('ucfirst', explode($sep, str_replace($delimiter, $sep, $str))));
    }

    public static function isNotEmpty(?string $text): bool
    {
        return isset($text) && '' !== $text;
    }

    public static function isEmpty(?string $text): bool
    {
        return !isset($text) || '' === $text;
    }
}
