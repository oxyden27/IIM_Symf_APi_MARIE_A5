# Symfony-API-REST-IIM

Veuillez bien suivre les étapes suivantes pour utiliser l'api

## Installation

Lancez une instance SQL
Une image docker (docker-compose.yml) est présente dans le projet si besoin.

Lancer l'image Docker :
```bash
docker-compose up -d
```

Dans le fichier .env (modifier user/password/db_name)
```php
DATABASE_URL=mysql://root:root@127.0.0.1:3306/iim_symfony
```

Installer les dépendances :
```bash
composer install
```

Mettre en place la base de donnée : 
```bash
php bin/console doctrine database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
```

## Mettre en place la connexion sécurisé par JWT

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
```
Taper un mot de passe, qui devra être récupéré à la prochaine commande
```bash
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```
Même mot de passe que précédemment

```php
JWT_PASSPHRASE=mot-de-passe
```
A mettre dans le fichier .env.local
Si il n'existe pas, créez le.

## Utilisation
Je conseille fortement d'utiliser PostMan

Se connecter et récupérer le token : 

```
POST
127.0.0.1:8000/api/login_check

BODY
{
	"username": "amaillot01@gmail.com",
	"password": "goldorak"
}

RETURN JWT
```

Exemple:
```
POST
127.0.0.1:8001/api/articles/24

AUTH JWT

BODY
{
    "name": "ArticleTest",
    "description": "Descriptionb du test de la mort",
    "author": {
        "firstname": "Le nouvel auteur ",
        "lastname": "New"
    }
}

RETURN SUCCESS
```

Exemple2:
```
GET
127.0.0.1:8001/api/articles

AUTH JWT

RETURN List of all articles
```
