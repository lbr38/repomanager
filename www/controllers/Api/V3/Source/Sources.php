<?php

namespace Controllers\Api\V3\Source;

use JsonException;
use Controllers\Api\HttpException;
use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;
use Controllers\Repo\Source\Source;
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  /sources: source repositories, from which curated repositories and mirrors are synced
 *  Their definition can contain credentials (authentication, certificates), the edit permission is required
 */
class Sources extends Controller
{
    private const PACKAGE_TYPES = ['deb', 'rpm', 'arch'];

    public function __construct(\Controllers\Api\V3\Request $request)
    {
        parent::__construct($request);

        if (!RepoPermission::allowedAction('edit-source')) {
            throw new HttpException(403, 'You are not allowed to edit source repositories');
        }
    }

    /**
     *  List the source repositories, optionally of a package type: ?package_type=deb
     */
    public function list(): Response
    {
        $packageType = $this->request->query('package_type');

        if ($packageType !== null and !in_array($packageType, self::PACKAGE_TYPES, true)) {
            throw HttpException::invalidQueryParameter('Invalid package type ' . $packageType . ' (' . implode(', ', self::PACKAGE_TYPES) . ')', 'package_type');
        }

        return Response::ok(array_map(self::format(...), (new Source())->listAll($packageType)));
    }

    /**
     *  Return a source repository
     */
    public function get(): Response
    {
        $source = (new Source())->get($this->request->param('package_type'), $this->request->param('name'));

        if (empty($source)) {
            throw new HttpException(404, 'Source repository ' . $this->request->param('name') . ' not found');
        }

        return Response::ok(self::format($source));
    }

    /**
     *  Import source repositories from a YAML template file (multipart request, file field named 'template')
     */
    public function import(): Response
    {
        $files = $this->request->files();

        if (empty($files['template']['tmp_name']) or count($files) > 1) {
            throw new HttpException(400, 'You must provide one template file, in a field named "template"');
        }

        (new Source())->importYamlFromApi($files['template']['tmp_name']);

        return Response::message('Source repositories imported successfully');
    }

    /**
     *  Return a source repository from its database row: its definition, with its Id and method
     */
    private static function format(array $source): array
    {
        try {
            $definition = json_decode($source['Definition'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $definition = [];
        }

        return array_merge(['id' => (int) $source['Id'], 'method' => $source['Method'] ?? null], (array) $definition);
    }
}
