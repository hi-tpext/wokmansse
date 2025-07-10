<?php

namespace wokmansse\common\model;

use think\Model;

class WokSseApp extends Model
{
    protected $name = 'wok_sse_app';

    protected $autoWriteTimestamp = 'datetime';
}
