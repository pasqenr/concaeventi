<?php

$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

$today = new DateTime();
$initDate = $today->format('Y-m-d H:i:s');
$finishDate = $today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

$I->amOnPage('/events/create/');
$I->submitForm('form', array(
    'titolo' => 'Evento_Funding',
    'descrizione' => 'Evento descrizione.',
    'istanteInizio' => $initDate,
    'istanteFine' => $finishDate,
    'associazioni[]' => '1',
    'revisionato' => 'on'
));

$I->seeInCurrentUrl('/events/');
$I->seeInSource('Evento_Funding');
$I->dontSeeInSource('Errore');

$I->amOnPage('/sponsors/create/');
$I->submitForm('form', array(
    'nome' => 'Sponsor_Funding'
));

$I->seeInCurrentUrl('/sponsors/');
$I->seeInSource('Sponsor_Funding');
$I->dontSeeInSource('Errore');

/* =================================
 *              CREATE
 * =================================
 */

/* Create funding: correct */

$I->amOnPage('/funding/');
$I->seeInSource('Non ci sono eventi finanziati.');

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15.00');
$I->click('Finanzia', 'form');

$I->seeInCurrentUrl('/funding/');
$I->seeInSource('"&#x2F;funding&#x2F;edit&#x2F;3,1"');
$I->seeInSource('"&#x2F;funding&#x2F;delete&#x2F;3,1"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;,"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;3,"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;,1"');

/* Create funding: wrong import length */

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15000000.00');
$I->click('Finanzia', 'form');

$I->seeInSource('Errore');

/* Create funding: import only cents */

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '.50');
$I->click('Finanzia', 'form');

$I->seeInSource('Errore');

/* Create funding correct: import with comma */

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15,00');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* Create funding correct: only integer part */

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* Create funding correct: only one decimal */

$I->amOnPage('/funding/create/');
$I->selectOption("idSponsor", 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15.0');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* =================================
 *              EDIT
 * =================================
 */

/* Edit funding: correct */

/*$I->amOnPage('/funding/');
$I->seeInSource('Z Associazione');

$I->amOnPage('/funding/edit/4');
$I->fillField('nomeAssociazione', 'Z Associazione 1');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '0123456789');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInCurrentUrl('/funding/');*/

/* Edit funding: wrong empty name */

/*$I->amOnPage('/funding/edit/4');
$I->fillField('nomeAssociazione', '');
$I->selectOption("//select[@name='membri[]']", '1');
$I->fillField('telefono', '0123456789');
$I->fillField('stile', '#00ff00');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');*/

/* =================================
 *              DELETE
 * =================================
 */

/*$I->amOnPage('/funding/delete/4');
$I->seeInSource('Z Associazione 1');
$I->click('Elimina');

$I->seeInCurrentUrl('/funding/');
$I->dontSeeInSource('Z Associazione 1');*/