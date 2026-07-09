<?php

namespace App\Enums;

enum Country: string
{
    case US = 'US';
    case CA = 'CA';
    case GB = 'GB';
    case AU = 'AU';
    case NZ = 'NZ';
    case NG = 'NG';

    /**
     * Get the display label for the country.
     */
    public function label(): string
    {
        return match ($this) {
            self::US => 'United States',
            self::CA => 'Canada',
            self::GB => 'United Kingdom',
            self::AU => 'Australia',
            self::NZ => 'New Zealand',
            self::NG => 'Nigeria',
        };
    }

    /**
     * Get the flag emoji for the country.
     */
    public function flag(): string
    {
        return match ($this) {
            self::US => '🇺🇸',
            self::CA => '🇨🇦',
            self::GB => '🇬🇧',
            self::AU => '🇦🇺',
            self::NZ => '🇳🇿',
            self::NG => '🇳🇬',
        };
    }

    /**
     * Get the dial code for the country.
     */
    public function dialCode(): string
    {
        return match ($this) {
            self::US => '+1',
            self::CA => '+1',
            self::GB => '+44',
            self::AU => '+61',
            self::NZ => '+64',
            self::NG => '+234',
        };
    }

    /**
     * Get the phone validation regex pattern for the country.
     */
    public function regex(): string
    {
        return match ($this) {
            self::US, self::CA => '/^(?:\+?1)?[2-9]\d{9}$/',
            self::GB => '/^(?:\+?44|0)7\d{9}$/',
            self::AU => '/^(?:\+?61|0)4\d{8}$/',
            self::NZ => '/^(?:\+?64|0)2\d{7,9}$/',
            self::NG => '/^(?:\+?234|0)?[789]\d{9}$/',
        };
    }

    /**
     * Get the placeholder format for the country.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::US, self::CA => '201 555 0123',
            self::GB => '7123 456789',
            self::AU => '412 345 678',
            self::NZ => '21 234 5678',
            self::NG => '803 123 4567',
        };
    }

    /**
     * Detect the country from a phone number prefix.
     */
    public static function detect(?string $phone): ?self
    {
        if (! $phone) {
            return null;
        }

        $cleanPhone = preg_replace('/[\s\-().]/', '', $phone);
        $cases = self::cases();
        usort($cases, fn ($a, $b) => strlen($b->dialCode()) <=> strlen($a->dialCode()));

        foreach ($cases as $country) {
            $dialCode = $country->dialCode();
            $dialCodeClean = ltrim($dialCode, '+');
            if (str_starts_with($cleanPhone, $dialCode) || str_starts_with($cleanPhone, $dialCodeClean)) {
                return $country;
            }
        }

        return null;
    }
}
