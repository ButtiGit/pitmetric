<?php

$editorEmails = array_values(array_filter(array_map(
    static fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env('PITMETRIC_EDITOR_EMAILS', '')),
)));

return [
    'update_editor_emails' => $editorEmails,
];
