<?php

namespace App\Support;

class NotificationText
{
    public static function resolve(string $type, array $data): string
    {
        if (! empty($data['message'])) {
            return (string) $data['message'];
        }

        return match (class_basename($type)) {
            'OrderPlacedNotification' => isset($data['order_id'])
                ? 'Your order #'.$data['order_id'].' has been placed successfully.'
                : 'Your order has been placed successfully.',
            'OrderStatusChangedNotification' => isset($data['order_id'], $data['old_status'], $data['new_status'])
                ? 'Order #'.$data['order_id'].' status changed from '.ucfirst((string) $data['old_status']).' to '.ucfirst((string) $data['new_status']).'.'
                : 'An order status was updated.',
            'NewOrderAdminNotification' => isset($data['order_id'])
                ? 'New order #'.$data['order_id'].' was placed.'
                : 'A new order was placed.',
            'NewReviewAdminNotification' => isset($data['book_title'])
                ? 'A new review was submitted for "'.$data['book_title'].'".'
                : 'A new review was submitted.',
            'BookCatalogUpdatedNotification' => isset($data['book_title'], $data['action'])
                ? 'Book "'.$data['book_title'].'" was '.$data['action'].'.'
                : 'The book catalog was updated.',
            'TwoFactorEnabledNotification' => 'Two-factor authentication was enabled on your account.',
            'TwoFactorDisabledNotification' => 'Two-factor authentication was disabled on your account.',
            'AuditCriticalAlertNotification' => isset($data['action'])
                ? 'Critical audit event detected: '.$data['action'].'.'
                : 'A critical audit event was detected.',
            default => 'You have a new notification.',
        };
    }
}