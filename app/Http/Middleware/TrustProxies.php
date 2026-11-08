<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * The container publishes no ports: in production it is reachable only
     * through Traefik on an internal network, and in development only from
     * the host. Everything that can reach it is therefore already a trusted
     * proxy, and pinning addresses would break whenever Docker hands out a
     * different subnet.
     *
     * Without this, Laravel never sees X-Forwarded-Proto, believes an HTTPS
     * request arrived over HTTP, and builds redirects as http://. The browser
     * is bounced back to https by the edge, the session cookie is lost in the
     * hop, and signing in silently returns to the login page.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
