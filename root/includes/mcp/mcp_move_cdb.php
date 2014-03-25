<?php
/**
* phpBB move topic to titania MCP Core
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
// Addon options
if (!defined('CDB_AJAX_MIN_CHARS'))
{
	define('CDB_AJAX_MIN_CHARS', 3);//Minimum chars required to trigger assisted typing
}
if (!defined('CDB_MOVE_PM_ALERT'))
{
	define('CDB_MOVE_PM_ALERT', true);//Send a PM to the topic's owner  ?? (Will only work if CDB_MOVE_DELETE is also true)
}
if (!defined('CDB_MOVE_DELETE'))
{
	define('CDB_MOVE_DELETE', true);//Delete the phpBB source topic? If false the MOD become: Copy topic to titania MCP :)
}

if (defined('CDB_LOAD_OPTIONS_ONLY'))
{
	return;//Small hack to allow you to load this file only for getting options :)
}

// Let's rock for constants' spamming !!
if (!defined('IN_TITANIA'))
{
	define('IN_TITANIA', true);
}
if (!defined('PHPBB_INCLUDED'))
{
	define('PHPBB_INCLUDED', true);
} 
if (!defined('TITANIA_ROOT'))
{
	define('TITANIA_ROOT', './../customise/db/');
} 
if (!defined('PHP_EXT'))
{
	define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
}

// Include the beauty
require(TITANIA_ROOT . 'common.' . PHP_EXT);

//We're into Titania, so we must redefine error level.
$level = E_ALL & ~E_DEPRECATED;
if (version_compare(PHP_VERSION, '5.4.0-dev', '>='))
{
	if (!defined('E_STRICT'))
	{
		define('E_STRICT', 2048);
	}
	$level &= ~E_STRICT;
}
error_reporting($level);
set_error_handler('titania_msg_handler');

/****
* load_cdb()
* Main function to display the confirm-box like
* @param array $topic_ids to move
* @return void
****/
function load_cdb($topic_ids)
{
	global $phpbb_root_path, $phpEx;

	$start = request_var('start', 0);
	$forum_id = request_var('f', 0);
	$move_pm = request_var('move_pm', 0);// Checkbox
	$contrib = request_var('contrib_permalink', '');// contrib permalink/user-keyword like

	if (titania::confirm_box(true) && $contrib)
	{
		//check if the requested contribuyion exists
		$sql = 'SELECT ctb.*, ctg.*
			FROM ' . TITANIA_CONTRIBS_TABLE . ' ctb
			LEFT JOIN ' . TITANIA_CATEGORIES_TABLE . " ctg
				ON(ctg.category_id = ctb.contrib_type)
			WHERE contrib_name_clean = '" . phpbb::$db->sql_escape($contrib) . "'";
		$result = phpbb::$db->sql_query($sql);
		$ctb_row = phpbb::$db->sql_fetchrow($result);
		phpbb::$db->sql_freeresult($result);

		if ($ctb_row)
		{
			//Run the machine gun, there's no survivor expected 8)
			$topic_cdb = copy_cdb($topic_ids, $contrib, $ctb_row);

			if ($move_pm && CDB_MOVE_PM_ALERT && CDB_MOVE_DELETE)
			{
				if (!function_exists('submit_pm'))
				{
					include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
				}

				$pm_data = array(
					'from_user_id'		=> phpbb::$user->data['user_id'],
					'from_user_ip'		=> phpbb::$user->data['user_ip'],
					'from_username'		=> phpbb::$user->data['username'],
					'enable_sig'		=> true,
					'enable_bbcode'		=> true,
					'enable_smilies'	=> true,
					'enable_urls'		=> true,
					'icon_id'			=> 0,
					'bbcode_bitfield'	=> '',
					'bbcode_uid'		=> '',
					'message'			=> phpbb::$user->lang('CDB_TOPIC_MOVED_PM', $topic_cdb['contrib_topic_title'], $topic_cdb['contrib_topic_url'], utf8_normalize_nfc(request_var('move_reason', '', true)), $topic_cdb['contrib_support_url'], $topic_cdb['contrib_name']),
					'address_list'		=> array('u' => array($topic_cdb['contrib_topic_user_id'] => 'to')),
				);
				submit_pm('post', phpbb::$user->lang['EXTENTED_TOPIC_MOVED_SUBJECT'], $pm_data, true);
			}
			if (!function_exists('delete_topics'))
			{
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}
			if(CDB_MOVE_DELETE)
			{
				delete_topics('topic_id', $topic_ids);
				$message =  phpbb::$user->lang['CDB_TOPIC_MOVED'];
			}
			else
			{
				$message =  phpbb::$user->lang['CDB_TOPIC_COPIED'];
			}

			$message .= '<br /><br />' . phpbb::$user->lang('CDB_GO_TOPIC', '<a href="' . $topic_cdb['contrib_topic_url'] . '">', '</a>');
			$message .= '<br /><br />' . phpbb::$user->lang('CDB_GO_SUPPORT', '<a href="' . $topic_cdb['contrib_support_url'] . '">', '</a>');

			if ($forum_id)
			{
				$message .= '<br /><br />' . phpbb::$user->lang('RETURN_FORUM', '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id) . '">', '</a>');
			}

			meta_refresh(5, $topic_cdb['contrib_topic_url']);
			trigger_error($message, E_USER_NOTICE);
		}
		else
		{
			trigger_error('NO_CONTRIB', E_USER_WARNING);
		}
	}
	else
	{
		titania::add_lang('contributions');
		titania::_include('functions_posting', false);
		generate_type_select();

		if (sizeof($topic_ids) === 1)
		{
			$topic_id = current($topic_ids);
			$s_mod_action = append_sid(PHPBB_ROOT_PATH . 'mcp.' . PHP_EXT, "f=$forum_id&amp;t=$topic_id" . "&amp;quickmod=1&amp;redirect=" . urlencode(str_replace('&amp;', '&', "{$phpbb_root_path}viewtopic.$phpEx?f=$forum_id&amp;t=$topic_id" . (($start == 0) ? '' : "&amp;start=$start"))), true, phpbb::$user->session_id);
		}
		else
		{
			$s_mod_action = append_sid(PHPBB_ROOT_PATH . 'mcp.' . PHP_EXT, "f=$forum_id" . "&amp;quickmod=1&amp;redirect=" . urlencode(str_replace('&amp;', '&', "{$phpbb_root_path}viewtopic.$phpEx?f=$forum_id&amp;t=$topic_id" . (($start == 0) ? '' : "&amp;start=$start"))), true, phpbb::$user->session_id);
		}
		$redirect_url = request_var('redirect', build_url(array('action', 'quickmod')));

		phpbb::$template->assign_vars(array(
			'S_CDB_MOVE_DELETE'			=> CDB_MOVE_DELETE,
			'S_AJAX_MIN_CHARS'			=> CDB_AJAX_MIN_CHARS,
			'S_MOVE_PM_ALERT'			=> CDB_MOVE_PM_ALERT,
			'S_CUSTOM_CONFIRM_ACTION'	=> $s_mod_action,
			'CONTRIB_TYPE_ID'			=> 1, // No constant, 1 == MOD by default (selector)
			'U_AJAX_AUTOCOMPLETE'		=> append_sid(PHPBB_ROOT_PATH . 'mcp.' . PHP_EXT, array('quickmod' => true, 'action' => 'move_cdb'), false, phpbb::$user->session_id),
			'S_TOPIC_ID_LIST'			=> json_encode($topic_ids),
		));

		$s_hidden_fields = array(
			'page'				=> 'support',
			'action'			=> 'move_cdb',
			'topic_id_list'		=> $topic_ids,
			'f'					=> request_var('f', 0),
			'redirect'			=> $redirect_url,
			'quickmod'			=> true,
		);

		titania::confirm_box(false, phpbb::$user->lang['CDB_MOVE_TOPIC' . ((sizeof($topic_ids) == 1) ? '' : 'S')], $s_mod_action, $s_hidden_fields, 'mods/mcp_move_cdb.html');
		redirect($redirect_url);
	}
}

/****
* copy_cdb()
* Convert phpBB topic into titania (support) topic
* @param array $topic_ids topic IDs to copy
* @param string $contrib Contrib permalink (contrib_name_clean)
* @param array $ctb_row Full contrib data
* @return array:
	array(
		'contrib_topic_url'		=> '',//The new Titania topic URL (the last if there's more than one topic in $topic_ids)
		'contrib_topic_title'	=> '',//The new Titania topic title (the last if there's more than one topic in $topic_ids)
		'contrib_topic_id'		=> 0,//The new Titania topic id (the last if there's more than one topic in $topic_ids)
		'contrib_topic_user_id'	=> 0,//The new Titania topic owner (the last if there's more than one topic in $topic_ids)
		'contrib_id'			=> 0,//The contrib ID
		'contrib_name'			=> '',//The contrib name
		'contrib_support_url'	=> '',//The contrib support URL
	)
****/
function copy_cdb($topic_ids, $contrib, $ctb_row)
{
	$topicsrow = $postsrow = $attachrow = $trackrow = $watchrow = array();

	if (!empty($topic_ids) && !is_array($topic_ids))
	{
		$topic_ids = array((int) $topic_ids);
	}
	// HAXXX (useless so essential)
	$_REQUEST['action'] = $_POST['action'] = $_GET['action'] = 'post';
	$_REQUEST['type'] = $_POST['type'] = $_GET['type'] = $ctb_row['category_name_clean'];
	$_REQUEST['c'] = $_POST['c'] = $_GET['c'] = $contrib;

	// Grab some data from the Contrib
	titania::$contrib = new titania_contribution();
	if (!titania::$contrib->load($contrib))
	{
		trigger_error('CONTRIB_NOT_FOUND');
	}

	// Grab attachements
	$sql = 'SELECT * 
		FROM ' . ATTACHMENTS_TABLE . '
		WHERE ' . phpbb::$db->sql_in_set('topic_id', $topic_ids) . '
		ORDER BY attach_id ASC';
	$result = phpbb::$db->sql_query($sql);
	while ($row = phpbb::$db->sql_fetchrow($result))
	{
		$attachrow[(int) $row['topic_id']][(int) $row['post_msg_id']][(int) $row['attach_id']] = (array) $row;
	}
	phpbb::$db->sql_freeresult($result);

	// Grab tracking
	$sql = 'SELECT * 
		FROM ' . TOPICS_TRACK_TABLE . '
		WHERE ' . phpbb::$db->sql_in_set('topic_id', $topic_ids) . '
		ORDER BY topic_id ASC';
	$result = phpbb::$db->sql_query($sql);
	while ($row = phpbb::$db->sql_fetchrow($result))
	{
		$trackrow[(int) $row['topic_id']][] = $row;
	}
	phpbb::$db->sql_freeresult($result);

	// Grab watching
	$sql = 'SELECT * 
		FROM ' . TOPICS_WATCH_TABLE . '
		WHERE ' . phpbb::$db->sql_in_set('topic_id', $topic_ids) . '
		ORDER BY topic_id ASC';
	$result = phpbb::$db->sql_query($sql);
	while ($row = phpbb::$db->sql_fetchrow($result))
	{
		$watchrow[(int) $row['topic_id']][] = $row;
	}
	phpbb::$db->sql_freeresult($result);

	// Grab posts
	$sql = 'SELECT * 
		FROM ' . POSTS_TABLE . '
		WHERE ' . phpbb::$db->sql_in_set('topic_id', $topic_ids) . '
		ORDER BY post_id ASC';
	$result = phpbb::$db->sql_query($sql);
	while ($row = phpbb::$db->sql_fetchrow($result))
	{
		$postsrow[$row['topic_id']][$row['post_id']] = $row;
		if (!isset($topicposts[$row['topic_id']]['public']))
		{
			$topicposts[$row['topic_id']]['public'] = 0;
		}
		if (!isset($topicposts[$row['topic_id']]['unapproved']))
		{
			$topicposts[$row['topic_id']]['unapproved'] = 0;
		}
		if ($row['post_approved'])
		{
			$topicposts[$row['topic_id']]['public']++;
		}
		else
		{
			$topicposts[$row['topic_id']]['unapproved']++;
		}
	}
	phpbb::$db->sql_freeresult($result);

	// Grab topics
	$sql = 'SELECT * 
		FROM ' . TOPICS_TABLE . '
		WHERE ' . phpbb::$db->sql_in_set('topic_id', $topic_ids) . '
		ORDER BY topic_id ASC';
	$result = phpbb::$db->sql_query($sql);
	while ($row = phpbb::$db->sql_fetchrow($result))
	{
		$topicsrow[$row['topic_id']] = $row;
	}
	phpbb::$db->sql_freeresult($result);

	//Let's rock
	phpbb::$db->sql_transaction('begin');

	foreach ($topicsrow AS $topic_id => $topicsrow_)
	{
		$topic_posts = array(
			'teams'			=> 0,
			'authors'		=> 0,
			'public'		=> $topicposts[$topic_id]['public'],
			'deleted'		=> 0,
			'unapproved'	=> $topicposts[$topic_id]['unapproved'],
		);

		$sql = 'INSERT INTO ' . TITANIA_TOPICS_TABLE . ' ' . phpbb::$db->sql_build_array('INSERT', array(
			//'topid_id'			=> $topic_id,// Auto-incremented
			'parent_id'				=> $ctb_row['contrib_id'],
			'topic_url'				=> titania_url::unbuild_url(titania::$contrib->get_url('support')),
			'topic_type'			=> TITANIA_SUPPORT,//
			'topic_access'			=> TITANIA_ACCESS_PUBLIC,
			'topic_category'		=> 0,// See includes/object/topic.php
			'topic_status'			=> 0,// See includes/object/topic.php
			'topic_assigned'		=> '',// See includes/object/topic.php
			'topic_time'			=> $topicsrow_['topic_time'],
			'topic_sticky'			=> (($topicsrow_['topic_type'] == POST_STICKY) ? true : false),
			'topic_locked'			=> (($topicsrow_['topic_status'] == ITEM_LOCKED) ? true : false),
			'topic_approved'		=> $topicsrow_['topic_approved'],
			'topic_reported'		=> false,// If a moderator move it, it should be non-reported. Also for now we cannot "copy" a report from phpBB to Titania.
			'topic_views'			=> $topicsrow_['topic_views'],
			'topic_posts'			=> titania_count::to_db($topic_posts),
			'topic_subject'			=> $topicsrow_['topic_title'],
			'topic_subject_clean'	=> titania_url::url_slug($topicsrow_['topic_title']),

			// Firsters/Lasters
			'topic_first_post_id'			=> 0,// We update it once we insert posts into DB
			'topic_first_post_user_id'		=> 0,// We update it once we insert posts into DB
			'topic_first_post_user_colour'	=> 0,// We update it once we insert posts into DB
			'topic_first_post_username'		=> '',// We update it once we insert posts into DB
			'topic_first_post_time'			=> 0,// We update it once we insert posts into DB
			'topic_last_post_id'			=> 0,// We update it once we insert posts into DB
			'topic_last_post_user_id'		=> 0,// We update it once we insert posts into DB
			'topic_last_post_user_colour'	=> 0,// We update it once we insert posts into DB
			'topic_last_post_username'		=> '',// We update it once we insert posts into DB
			'topic_last_post_time'			=> 0,// We update it once we insert posts into DB
			'topic_last_post_subject'		=> '',// We update it once we insert posts into DB
		));
		phpbb::$db->sql_query($sql);
		$new_topic_id = (int) phpbb::$db->sql_nextid();

		$posting = new titania_posting();
		$topic = $posting->load_topic($new_topic_id);
		$sql_post_ary = $sql_attach_ary = $sql_track_ary = $sql_watch_ary = array();

		// Re-insert tracking
		if (!empty($trackrow[$topic_id]))
		{
			foreach ($trackrow[$topic_id] AS $trackrow_)
			{
				$sql_track_ary[] = array(
					'track_type' => TITANIA_TOPIC,
					'track_id' => $new_topic_id,
					'track_user_id' => $trackrow_['user_id'],
					'track_time' => $trackrow_['mark_time'],
				);
			}
			phpbb::$db->sql_multi_insert(TITANIA_TRACK_TABLE, $sql_track_ary);
			unset($sql_track_ary);
		}

		if (!empty($watchrow[$topic_id]))
		{
			// Re-insert watching
			foreach ($watchrow[$topic_id] AS $watchrow_)
			{
				$sql_watch_ary[] = array(
					'watch_type' => 1,//SUBSCRIPTION_EMAIL, constant redeclared in customise/db/includes/tools/subscriptions.php on line 16 :/
					'watch_object_type' => TITANIA_TOPIC,
					'watch_object_id' => $new_topic_id,
					'watch_user_id' => $watchrow_['user_id'],
					'watch_mark_time' => time(),
				);
			}
			phpbb::$db->sql_multi_insert(TITANIA_WATCH_TABLE, $sql_watch_ary);
			unset($sql_watch_ary);
		}

		// Re-insert posts
		foreach ($postsrow[$topic_id] AS $post_id => $postsrow_)
		{
			$sql = 'INSERT INTO ' . TITANIA_POSTS_TABLE . ' ' . phpbb::$db->sql_build_array('INSERT', array(
				//'post_id'			=> $post_id,// Auto-incremented
				'topic_id'			=> $new_topic_id,
				'post_url'			=> titania_url::unbuild_url($topic->get_url()),
				'post_type'			=> TITANIA_SUPPORT,
				'post_access'		=> TITANIA_ACCESS_PUBLIC,
				'post_locked'		=> (($postsrow_['post_edit_locked'] == ITEM_LOCKED) ? true : false),
				'post_approved'		=> $postsrow_['post_approved'],
				'post_reported'		=> $postsrow_['post_reported'],
				'post_attachment'	=> $postsrow_['post_attachment'],
				'post_user_id'		=> $postsrow_['poster_id'],
				'post_ip'			=> $postsrow_['poster_ip'],
				'post_time'			=> $postsrow_['post_time'],
				'post_edited'		=> false,
				'post_deleted'		=> false,
				'post_delete_user'	=> 0,
				'post_edit_user'	=> 0,
				'post_edit_reason'	=> '',
				'post_subject'		=> $postsrow_['post_subject'],
				'post_text'			=> $postsrow_['post_text'],
				'post_text_bitfield'=> $postsrow_['bbcode_bitfield'],
				'post_text_uid'		=> $postsrow_['bbcode_uid'],
				'post_text_options'	=> (($postsrow_['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($postsrow_['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($postsrow_['enable_magic_url']) ? OPTION_FLAG_LINKS : 0),
				'post_edit_time'	=> 0,
			));
			phpbb::$db->sql_query($sql);
			$new_post_id = (int) phpbb::$db->sql_nextid();

			//Update user postcount
			if ($postsrow_['post_approved'] && $postsrow_['post_postcount'])
			{
				phpbb::update_user_postcount($postsrow_['poster_id']);
			}

			if (!isset($first_post_id))
			{
				$first_post_id = $new_post_id;
			}
			$last_post_id = $new_post_id;
			$attachment_order = array();

			if ($postsrow_['post_attachment'])
			{
				// Re-insert attachements
				foreach ($attachrow[$topic_id][$post_id] AS $attachement_id => $attachrow_)
				{
					$attachment_order[$new_post_id] = (isset($attachment_order[$new_post_id]) ? ++$attachment_order[$new_post_id] : 0);
					$sql_attach_ary[] = array(
						//'attachement_id'			=> $attachement_id,// Auto-incremented
						'object_type'			=> TITANIA_SUPPORT,
						'object_id'				=> $new_post_id,
						'attachment_access'		=> TITANIA_ACCESS_PUBLIC,
						'attachment_comment'	=> $attachrow_['attach_comment'],
						'attachment_directory'	=> 'support',
						'physical_filename'		=> $attachrow_['physical_filename'],
						'real_filename'			=> $attachrow_['real_filename'],
						'download_count'		=> $attachrow_['download_count'],
						'filesize'				=> $attachrow_['filesize'],
						'filetime'				=> $attachrow_['filetime'],
						'extension'				=> $attachrow_['extension'],
						'mimetype'				=> $attachrow_['mimetype'],
						'hash'					=> md5_file(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/{$attachrow_['physical_filename']}"),
						'thumbnail'				=> $attachrow_['thumbnail'],
						'is_orphan'				=> $attachrow_['is_orphan'],
						'attachment_user_id'	=> $attachrow_['poster_id'],
						'is_preview'			=> (strpos($attachrow_['mimetype'], 'image') ? true : false),
						'attachment_order'		=> $attachment_order[$new_post_id],
					);

					// Move the attachment
					if(file_exists(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/{$attachrow_['physical_filename']}"))
					{
						if (copy(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/{$attachrow_['physical_filename']}", TITANIA_ROOT . phpbb::$config['upload_path'] . "/support/{$attachrow_['physical_filename']}"))
						{
							@unlink(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/{$attachrow_['physical_filename']}");
						}
					}

					// Move the thumbnail (if exists)
					if ($attachrow_['thumbnail'] && file_exists(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/thumb_{$attachrow_['physical_filename']}"))
					{
						if (copy(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/thumb_{$attachrow_['physical_filename']}", TITANIA_ROOT . phpbb::$config['upload_path'] . "/support/thumb_{$attachrow_['physical_filename']}"))
						{
							@unlink(PHPBB_ROOT_PATH . phpbb::$config['upload_path'] . "/thumb_{$attachrow_['physical_filename']}");
						}
					}
				}
				phpbb::$db->sql_multi_insert(TITANIA_ATTACHMENTS_TABLE, $sql_attach_ary);
				unset($sql_attach_ary);
			}
		}

		$sql = 'SELECT p.*, u.user_id, u.user_colour, u.username
			FROM ' . TITANIA_POSTS_TABLE . ' p
			LEFT JOIN ' . USERS_TABLE . ' u
				ON(u.user_id = p.post_user_id)
			WHERE ' . phpbb::$db->sql_in_set('p.post_id', array($first_post_id, $last_post_id)) . '
			ORDER BY p.post_id';
		$presult = phpbb::$db->sql_query($sql);
		$set_ary = array();

		while ($row = phpbb::$db->sql_fetchrow($presult))
		{
			if (!isset($first_post_update))
			{
				$set_ary += array(
					'topic_first_post_id'			=> $row['post_id'],
					'topic_first_post_user_id'		=> $row['user_id'],
					'topic_first_post_user_colour'	=> $row['user_colour'],
					'topic_first_post_username'		=> $row['username'],
					'topic_first_post_time'			=> $row['post_time'],
				);
				$first_post_update = true;
			}
			else
			{
				$set_ary += array(
					'topic_last_post_id'			=> $row['post_id'],
					'topic_last_post_user_id'		=> $row['user_id'],
					'topic_last_post_user_colour'	=> $row['user_colour'],
					'topic_last_post_username'		=> $row['username'],
					'topic_last_post_time'			=> $row['post_time'],
					'topic_last_post_subject'		=> $row['post_subject'],
				);
			}
		}
		phpbb::$db->sql_freeresult($presult);
		
		// Update Firsters/Lasters
		$sql = 'UPDATE ' . TITANIA_TOPICS_TABLE . '
			SET ' . phpbb::$db->sql_build_array('UPDATE', $set_ary) . " 
			WHERE topic_id = $new_topic_id";
		phpbb::$db->sql_query($sql);

		$result = array(
			'contrib_topic_url'		=> $topic->get_url(),
			'contrib_topic_title'	=> $topicsrow_['topic_title'],
			'contrib_topic_id'		=> $topic_id,
			'contrib_topic_user_id'	=> $set_ary['topic_first_post_user_id'],
			'contrib_id'			=> $ctb_row['contrib_id'],
			'contrib_name'			=> $ctb_row['contrib_name'],
			'contrib_support_url'	=> titania::$contrib->get_url('support'),
		);

		// Take care of sub.notifications!
		$email_vars = array(
			'NAME'			=> htmlspecialchars_decode($topicsrow_['topic_title']),
			'U_VIEW'		=> $result['contrib_topic_url'],
			'CONTRIB_NAME'	=> titania::$contrib->contrib_name,
		);
		titania_subscriptions::send_notifications(TITANIA_SUPPORT, $ctb_row['contrib_id'], 'subscribe_notify_forum_contrib.txt', $email_vars, false);

		// End of topic loop, reset it
		unset($first_post_id, $first_post_update);
	}
	phpbb::$db->sql_transaction('commit');

	return $result;
}

/****
* find_cdb()
* Search a contribution and display result JSON formated (Ajax)
* @param string $keywords Keywords we're looking for
* @return void
* @terminate the script
****/
function find_cdb($keywords)
{
	$contrib_type = request_var('contrib_type', 0);
	$contrib_mode = request_var('mode_cdb', '');

	if (empty($contrib_mode))
	{
		$ctb_row = array();
		$search_terms = '*' . strtolower($keywords) . '*';

		if ($search_terms != '**')
		{
			$search_terms = str_replace(array('*', '?'), array(phpbb::$db->any_char, phpbb::$db->one_char), $search_terms);
		}

		$sql_where = '(';
		if ($contrib_type >= 1)
		{
			$sql_where .= 'ctb.contrib_type = ' . (int) $contrib_type . ' AND ';
		}
		$sql_where .= 'LOWER(ctb.contrib_name) ' . phpbb::$db->sql_like_expression($search_terms) . ' OR LOWER(ctb.contrib_name_clean) ' . phpbb::$db->sql_like_expression($search_terms) . ')';

		if (strpos(phpbb::$db->sql_like_expression($search_terms), ' ') !== false)
		{
			$sql_where = '(';
			if ($contrib_type >= 1)
			{
				$sql_where .= 'ctb.contrib_type = ' . (int) $contrib_type . ' AND (';
			}
			$i = 1;
			foreach (explode(' ', $keywords) AS $terms)
			{
				if (utf8_strlen($terms) < CDB_AJAX_MIN_CHARS)
				{
					continue;
				}
				if ($i > 5)
				{
					break;
				}
				$terms = '*' . strtolower($terms) . '*';

				if ($terms != '**')
				{
					$terms = str_replace(array('*', '?'), array(phpbb::$db->any_char, phpbb::$db->one_char), $terms);
				}
				$sql_where .= (($i > 1) ? ' OR ': '') . 'LOWER(ctb.contrib_name) ' . phpbb::$db->sql_like_expression($terms) . ' OR LOWER(ctb.contrib_name_clean) ' . phpbb::$db->sql_like_expression($terms);

				$i++;
			}
			$sql_where .= ')';
			if ($contrib_type >= 1)
			{
				$sql_where .= ')';
			}
		}

		$sql = 'SELECT ctb.contrib_name, ctb.contrib_name_clean, ctg.category_name
			FROM ' . TITANIA_CONTRIBS_TABLE . ' ctb
			LEFT JOIN ' . TITANIA_CATEGORIES_TABLE . " ctg
				ON(ctg.category_id = ctb.contrib_type)
			WHERE $sql_where";
		$result = phpbb::$db->sql_query_limit($sql, 15);
		while ($row = phpbb::$db->sql_fetchrow($result))
		{
			$row['category_name'] = ((isset(phpbb::$user->lang[$row['category_name']])) ? phpbb::$user->lang[$row['category_name']] : $row['category_name']);
			$ctb_row[$row['contrib_name_clean']] = array(
				'name' => "[{$row['category_name']}] {$row['contrib_name']}",
				'value' => $row['contrib_name_clean']
			);
		}
		phpbb::$db->sql_freeresult($result);

		if (!sizeof($ctb_row))
		{
			$ctb_row[] = array(
				'name' => phpbb::$user->lang['CDB_NO_AJAX_RESULT'],
				'value' => ''
			);
		}
		echo json_encode(array(
			'contributions' => $ctb_row
		));
	}
	else
	{
		$sql = 'SELECT *
			FROM ' . TITANIA_CONTRIBS_TABLE . "
			WHERE contrib_name_clean = '" . phpbb::$db->sql_escape($keywords) . "'";
		if ($contrib_type >= 1)
		{
			$sql .= ' AND contrib_type = ' . (int) $contrib_type;
		}
		$result = phpbb::$db->sql_query($sql);
		$ctb_row = phpbb::$db->sql_fetchrow($result);
		phpbb::$db->sql_freeresult($result);

		if ($ctb_row)
		{
			$result_cdb = 'ok';
		}
		else
		{
			$result_cdb = 'fail';
		}
		echo json_encode(array(
			'result' => $result_cdb
		));
	}

	garbage_collection();
	exit_handler();
}