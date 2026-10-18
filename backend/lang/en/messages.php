<?php

// Business error messages returned by the API (language chosen via Accept-Language).
return [
    'account_locked' => 'Account temporarily locked. Please try again later.',
    'invalid_credentials' => 'Incorrect email or password.',
    'reset_link_invalid' => 'Invalid or expired reset link.',
    'current_password_wrong' => 'The current password is incorrect.',
    'documents_limit' => 'You have reached the limit of 50 tracked documents.',
    'category_not_empty' => 'A category that contains procedures cannot be deleted: move them or deactivate the category.',
    'self_role' => 'You cannot change your own role or deactivate your own account.',
    'last_super_admin' => 'At least one active super administrator must remain.',
    'self_delete' => 'You cannot delete your own account.',
    'status_moderator_only' => 'Only an administrator can change the publication status.',
    'forbidden' => 'Unauthorized action. You do not have the required privileges.',
    'citizen_only' => 'This action is reserved for active citizen accounts.',
    'account_deleted' => 'Your account and all its data have been deleted.',
    'signalement_sent' => 'Your report has been sent to the team. Thank you for your contribution.',
    'signalement_limit' => 'You have already sent 3 reports this hour. Please try again in :minutes minute.|You have already sent 3 reports this hour. Please try again in :minutes minutes.',
    'submission_limit' => 'You have reached the submission limit for this hour. Please try again in :minutes minute.|You have reached the submission limit for this hour. Please try again in :minutes minutes.',
    'review_received' => 'Thank you! Your review will be published after the team checks it.',
    'question_received' => 'Thank you! Your question will be published after the team checks it.',
    'answer_received' => 'Thank you! Your answer will be published after the team checks it.',
    'import_invalid_file' => 'The file must be an Excel spreadsheet (.xlsx) or a CSV file.',
    'import_unknown_category' => 'Unknown category: “:value”.',
    'import_missing_field' => 'Missing required field: :field.',
    'import_duplicate' => 'A procedure already has this title.',
    'city_has_offices' => 'A city that still has offices cannot be deleted: delete or move them first.',
];
