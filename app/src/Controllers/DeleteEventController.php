<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class DeleteEventController extends Controller
{
    public function delete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $eventID = (int)$args['id'];
        $event = $this->getEvent($eventID);

        return $this->render($response, 'events/delete.twig', [
            'utente' => $user,
            'evento' => $event
        ]);
    }

    public function doDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $eventID = (int)$args['id'];
        $this->deleteEvent($eventID);

        return $response->withRedirect($this->router->pathFor('events'));
    }

    private function getEvent($eventID)
    {
        $sth = $this->db->prepare("
            SELECT E.idEvento, E.titolo
            FROM Evento E
            WHERE E.idEvento = :eventID
        ");
        $sth->bindParam(':eventID', $eventID, \PDO::PARAM_INT);
        $sth->execute();

        $event = $sth->fetch();

        return $event;
    }

    private function deleteEvent($eventID)
    {
        $good = $this->deleteFromProposes($eventID);

        if (!$good) {
            return false;
        }

        $sth = $this->db->prepare("
            DELETE
            FROM Evento
            WHERE idEvento = :idEvento
        ");
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteFromProposes($eventID)
    {
        $sth = $this->db->prepare("
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ");
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }
}