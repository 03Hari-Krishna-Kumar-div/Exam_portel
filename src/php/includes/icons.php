<?php
/**
 * Lucide Icon Helper — Enterprise Icon System
 *
 * Uses Lucide icons (data-lucide attributes) with filled styling.
 * Renders via Lucide CDN loaded in header.
 *
 * Usage: <?= icon('dashboard') ?>
 *        <?= icon('chevron-right', 16) ?>
 *        <?= icon('check', 20, 'var(--green)') ?>
 *
 * Icon naming maps to SF Symbols aesthetic via Lucide equivalents.
 * All icons use filled style by default (fill="currentColor" with stroke-width=1.5).
 */

function icon(string $name, int $size = 20, string $color = 'currentColor', string $className = ''): string {
    static $map = null;
    if ($map === null) {
        $map = getLucideMap();
    }

    $lucideName = $map[$name] ?? $name;

    // Map color aliases
    if ($color === 'var(--green)')  $color = '#22C55E';
    if ($color === 'var(--red)')    $color = '#EF4444';
    if ($color === 'var(--accent)') $color = '#4F8CFF';

    $attrs = 'data-lucide="' . htmlspecialchars($lucideName, ENT_QUOTES) . '"';
    $attrs .= ' width="' . $size . '" height="' . $size . '"';
    if ($className) {
        $attrs .= ' class="' . htmlspecialchars($className, ENT_QUOTES) . '"';
    }
    // Filled style: Lucide uses fill attribute for filled variants
    if ($color !== 'currentColor') {
        $attrs .= ' fill="' . htmlspecialchars($color, ENT_QUOTES) . '"';
    }

    return '<i ' . $attrs . '></i>';
}

function iconSprite(): string {
    // Lucide renders inline — no sprite needed
    return '';
}

/**
 * Map old Fluent UI icon names → Lucide icon names.
 * Follows SF Symbols naming convention where possible.
 */
function getLucideMap(): array {
    return [
        // ── Sidebar / Navigation ──────────────────────────
        'dashboard'      => 'layout-dashboard',
        'college'        => 'building2',
        'course'         => 'book-open',
        'batch'          => 'layers',
        'student'        => 'graduation-cap',
        'test'           => 'file-text',
        'grading'        => 'clipboard-check',
        'reports'        => 'chart-bar-big',
        'settings'       => 'settings',
        'notifications'  => 'bell',
        'search'         => 'search',
        'menu'           => 'menu',

        // ── Actions ───────────────────────────────────────
        'plus'           => 'circle-plus',
        'edit'           => 'square-pen',
        'trash'          => 'trash-2',
        'copy'           => 'copy',
        'filter'         => 'filter',
        'refresh'        => 'refresh-cw',

        // ── Navigation ────────────────────────────────────
        'arrow-right'    => 'arrow-right',
        'arrow-left'     => 'arrow-left',
        'chevron-right'  => 'chevron-right',
        'chevron-left'   => 'chevron-left',
        'chevron-down'   => 'chevron-down',
        'external-link'  => 'external-link',
        'logout'         => 'log-out',
        'login'          => 'log-in',

        // ── Media / Player ────────────────────────────────
        'play'           => 'play',
        'pause'          => 'pause',
        'stop'           => 'square',

        // ── Status / Alerts ───────────────────────────────
        'check'          => 'check',
        'check-circle'   => 'check-circle',
        'x'             => 'x',
        'info'           => 'info',
        'warning'        => 'triangle-alert',
        'question-circle'=> 'circle-help',
        'help'           => 'circle-help',
        'shield'         => 'shield',

        // ── Data / Charts ─────────────────────────────────
        'chart'          => 'chart-bar-big',
        'graph'          => 'chart-line',
        'activity'       => 'activity',
        'pulse'          => 'activity',
        'status'         => 'circle-dot',
        'data-usage'     => 'hard-drive',
        'database'       => 'database',
        'globe'          => 'globe',
        'code'           => 'code',
        'flag'           => 'flag',
        'tag'            => 'tag',
        'folder'         => 'folder',
        'file'           => 'file',
        'document'       => 'file-text',
        'clipboard'      => 'clipboard',
        'timer'          => 'timer',
        'clock'          => 'clock',
        'calendar'       => 'calendar',
        'star'           => 'star',

        // ── System ────────────────────────────────────────
        'user'           => 'user',
        'users'          => 'users',
        'mail'           => 'mail',
        'lock'           => 'lock',
        'eye'            => 'eye',
        'eye-off'        => 'eye-off',
        'more-vertical'  => 'ellipsis-vertical',
        'download'       => 'download',
        'upload'         => 'upload',
        'lightbulb'      => 'lightbulb',
        'moon'           => 'moon',

        // ── Layout ────────────────────────────────────────
        'grid'           => 'grid-3x3',
        'list'           => 'list',

        // ── Specific overrides ────────────────────────────
        // These are already using correct Lucide names
        'building2'      => 'building2',
        'chart-pie'      => 'chart-pie',
        'chart-bar-big'  => 'chart-bar-big',
    ];
}
