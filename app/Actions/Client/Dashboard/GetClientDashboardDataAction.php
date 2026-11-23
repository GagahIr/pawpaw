<?php

namespace App\Actions\Client\Dashboard;

use App\Actions\Client\Booking\GetActiveBookingsAction;
use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Illuminate\Support\Collection;

class GetClientDashboardDataAction
{
    /**
     * @return array{stats: array{active: int, pending: int, completed: int, total: int}, recent: Collection}
     */
    public function execute(int $userClientId): array
    {
        $models = [BookingClinic::class, BookingGrooming::class, BookingPetHotel::class];
        $stats = [
            'active' => 0,
            'pending' => 0,
            'completed' => 0,
            'total' => 0,
        ];

        foreach ($models as $model) {
            $query = $model::query()->where('user_client_id', $userClientId);

            $stats['active'] += (clone $query)->whereIn('status', ['pending', 'confirmed'])->count();
            $stats['pending'] += (clone $query)->where('status', 'pending')->count();
            $stats['completed'] += (clone $query)->where('status', 'completed')->count();
            $stats['total'] += $query->count();
        }

        return [
            'stats' => $stats,
            'recent' => app(GetActiveBookingsAction::class)->execute(
                $userClientId,
                history: true,
                filters: ['sort' => 'latest'],
                limit: 5,
            )['items'],
        ];
    }
}
