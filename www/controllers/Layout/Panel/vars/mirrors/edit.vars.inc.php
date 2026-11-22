<?php
use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\User\Permission\Mirror as MirrorPermission;

if (!MirrorPermission::allowedAction('edit')) {
    throw new Exception('You are not allowed to access this panel');
}

if (empty($item['mirror-id']) or !is_numeric($item['mirror-id'])) {
    throw new Exception('Mirror Id is invalid');
}

$mirrorController = new \Controllers\Mirror\Mirror();

$mirror = $mirrorController->getById((int) $item['mirror-id']);

if (empty($mirror) or !RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
    throw new Exception('Mirror does not exist');
}

/**
 *  The versions, contents and architectures of an openbsd mirror can be edited: the values of its source repository are suggested,
 *  with the current ones (they may have been typed when creating the mirror)
 */
$openbsd = null;

if ($mirror['Package_type'] == 'openbsd') {
    $params = \Controllers\Mirror\Mirror::getParams($mirror);
    $source = (new \Controllers\Repo\Source\Source())->get('openbsd', $mirror['Source']);
    $definition = json_decode($source['Definition'] ?? '', true) ?: [];

    $openbsd = [
        'versions' => ['selected' => $params['versions'] ?? [], 'available' => array_column($definition['versions'] ?? [], 'name')],
        'archs'    => ['selected' => $params['arch'] ?? [], 'available' => array_column($definition['archs'] ?? [], 'name')],
        'content'  => $params['content'] ?? []
    ];

    foreach (['versions', 'archs'] as $field) {
        $openbsd[$field]['available'] = array_values(array_unique(array_merge($openbsd[$field]['available'], $openbsd[$field]['selected'])));
    }

    unset($params, $source, $definition, $field);
}

unset($mirrorController);
