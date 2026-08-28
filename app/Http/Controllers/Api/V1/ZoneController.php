<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Zones\ZoneResolver;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ZoneResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ZoneController extends Controller
{
    public function __invoke(ZoneResolver $resolver): AnonymousResourceCollection
    {
        return ZoneResource::collection($resolver->activeZones());
    }
}
