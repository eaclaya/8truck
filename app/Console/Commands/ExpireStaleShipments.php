<?php

namespace App\Console\Commands;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Console\Command;

class ExpireStaleShipments extends Command
{
    protected $signature = 'shipments:expire';

    protected $description = 'Expire published shipments whose expiry passed without an accepted quote';

    public function handle(TransitionShipmentStatusAction $transition): int
    {
        $expired = 0;

        Shipment::query()
            ->whereIn('status', [ShipmentStatus::Published, ShipmentStatus::Quoted])
            ->where('expires_at', '<=', now())
            ->with(['customer', 'originCity:id,name', 'destinationCity:id,name'])
            ->each(function (Shipment $shipment) use ($transition, &$expired) {
                $transition->execute($shipment, ShipmentStatus::Expired, null, 'Expirado automáticamente');

                $shipment->customer->notify(new ShipmentStatusUpdated($shipment));

                $expired++;
            });

        $this->info("Expired {$expired} shipment(s).");

        return self::SUCCESS;
    }
}
