<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Real Banha neighbourhoods, with coordinates and radii that reflect how the
 * city is actually laid out.
 *
 * Distances, prices and dispatch decisions all key off this geography, so
 * plausible-but-invented zones would make every downstream number meaningless
 * during the pilot.
 */
class ZoneSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, name_ar: string, lat: float, lng: float, radius: int, price: int, minutes: int}>
     */
    private const ZONES = [
        [
            'code' => 'BNH-CTR', 'name' => 'Banha City Centre', 'name_ar' => 'وسط البلد',
            'lat' => 30.46100, 'lng' => 31.18400, 'radius' => 1200, 'price' => 1500, 'minutes' => 18,
        ],
        [
            'code' => 'BNH-FND', 'name' => 'Farid Nada Street', 'name_ar' => 'شارع فريد ندا',
            'lat' => 30.46550, 'lng' => 31.17950, 'radius' => 1000, 'price' => 1500, 'minutes' => 20,
        ],
        [
            'code' => 'BNH-STN', 'name' => 'Railway Station', 'name_ar' => 'محطة السكة الحديد',
            'lat' => 30.46400, 'lng' => 31.18100, 'radius' => 900, 'price' => 1500, 'minutes' => 18,
        ],
        [
            'code' => 'BNH-MNS', 'name' => 'El Manshia', 'name_ar' => 'المنشية',
            'lat' => 30.45600, 'lng' => 31.19000, 'radius' => 1300, 'price' => 2000, 'minutes' => 24,
        ],
        [
            'code' => 'BNH-UNI', 'name' => 'Banha University', 'name_ar' => 'جامعة بنها',
            'lat' => 30.45300, 'lng' => 31.17800, 'radius' => 1400, 'price' => 2000, 'minutes' => 22,
        ],
        [
            'code' => 'BNH-NEW', 'name' => 'New District', 'name_ar' => 'الحي الجديد',
            'lat' => 30.47000, 'lng' => 31.19600, 'radius' => 1600, 'price' => 2000, 'minutes' => 26,
        ],
        [
            'code' => 'BNH-KGZ', 'name' => 'Kafr El Gazzar', 'name_ar' => 'كفر الجزار',
            'lat' => 30.47200, 'lng' => 31.17200, 'radius' => 1500, 'price' => 2000, 'minutes' => 26,
        ],
        [
            'code' => 'BNH-SND', 'name' => 'Sandanhour', 'name_ar' => 'سندنهور',
            'lat' => 30.44800, 'lng' => 31.16500, 'radius' => 1800, 'price' => 2500, 'minutes' => 32,
        ],
        [
            'code' => 'BNH-MTR', 'name' => 'Mit Rady', 'name_ar' => 'ميت راضي',
            'lat' => 30.44000, 'lng' => 31.20100, 'radius' => 1800, 'price' => 2500, 'minutes' => 34,
        ],
        [
            'code' => 'BNH-KSD', 'name' => 'Kafr Saad', 'name_ar' => 'كفر سعد',
            'lat' => 30.48200, 'lng' => 31.18800, 'radius' => 1600, 'price' => 2500, 'minutes' => 30,
        ],
        [
            'code' => 'BNH-SHR', 'name' => 'Ezbet El Sharkawy', 'name_ar' => 'عزبة الشرقاوي',
            'lat' => 30.43800, 'lng' => 31.17400, 'radius' => 1500, 'price' => 2500, 'minutes' => 33,
        ],
        // Outside the city proper. Priced and timed accordingly, and a useful
        // test of the network's willingness to serve the edge of its map.
        [
            'code' => 'QLB-TKH', 'name' => 'Toukh', 'name_ar' => 'طوخ',
            'lat' => 30.35500, 'lng' => 31.20000, 'radius' => 3000, 'price' => 4000, 'minutes' => 55,
        ],
    ];

    public function run(): void
    {
        foreach (self::ZONES as $index => $zone) {
            Zone::updateOrCreate(
                ['code' => $zone['code']],
                [
                    'name' => $zone['name'],
                    'name_ar' => $zone['name_ar'],
                    'city' => 'Banha',
                    'governorate' => 'Qalyubia',
                    'centroid_latitude' => $zone['lat'],
                    'centroid_longitude' => $zone['lng'],
                    'radius_meters' => $zone['radius'],
                    'base_price_minor' => $zone['price'],
                    'estimated_minutes' => $zone['minutes'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }
}
