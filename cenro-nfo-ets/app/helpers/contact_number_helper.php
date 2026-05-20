<?php

function normalizeContactNumber(?string $contactNumber): string
{
    $normalized = preg_replace('/\D+/', '', (string) $contactNumber);

    return $normalized ?? '';
}

function userContactNumberExists(PDO $pdo, ?string $contactNumber, ?int $excludeUserId = null): bool
{
    $normalizedInput = normalizeContactNumber($contactNumber);

    if ($normalizedInput === '') {
        return false;
    }

    $sql = "
        SELECT id, contact_number
        FROM users
        WHERE contact_number IS NOT NULL
          AND TRIM(contact_number) <> ''
    ";

    $params = [];

    if ($excludeUserId !== null && $excludeUserId > 0) {
        $sql .= " AND id <> :exclude_user_id";
        $params[':exclude_user_id'] = $excludeUserId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (normalizeContactNumber($row['contact_number'] ?? '') === $normalizedInput) {
            return true;
        }
    }

    return false;
}
