<?php

declare(strict_types=1);

namespace App\Scheduler;

use Infrastructure\Messenger\CleanupOldBaskets;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * @see <https://symfony.com/blog/new-in-symfony-6-3-scheduler-component>
 */
#[AsSchedule('cleanup')]
final class CleanupScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::cron('* * * * *', new CleanupOldBaskets()));
    }
}
