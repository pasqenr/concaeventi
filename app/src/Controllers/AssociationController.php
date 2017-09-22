<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
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

    public function __construct($container)
    {
        parent::__construct($container);
        $this->associationModel = new AssociationModel($this->db);
        $this->userModel = new UserModel($this->db);
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
                'err' => $this->getErrorMessage()
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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
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
                'err' => $this->getErrorMessage()
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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
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
                'err' => $this->getErrorMessage()
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
            $this->setErrorMessage(
                'Empty request fields.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->setErrorMessage(
                'Wrong telephone format.',
                'Il numero di telefono non è nel formato corretto.');

            return false;
        }

        /*$associationID = $this->getLastAssociationID() + 1;
        $styleCreated = $this->setStyle($associationID, $style);
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        if ($styleCreated === false) {
            $this->setErrorMessage(
                'Impossible to write the new style CSS file.',
                'Impossibile creare lo stile associato.');

            return false;
        }*/

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->setErrorMessage(
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
            $this->setErrorMessage(
                'Empty field.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->setErrorMessage(
                'Wrong telephone format.',
                'Il formato del numero di telefono non è valido.');

            return false;
        }

        /*$styleCreated = $this->setStyle($associationID, $style);
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        if ($styleCreated === false) {
            $this->setErrorMessage(
                'Impossible to write the style CSS file.',
                'Impossibile modificare lo stile associato.');

            return false;
        }*/

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->setErrorMessage(
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

    /*private function setStyle($associationID, $style): bool
    {
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        $good = file_put_contents($style_path, $style, LOCK_EX);

        return $good !== false;
    }*/

    private function isValidTelephone($telNumber): bool
    {
        return preg_match('^\d{10}$', $telNumber) !== 0;
    }

    private function isValidHex($hex): bool
    {
        return preg_match('^#(\d|[a-f]){6}$', $hex) !== 0;
    }
}