<?php
class HookModel extends Model
{
    public function exec($name, $params)
    {
        hook::hook_exec($name, $params);
    }
}
