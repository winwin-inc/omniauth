<?php

namespace winwin\omniauth;

class Text
{
    public static function camelize($str, $delimiter = null)
    {
        $sep = "\x00";
        $delimiter = null === $delimiter ? ['_'] : str_split($delimiter);

        return implode('', array_map('ucfirst', explode($sep, str_replace($delimiter, $sep, $str))));
    }
}
