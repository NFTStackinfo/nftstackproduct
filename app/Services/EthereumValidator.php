<?php

namespace App\Services;

use kornrunner\Keccak;

class EthereumValidator
{
    /**
     * @param string $address
     * @return bool
     * @throws Exception
     */
    public static function isAddress(string $address): bool {
        if (self::matchesPattern($address)) {
            return self::isAllSameCaps($address) ?: self::isValidChecksum($address);
        }

        return false;
    }

    /**
     * @param string $address
     * @return int
     */
    protected static function matchesPattern(string $address): int {
        return preg_match('/^(0x)?[0-9a-f]{40}$/i', $address);
    }

    /**
     * @param string $address
     * @return bool
     */
    protected static function isAllSameCaps(string $address): bool {
        return preg_match('/^(0x)?[0-9a-f]{40}$/', $address) || preg_match('/^(0x)?[0-9A-F]{40}$/', $address);
    }

    /**
     * @param $address
     * @return bool
     * @throws Exception
     */
    protected static function isValidChecksum($address) {
        $address = str_replace('0x', '', $address);
        $hash = Keccak::hash(strtolower($address), 256);

        for ($i = 0; $i < 40; $i++ ) {
            if (ctype_alpha($address[$i])) {
                $charInt = intval($hash[$i], 16);

                if ((ctype_upper($address[$i]) && $charInt <= 7) || (ctype_lower($address[$i]) && $charInt > 7)) {
                    return false;
                }
            }
        }

        return true;
    }
}
