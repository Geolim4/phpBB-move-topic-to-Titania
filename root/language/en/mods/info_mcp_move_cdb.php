<?php
/**
* phpBB move topic to titania MCP language [english]
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
	'CDB_CONTRIB_PERMALINK_EXPLAIN' => 'Cleaned version of the contribution name, used to build the url for the contribution.
										<br />If you do not know the permalink, type at least the first three letters of the contribution and let yourself be guided by semi-assisted completion.',
	'CDB_CONTRIB_TYPE_EXPLAIN' => 'If you do not know the type of contribution, select the <em>Unknown</em> option to enlarge the search area.',//@link CDB_UNKNOWN_CATEGORY
	'CDB_NO_AJAX_CHECK' => 'Check',
	'CDB_NO_AJAX_RESULT' => 'No result, try modifying contribution type.',
	'CDB_MOVE_TOPIC' => 'Move topic (Customisation database)',
	'CDB_MOVE_TOPICS' => 'Move topics (Customisation database)',//Not used for now, may be used later
	'CDB_COPY_TOPIC' => 'Copy topic (Customisation database)',
	'CDB_COPY_TOPICS' => 'Copy topics (Customisation database)',//Not used for now, may be used later
	'CDB_MOVE_WARNING' => 'Warning, once moved in the database, the topic can not be moved back in the forum, are you sure you want to continue?',
	'CDB_COPY_WARNING' => 'You are going to copy the topic into the customisation database, are you sure want to continue?',
	'CDB_SELECT_DESTINATION_CAT' => 'Select the target category',
	'CDB_UNKNOWN_CATEGORY' => 'Unknown',
	'CDB_KNOWN_CATEGORY_LABEL' => 'If you known the contribution type',
	'CDB_UNKNOWN_CATEGORY_LABEL' => 'If you don’t known the contribution type',
	'CDB_TOPIC_MOVED' => 'The topic has been moved.',
	'CDB_TOPIC_COPIED' => 'The topic has been copied.',
	'CDB_GO_TOPIC' => '%sGo to new topic%s',
	'CDB_GO_SUPPORT' => '%sGo to the contribution support%s',
	'CDB_TOPIC_MOVED_PM' => 'Hello,<br /><br /> A topic called  <strong><a href="%2$s">%1$s</a></strong> which has been started by you, has been moved to the contribution support <strong><a href="%4$s">%5$s</a></strong> by a moderator or administrator.<br /><br /><strong>Reason for moving your topic:</strong> <em>%3$s</em><br /><br />Click on the following link to go to your topic:<br /><a href="%2$s">%1$s</a>',
));