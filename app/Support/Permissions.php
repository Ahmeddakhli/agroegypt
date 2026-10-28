<?php

namespace App\Support;

/**
 * The admin permission catalogue: every module gets view/create/update/delete.
 */
final class Permissions
{
    /** @var list<string> */
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    /** @var list<string> */
    public const MODULES = [
        'pages', 'products', 'categories', 'galleries', 'media', 'menus', 'sliders', 'documents',
        'languages', 'translations', 'settings', 'appearance', 'contact_messages', 'job_applications',
        'redirects', 'users', 'roles', 'audit_logs',
    ];

    /**
     * Role => modules it may fully manage ("module"), or with selected actions only ("module:view,update").
     * super-admin bypasses checks via Gate::before.
     *
     * @var array<string, list<string>>
     */
    public const ROLES = [
        'admin' => [
            'pages', 'products', 'categories', 'galleries', 'media', 'menus', 'sliders', 'documents', 'languages',
            'translations', 'settings', 'appearance', 'contact_messages', 'job_applications', 'redirects', 'audit_logs',
        ],
        'editor' => ['pages', 'products', 'categories', 'galleries', 'media', 'menus', 'sliders', 'documents'],
        // Translators edit the wording of existing content but cannot create, delete or restructure it.
        'translator' => [
            'translations', 'pages:view,update', 'products:view,update', 'categories:view,update', 'galleries:view,update',
            'menus:view,update', 'sliders:view,update', 'documents:view,update', 'media:view,update',
        ],
        'inbox' => ['contact_messages', 'job_applications'],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$action} {$module}";
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function forModules(array $modules): array
    {
        $permissions = [];
        foreach ($modules as $entry) {
            [$module, $actions] = str_contains($entry, ':') ? explode(':', $entry, 2) : [$entry, implode(',', self::ACTIONS)];
            foreach (explode(',', $actions) as $action) {
                $permissions[] = "{$action} {$module}";
            }
        }

        return array_values(array_intersect(self::all(), $permissions));
    }
}
