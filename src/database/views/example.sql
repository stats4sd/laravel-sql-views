SELECT
    users.id,
    users.name
FROM users
WHERE users.email_verified_at is NULL;