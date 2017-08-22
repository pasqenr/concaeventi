<?php

if ($argc != 2) {
    echo 'Usage: ' . $argv[0] . ' <password> ' . "\n";
    exit(1);
} else {
    echo 'Password: ' . password_hash($argv[1], PASSWORD_DEFAULT) . "\n";
}
