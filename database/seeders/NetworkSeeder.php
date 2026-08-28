<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\RiderStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The pilot network described in the rollout plan: two delivery companies, a
 * handful of local businesses, and enough riders to actually run deliveries.
 *
 * Business names are invented rather than borrowed from real Banha shops, so
 * seeding a development database never implies a commercial relationship that
 * does not exist.
 */
class NetworkSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * @var array<int, array{name: string, name_ar: string, contact: string, phone: string, email: string, zones: array<int, string>, auto: bool, riders: int}>
     */
    private const COMPANIES = [
        [
            'name' => 'Banha Express',
            'name_ar' => 'بنها إكسبريس',
            'contact' => 'محمود السيد',
            'phone' => '01100100101',
            'email' => 'dispatch@banha-express.test',
            'zones' => ['BNH-CTR', 'BNH-FND', 'BNH-STN', 'BNH-MNS', 'BNH-UNI', 'BNH-NEW', 'BNH-KGZ'],
            'auto' => false,
            'riders' => 6,
        ],
        [
            'name' => 'Al Amana Logistics',
            'name_ar' => 'الأمانة للنقل السريع',
            'contact' => 'أحمد عبد الله',
            'phone' => '01100100202',
            'email' => 'dispatch@al-amana.test',
            'zones' => ['BNH-CTR', 'BNH-MNS', 'BNH-SND', 'BNH-MTR', 'BNH-KSD', 'BNH-SHR', 'QLB-TKH'],
            'auto' => true,
            'riders' => 5,
        ],
    ];

    /**
     * @var array<int, array{name: string, name_ar: string, category: string, contact: string, phone: string, email: string, zone: string}>
     */
    private const BUSINESSES = [
        ['name' => 'Zad Restaurant', 'name_ar' => 'مطعم زاد', 'category' => 'restaurant',
            'contact' => 'كريم فؤاد', 'phone' => '01012000101', 'email' => 'owner@zad.test', 'zone' => 'BNH-CTR'],
        ['name' => 'Al Nour Pharmacy', 'name_ar' => 'صيدلية النور', 'category' => 'pharmacy',
            'contact' => 'د. هبة مصطفى', 'phone' => '01012000102', 'email' => 'owner@alnour.test', 'zone' => 'BNH-FND'],
        ['name' => 'Beit El Askan Market', 'name_ar' => 'ماركت بيت السكن', 'category' => 'grocery',
            'contact' => 'سامي رشدي', 'phone' => '01012000103', 'email' => 'owner@beitmarket.test', 'zone' => 'BNH-MNS'],
        ['name' => 'Nada Fashion', 'name_ar' => 'ندى للأزياء', 'category' => 'clothing',
            'contact' => 'ندى شريف', 'phone' => '01012000104', 'email' => 'owner@nadafashion.test', 'zone' => 'BNH-STN'],
        ['name' => 'TechPoint Electronics', 'name_ar' => 'تك بوينت للإلكترونيات', 'category' => 'electronics',
            'contact' => 'يوسف حسن', 'phone' => '01012000105', 'email' => 'owner@techpoint.test', 'zone' => 'BNH-NEW'],
        ['name' => 'Sondos Online Store', 'name_ar' => 'متجر سندس أونلاين', 'category' => 'online',
            'contact' => 'سندس علاء', 'phone' => '01012000106', 'email' => 'owner@sondos.test', 'zone' => 'BNH-UNI'],
    ];

    /**
     * @var array<int, string>
     */
    private const RIDER_NAMES = [
        'محمد إبراهيم', 'أحمد سمير', 'مصطفى جمال', 'عمرو صلاح', 'كريم عادل',
        'إسلام رضا', 'طارق منصور', 'هاني وليد', 'شريف نبيل', 'عبد الرحمن ماهر',
        'يوسف عصام', 'زياد فتحي',
    ];

    public function run(): void
    {
        $zones = Zone::query()->get()->keyBy('code');

        $this->seedPlatformStaff();

        $riderIndex = 0;

        foreach (self::COMPANIES as $definition) {
            $company = $this->seedCompany($definition, $zones);
            $this->seedRiders($company, $definition['riders'], $riderIndex);
            $riderIndex += $definition['riders'];
        }

        foreach (self::BUSINESSES as $definition) {
            $this->seedBusiness($definition, $zones);
        }
    }

    private function seedPlatformStaff(): void
    {
        $admin = $this->user('مدير المنصة', 'admin@banha.test', '01000000001');
        $this->attachRole($admin, UserRole::PlatformAdmin);

        $operator = $this->user('غرفة العمليات', 'ops@banha.test', '01000000002');
        $this->attachRole($operator, UserRole::PlatformOperator);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, Zone>  $zones
     */
    private function seedCompany(array $definition, $zones): DeliveryCompany
    {
        $company = DeliveryCompany::updateOrCreate(
            ['slug' => Str::slug($definition['name'])],
            [
                'name' => $definition['name'],
                'name_ar' => $definition['name_ar'],
                'contact_person' => $definition['contact'],
                'phone' => $definition['phone'],
                'email' => $definition['email'],
                'status' => AccountStatus::Active,
                'provider' => 'internal',
                'auto_accept_offers' => $definition['auto'],
                'max_concurrent_deliveries' => 40,
                'commission_bps' => 1200,
                'settlement_period' => 'weekly',
                'latitude' => 30.4610,
                'longitude' => 31.1840,
                'onboarded_at' => now(),
                'working_hours' => $this->workingHours(),
                'rating_bps' => 4300,
                'acceptance_rate_bps' => 8200,
                'completion_rate_bps' => 9500,
                'average_pickup_minutes' => 11,
                'completed_deliveries_count' => 0,
            ],
        );

        $company->serviceAreas()->sync(
            collect($definition['zones'])
                ->map(fn (string $code) => $zones->get($code)?->id)
                ->filter()
                ->mapWithKeys(fn (string $id) => [$id => [
                    'accepts_pickup' => true,
                    'accepts_dropoff' => true,
                    'surcharge_minor' => 0,
                ]])
                ->all()
        );

        $owner = $this->user($definition['contact'], $definition['email'], $definition['phone']);

        $company->memberships()->updateOrCreate(
            ['user_id' => $owner->id],
            [
                'role' => UserRole::CompanyOwner->value,
                'is_primary_contact' => true,
                'is_active' => true,
            ],
        );

        $this->attachRole($owner, UserRole::CompanyOwner, 'delivery_company', $company->id);

        return $company;
    }

    private function seedRiders(DeliveryCompany $company, int $count, int $offset): void
    {
        for ($i = 0; $i < $count; $i++) {
            $name = self::RIDER_NAMES[($offset + $i) % count(self::RIDER_NAMES)];
            $phone = '0121000'.str_pad((string) ($offset + $i + 10), 4, '0', STR_PAD_LEFT);
            $email = 'rider'.($offset + $i + 1).'@'.Str::before(Str::after($company->email, '@'), '.').'.test';

            $user = $this->user($name, $email, $phone);
            $this->attachRole($user, UserRole::Rider, 'delivery_company', $company->id);

            // Most of the fleet starts on shift so a freshly seeded network
            // can actually dispatch; the rest are off, which is realistic and
            // exercises the availability filter.
            $online = $i < max(1, (int) ceil($count * 0.7));

            Rider::updateOrCreate(
                ['delivery_company_id' => $company->id, 'phone' => $phone],
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'status' => $online ? RiderStatus::Online : RiderStatus::Offline,
                    'vehicle_type' => $i % 5 === 4 ? VehicleType::Car : VehicleType::Motorcycle,
                    'vehicle_identifier' => 'ق ب '.random_int(1000, 9999),
                    'max_concurrent_deliveries' => 2,
                    'active_deliveries_count' => 0,
                    'current_latitude' => $online ? 30.4599 + (random_int(-120, 120) / 10000) : null,
                    'current_longitude' => $online ? 31.1837 + (random_int(-120, 120) / 10000) : null,
                    'location_updated_at' => $online ? now() : null,
                    'last_seen_at' => $online ? now() : null,
                    'went_online_at' => $online ? now()->subHours(2) : null,
                    'rating_bps' => random_int(4000, 4900),
                    'acceptance_rate_bps' => random_int(7500, 9700),
                    'completion_rate_bps' => random_int(9000, 9900),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, Zone>  $zones
     */
    private function seedBusiness(array $definition, $zones): Business
    {
        $zone = $zones->get($definition['zone']);

        $business = Business::updateOrCreate(
            ['slug' => Str::slug($definition['name'])],
            [
                'name' => $definition['name'],
                'name_ar' => $definition['name_ar'],
                'category' => $definition['category'],
                'contact_person' => $definition['contact'],
                'phone' => $definition['phone'],
                'email' => $definition['email'],
                'status' => AccountStatus::Active,
                'default_zone_id' => $zone?->id,
                'address_line' => $definition['name_ar'].' — '.($zone?->name_ar ?? 'بنها'),
                'latitude' => $zone?->centroid_latitude,
                'longitude' => $zone?->centroid_longitude,
                'onboarded_at' => now(),
            ],
        );

        $business->addresses()->updateOrCreate(
            ['label' => 'الفرع الرئيسي'],
            [
                'zone_id' => $zone?->id,
                'contact_name' => $definition['contact'],
                'contact_phone' => $definition['phone'],
                'address_line' => $definition['name_ar'].' — '.($zone?->name_ar ?? 'بنها'),
                'area' => $zone?->name_ar,
                'city' => 'Banha',
                'latitude' => $zone?->centroid_latitude,
                'longitude' => $zone?->centroid_longitude,
                'is_default' => true,
            ],
        );

        $owner = $this->user($definition['contact'], $definition['email'], $definition['phone']);

        $business->memberships()->updateOrCreate(
            ['user_id' => $owner->id],
            [
                'role' => UserRole::BusinessOwner->value,
                'is_primary_contact' => true,
                'is_active' => true,
            ],
        );

        $this->attachRole($owner, UserRole::BusinessOwner, 'business', $business->id);

        return $business;
    }

    private function user(string $name, string $email, string $phone): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => self::PASSWORD,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    private function attachRole(
        User $user,
        UserRole $role,
        ?string $tenantType = null,
        ?string $tenantId = null,
    ): void {
        $model = Role::where('slug', $role->value)->first();

        if ($model === null) {
            return;
        }

        $exists = $model->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('tenant_type', $tenantType)
            ->wherePivot('tenant_id', $tenantId)
            ->exists();

        if ($exists) {
            return;
        }

        $model->users()->attach($user->id, [
            'tenant_type' => $tenantType,
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Banha's shops open late and close late; Friday runs shorter.
     *
     * @return array<string, array{closed: bool, opens: string, closes: string}>
     */
    private function workingHours(): array
    {
        $standard = ['closed' => false, 'opens' => '09:00', 'closes' => '23:59'];

        return [
            'saturday' => $standard,
            'sunday' => $standard,
            'monday' => $standard,
            'tuesday' => $standard,
            'wednesday' => $standard,
            'thursday' => $standard,
            'friday' => ['closed' => false, 'opens' => '13:00', 'closes' => '23:59'],
        ];
    }
}
