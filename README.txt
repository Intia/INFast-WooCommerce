=== INFast pour WooCommerce ===
Contributors: intia
Tags: invoice, facture, infast, intia, woocommerce
Requires at least: 5.6
Tested up to: 5.8
Requires PHP: 7.2
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html


Créez et envoyez par e-mail des factures conformes automatiquement à chaque commande passée sur votre e-boutique WooCommerce.

== Description ==
Cette extension vous permet de générer automatiquement des factures lorsqu’une vente est effectuée sur votre e-boutique WooCommerce et de synchroniser vos comptes Woocommerce et INFast.  
  
INFast est un logiciel de facturation 100% français qui vous fait gagner du temps en automatisant vos devis, factures, avoirs.  
Grâce à une interface intuitive, vous gérez les commandes manuelles, et paiements hors boutique en ligne, fiches clients et produits depuis n’importe quel support (tablette, ordinateur, smartphone). INFast s’adresse aussi bien aux auto-entrepreneurs, qu’aux TPE et PME souhaitant se conformer aux exigences de la réglementation française, notamment en matière de facturation. Service client par tchat 7/7 et [par téléphone sur rendez-vous](https://calendly.com/intia-devis-factures/renseignement-plugin-woocomerce).

  
= Fonctionnalités du plugin =
- Création automatique d’une facture personnalisée lors de chaque commande en ligne
- Centralisation de vos documents de facturation, données clients et articles
- Création et mise à jour instantanées des clients de WooCommerce vers INFast
- Création et mise à jour en temps réel des articles de  WooCommerce vers INFast
- Ajout automatique d’un nouvel article dans INFast lors de la création de facture, si l'article n'existe pas déjà dans INFast
- Synchronisation de tous les articles WooCommerce dans INFast
- Envoi automatique des factures par e-mail (paramétrable)
- Ajout d’un destinataire en copie lors des envois d’e-mails (paramétrable)

**Consulter l’exemple de facture disponible depuis un portail personnalisé pour voir la facturation INFast en action**
[Exemple de facture](https://inbox.intia.fr/ckto6edjy00f6j2uka1z4elyi)
= Les avantages à utiliser l’extension INFast pour WooCommerce =
En plus de gagner du temps avec l’automatisation de la facturation et de la synchronisation des données clients et articles, vous pouvez accéder à d’autres fonctionnalités directement depuis le logiciel devis factures INFast, comme :

- le choix de la numérotation des factures
- la personnalisation de vos factures avec votre logo 
- vos factures au format pdf
- l’export de vos factures et bases de données client et article au format Excel
- le suivi automatique et l’historique de l’envoi de vos documents de facturation
- le suivi de votre chiffre d’affaires mois par mois
- le partage des données comptables

= Sécurité =
- INFast est conforme à la loi anti-fraude
- Vos données INFast sont sauvegardées et sécurisées sur des serveurs français
- INFast respecte le règlement général de protection des données personnelles (RGPD)


== Installation ==

= Pré-requis =
* PHP 7.2 ou ultérieur 
* MySQL 5.6 ou ultérieur
- WordPress 3.1 ou ultérieure
- WooCommerce 5.6 ou ultérieure
- Un compte [INFast](https://intia.fr/fr/infast/) sur l’offre FURIOUS :feu:

= Installation =
Depuis l'administration de Wordpress : 
- Rendez-vous dans la rubrique "plugins"
- Cliquez sur "Add new"
- Recherchez "INFast"
- Cliquez sur "Install"
- Activez le plugin

Une fois le module activé, un nouveau sous-menu apparaît dans le menu WooCommerce.  
Pour lier WooCommerce à INFast, renseignez le ClientID et ClientSecret de votre compte INFast.  
Ces identifiants sont accessibles depuis votre compte INFast : allez dans le menu principal (en haut à droite),sélectionnez "Paramètres", puis “API”.  
  
Si vous souhaitez envoyer automatiquement les factures à vos clients, cochez la case "Envoyer les factures automatiquement par email ?"  
Vous pouvez également recevoir une copie des emails en renseignant votre adresse mail.  
  
N'oubliez pas de sauvegarder ces changements.  
    
Pour ajouter des produits déjà existants sur WooCommerce dans votre compte INFast et synchroniser vos données, cliquez sur "Lancer la synchronisation".  
  
== Frequently Asked Questions ==
= Est-ce que ce plugin fonctionne sans WooCommerce ? =
Non, ce plugin est dédié à la synchronisation des commandes WooCommerce dans INFast.

= Est-ce que ce plugin est gratuit ? =
Oui, ce plugin est gratuit mais nécessite d’avoir un compte INFast actif.  
Vous devez également posséder un compte WooCommerce.  

= Est-ce qu'INFast est gratuit ? =
INFast est gratuit pendant 30 jours.
Vous devrez ensuite vous abonner à l'offre FURIOUS pour disposer des accès API permettant l’interconnexion avec WooCommerce.  

= Les articles sont-ils mis à jour automatiquement ? =
Oui.  
Dès qu'un article est créé ou modifié dans WooCommerce, il sera également créé ou modifié dans INFast.  
En revanche une mise à jour dans INFast n’entraîne pas de mise à jour dans WooCommerce.  

== Changelog ==
= Version 1.0.0 =
- Création des clients dans INFast dès la création dans WooCommerce
- Mise à jour des clients dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast dès la création dans WooCommerce
- Mise à jour des articles dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast lors de la création de facture si les articles ne sont pas déjà dans INFast
- Synchronisation de tous les articles WooCommerce dans INFast
- Possibilité d'activer ou non l'envoi d'e-mails
- Possibilité d'ajouter un destinataire en copie des envois d’e-mails
