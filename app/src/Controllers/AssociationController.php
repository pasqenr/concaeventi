<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use \App\Helpers\ErrorHelper;
use \App\Models\AssociationModel;
use \App\Models\UserModel;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class AssociationController extends Controller
{
    private $associationModel;
    private $userModel;
    private $errorHelper;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->associationModel = new AssociationModel($this->db, $this->errorHelper);
        $this->userModel = new UserModel($this->db, $this->errorHelper);
    }

    public function showAll(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associations = $this->getAssociations();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/associations.twig', [
            'utente' => $this->user,
            'associazioni' => $associations
        ]);
    }

    public function create(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $members = $this->getAllMembers();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/create.twig', [
            'utente' => $this->user,
            'membri' => $members
        ]);
    }

    public function doCreate(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createAssociation($parsedBody);

        if ($created === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    public function edit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];

        try {
            $association = $this->getAssociation($associationID);
            $members = $this->getAllMembers();
            $belongs = $this->getBelongsByAssociation($associationID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/edit.twig', [
            'utente' => $this->user,
            'ass' => $association,
            'membri' => $members,
            'appartenenza' => $belongs
        ]);
    }

    public function doEdit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateAssociation($associationID, $parsedBody);

        if ($updated === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    public function delete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        try {
            $association = $this->getAssociation($associationID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/delete.twig', [
            'utente' => $this->user,
            'ass' => $association
        ]);
    }

    public function doDelete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];

        try {
            $this->deleteAssociation($eventID);
        } catch (\PDOException $e) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    private function getAssociations(): array
    {
        return $this->associationModel->getAssociations();
    }

    private function getAllMembers(): array
    {
        return $this->userModel->getAllMembers();
    }

    private function createAssociation($data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        if ($associationName === '' || empty($members)) {
            $this->errorHelper->setErrorMessage(
                'Empty request fields.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->errorHelper->setErrorMessage(
                'Wrong telephone format.',
                'Il numero di telefono non è nel formato corretto.');

            return false;
        }

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->errorHelper->setErrorMessage(
                    'Wrong hex format.',
                    'Il colore scelto non è nel formato corretto.');

                return false;
        }

        return $this->associationModel->createAssociation($data);
    }

    private function getAssociation($associationID): array
    {
        return $this->associationModel->getAssociation($associationID);
    }

    private function updateAssociation($associationID, $data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        if ($associationName === '' || empty($members)) {
            $this->errorHelper->setErrorMessage(
                'Empty field.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->errorHelper->setErrorMessage(
                'Wrong telephone format.',
                'Il formato del numero di telefono non è valido.');

            return false;
        }

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->errorHelper->setErrorMessage(
                    'Wrong hex format.',
                    'Il colore scelto non è nel formato corretto.');

                return false;
        }

        return $this->associationModel->updateAssociation($associationID, $data);
    }

    private function deleteAssociation($associationID): bool
    {
        return $this->associationModel->deleteAssociation($associationID);
    }

    private function getBelongsByAssociation($associationID): array
    {
        return $this->associationModel->getBelongsByAssociation($associationID);
    }

    private function isValidTelephone($telNumber): bool
    {
        return preg_match('^\d{10}$', $telNumber) !== 0;
    }

    private function isValidHex($hex): bool
    {
        return preg_match('^#(\d|[a-f]){6}$', $hex) !== 0;
    }
}