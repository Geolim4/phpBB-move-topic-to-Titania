<?php
/**
* phpBB move topic to titania MCP Hook
*
* author: phpbb-fr website team
* begin: 27/03/2014
* version: 0.0.1 - 27/03/2014
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
	global $auth, $user, $phpEx;

	if ($auth->acl_getf_global('m_move') && ($user->page['page_name'] == "viewtopic.$phpEx" || $user->page['page_name'] == "mcp.$phpEx"))
	{
		$user->add_lang('mods/info_mcp_move_cdb');
	}
}

function hook_move_cdb_mcp()
{
	global $auth, $user, $phpbb_root_path, $phpEx;

	// In session.php, phpbb_user_session_handler() is called too early, and we didn't finished some important steps.
	hook_move_cdb_user_finish_setup();

	$topic_id = array(request_var('t', 0));
	$action = request_var('action', '');
	$quickmod = request_var('quickmod', false);

	if($quickmod && $action == 'move_cdb' && $user->page['page_name'] == "mcp.$phpEx" && !empty($topic_id))
	{
		include($phpbb_root_path . 'includes/mcp/mcp_move_cdb.' . $phpEx);

		$ajax_cdb = request_var('ajax_cdb', false);
		$submit_cdb = (!empty($_POST['submit_cdb']) ? true : false);

		if($ajax_cdb)
		{
			find_cdb(utf8_normalize_nfc(request_var('keyword_cdb', '', true)));
		}
		else
		{
			if (check_ids($topic_id, TOPICS_TABLE, 'topic_id', array('m_move')))
			{
				load_cdb($topic_id);
			}
			else
			{
				trigger_error('NO_TOPIC_SELECTED');
			}
		}
		if (class_exists('titania'))
		{
			titania::page_header('CUSTOMISATION_DATABASE');
			titania::page_footer(true, 'index_body.html');
		}

		// Don't let mcp.php working anymore.
		garbage_collection();
		exit_handler();
	}
}

function cdb_alter_quickmod()
{
	global $auth, $user, $phpbb_root_path, $phpEx, $template, $forum_id;

	// Yep, another private method hack -_-'
	$s_topic_mod = &$template->_rootref['S_TOPIC_MOD'];

	if ($user->page['page_name'] == "viewtopic.$phpEx" && $forum_id && $auth->acl_get('m_move', $forum_id) && strpos($s_topic_mod, 'value="move"') !== false)
	{
		// Check for copy/move status
		define('CDB_LOAD_OPTIONS_ONLY', true);
		include($phpbb_root_path . 'includes/mcp/mcp_move_cdb.' . $phpEx);

		// Redefine the right term if needed.
		if(!CDB_MOVE_DELETE)
		{
			$user->lang['CDB_MOVE_TOPIC'] = $user->lang['CDB_COPY_TOPIC'];
			$user->lang['CDB_MOVE_TOPICS'] = $user->lang['CDB_COPY_TOPICS'];
		}

		// Search for Bryan.
		$move_option = '<option value="move">' . $user->lang['MOVE_TOPIC'] . '</option>';
		$move_option_cdb = '<option value="move_cdb">' . $user->lang['CDB_MOVE_TOPIC'] . '</option>';

		// Hack the quickmod
		$s_topic_mod = str_replace($move_option, $move_option . $move_option_cdb, $s_topic_mod);
	}
}


function hook_move_cdb_user_finish_setup()
{
	global $user, $db, $config, $auth, $phpbb_root_path, $phpEx;

	// If this function got called from the error handler we are finished here.
	if (defined('IN_ERROR_HANDLER'))
	{
		return;
	}

	// Disable board if the install/ directory is still present
	// For the brave development army we do not care about this, else we need to comment out this everytime we develop locally
	if (!defined('DEBUG_EXTRA') && !defined('ADMIN_START') && !defined('IN_INSTALL') && !defined('IN_LOGIN') && file_exists($phpbb_root_path . 'install') && !is_file($phpbb_root_path . 'install'))
	{
		// Adjust the message slightly according to the permissions
		if ($auth->acl_gets('a_', 'm_') || $auth->acl_getf_global('m_'))
		{
			$message = 'REMOVE_INSTALL';
		}
		else
		{
			$message = (!empty($config['board_disable_msg'])) ? $config['board_disable_msg'] : 'BOARD_DISABLE';
		}
		trigger_error($message);
	}

	// Is board disabled and user not an admin or moderator?
	if ($config['board_disable'] && !defined('IN_LOGIN') && !$auth->acl_gets('a_', 'm_') && !$auth->acl_getf_global('m_'))
	{
		if ($user->data['is_bot'])
		{
			send_status_line(503, 'Service Unavailable');
		}

		$message = (!empty($config['board_disable_msg'])) ? $config['board_disable_msg'] : 'BOARD_DISABLE';
		trigger_error($message);
	}

	// Is load exceeded?
	if ($config['limit_load'] && $user->load !== false)
	{
		if ($user->load > floatval($config['limit_load']) && !defined('IN_LOGIN') && !defined('IN_ADMIN'))
		{
			// Set board disabled to true to let the admins/mods get the proper notification
			$config['board_disable'] = '1';

			if (!$auth->acl_gets('a_', 'm_') && !$auth->acl_getf_global('m_'))
			{
				if ($user->data['is_bot'])
				{
					send_status_line(503, 'Service Unavailable');
				}
				trigger_error('BOARD_UNAVAILABLE');
			}
		}
	}

	if (isset($user->data['session_viewonline']))
	{
		// Make sure the user is able to hide his session
		if (!$user->data['session_viewonline'])
		{
			// Reset online status if not allowed to hide the session...
			if (!$auth->acl_get('u_hideonline'))
			{
				$sql = 'UPDATE ' . SESSIONS_TABLE . '
					SET session_viewonline = 1
					WHERE session_user_id = ' . $user->data['user_id'];
				$db->sql_query($sql);
				$user->data['session_viewonline'] = 1;
			}
		}
		else if (!$user->data['user_allow_viewonline'])
		{
			// the user wants to hide and is allowed to  -> cloaking device on.
			if ($auth->acl_get('u_hideonline'))
			{
				$sql = 'UPDATE ' . SESSIONS_TABLE . '
					SET session_viewonline = 0
					WHERE session_user_id = ' . $user->data['user_id'];
				$db->sql_query($sql);
				$user->data['session_viewonline'] = 0;
			}
		}
	}


	// Does the user need to change their password? If so, redirect to the
	// ucp profile reg_details page ... of course do not redirect if we're already in the ucp
	if (!defined('IN_ADMIN') && !defined('ADMIN_START') && $config['chg_passforce'] && !empty($user->data['is_registered']) && $auth->acl_get('u_chgpasswd') && $user->data['user_passchg'] < time() - ($config['chg_passforce'] * 86400))
	{
		if (strpos($user->page['query_string'], 'mode=reg_details') === false && $user->page['page_name'] != "ucp.$phpEx")
		{
			redirect(append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=profile&amp;mode=reg_details'));
		}
	}
}
// Firstly, hook the language file
$phpbb_hook->register('phpbb_user_session_handler', 'hook_move_cdb_lang', 'first');

// Secondly, load the mcp hack
$phpbb_hook->register('phpbb_user_session_handler', 'hook_move_cdb_mcp');

// Finally, try to hack the quickmod
$phpbb_hook->register(array('template', 'display'), 'cdb_alter_quickmod', 'last');