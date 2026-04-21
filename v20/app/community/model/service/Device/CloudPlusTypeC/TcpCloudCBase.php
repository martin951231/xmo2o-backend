<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class TcpCloudCBase
{
    protected static function buildFrame(array $bytes)
    {
        $normalized = [];
        foreach ($bytes as $byte) {
            $normalized[] = Tool::normalizeHexByte($byte);
        }
        $normalized[] = Tool::checksum($normalized);
        $normalized[] = '03';
        return Tool::bytesToHex($normalized);
    }
}