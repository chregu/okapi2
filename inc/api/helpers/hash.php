<?php

class api_helpers_hash {
    /**
     * cryptographic hash, use for passwords
     *
     * default length using whirlpool is 128 chars
     */
    public static function hash($data, $maxLength = null, $algo = 'whirlpool') {
        $hash = hash($algo, $data);
        return $maxLength ? substr($hash, 0, $maxLength) : $hash;
    }

    /**
     * generates a random cryptographic hash with good entropy
     *
     * default length using whirlpool is 128 chars
     */
    public static function generate($maxLength = null, $algo = 'whirlpool') {
        $entropy = uniqid(mt_rand(), true);
        if (is_readable('/dev/urandom')) {
            $h = fopen('/dev/urandom', 'r');
            $entropy .= fread($h, 64);
            fclose($h);
        }
        return self::hash($entropy, $maxLength, $algo);
    }

    /**
     * does a fast low quality checksum of given data
     *
     * default length using fnv132 or md4 is 32 chars
     */
    public static function checksum($data, $maxLength = null, $algo = 'fnv132') {
        // fallback to md4 since fnv132 will only
        // be available in the next php version
        if (!in_array($algo, hash_algos())) {
            $algo = 'md4';
        }
        return self::hash($data, $maxLength, $algo);
    }
}
