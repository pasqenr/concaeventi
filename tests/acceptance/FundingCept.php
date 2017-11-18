<?php

$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

$initDateTime = new DateTime('now');
$initDateTime->add(new DateInterval('P1D'));
$finishDateTime = new DateTime('now');
$finishDateTime->add(new DateInterval('P2D'));

$I->amOnPage('/events/create/');
$I->submitForm('form', array(
    'titolo' => 'Evento_Funding',
    'descrizione' => 'Evento descrizione.',
    'giornoInizio' => $initDateTime->format('d'),
    'meseInizio' => $initDateTime->format('m'),
    'annoInizio' => $initDateTime->format('Y'),
    'oraInizio' => $initDateTime->format('H'),
    'minutoInizio' => $initDateTime->format('i'),
    'giornoFine' => $finishDateTime->format('d'),
    'meseFine' => $finishDateTime->format('m'),
    'annoFine' => $finishDateTime->format('Y'),
    'oraFine' => $finishDateTime->format('H'),
    'minutoFine' => $finishDateTime->format('i'),
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
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15.00');
$I->click('Finanzia', 'form');

$I->seeInCurrentUrl('/funding/');
$I->seeInSource('"&#x2F;funding&#x2F;edit&#x2F;2,1"');
$I->seeInSource('"&#x2F;funding&#x2F;delete&#x2F;2,1"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;,"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;2,"');
$I->dontSeeInSource('"&#x2F;funding&#x2F;edit&#x2F;,1"');

/* Create funding: wrong import length */

$I->amOnPage('/funding/create/');
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15000000.00');
$I->click('Finanzia', 'form');

$I->seeInSource('Errore');

/* Create funding: import only cents */

$I->amOnPage('/funding/create/');
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '.50');
$I->click('Finanzia', 'form');

$I->seeInSource('Errore');

/* Create funding correct: import with comma */

$I->amOnPage('/funding/delete/2,1');
$I->seeInSource('Evento_Funding');
$I->seeInSource('Sponsor_Funding');
$I->click('Elimina');

$I->seeInCurrentUrl('/funding/');
$I->dontSeeInSource('Evento_Funding');
$I->dontSeeInSource('Sponsor_Funding');
$I->dontSeeInSource('Errore');

/* ------------ */

$I->amOnPage('/funding/create/');
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15,00');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* Create funding correct: only integer part */

$I->amOnPage('/funding/delete/2,1');
$I->seeInSource('Evento_Funding');
$I->seeInSource('Sponsor_Funding');
$I->click('Elimina');

$I->seeInCurrentUrl('/funding/');
$I->dontSeeInSource('Evento_Funding');
$I->dontSeeInSource('Sponsor_Funding');
$I->dontSeeInSource('Errore');

/* ------------ */

$I->amOnPage('/funding/create/');
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* Create funding correct: only one decimal */

$I->amOnPage('/funding/delete/2,1');
$I->seeInSource('Evento_Funding');
$I->seeInSource('Sponsor_Funding');
$I->click('Elimina');

$I->seeInCurrentUrl('/funding/');
$I->dontSeeInSource('Evento_Funding');
$I->dontSeeInSource('Sponsor_Funding');
$I->dontSeeInSource('Errore');

/* ------------ */

$I->amOnPage('/funding/create/');
$I->selectOption('idSponsor', 'Sponsor_Funding');
$I->selectOption('idEvento', 'Evento_Funding');
$I->fillField('importo', '15.0');
$I->click('Finanzia', 'form');

$I->dontSeeInSource('Errore');

/* =================================
 *              EDIT
 * =================================
 */

/* Edit funding: correct */

$I->amOnPage('/funding/edit/2,1');
$I->fillField('importo', '20.0');
$I->click('Modifica', 'form');

$I->dontSeeInSource('Errore');

/* Edit funding: correct import with comma */

$I->amOnPage('/funding/edit/2,1');
$I->fillField('importo', '25,0');
$I->click('Modifica', 'form');

$I->dontSeeInSource('Errore');