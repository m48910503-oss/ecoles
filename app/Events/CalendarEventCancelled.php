<?php

namespace App\Events;

use App\Models\CalendarEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalendarEventCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly CalendarEvent $calendarEvent,
    ) {
    }
}
