<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case StatusChanged = 'status_changed';
    case Suspended = 'suspended';
    case Reinstated = 'reinstated';
    case PricingChanged = 'pricing_changed';
    case OfferSent = 'offer_sent';
    case OfferAccepted = 'offer_accepted';
    case OfferRejected = 'offer_rejected';
    case RiderAssigned = 'rider_assigned';
    case SettlementCreated = 'settlement_created';
    case SettlementPaid = 'settlement_paid';
    case ApiKeyIssued = 'api_key_issued';
    case ApiKeyRevoked = 'api_key_revoked';
    case LoggedIn = 'logged_in';
    case LoginFailed = 'login_failed';

    /*
     * Reading somebody's identity document is itself an event worth
     * recording: holding an ID card obliges you to be able to say who
     * looked at it and when.
     */
    case IdentityViewed = 'identity_viewed';
    case IdentityVerified = 'identity_verified';
    case IdentityRejected = 'identity_rejected';
    case AccountApproved = 'account_approved';

    public function label(): string
    {
        return __('audit.action.'.$this->value);
    }
}
