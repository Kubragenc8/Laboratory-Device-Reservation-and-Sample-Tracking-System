<?php

function cleanInput(?string $value): string
{
    return trim($value ?? '');
}

function isEmptyValue(?string $value): bool
{
    return trim($value ?? '') === '';
}

function isValidEmailAddress(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPasswordLength(string $password, int $minLength = 6): bool
{
    return strlen($password) >= $minLength;
}

function doPasswordsMatch(string $password, string $confirmPassword): bool
{
    return $password === $confirmPassword;
}

function isValidStudentNumber(string $studentNo): bool
{
    return preg_match('/^[0-9A-Za-z]{4,20}$/', $studentNo) === 1;
}

function isPositiveInteger($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0;
}

function isValidClassYear($value): bool
{
    if (!isPositiveInteger($value)) {
        return false;
    }

    $classYear = (int) $value;

    return $classYear >= 1 && $classYear <= 6;
}