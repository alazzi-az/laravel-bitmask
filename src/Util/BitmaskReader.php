<?php

namespace Alazziaz\LaravelBitmask\Util;

class BitmaskReader
{
    public function getActiveBits(int $bitmask): array
    {
        $activeBits = [];
        $bitPosition = 1;

        while ($bitmask >= $bitPosition) {
            if ($bitmask & $bitPosition) {
                $activeBits[] = $bitPosition;
            }
            $bitPosition <<= 1;
        }

        return $activeBits;
    }

    public function getMostSignificantBitIndex(int $bitmask): int
    {
        return (int) log($bitmask, 2);
    }

    public function convertToBinaryString(int $bitmask): string
    {
        return decbin($bitmask);
    }
}
