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

    /**
     * AssociationController constructor.
     * @param \Slim\Container $container
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->associationModel = new AssociationModel($this->db, $this->errorHelper);
        $this->userModel = new UserModel($this->db, $this->errorHelper);
    }

    /**
     * Show all the associations.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Create page for a new association.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Association creation action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Edit page for an association.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Association edit action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Delete page for an association.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Association deletion action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Get all the associations.
     *
     * @return array The associations.
     */
    private function getAssociations(): array
    {
        return $this->associationModel->getAssociations();
    }

    /**
     * Get all members (users).
     *
     * @return array The members.
     */
    private function getAllMembers(): array
    {
        return $this->userModel->getAllMembers();
    }

    /**
     * Create a new entry for the association.
     *
     * @param array $data The array with the required fields to create a new association.
     * @return bool TRUE if the association has been created, FALSE if the fields are wrong or some
     * other error have been occurred.
     */
    private function createAssociation($data): bool
    {
        if ($this->checkAssociationData($data) === false) {
            return false;
        }

        return $this->associationModel->createAssociation($data);
    }

    /**
     * Get the association identified by $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return array The association with its information.
     * @throws \PDOException
     */
    private function getAssociation($associationID): array
    {
        return $this->associationModel->getAssociation($associationID);
    }

    /**
     * Update all the fields in the association identified by $associationID with the fields in
     * $data.
     *
     * @param int $associationID A valid association identifier.
     * @param array $data The array with the required fields to update the association.
     * @return bool TRUE if the association has been updated, FALSE if the fields are wrong or some
     * other error have been occurred.
     */
    private function updateAssociation($associationID, $data): bool
    {
        if ($this->checkAssociationData($data) === false) {
            return false;
        }

        return $this->associationModel->updateAssociation($associationID, $data);
    }

    /**
     * Delete an association identified by $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return bool TRUE if the association has been deleted, FALSE some error have been occurred.
     * @throws \PDOException
     */
    private function deleteAssociation($associationID): bool
    {
        return $this->associationModel->deleteAssociation($associationID);
    }

    /**
     * Get all the users ids that belong to the association identified by $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return array An array of the belongs with associated ids.
     * @throws \PDOException
     */
    private function getBelongsByAssociation($associationID): array
    {
        return $this->associationModel->getBelongsByAssociation($associationID);
    }

    /**
     * Check if a string contains a valid telephone italian format.
     *
     * @param string $telNumber A string with the telephone number.
     * @return bool TRUE if the number string is valid, FALSE otherwise.
     */
    private function isValidTelephone($telNumber): bool
    {
        return preg_match('/^\d{10}$/', $telNumber) === 1;
    }

    /**
     * Check if a string contains a valid hexadecimal number. The function require '#' as
     * first character and a number format of 6 digits.
     *
     * @param string $hex The string with the hexadecimal number.
     * @return bool TRUE if the hexadecimal string is valid, FALSE otherwise.
     */
    private function isValidHex($hex): bool
    {
        return preg_match('/^#(\d|[a-f]){6}$/', $hex) === 1;
    }

    /**
     * Check the parameters of create or edit association.
     *
     * @param array $data The array with the required fields to be checked.
     * @return bool TRUE if the tests pass, FALSE otherwise. Error message is also set.
     */
    private function checkAssociationData($data): bool
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

        return true;
    }
}