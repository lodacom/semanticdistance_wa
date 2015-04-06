Architecture et autres explications
===================================

Pour comprendre l'architecture de Symfony, je vous enjoint
fortement de vous reporter à la documentation française qui
se trouve [ici][1]. Plus rapidement reporter vous à la section
appelée "La structure des répertoires" [ici][2].

Il est expliqué dans la documentation que tous les fichiers
accessibles publiquement se trouve dans le répertoire web.
Or il n'y a pas grand chose, à part app.php et app_dev.php .
Les templates twig (correspondant aux vues) se trouvent
tous dans le répertoire /src/Acme/BiomedicalBundle/Resources/views .
Les fichiers concernant le css, les images et les js se trouvent
dans le répertoire /src/Acme/BiomedicalBundle/Resources/public.
Les fichiers de traduction se trouvent dans le répertoire
/src/Acme/BiomedicalBundle/Resources/translations .

1) Règles primordiales
----------------------

A chaque fois que vous créez un fichier de traducation, il faut demander à Symfony de le
prendre en compte en mettant en ligne de commande:
app/console cache:clear --env=dev (pour le mode dev)
app/console cache:clear --env=prod (pour le mode prod)

A chaque fois que vous créez ou installer un nouveau bundle il faut déclencher au moins une fois
la ligne de commande suivante:
php app/console assets:install web --symlink

2) Astuce(s)
------------

Si vous voulez passer en mode prod et ainsi faire découvrir
le site aux non initiés, il vous suffit de faire basculer
un paramètre dans le fichier app.php (dans /web) à la ligne 20.
Il faut ainsi faire passer le "false" à true.

Si vous voulez ajouter une route pour l'application, il ne faut non
pas l'ajouter dans le fichier config.yml dans le répertoire /app/config
mais dans le fichier du même nom dans le répertoire 
/src/Acme/BiomedicalBundle/Resources/config .

Si vous voulez changer la base de données requêtée, il vous suffit
de changer la ligne 6 du fichier parameters.yml (/app/config) et 
éventuellement de changer les lignes 7 et 8 en conséquence.

3) Integration de Symfony dans Eclipse
--------------------------------------

Si vous voulez intégrer le projet Symfony dans Eclipse, il vous faut
tout d'abord installer plusieurs plugins. Pour cela rendez-vous dans
Help->Install New Software (de Eclipse) et mettez l'url suivante:
http://p2-dev.pdt-extensions.org/

Ensuite cochez les cases appelées:

  * Symfony Feature
 
  * Doctrine Feature

  * PHP Development Tools (PDT)
	
  * Json EditorFeature
	
  * Twig Editor Feature
	
  * YEdit Feature

Une fois ces différents plugins installés et Eclipse redémarré. Normalement
vous pouvez importer le projet Symfony (le .project est présent sur le serveur
donc sa devrait être bon...). Avant la première importation (et pour les suivantes éventuelles),
il faut configurer les préférences d'Eclipse pour PHP (Eclipse->Préférences):
Tout d'abord: PHP->PHP Executables (là ajouter une ligne ou modifier la première s'il y en a déjà une)
PHP Debuger=XDebug
Executable Path=la localisation de l'executable PHP
SAPI Type=CLI

Puis dans PHP->Debug
PHP Debuger=XDebug
Server=Default PHP Web Server
PHP Executables=PHP

Maintenant que ces menus configurations ont été effectuées vous pouvez importer.
Pour ce faire: File->Import . Puis choisissez Symfony Project et remplissez les champs demandés.

Autre chose importante, il faut absolument garder le même encodage des fichiers qui doivent 
être en UTF-8 (obligatoire sous Symfony).

[1]:  http://symfony.com/pdf/Symfony_book_2.4_fr.pdf?v=4
[2]:  http://symfony.com/fr/doc/current/book/page_creation.html#la-structure-des-repertoires	
