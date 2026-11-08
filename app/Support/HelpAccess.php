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

        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        $get = fn (string $key) => is_array($topic) ? ($topic[$key] ?? null) : ($topic->{$key} ?? null);

        // A deactivated topic is a draft or a retired manual. Only whoever
        // administers the help module should still reach it; otherwise
        // switching a topic off would hide it from the menu while its URL
        // kept serving the file.
        if (!filter_var($get('is_active'), FILTER_VALIDATE_BOOLEAN)) {
            return $user->can('ayuda index');
        }

        $slug = trim((string) $get('permission_slug'));

        return $slug === '' ? true : $user->can($slug);
    }
}
