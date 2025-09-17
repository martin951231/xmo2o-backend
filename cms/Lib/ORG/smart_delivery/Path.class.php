<?php

class Path
{
    public static function combine($a, $m)
    {
        $r = array();
        $n = count($a);
        if ($m <= 0 || $m > $n) {
            return $r;
        }
        for ($i = 0; $i < $n; $i++) {
            $b = $a;
            $t = array_splice($b, $i, 1);
            if ($m == 1) {
                $r[] = $t;
            } else {
                $c = self::combine($b, $m - 1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }
        return $r;
    }
}