<?php

namespace Utils;

class Base58
{
    public static string $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

    public static string $regex = '^[123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ]*';

    public static function encode(int $int): string
    {
        $encoded = '';

        while ($int) {
            $remainder = $int % 58;
            $int = floor($int / 58);
            $encoded = self::$alphabet[$remainder].$encoded;
        }

        return $encoded;
    }

    public static function decode(string $str): int
    {
        $decoded = 0;

        while ($str) {
            if (($alphabetPosition = strpos(self::$alphabet, $str[0])) === false) {
                throw new \RuntimeException('decode() cannot find "'.$str[0].'" in alphabet.');
            }

            $decoded += $alphabetPosition * (pow(58, strlen($str) - 1));
            $str = substr($str, 1);
        }

        return $decoded;
    }
}
