# UTM framework
Framework PHP5 : rapide, leger et éprouvé

- Facilite la mise oeuvre d'un projet grâce à des fichiers d'exemples fournis par defaut avec l'application
- Utilisation du modèle MVC simplifié.
- Gestionnaire de plugins natif. Vous pouvez également créer facilement votre propres plugins, afin d'étendre les fonctionnalités du framework
- Gestionnaire d'évenements, vous permettant de réagir à des evenements ou de déclencher vos propres évenements.
- Chargement automatique des fichiers de config.
- Shell interactif (beta)

## Téléchargement
- [Télécharger la derniere version](https://github.com/Dizagn/utm/archive/master.zip)
- [Sur le site officiel](http://utm.dizagn.com)
- Cloner le dépot GIT : git clone https://github.com/Dizagn/utm.git

## Installation
C'est la toute la beauté du framework il n'y a rien a installer en plus ;). Il vous suffit d'avoir un serveur web avec php 5 fonctionnel.
Cependant pour des raisons de sécurité, il convient de faire pointer le document root de votre projet dans le dossier www du framework.

## Documentation
Toute la documentation est disponible sur [le site officiel ](http://utm.dizagn.com/?ctrl=documentation)

## A propos du framework UTM
### Découvrez l'historique du projet UTM
Depuis plus de dix ans maintenant nous developpons des applicatifs web pour des projets extrement variés. Du site E-commerce à la simple plaquette en passant par les réseaux sociaux ou les sites de rencontres.
Ces sites a faible ou fort trafic, hébergés sur des plateformes mutualisées ou dédiées, nous ont permis d'acquerir une grande expérience dans la réalisation de sites web. Ces projets extrement variés nous ont poussé à chercher une solution unique permettant de developper rapidement nos solutions quelque soit le client.
Ayant utilisé de nombreux autres frameworks, nous avons su en apprécier leurs qualités mais également leurs defauts.
Puis comme la plupart des developpeurs curieux nous avons commencer par ecrire une premiere version de notre framework, qui était très proche de ce que l'on trouve dans la plupart des frameworks connus. Puis cette premiere esquisse laissa sa place à une seconde version plus apte à la production et aussi plus simple. Mais il lui manquait beaucoup de fonctionnalités essentielles digne d'un framework moderne tel que nous le conçevions. C'est a partir de ce moment que commenca le developpement d'une version plus originale, plus fonctionnelle, et surtout plus securisé et performante. La version que nous estimerions suffisament mature pour réaliser nos projets mais également pour la diffuser à tous ceux qui souhaitent poursuivre notre objectif :
faire rapidement des sites performants, évolutifs et fonctionnels en maitrisant tous les rouages de leurs applicatifs.

### Philosophie des frameworks
Un framework si on le traduit littéralement n'est q'un cadre de travail. Quelque chose qui permet à tous les acteurs d'un projet de travailler de façon définie et si possible homogène.
Aujourd'hui ils sont tres souvent liés a un des plus célébres motifs de conception (AKA: design pattern) : M.V.C. , pour lequel quasiment toute la communauté des developpeurs est d'accord pour lui donner la définition suivante :

Séparer la logique métier(M), la présentation des données (V), et le controle de l'action(C).

Cependant lorque l'on compare les différents versions de framework, on s'apercoit qu'il y a presque autant d'implémentations que de developpeurs.
Car si l'on résume, les actions minimales d'un framework MVC sont :
- Recevoir et traiter une requete
- Executer le controlleur correspondant à la requete à l'aide ou non d'un modèle
- Afficher une éventuelle réponse à l'aide ou non d'un modèle

Ceci pourrait tenir en un seul fichier et permettre d'obtenir un niveau de performances tres bon mais au prix d'une modularité très insuffisante. D'autres sont dans l'exces inverse si bien qu'au final la complexité et le nombre de fichiers à maitriser pour utiliser un tel framework rebute les developpeurs à l'utiliser.
Voila pour nous le principal point de différence entre tous les frameworks :
L'équilibre entre la simplicité, la modularité, les performances, et les fonctionnalités !

Dans le framework UTM, nous avons donc décidé de reprendre le motif MVC à son origine et tenter de synthétiser et de comprendre l'intéret de ce concept datant de 1977, à un média comme internet. Puis nous avonc fait le choix d'apporter un maximum de fonctionnalités dans un minimum de fichiers, en tentant de rester le plus modulaire possible afin que chacun puisse prendre en main l'intégralité de ces concepts, KISS and DRY :) .
