<?php
/**
 * @version $Id$
 * Kunena Component
 * @package Kunena
 *
 * @Copyright (C) 2008 - 2010 Kunena Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.com
 *
 * Based on FireBoard Component
 * @Copyright (C) 2006 - 2007 Best Of Joomla All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.bestofjoomla.com
 *
 * Based on Joomlaboard Component
 * @copyright (C) 2000 - 2004 TSMF / Jan de Graaff / All Rights Reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @author TSMF & Jan de Graaff
 **/

// Dont allow direct linking
defined( '_JEXEC' ) or die();


$kunena_config = &CKunenaConfig::getInstance ();
$kunena_db = &JFactory::getDBO ();

global $kunena_icons;

$func = JString::strtolower ( JRequest::getCmd ( 'func', 'listcat' ) );
$catid = JRequest::getInt ( 'catid', 0 );
$id = JRequest::getInt ( 'id', 0 );

?>
<!-- Pathway -->
<?php
$sfunc = JRequest::getVar ( "func", null );

if ($func != "") {
	$catids = intval ( $catid );
	$jr_path_menu = array ();

	$fr_title_name = _KUNENA_CATEGORIES;
	while ( $catids > 0 ) {
		$query = "SELECT * FROM #__fb_categories WHERE id='{$catids}' AND published='1'";
		$kunena_db->setQuery ( $query );
		$results = $kunena_db->loadObject ();
		check_dberror ( "Unable to load categories." );

		if (! $results)
			break;
		$parent_ids = $results->parent;
		$fr_name = kunena_htmlspecialchars ( JString::trim ( stripslashes ( $results->name ) ) );
		$sname = CKunenaLink::GetCategoryLink ( 'showcat', $catids, $fr_name );

		if ($catid == $catids && $sfunc != "view") {
			$fr_title_name = $fr_name;
			$jr_path_menu [] = $fr_name;
		} else {
			$jr_path_menu [] = $sname;
		}

		// next looping
		$catids = $parent_ids;
	}

	//reverse the array
	$jr_path_menu = array_reverse ( $jr_path_menu );

	//attach topic name
	$this->kunena_topic_title = '';
	if ($sfunc == "view" and $id) {
		$sql = "SELECT subject, id FROM #__fb_messages WHERE id='{$id}'";
		$kunena_db->setQuery ( $sql );
		$this->kunena_topic_title = CKunenaTools::parseText ( html_entity_decode_utf8 ( $kunena_db->loadResult () ) );
		check_dberror ( "Unable to load subject." );
		$jr_path_menu [] = $this->kunena_topic_title;
	}

	// print the list
	if (count ( $jr_path_menu ) == 0)
		$jr_path_menu [] = '';
	$jr_forum_count = count ( $jr_path_menu );

	$fireinfo = '';
	if (! empty ( $this->kunena_forum_locked )) {
		$fireinfo = isset ( $kunena_icons ['forumlocked'] ) ? ' <img src="' . KUNENA_URLICONSPATH . $kunena_icons ['forumlocked'] . '" border="0" alt="' . _GEN_LOCKED_FORUM . '" title="' . _GEN_LOCKED_FORUM . '"/>' : ' <img src="' . KUNENA_URLEMOTIONSPATH . 'lock.gif"  border="0"  alt="' . _GEN_LOCKED_FORUM . '" title="' . _GEN_LOCKED_FORUM . '">';
		$lockedForum = 1;
	}

	if (! empty ( $this->kunena_forum_reviewed )) {
		$fireinfo = isset ( $kunena_icons ['forummoderated'] ) ? ' <img src="' . KUNENA_URLICONSPATH . $kunena_icons ['forummoderated'] . '" border="0" alt="' . _GEN_MODERATED . '" title="' . _GEN_MODERATED . '"/>' : ' <img src="' . KUNENA_URLEMOTIONSPATH . 'review.gif" border="0"  alt="' . _GEN_MODERATED . '" title="' . _GEN_MODERATED . '">';
		$moderatedForum = 1;
	}

	$firepath = '<div class="path-element-first">' . CKunenaLink::GetKunenaLink ( kunena_htmlspecialchars ( stripslashes ( $kunena_config->board_title ) ) ) . '</div>';

	$firelast = '';
	for($i = 0; $i < $jr_forum_count; $i ++) {
		if ($i == $jr_forum_count - 1) {
			$firelast .= '<br /><div class="path-element-last">' . $jr_path_menu [$i] . $fireinfo . '</div>';
		} else {
			$firepath .= '<div class="path-element">' . $jr_path_menu [$i] . '</div>';
		}
	}

	//get viewing
	$fb_queryName = $kunena_config->username ? "username" : "name";
	$query = "SELECT w.userid, u.$fb_queryName AS username, k.showOnline FROM #__fb_whoisonline AS w LEFT JOIN #__users AS u ON u.id=w.userid LEFT JOIN #__fb_users AS k ON k.userid=w.userid WHERE w.link LIKE '%" . addslashes ( JURI::current () ) . "%' GROUP BY w.userid ORDER BY u.{$fb_queryName} ASC";
	$kunena_db->setQuery ( $query );
	$users = $kunena_db->loadObjectList ();
	check_dberror ( "Unable to load who is online." );
	$total_viewing = count ( $users );

	$fireonline = '';
	if ($sfunc == "userprofile") {
		$fireonline .= _USER_PROFILE;
		$fireonline .= $this->kunena_username;
	} else {
		$fireonline .= "<div class=\"path-element-users\">($total_viewing " . _KUNENA_PATHWAY_VIEWING . ")&nbsp;";
		$totalguest = 0;
		$divider = ', ';
		$lastone = end ( $users );
		foreach ( $users as $user ) {
			if ($user->userid != 0) {
				if ($user == $lastone && ! $totalguest) {
					$divider = '';
				}
				if ($user->showOnline > 0) {
					$fireonline .= CKunenaLink::GetProfileLink ( $kunena_config, $user->userid, $user->username ) . $divider;
				}
			} else {
				$totalguest = $totalguest + 1;
			}
		}
		if ($totalguest > 0) {
			if ($totalguest == 1) {
				$fireonline .= '(' . $totalguest . ') ' . _WHO_ONLINE_GUEST;
			} else {
				$fireonline .= '(' . $totalguest . ') ' . _WHO_ONLINE_GUESTS;
			}
		}
		$fireonline .= '</div>';
	}

	$document = & JFactory::getDocument ();
	$document->setTitle ( htmlspecialchars_decode ( $this->kunena_topic_title ? $this->kunena_topic_title : $fr_title_name ) . ' - ' . stripslashes ( $kunena_config->board_title ) );

	$this->kunena_pathway1 = $firepath . $fireinfo;
	$this->kunena_pathway2 = $firelast . $fireonline;

	echo '<div class = "kforum-pathway">';
	echo $this->kunena_pathway1 . $this->kunena_pathway2;
	echo '</div>';
}