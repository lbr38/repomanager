<?php

namespace Controllers\Api\V3;

use Controllers\Api\V3\Repository\Repositories;
use Controllers\Api\V3\Repository\Snapshots;
use Controllers\Api\V3\Repository\Environments as SnapshotEnvironments;
use Controllers\Api\V3\Repository\Packages;
use Controllers\Api\V3\Environment\Environments;
use Controllers\Api\V3\Source\Sources;
use Controllers\Api\V3\Task\Tasks;
use Controllers\Api\V3\Host\Hosts;
use Controllers\Api\V3\Host\Agent;
use Controllers\Api\V3\Profile\Profiles;

/**
 *  API v3 routes table
 *  Each route: [method, pattern, handler class, handler method, authentication]
 *  Authentication:
 *  - user: API key of a user ("Authorization: Bearer <API_KEY>"), the user permissions apply
 *  - host: host Id and token ("Authorization: Host <HOST_ID>:<HOST_TOKEN>"), used by the linupdate agent
 *  - any:  either of them
 */
class Routes
{
    // Repository types: curated and local repositories share the same Ids, mirrors have their own
    private const TYPE = '{type:curated|local|mirror}';
    // Repository types that have packages and metadata managed by Repomanager
    private const REPO_TYPE = '{type:curated|local}';
    // Repository path
    private const REPO = '/repositories/' . self::TYPE . '/{id:int}';
    private const SNAPSHOT = self::REPO . '/snapshots/{snapshot:int}';
    private const REPO_SNAPSHOT = '/repositories/' . self::REPO_TYPE . '/{id:int}/snapshots/{snapshot:int}';

    public static function get(): array
    {
        return [
            ['GET', '/status', Status::class, 'get', 'any'],

            // Repositories (curated, local and mirrors)
            ['GET', '/repositories', Repositories::class, 'list', 'user'],
            ['GET', '/repositories/' . self::TYPE, Repositories::class, 'list', 'user'],
            ['POST', '/repositories/' . self::TYPE, Repositories::class, 'create', 'user'],
            ['GET', self::REPO, Repositories::class, 'get', 'user'],
            ['PATCH', self::REPO, Repositories::class, 'edit', 'user'],
            ['DELETE', self::REPO, Repositories::class, 'delete', 'user'],
            ['POST', '/repositories/' . self::REPO_TYPE . '/{id:int}/rename', Repositories::class, 'rename', 'user'],

            // Snapshots
            ['GET', self::REPO . '/snapshots', Snapshots::class, 'list', 'user'],
            ['POST', self::REPO . '/snapshots', Snapshots::class, 'create', 'user'],
            ['GET', self::SNAPSHOT, Snapshots::class, 'get', 'user'],
            ['DELETE', self::SNAPSHOT, Snapshots::class, 'delete', 'user'],
            ['POST', self::REPO_SNAPSHOT . '/rebuild', Snapshots::class, 'rebuild', 'user'],
            ['POST', self::REPO_SNAPSHOT . '/duplicate', Snapshots::class, 'duplicate', 'user'],
            ['GET', self::REPO_SNAPSHOT . '/diff/{other:int}', Snapshots::class, 'diff', 'user'],

            // Environments pointing to a snapshot
            ['POST', self::SNAPSHOT . '/environments', SnapshotEnvironments::class, 'point', 'user'],
            ['DELETE', self::SNAPSHOT . '/environments/{env}', SnapshotEnvironments::class, 'remove', 'user'],

            // Packages of a snapshot
            ['GET', self::REPO_SNAPSHOT . '/packages', Packages::class, 'list', 'user'],
            ['POST', self::REPO_SNAPSHOT . '/packages', Packages::class, 'upload', 'user'],
            ['DELETE', self::REPO_SNAPSHOT . '/packages', Packages::class, 'delete', 'user'],

            // Environments
            ['GET', '/environments', Environments::class, 'list', 'user'],

            // Source repositories
            ['GET', '/sources', Sources::class, 'list', 'user'],
            ['POST', '/sources/import', Sources::class, 'import', 'user'],
            ['GET', '/sources/{package_type:deb|rpm|arch}/{name}', Sources::class, 'get', 'user'],

            // Tasks
            ['GET', '/tasks', Tasks::class, 'list', 'user'],
            ['GET', '/tasks/{id:int}', Tasks::class, 'details', 'user'],
            ['DELETE', '/tasks/{id:int}', Tasks::class, 'delete', 'user'],
            ['POST', '/tasks/{id:int}/{action:enable|disable|stop|relaunch}', Tasks::class, 'action', 'user'],

            // Hosts, as seen by users
            ['GET', '/hosts', Hosts::class, 'list', 'user'],
            ['GET', '/hosts/{id:int}', Hosts::class, 'get', 'any'],
            ['DELETE', '/hosts/{id:int}', Hosts::class, 'delete', 'user'],
            ['GET', '/hosts/{id:int}/packages/{state:installed|available}', Hosts::class, 'packages', 'any'],

            // Hosts, as seen by the linupdate agent: registering with an API key, then 'me' is the authenticated host
            ['POST', '/hosts', Agent::class, 'register', 'user'],
            ['DELETE', '/hosts/me', Agent::class, 'unregister', 'host'],
            ['PUT', '/hosts/me/status', Agent::class, 'status', 'host'],
            ['PUT', '/hosts/me/packages/{state:installed|available}', Agent::class, 'packages', 'host'],
            ['PUT', '/hosts/me/packages/events', Agent::class, 'events', 'host'],

            // Hosts profiles
            ['GET', '/profiles', Profiles::class, 'list', 'user'],
            ['GET', '/profiles/{name}', Profiles::class, 'get', 'any'],
            ['GET', '/profiles/{name}/repositories', Profiles::class, 'repositories', 'any'],
        ];
    }
}
