<?php

function normalizeEmail(?string $email): string
{
    return strtolower(trim((string) $email));
}

function userEmailExists(PDO $pdo, ?string $email, ?int $excludeUserId = null): bool
{
    $normalizedEmail = normalizeEmail($email);

    if ($normalizedEmail === '') {
        return false;
    }

    $sql = 'SELECT id FROM users WHERE LOWER(TRIM(email)) = :email';
    $params = [':email' => $normalizedEmail];

    if ($excludeUserId !== null && $excludeUserId > 0) {
        $sql .= ' AND id <> :exclude_user_id';
        $params[':exclude_user_id'] = $excludeUserId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}
