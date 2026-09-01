#!/bin/sh
# Script lancé au démarrage du conteneur PHP-FPM, avant que php-fpm ne se
# mette réellement à écouter. Rôle : attendre que MySQL soit joignable, puis
# appliquer les migrations Doctrine et préparer le cache Symfony, pour que le
# conteneur soit toujours prêt à servir dès qu'il répond aux requêtes.
set -e

echo "[entrypoint] En attente de la base de données MySQL (${DB_HOST:-mysql}:${DB_PORT:-3306})..."
until php -r '
$host = getenv("DB_HOST") ?: "mysql";
$port = (int) (getenv("DB_PORT") ?: 3306);
$c = @fsockopen($host, $port, $errno, $errstr, 1);
if ($c) { fclose($c); exit(0); }
exit(1);
'; do
  echo "[entrypoint]   ...pas encore prêt, nouvel essai dans 2s"
  sleep 2
done
echo "[entrypoint] MySQL est joignable."

echo "[entrypoint] Application des migrations Doctrine..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "[entrypoint] Réchauffement du cache Symfony..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

echo "[entrypoint] Prêt. Démarrage de : $*"
exec "$@"
