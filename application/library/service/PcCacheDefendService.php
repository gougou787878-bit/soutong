<?php

namespace service;

class PcCacheDefendService
{
    public static function defendAll()
    {
        FileService::publishJob('delete', 'all');
    }
}

