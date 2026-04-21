<?php

namespace app\community\model\service\Device\CloudPlusTypeC;

class CommandFactory
{
    public static function build($command, $door = '01')
    {
        $command = trim((string)$command);
        $door = trim((string)$door) ?: '01';

        switch ($command) {
            case 'Command_2C':
                return self::result($command, 'Open Door', $door, TcpCloudCCmd::command2C($door));
            case 'Command_2E':
                return self::result($command, 'Close Door', $door, TcpCloudCCmd::command2E($door));
            case 'Command_2D':
                return self::result($command, 'Normally Open', $door, TcpCloudCCmd::command2D($door));
            case 'Command_2F':
                return self::result($command, 'Lock Door', $door, TcpCloudCCmd::command2F($door, '1'));
            case 'Command_2F_2':
                return self::result($command, 'Unlock Door', $door, TcpCloudCCmd::command2F($door, '0'));
            case 'Command_07':
                return self::result($command, 'Sync Time', $door, TcpCloudCCmd::command07());
            default:
                throw new \InvalidArgumentException('Unsupported command type: ' . $command);
        }
    }

    private static function result($command, $label, $door, $cmdValue)
    {
        return [
            'command' => $command,
            'label' => $label,
            'door' => str_pad((string)$door, 2, '0', STR_PAD_LEFT),
            'cmd_value' => strtoupper($cmdValue),
        ];
    }
}