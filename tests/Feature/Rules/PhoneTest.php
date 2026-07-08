<?php

use App\Rules\Phone;
use Illuminate\Support\Facades\Validator;

test('it validates phone numbers for US/CA', function () {
    $rule = new Phone('US');

    // Valid formats
    expect(validate($rule, '5555555555'))->toBeTrue();
    expect(validate($rule, '+15555555555'))->toBeTrue();
    expect(validate($rule, '(555) 555-5555'))->toBeTrue();
    expect(validate($rule, '555-555-5555'))->toBeTrue();

    // Invalid formats
    expect(validate($rule, '1234567'))->toBeFalse();
    expect(validate($rule, 'invalid-phone'))->toBeFalse();
    expect(validate($rule, '15555555555'))->toBeTrue(); // matches +1 logic after cleaning
});

test('it validates phone numbers for GB', function () {
    $rule = new Phone('GB');

    // Valid formats
    expect(validate($rule, '07123456789'))->toBeTrue();
    expect(validate($rule, '+447123456789'))->toBeTrue();
    expect(validate($rule, '07123 456 789'))->toBeTrue();

    // Invalid formats
    expect(validate($rule, '01234567890'))->toBeFalse(); // Not a mobile number starting with 7
    expect(validate($rule, '+441234567890'))->toBeFalse();
});

test('it validates phone numbers for NG', function () {
    $rule = new Phone('NG');

    // Valid formats
    expect(validate($rule, '08031234567'))->toBeTrue();
    expect(validate($rule, '+2348031234567'))->toBeTrue();
    expect(validate($rule, '234 803 123 4567'))->toBeTrue();
    expect(validate($rule, '09012345678'))->toBeTrue();

    // Invalid formats
    expect(validate($rule, '05012345678'))->toBeFalse(); // 050 prefix is not valid/common
    expect(validate($rule, '12345678'))->toBeFalse();    // Too short
});

test('it validates phone numbers dynamically based on field data', function () {
    $data = [
        'country_code' => 'GB',
        'phone' => '07123456789',
    ];

    $validator = Validator::make($data, [
        'phone' => [new Phone('country_code')],
    ]);

    expect($validator->passes())->toBeTrue();

    $invalidData = [
        'country_code' => 'US',
        'phone' => '07123456789', // Invalid for US
    ];

    $invalidValidator = Validator::make($invalidData, [
        'phone' => [new Phone('country_code')],
    ]);

    expect($invalidValidator->passes())->toBeFalse();
});

test('it falls back to any format if country is not specified or recognized', function () {
    $rule = new Phone('any');

    expect(validate($rule, '+15555555555'))->toBeTrue();
    expect(validate($rule, '+447123456789'))->toBeTrue();
    expect(validate($rule, '+33123456789'))->toBeTrue();
    expect(validate($rule, '123'))->toBeFalse(); // too short
});

/**
 * Helper to run validation rule.
 */
function validate(Phone $rule, mixed $value): bool
{
    $passes = true;

    $rule->validate('phone', $value, function ($message) use (&$passes) {
        $passes = false;
    });

    return $passes;
}
