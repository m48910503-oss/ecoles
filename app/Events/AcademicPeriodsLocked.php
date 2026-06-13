<?php

namespace App\Events;

use App\Models\AcademicPeriod;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademicPeriodsLocked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AcademicPeriod $academicPeriod,
    ) {
    }
}
