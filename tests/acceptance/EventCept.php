<?php

$I = new AcceptanceTester($scenario);
$I->resetCookie('ConcaEventi');

$I->amOnPage('/events/');
$I->seeInCurrentUrl('auth-error');

$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

$I->seeInSource('Non ci sono eventi programmati.');

$I->amOnPage('/events/create/');

/* Create correct event */
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
$I->assertEquals(trim($option), 'Comune');
$option = $I->grabTextFrom("//select[@name='assPrimaria']", 'Comune');
$I->assertEquals(trim($option), 'Comune');
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