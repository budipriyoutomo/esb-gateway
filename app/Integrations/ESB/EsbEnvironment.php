<?php

namespace App\Integrations\ESB;

enum EsbEnvironment: string
{
    case Production = 'production';
    case Staging = 'staging';
    case StagingInt = 'staging_int';
}
