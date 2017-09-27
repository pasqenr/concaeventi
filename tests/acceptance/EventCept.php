<?php

$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

$I->seeInSource('Non ci sono eventi programmati.');

/* Create correct event */
$I->amOnPage('/events/create/');

$today = new DateTime();
$initDate = $today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate);
$finishDate = $today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');
$I->fillField('istanteFine', $finishDate);
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/events/');
$I->seeInSource('&#x2F;events&#x2F;edit&#x2F;');
$I->seeInSource('&#x2F;events&#x2F;page&#x2F;');
$I->seeInSource('&#x2F;events&#x2F;delete&#x2F;');

/* Edit correct event */

$I->amOnPage('/events/edit/1');
$I->seeInField('titolo', 'Titolo evento');
$I->seeInField('descrizione', 'Descrizione evento.');
$date = $I->grabValueFrom("//input[@name='istanteInizio']");
$I->assertEquals($date, $initDate);
$date = $I->grabValueFrom("//input[@name='istanteFine']");
$I->assertEquals($date, $finishDate);
$option = $I->grabTextFrom("//select[@name='associazioni[]']");
$I->assertEquals(substr(trim($option),  0, 6), 'Comune');
$option = $I->grabTextFrom("//select[@name='assPrimaria']", 'Comune');
$I->assertEquals(substr(trim($option),  0, 6), 'Comune');
$I->seeCheckboxIsChecked('revisionato');

$initDate = $today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

$I->fillField('titolo', 'Titolo evento modificato');
$I->fillField('descrizione', 'Descrizione evento modificato.');
$I->fillField('istanteInizio', $initDate);
$finishDate = $today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');
$I->fillField('istanteFine', $finishDate);
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$initDate = new DateTime($initDate);
$finishDate = new DateTime($finishDate);
$I->seeInCurrentUrl('/events/');
$I->seeInSource('Titolo evento modificato');
$I->seeInSource('Descrizione evento modificato.');
$I->seeInSource($initDate->format('d/m/Y H:i'));
$I->seeInSource($finishDate->format('d/m/Y H:i'));
$I->seeInSource('Comune');

/* =================================
 *              CREATE
 * =================================
 */

/* Wrong create event: empty title */

$I->amOnPage('/events/create/');

$I->fillField('titolo', '');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty description */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: initDate > finishDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $finishDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $initDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty initDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', '');
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty finishDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', '');
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: wrong format initDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('d/m/Y'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: wrong format finishDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('d/m/Y'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty associazioni */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Correct create event: initDate alternative format #1 */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento 1');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate->format('Y-m-d'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/events/');

/* Correct create event: initDate alternative format #2 */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento 2');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/events/');

/* Correct create event: initDate alternative format #3 */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento 3');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:m'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:m'));
$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->checkOption('revisionato');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/events/');

/* =================================
 *              EDIT
 * =================================
 */

/* Wrong create event: empty title */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', '');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty description */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: initDate > finishDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');
$I->fillField('istanteInizio', $finishDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $initDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty initDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', '');
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty finishDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', '');
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: wrong format initDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('d/m/Y'));
$I->fillField('istanteFine', $finishDate->format('Y-m-d H:i:s'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong create event: wrong format finishDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');
$I->fillField('istanteInizio', $initDate->format('Y-m-d H:i:s'));
$I->fillField('istanteFine', $finishDate->format('d/m/Y'));
$I->selectOption("//select[@name='associazioni[]']", '1');
$I->checkOption('revisionato');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* =================================
 *              DELETE
 * =================================
 */

$I->amOnPage('/events/delete/1');

$I->seeInSource('Titolo evento modificato');
$I->click('Elimina');

$I->seeInCurrentUrl('/events/');
$I->dontSeeInSource('Titolo evento modificato');