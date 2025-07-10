<?php

namespace wokmansse\common\model;

use think\Model;

class WokSseUser extends Model
{
    protected $name = 'wok_sse_user';

    protected $autoWriteTimestamp = 'datetime';

    public function app()
    {
        return $this->belongsTo(WokSseApp::class, 'app_id', 'id');
    }
}
