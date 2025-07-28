# EpiClub
Gestion en ligne décentralisée des EPI d'une association

Permettre la gestion décentralisée et multi utilisateurs des Equipements de Protection Individuelle (E.P.I.) dans les clubs s'escalade et de sports de montagne (mais pas que).

Dans une grosse structure associative, la gestion des EPI qui peuvent être dispersés sur plusieurs sites de stockage et/ou répartis entre plusieurs encadrants peut s'avérer vite problématique.
Le club d'escalade que j'anime s'y est vite trouvé confronté et aucune solution satisfaisante ne semble disponible sur le marché libre. C'est la raison pour laquelle j'ai construit ce petit logiciel web basé sur un serveur mySQL et accessible via une interface web.
Le code utilisé est du PHP. 

Le logiciel client est accessible par login/motDePasse via un simple logiciel web aux utilisateurs référencés. La gestion en back-end se fait via PHPmyadmin. Une connaissance basique de celui-ci est donc nécessaire pour l'installer et assurer les opérations éventuelles de gestion de la base :

- Installation du logiciel
- Gestion des utilisateurs
- Entretien de la base de données
- ...

  ## Installation

  1- Copier le dossier epiclub et son contenu dans un dossier accessible par votre serveur web (www/ ou public_html en général).
  
  2- Vérifier que le dossier et son contenu sont accessibles en lectre et ecriture par sotre serveur web.
  
  3- Editer le fichier 'config.php' pour y inscrire le nom et l'url de votre base de données ainsi que votre login/mdp pour accéder à votre serveur mySQL ou mariaDB.
  
  4- Si ce n'est pas fait, installez phpmyadmin sur votre serveur. Connectez vous-y pour importer votre base de données disponible dans le dossier : sql/init.sql
  
  5- Créer vos utilisateurs (dont vous-même)
  
  6- Accédez au dossier de l'application avec votre navigateur web en entrant son adresse dans la barre d'adresse : https://mon.serveur.web/epiclub

  ## Utilisation
  
  Vous devriez arriver à savoir l'utiliser tout seul. Je rédigerai un mod d'emploi lorsqu'il s'étoffera...
