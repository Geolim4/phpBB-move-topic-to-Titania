<?php
/**
* phpBB move topic to titania MCP language [french]
*
* author: phpbb-fr website team
* begin: 24/03/2014
* version: 0.0.1 - 24/03/2014
* licence: http://opensource.org/licenses/gpl-license.php GNU Public License
*/

// ignore
if ( !defined('IN_PHPBB') )
{
	exit;
}

// init lang ary, if it doesn't !
if ( empty($lang) || !is_array($lang) )
{
	$lang = array();
}

//Move to CDB
$lang = array_merge($lang, array(
	'CDB_CONTRIB_PERMALINK_EXPLAIN' => 'Version épurée du nom de la contribution, utilisée lors de la création contribution.
										<br />Si vous ne le connaissez pas entrez au moins les trois premières lettres de la contribution et laissez-vous guider par la saisie assistée.',
	'CDB_CONTRIB_TYPE_EXPLAIN' => 'Si vous ne connaissez pas le type de la contribution, sélectionnez l’option <em>Inconnue</em> afin d’élargir la zone de recherche.',//@link CDB_UNKNOWN_CATEGORY
	'CDB_NO_AJAX_CHECK' => 'Vérifier',
	'CDB_NO_AJAX_RESULT' => 'Aucun résultat, essayez de modifier le type de contribution.',
	'CDB_MOVE_TOPIC' => 'Déplacer le sujet (Base de données)',
	'CDB_MOVE_TOPICS' => 'Déplacer les sujets (Base de données)',
	'CDB_MOVE_WARNING' => 'Attention, une fois déplacé dans la base de données, le sujet ne pourra pas être restauré dans le forum, êtes-vous sûr de vouloir continuer?',
	'CDB_SELECT_DESTINATION_CAT' => 'Choisissez la catégorie de destination',
	'CDB_UNKNOWN_CATEGORY' => 'Inconnue',
	'CDB_KNOWN_CATEGORY_LABEL' => 'Si vous connaissez la catégorie',
	'CDB_UNKNOWN_CATEGORY_LABEL' => 'Si vous ne connaissez pas la catégorie',
	'CDB_TOPIC_MOVED' => 'Le sujet a été déplacé.',
	'CDB_GO_TOPIC' => '%sAller au sujet déplacé%s',
	'CDB_GO_SUPPORT' => '%sAller au support de la contribution%s',
	'CDB_TOPIC_MOVED_PM' => 'Bonjour,<br /><br /> Un de vos sujets nommé <strong><a href="%2$s">%1$s</a></strong> dont vous êtes l’auteur, a été déplacé dans le support de la contribution <strong><a href="%4$s">%5$s</a></strong> par un modérateur ou un administrateur.<br /><br /><strong>La raison fournie pour le déplacement est la suivante:</strong> <em>%3$s</em><br /><br />Cliquez sur le lien ci-contre pour voir votre sujet:<br /><a href="%2$s">%1$s</a>',
));