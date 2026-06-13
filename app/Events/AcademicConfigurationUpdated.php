<?php

namespace App\Events;

use App\Models\AcademicConfiguration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademicConfigurationUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AcademicConfiguration $academicConfiguration,
    ) {
    }
}
