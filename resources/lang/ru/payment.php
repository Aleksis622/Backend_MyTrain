<?php

return [

    // Payment UI
    'payment_title' => 'Оплата',
    'select_method' => 'Выберите способ оплаты',
    'card_payment' => 'Банковская карта',
    'bank_payment' => 'Банковский перевод',
    'card_details' => 'Данные карты',
    'bank_details' => 'Банковская информация',

    // Card fields
    'card_number' => 'Номер карты',
    'card_expiry' => 'Срок действия',
    'card_cvv' => 'Код безопасности (CVV)',
    'card_holder' => 'Имя владельца карты',

    // Bank transfer
    'bank_account' => 'Банковский счёт',
    'bank_reference' => 'Назначение платежа',
    'bank_instructions' => 'Пожалуйста, выполните перевод на указанный счёт',

    // Buttons
    'pay_now' => 'Оплатить',
    'confirm_payment' => 'Подтвердить оплату',
    'cancel_payment' => 'Отменить оплату',

    // Status
    'processing' => 'Оплата выполняется...',
    'payment_success' => 'Оплата прошла успешно',
    'payment_failed' => 'Ошибка оплаты',
    'redirecting_bank' => 'Перенаправление в банк...',

    // Errors
    'invalid_card' => 'Неверная карта',
    'invalid_cvv' => 'Неверный CVV код',
    'invalid_expiry' => 'Неверный срок действия',
    'invalid_holder' => 'Неверное имя владельца карты',
    'missing_method' => 'Способ оплаты не выбран',
    'missing_fields' => 'Пожалуйста, заполните все поля',
    'bank_timeout' => 'Соединение с банком прервано',
    'payment_declined' => 'Платёж отклонён',

    // Warnings
    'do_not_close' => 'Пожалуйста, не закрывайте страницу во время оплаты',
    'secure_payment' => 'Безопасная оплата с защищённым соединением',
];
