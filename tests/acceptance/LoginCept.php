<?php

$I = new AcceptanceTester($scenario);
$I->amOnPage('/');
$I->click('Login');
$I->seeInCurrentUrl('login');

$I->fillField('email', 'mail@mail.com');
$I->fillField('password', 'qwerty');
$I->click('Login', 'form');

$I->dontSeeInSource('Errore');
$I->seeInCurrentUrl('/');
$I->see('Logout', '.navbar-item');

$I->click('Logout');

$I->amOnPage('/');
$I->see('Login', '.navbar-item');