# Conca Eventi

ConcaEventi is a website created to manage and display upcoming events in Concamarise (VR).
The aim of the project is to provide a simply CMS (Content Management System) written from scratch.

## Installation

**Requirements:**
* PHP >= 7.1 (php7.1-mbstring php7.1-xml php7.1-curl php7.1-zip php7.1-mysql)
* MySQL >=5
* [Composer ](https://getcomposer.org/)

While waiting for some virtualization for the project you need to clone the repository and install it manually.

```
git clone --depth=1 https://github.com/pasqenr/concaeventi.git
cd concaeventi/
composer install
mysql -u USERNAME -p < app/concaeventi_db.sql
```
You need to set your MySQL username and password in *app/config/config.yml*. If you have imported the database using
the instructions above you need to set the *dbname* to *concaeventi_db*.

To start a development server use:
```
cd public/
php -S localhost:8080
```

The SQL has created a default user. Log-in with:
```
email: mail@mail.com
password: admin
```

## F.A.Q.

**Why don't you use a more complete framework?**

This project is for learning purposes. I don't even know the mechanics of a full featured framework but I wanted 
to be free to make my mistakes before use something more abstract and complex.
Probably future projects will be made using these frameworks.

**Why use PHP instead other languages?**

I'd be like to use a different language for the project but one of my requirements was to host the site on a
cheap hosting. Put in this perspective it's clear that PHP would be the best choice.


## License

GNU General Public License v3.0. Please see [License File](LICENSE.md) for more information.
