<?php

namespace App\Events;

use App\Models\SchoolDayConfiguration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchoolDayConfigurationUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SchoolDayConfiguration $schoolDayConfiguration,
    ) {
    }
}
