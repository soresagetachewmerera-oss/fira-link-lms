<?php

namespace App\Notifications\Catalog;

/** One catalogue entry (see NotificationType). Immutable. */
final class TypeDefinition
{
    /**
     * @param string $group A to K (catalogue section)
     * @param string[] $channels channels the type may use (Channel::*)
     * @param string[] $defaults channels used when the person never changed a setting
     * @param string $priority high, normal or low (push priority, queue order)
     * @param int $ttlSeconds after this a queued or pushed message is dropped (stale)
     * @param string|null $appRoute AppRouter route a tap opens; null when there is no screen (codes)
     * @param string $auth NotificationType::AUTH_* (every app route goes through RequireSignIn)
     * @param string $androidChannel security, deals, learning or updates
     * @param bool $immediate sent in the request, not queued (codes and reset links)
     * @param string|null $routeParam the parameter that carries the id the route opens
     */
    public function __construct(
        public readonly string $group,
        public readonly MessageClass $class,
        public readonly array $channels,
        public readonly array $defaults,
        public readonly bool $canDisable,
        public readonly string $priority,
        public readonly int $ttlSeconds,
        public readonly ?string $appRoute,
        public readonly string $auth,
        public readonly string $androidChannel,
        public readonly bool $immediate,
        public readonly ?string $routeParam = null,
    ) {
    }
}
