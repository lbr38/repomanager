<?php

namespace Controllers\Api\V3\Repository;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Response;
use Controllers\Repo\Snapshot\Package;
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  /repositories/{type}/{id}/snapshots/{snapshot}/packages (curated and local repositories)
 */
class Packages extends Controller
{
    /**
     *  List the packages of the snapshot
     */
    public function list(): Response
    {
        self::checkPermission(RepoPermission::allowedAction('browse'), 'You are not allowed to list packages (browse repository)');

        return Response::ok(self::formatList($this->package()->list()));
    }

    /**
     *  Upload packages to the snapshot (multipart request), its metadata must then be rebuilt
     *  Fields: one or more files, overwrite and ignore_if_exists (optional, 'true' or 'false')
     */
    public function upload(): Response
    {
        self::checkPermission(RepoPermission::allowedAction('upload-package'), 'You are not allowed to upload packages');

        $package = $this->package();
        $files = [];

        $this->request->allow(['overwrite', 'ignore_if_exists']);

        // Files can be sent as separate fields (-F file1=@a.deb) or as a list (-F packages[]=@a.deb -F packages[]=@b.deb)
        foreach ($this->request->files() as $file) {
            if (is_array($file['name'])) {
                array_push($files, ...\Controllers\Utils\Array\Sort::byPostFiles($file));
                continue;
            }

            $files[] = $file;
        }

        if (empty($files)) {
            throw new HttpException(400, 'No package to upload, send the packages as files of a multipart request');
        }

        $results = $package->upload($files, $this->request->bool('overwrite', false), $this->request->bool('ignore_if_exists', false));

        return Response::ok([
            'uploaded' => array_values($results[Package::UPLOADED] ?? []),
            'ignored'  => array_values($results[Package::IGNORED] ?? [])
        ], 'Packages uploaded, rebuild the snapshot metadata to make them available');
    }

    /**
     *  Delete packages from the snapshot by their file name: {"packages": ["nginx_1.26.0-1~bookworm_amd64.deb"]}
     */
    public function delete(): Response
    {
        self::checkPermission(RepoPermission::allowedAction('delete-package'), 'You are not allowed to delete packages');

        $package = $this->package();

        $this->request->allow(['packages']);

        $deleted = $package->deleteByName($this->request->list('packages'));

        return Response::ok(['deleted' => array_values(array_column($deleted, 'name'))], 'Packages deleted, rebuild the snapshot metadata to apply the changes');
    }

    /**
     *  Return packages details in the API format
     */
    public static function formatList(array $packages): array
    {
        return array_map(function ($package) {
            return [
                'filename' => $package['filename'],
                'name'     => $package['name'],
                'version'  => $package['version'],
                'release'  => $package['release'] ?? null,
                'arch'     => $package['arch'],
                'size'     => $package['size'],
                'path'     => $package['relative-path'],
                'url'      => $package['download-url']
            ];
        }, $packages);
    }

    /**
     *  Return the packages controller of the snapshot addressed by the route
     */
    private function package(): Package
    {
        $repository = $this->repo();

        // Check that the snapshot is an active snapshot of the repository
        $repository->formatSnapshot($this->snapshotId());

        return new Package($this->snapshotId());
    }
}
