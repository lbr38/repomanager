<?php
use \Controllers\Repo\Source\Deb as DebSourceRepo;

/**
 *  Add a distribution component
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['component'])) {
    $debSourceController = new DebSourceRepo($_POST['id']);

    try {
        $debSourceController->addComponent($_POST['distributionId'], $_POST['component']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Component added');
}

/**
 *  Edit a distribution component
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['componentId']) and isset($_POST['params'])) {
    $debSourceController = new DebSourceRepo($_POST['id']);

    try {
        $debSourceController->editComponent($_POST['distributionId'], $_POST['componentId'], $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Component edited');
}

/**
 *  Remove a distribution component
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['componentId'])) {
    $debSourceController = new DebSourceRepo($_POST['id']);

    try {
        $debSourceController->removeComponent($_POST['distributionId'], $_POST['componentId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Component removed');
}

/**
 *  Get predefined components values for a task
 */
if ($_POST['action'] == 'get-predefined-components' and !empty($_POST['source']) and !empty($_POST['distribution'])) {
    $debSourceController = new DebSourceRepo();

    try {
        $content = $debSourceController->getPredefinedComponents($_POST['source'], $_POST['distribution']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get predefined components architectures values for a task
 */
if ($_POST['action'] == 'get-predefined-architectures' and !empty($_POST['source']) and !empty($_POST['distribution']) and !empty($_POST['component'])) {
    $debSourceController = new DebSourceRepo();

    try {
        $content = $debSourceController->getPredefinedArchitectures($_POST['source'], $_POST['distribution'], $_POST['component']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
