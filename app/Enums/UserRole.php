<?php

namespace App\Enums;

/**
 * Role slugs seeded into the roles table. Authorization is always checked
 * through policies and gates, never by comparing these values inline.
 */
enum UserRole: string
{
    case PlatformAdmin = 'platform_admin';
    case PlatformOperator = 'platform_operator';
    case PlatformSupport = 'platform_support';
    case BusinessOwner = 'business_owner';
    case BusinessStaff = 'business_staff';
    case CompanyOwner = 'company_owner';
    case CompanyDispatcher = 'company_dispatcher';
    case Rider = 'rider';

    /**
     * @return array<int, self>
     */
    public static function platformRoles(): array
    {
        return [self::PlatformAdmin, self::PlatformOperator, self::PlatformSupport];
    }

    public function tenantType(): ?TenantType
    {
        return match ($this) {
            self::BusinessOwner, self::BusinessStaff => TenantType::Business,
            self::CompanyOwner, self::CompanyDispatcher, self::Rider => TenantType::DeliveryCompany,
            default => null,
        };
    }

    /**
     * The dashboard a user with this role lands on after authenticating.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::PlatformAdmin, self::PlatformOperator, self::PlatformSupport => 'admin.dashboard',
            self::BusinessOwner, self::BusinessStaff => 'business.dashboard',
            self::CompanyOwner, self::CompanyDispatcher => 'company.dashboard',
            self::Rider => 'rider.home',
        };
    }

    public function label(): string
    {
        return __('account.role.'.$this->value);
    }
}
