=== INFast ===
Contributors: intia
Tags: invoice, facture, infast, intia
Requires at least: 3.0.1
Tested up to: 5.8
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Permet la création de factures INFast lors d\'une commande faite sous WooCommerce.

== Description ==
Ce plugin permet de connecter votre boutique WooCommerce avec INFast.  
  
INFast automatise vos devis, factures et relances clients.  
Interface simple et intuitive, accessible sur tout support.  
  
Ce plugin est gratuit mais nécessite un compte INFast.  
WooCommerce doit également être installé.  
  
  
Le plugin vous permet lors d\'une commande d\'un de vos clients de lui envoyer directement par mail une facture générée par INFast.  
Ceci vous permet de centraliser toutes vos factures (achats en ligne et achats physiques) dans le même outil.  
  
Les articles et clients sont automatiquement créés et mis à jour dans INFast.  
  
Voici la liste des fonctionnalités :
- Création des clients dans INFast dès la création dans WooCommerce
- Mise à jour des clients dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast dès la création dans WooCommerce
- Mise à jour des articles dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast lors de la création des factures si l\'article n\'existe pas déjà dans INFast
- Synchronisation de tous les articles WooCommerce dans INFast
- Possibilité d\'activer ou non l\'envoi de mail
- Possibilité d\'ajouter un destinataire en copie des envoi de mail


== Installation ==
Depuis l\'administration de Wordpress : 
- Rendez vous dans la rubrique \"plugins\"
- Cliquez sur \"Add new\"
- Recherchez \"INFast\"
- Cliquez sur \"Install\"
- Activez le plugin

Une fois activé, un nouveau sous menu apparaît dans le menu WooCommerce.  
Pour lier WooCommerce à INFast, il est nécessaire de renseigner le ClientID et ClientSecret de votre compte INFast.  
Ces identifiants sont accessibles dans INFast, menu principal (en haut à droite), puis \"Paramètres\", puis API.  
  
Il est possible d\'envoyer automatiquement les factures à vos clients si vous cochez la case \"Envoyer les factures automatiquement par email ?\"  
Vous pouvez également recevoir une copie des emails en renseignant une adresse email.  
  
N\'oubliez pas de sauvegarder ces changements.  
  
  
Si vous avez déjà des produits de renseignés dans WooCommerce, il est possible de les créer dans INFast en cliquant sur \"Lancer la synchronisation\".  
  
== Frequently Asked Questions ==
= Est-ce que ce plugin fonctionne sans WooCommerce ? =
Non, ce plugin est dédié à la synchronisation des commandes WooCommerce dans INFast

= Est ce qu\'INFast est gratuit ? =
INFast est gratuit pendant 30 jours.  
Il est ensuite nécessaire de prendre un abonnement sur l\'offre FURIOUS pour disposer des accès API qui permettra l’interconnexion avec WooCommerce.  

= Les articles sont-ils mis à jours automatiquement ? =
Oui.  
Dès qu\'un article est créé ou modifié dans WooCommerce, il sera créé ou modifié dans INFast.  
En revanche une mise à jours dans INFast n’entraîne pas de mise à jour dans WooCommerce.  

== Changelog ==
= Version 1.0.0 =
- Création des clients dans INFast dès la création dans WooCommerce
- Mise à jour des clients dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast dès la création dans WooCommerce
- Mise à jour des articles dans INFast dès la mise à jour dans WooCommerce
- Création des articles dans INFast lors de la création des factures si l\'article n\'est pas déjà dans INFast
- Synchronisation de tous les articles WooCommerce dans INFast
- Possibilité d\'activer ou non l\'envoi de mail
- Possibilité d\'ajouter un destinataire en copie des envoi de mail