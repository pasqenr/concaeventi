<?php

/** @noinspection PhpUndefinedVariableInspection */
$I = new AcceptanceTester($scenario);

$I->resetCookie('ConcaEventi');
$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail2@mail.com',
    'password' => 'qwerty'
));

$I->seeInSource('Non ci sono eventi programmati.');

/* Create correct event */
$I->amOnPage('/events/create/');

$initDateTime = new DateTime('now');
$initDateTime->add(new DateInterval('P1D'));
$finishDateTime = new DateTime('now');
$finishDateTime->add(new DateInterval('P2D'));

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->click('Crea', 'form');

$I->seeInCurrentUrl('/events/');
$I->dontSeeInSource('Errore');

/* Edit correct event */

$I->amOnPage('/events/edit/1');
$I->seeInField('titolo', 'Titolo evento');
$I->seeInField('descrizione', 'Descrizione evento.');

$dateTime = $I->grabValueFrom("//select[@name='giornoInizio']");
$I->assertEquals($dateTime, $initDateTime->format('d'));
$dateTime = $I->grabValueFrom("//select[@name='meseInizio']");
$I->assertEquals($dateTime, $initDateTime->format('m'));
$dateTime = $I->grabValueFrom("//select[@name='annoInizio']");
$I->assertEquals($dateTime, $initDateTime->format('Y'));
$dateTime = $I->grabValueFrom("//select[@name='oraInizio']");
$I->assertEquals($dateTime, $initDateTime->format('H'));
$dateTime = $I->grabValueFrom("//select[@name='minutoInizio']");
$I->assertEquals($dateTime, $initDateTime->format('i'));

$dateTime = $I->grabValueFrom("//select[@name='giornoFine']");
$I->assertEquals($dateTime, $finishDateTime->format('d'));
$dateTime = $I->grabValueFrom("//select[@name='meseFine']");
$I->assertEquals($dateTime, $finishDateTime->format('m'));
$dateTime = $I->grabValueFrom("//select[@name='annoFine']");
$I->assertEquals($dateTime, $finishDateTime->format('Y'));
$dateTime = $I->grabValueFrom("//select[@name='oraFine']");
$I->assertEquals($dateTime, $finishDateTime->format('H'));
$dateTime = $I->grabValueFrom("//select[@name='minutoFine']");
$I->assertEquals($dateTime, $finishDateTime->format('i'));

$option = $I->grabTextFrom("//select[@name='associazioni[]']");
$I->assertEquals(substr(trim($option),  0, 6), 'Comune');
$option = $I->grabTextFrom("//select[@name='assPrimaria']", 'Comune');
$I->assertEquals(substr(trim($option),  0, 6), 'Comune');

$initDateTime = $initDateTime->add(new DateInterval('P1D'));
$finishDateTime = $finishDateTime->add(new DateInterval('P1D'));

$I->fillField('titolo', 'Titolo evento modificato');
$I->fillField('descrizione', 'Descrizione evento modificato.');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", '1');
$I->click('Modifica', 'form');

$I->seeInCurrentUrl('/events/');
$I->seeInSource('Titolo evento modificato');
$I->seeInSource('Descrizione evento modificato.');
$I->seeInSource($initDateTime->format('d/m/Y H:i'));
$I->seeInSource($finishDateTime->format('d/m/Y H:i'));
$I->seeInSource('Comune');

/* =================================
 *              CREATE
 * =================================
 */

/* Wrong create event: empty title */

$I->amOnPage('/events/create/');

$I->fillField('titolo', '');
$I->fillField('descrizione', 'Descrizione evento.');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: empty description */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* Wrong create event: initDate > finishDate */

$I->amOnPage('/events/create/');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');

$I->selectOption("//select[@name='giornoInizio']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $initDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", 'Comune');
$I->click('Crea', 'form');

$I->seeInSource('Errore');

/* =================================
 *              EDIT
 * =================================
 */

/* Wrong edit event: empty title */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', '');
$I->fillField('descrizione', 'Descrizione evento.');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", '1');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong edit event: empty description */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', '');

$I->selectOption("//select[@name='giornoInizio']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $initDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", '1');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Wrong edit event: initDate > finishDate */

$I->amOnPage('/events/edit/1');

$I->fillField('titolo', 'Titolo evento');
$I->fillField('descrizione', 'Descrizione evento.');

$I->selectOption("//select[@name='giornoInizio']", $finishDateTime->format('d'));
$I->selectOption("//select[@name='meseInizio']", $finishDateTime->format('m'));
$I->selectOption("//select[@name='annoInizio']", $finishDateTime->format('Y'));
$I->selectOption("//select[@name='oraInizio']", $finishDateTime->format('H'));
$I->selectOption("//select[@name='minutoInizio']", $finishDateTime->format('i'));

$I->selectOption("//select[@name='giornoFine']", $initDateTime->format('d'));
$I->selectOption("//select[@name='meseFine']", $initDateTime->format('m'));
$I->selectOption("//select[@name='annoFine']", $initDateTime->format('Y'));
$I->selectOption("//select[@name='oraFine']", $initDateTime->format('H'));
$I->selectOption("//select[@name='minutoFine']", $initDateTime->format('i'));

$I->selectOption("//select[@name='associazioni[]']", '1');
$I->click('Modifica', 'form');

$I->seeInSource('Errore');

/* Correct edit event:  */

$I->amOnPage('/events/edit/1');

$I->submitForm('form', array(
    'titolo'         => 'Titolo evento modificato',
    'descrizione'    => 'Descrizione evento modificato.',
    'giornoInizio'   => $initDateTime->format('d'),
    'meseInizio'     => $initDateTime->format('m'),
    'annoInizio'     => $initDateTime->format('Y'),
    'oraInizio'      => $initDateTime->format('H'),
    'minutoInizio'   => $initDateTime->format('i'),
    'giornoFine'     => $finishDateTime->format('d'),
    'meseFine'       => $finishDateTime->format('m'),
    'annoFine'       => $finishDateTime->format('Y'),
    'oraFine'        => $finishDateTime->format('H'),
    'minutoFine'     => $finishDateTime->format('i'),
    'associazioni[]' => '1',
    'revisionato'    => 'off',
));

$I->amOnPage('/events/edit/1');
$I->dontSeeInSource('Approvato');

$I->submitForm('form', array(
    'titolo'         => 'Titolo evento modificato',
    'descrizione'    => 'Descrizione evento modificato.',
    'giornoInizio'   => $finishDateTime->format('d'),
    'meseInizio'     => $finishDateTime->format('m'),
    'annoInizio'     => $finishDateTime->format('Y'),
    'oraInizio'      => $finishDateTime->format('H'),
    'minutoInizio'   => $finishDateTime->format('i'),
    'giornoFine'     => $initDateTime->format('d'),
    'meseFine'       => $initDateTime->format('m'),
    'annoFine'       => $initDateTime->format('Y'),
    'oraFine'        => $initDateTime->format('H'),
    'minutoFine'     => $initDateTime->format('i'),
    'associazioni[]' => '1',
    'revisionato'    => 'on',
));

$I->seeInSource('Errore');
$I->amOnPage('/events/');