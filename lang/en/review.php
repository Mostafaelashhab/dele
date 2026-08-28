<?php

/*
 * The review queue: the screen that lets a person keep the promise the
 * registration form makes.
 */

return [
    'title' => 'Join requests',
    'subtitle' => 'Accounts that registered themselves and are waiting for approval.',
    'empty' => 'Nothing is waiting for review.',
    'empty_hint' => 'Every registered account has been reviewed.',

    'company' => 'Delivery company',
    'rider' => 'Independent rider',
    'registered' => 'Registered',
    'zones' => 'Requested zones',
    'fleet' => 'Riders',
    'contact' => 'Contact',

    'identity' => 'Identity documents',
    'identity_hint' => 'Every viewing of these documents is written to the audit log.',
    'id_front' => 'ID card — front',
    'id_back' => 'ID card — back',
    'face' => 'Photo',
    'missing' => 'Not uploaded',
    'open_full' => 'Open full size',

    'approve' => 'Approve',
    'approve_rider_hint' => 'Approving means you have looked at the ID and it matches the photo.',
    'approve_company_hint' => 'Approving puts the company straight into dispatch.',
    'reject' => 'Reject',
    'reason' => 'Reason for rejection',
    'reason_hint' => 'The reason is kept with the account and appears in the audit log.',
    'confirm_reject' => 'Confirm rejection',
    'cancel' => 'Cancel',
    'approved' => 'The account has been approved.',
    'rejected' => 'The request was rejected and the reason recorded.',
];
