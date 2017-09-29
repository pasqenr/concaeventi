<?php

$I = new AcceptanceTester($scenario);
$I->resetCookie('ConcaEventi');

$I->amOnPage('/panel/');
$I->seeInCurrentUrl('auth-error');

$I->amOnPage('/login/');
$I->submitForm('form', array(
    'email' => 'mail@mail.com',
    'password' => 'qwerty'
));

$I->amOnPage('/panel/');
$I->seeInCurrentUrl('panel');
$I->seeInSource('Pannello di gestione');
$I->seeInSource('&#x2F;events&#x2F;');
$I->seeInSource('&#x2F;associations&#x2F;');
$I->seeInSource('&#x2F;sponsors&#x2F;');
$I->seeInSource('&#x2F;funding&#x2F;');

/* Test all panel sections */

$I->amOnPage('/events/');
$I->dontSeeInCurrentUrl('auth-error');
$I->seeInSource('&#x2F;events&#x2F;create&#x2F;');

$I->amOnPage('/associations/');
$I->dontSeeInCurrentUrl('auth-error');
$I->seeInSource('&#x2F;associations&#x2F;create');
$I->seeInSource('<strong>Nome associazione: </strong> Comune.', '.content');
$I->seeInSource('Modifica', '.content');
$I->seeInSource('Elimina', '.content');

$I->amOnPage('/sponsors/');
$I->dontSeeInCurrentUrl('auth-error');
$I->seeInSource('&#x2F;sponsors&#x2F;create');

$I->amOnPage('/funding/');
$I->dontSeeInCurrentUrl('auth-error');
$I->seeInSource('&#x2F;funding&#x2F;create');