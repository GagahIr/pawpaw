<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Vendor\HandleMidtransNotificationAction;
use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    public function handle(Request $request, HandleMidtransNotificationAction $action, MidtransService $midtransService): JsonResponse
    {
        $notification = $request->all();

        if (! $midtransService->isValidNotification($notification)) {
            return response()->json(['status' => 'invalid signature'], 403);
        }

        $action->execute($notification);

        return response()->json(['status' => 'ok']);
    }
}
