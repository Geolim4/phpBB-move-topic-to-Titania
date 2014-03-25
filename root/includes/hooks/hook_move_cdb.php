<?php
/**
* phpBB move topic to titania MCP Hook
*
* author: phpbb-fr website team
* begin: 24/03/2014
* version: 0.0.1 - 24/03/2014
* licence: http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

function hook_move_cdb_lang()
{
	global $auth, $user, $phpbb_root_path, $phpEx;

	if($auth->acl_getf_global('m_move'))
	{
		//Don't add mcp.php into this, the language file is auto-loaded
		$pages = array("viewtopic.$phpEx", "any_page_you_want.$phpEx");

		if (in_array($user->page['page_name'], $pages))
		{
			$user->add_lang('mods/info_mcp_move_cdb');

			define('CDB_LOAD_OPTIONS_ONLY', true);
			include($phpbb_root_path . 'includes/mcp/mcp_move_cdb.' . $phpEx);

			if(!CDB_MOVE_DELETE)
			{
				$user->lang['CDB_MOVE_TOPIC'] = $user->lang['CDB_COPY_TOPIC'];
				$user->lang['CDB_MOVE_TOPICS'] = $user->lang['CDB_COPY_TOPICS'];
			}
		}
	}
}
$phpbb_hook->register('phpbb_user_session_handler', 'hook_move_cdb_lang');