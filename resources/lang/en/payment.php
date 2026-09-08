<?php

return [

    // Payment UI
    'payment_title' => 'Payment',
    'select_method' => 'Select payment method',
    'card_payment' => 'Credit/Debit Card',
    'bank_payment' => 'Bank Transfer',
    'card_details' => 'Card Details',
    'bank_details' => 'Bank Information',

    // Card fields
    'card_number' => 'Card Number',
    'card_expiry' => 'Expiry Date',
    'card_cvv' => 'Security Code (CVV)',
    'card_holder' => 'Card Holder Name',

    // Bank transfer
    'bank_account' => 'Bank Account',
    'bank_reference' => 'Payment Reference',
    'bank_instructions' => 'Please complete the transfer to the provided account',

    // Buttons
    'pay_now' => 'Pay Now',
    'confirm_payment' => 'Confirm Payment',
    'cancel_payment' => 'Cancel Payment',

    // Status
    'processing' => 'Processing payment...',
    'payment_success' => 'Payment successful',
    'payment_failed' => 'Payment failed',
    'redirecting_bank' => 'Redirecting to bank...',

    // Errors
    'invalid_card' => 'Invalid card',
    'invalid_cvv' => 'Invalid CVV code',
    'invalid_expiry' => 'Invalid expiry date',
    'invalid_holder' => 'Invalid card holder name',
    'missing_method' => 'No payment method selected',
    'missing_fields' => 'Please fill in all fields',
    'bank_timeout' => 'Bank connection timed out',
    'payment_declined' => 'Payment was declined',

    // Warnings
    'do_not_close' => 'Please do not close this page during payment',
    'secure_payment' => 'Secure payment with encrypted connection',
];
