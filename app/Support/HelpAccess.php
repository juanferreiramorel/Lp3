<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Who may read a help topic.
 *
 * Reading the manual for a screen is not the same as administering the help
 * module. Each topic already declares the permission it belongs to in its
 * `permission_slug` column — "clientes index" for the client listing, and in a
 * couple of cases a permission from another module, so that the two till
 * manuals attached to the sales listing only reach people who open and close
 * the till. That column is the gate.
 *
 * A topic with no slug is general help and reaches anyone signed in.
 * Creating, editing and deleting topics stays behind `ayuda *`.
 */
class HelpAccess
{
    /**
     * @param object|array|null $topic A row from help_topics.
     */
    public static function canRead($topic): bool
    {
        if ($topic === null) {
            return false;
        }

        $slug = trim((string) (is_array($topic) ? ($topic['permission_slug'] ?? '') : ($topic->permission_slug ?? '')));

        if ($slug === '') {
            return Auth::check();
        }

        $user = Auth::user();

        return $user !== null && $user->can($slug);
    }
}
