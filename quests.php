<?php

// Необходима функция questinfo
require_once('includes/allquests.php');

$smarty->config_load($conf_file, 'quests');

// Разделяем из запроса класс и подкласс квестов
point_delim($podrazdel,$Type,$ZoneOrSort);

$cache_str = (empty($Type)?'x':intval($Type)).'_'.(empty($ZoneOrSort)?'x':intval($ZoneOrSort));

if(!$quests = load_cache(12, $cache_str))
{
	unset($quests);

	global $DB, $quest_class, $quest_cols;

	$rows = $DB->select('
		SELECT ?#, q.entry
		{
			, l.Title_loc?d 
		}
		FROM quest_template q
		{ LEFT JOIN (locales_quest l) ON l.entry=q.entry AND ? }
		WHERE
			1 = 1
			{ AND ZoneOrSort = ? }
			{ AND ZoneOrSort IN (?a) }
		ORDER BY Title
		{LIMIT ?d}
		',
		$quest_cols[2],
		($_SESSION['locale']>0)? $_SESSION['locale']: DBSIMPLE_SKIP,
		($_SESSION['locale']>0)? 1: DBSIMPLE_SKIP,
		(IsSet($ZoneOrSort))? $ZoneOrSort : DBSIMPLE_SKIP,
		((!IsSet($ZoneOrSort)) and $Type)? $quest_class[$Type] : DBSIMPLE_SKIP,
		($AoWoWconf['limit'] > 0)? $AoWoWconf['limit']: DBSIMPLE_SKIP
	);

	$quests = array();
	foreach($rows as $numRow=>$row)
		$quests[] = GetQuestInfo($row, QUEST_DATAFLAG_LISTINGS);

	save_cache(12, $cache_str, $quests);
}
global $page;
$page = array(
	'Mapper' => false,
	'Book' => false,
	'Title' => $smarty->get_config_vars('Quests'),
	'tab' => 0,
	'type' => 0,
	'typeid' => 0,
	'path' => '[0, 3,'.$Type.' ,'.$ZoneOrSort.']'
);
$smarty->assign('page', $page);

// Если хоть одна информация о вещи найдена - передаём массив с информацией о вещях шаблонизатору
if($allitems)
	$smarty->assign('allitems',$allitems);
if($quests)
	$smarty->assign('quests',$quests);
// Количество MySQL запросов
$smarty->assign('mysql', $DB->getStatistics());
// Загружаем страницу
$smarty->display('quests.tpl');

?>
