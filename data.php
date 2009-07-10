<?php

require_once('includes/allutil.php');

switch($_GET['data'])
{
	case 'talents':
		// i - id ������ (id �� aowow_talent)
		// n - �������� (spellname �� aowow_spell ��� spellID=rank1)
		// m - ���-�� ������ (6-����������_������_������)
		// s - ������ ��� ������ (rank1, rank2, ..., rank5 �� aowow_talent)
		// d - �������� �������
		// x - ������� (col �� aowow_talent)
		// y - ������ (row �� aowow_talent)
		// r - �� ���� � ������ ����� �������: "r: [r_1, r_2]", ��� r_1 - ����� (��������� � 0) �������, r_2 - ����
		$class = intval($_GET['class']);
		if(!in_array($class, array_keys($classes)))
			exit;

		if(!$p_arr = load_cache(20, $class))
		{
			unset($p_arr);

			require_once('includes/allspells.php');

			// ��� "����" �������� ��������� ������
			$tabs = $DB->select('
					SELECT `id`, name_loc?d as `name`
					FROM ?_talenttab
					WHERE `classes`=?d
					ORDER by `order`
				',
				$_SESSION['locale'],
				pow(2, $_GET['class']-1)
			);
			
			$t_nums = array();
			$p_arr = array();
			$i = 0;
			foreach($tabs as $tab)
			{
				$p_arr[$i] = array();
				$p_arr[$i]['n'] = $tab['name'];
				$talents = $DB->select('
						SELECT t.*, s.spellname_loc?d as `spellname`, i.`iconname`
						FROM ?_talent t, ?_spell s
						{LEFT JOIN (?_spellicons i) ON i.`id`=s.`spellicon`}
						WHERE
							t.`tab`=?d AND
							s.`spellID` = t.`rank1`
						ORDER by t.`row`, t.`col`
					',
					$_SESSION['locale'],
					$tab['id']
				);
				$j = 0;
				$p_arr[$i]['t'] = array();
				foreach($talents as $talent)
				{
					$t_nums[$talent['id']] = $j;
					$p_arr[$i]['t'][$j] = array();
					$p_arr[$i]['t'][$j]['i'] = (integer) $talent['id'];
					$p_arr[$i]['t'][$j]['n'] = (string) $talent['spellname'];
					$p_arr[$i]['t'][$j]['m'] = (integer) ($talent['rank2']==0 ? 1 : ($talent['rank3']==0 ? 2 : (($talent['rank4']==0 ? 3 : (($talent['rank5']==0 ? 4 : 5))))));
					for ($k=0;$k<=($p_arr[$i]['t'][$j]['m']-1);$k++)
					{
						$p_arr[$i]['t'][$j]['s'][] = (integer) $talent['rank'.($k+1)];
						$p_arr[$i]['t'][$j]['d'][] = (string) spell_desc($talent['rank'.($k+1)]);
					}
					$p_arr[$i]['t'][$j]['x'] = (integer) $talent['col'];
					$p_arr[$i]['t'][$j]['y'] = (integer) $talent['row'];
					if ($talent['dependsOn'] AND $talent['dependsOnRank'])
						$p_arr[$i]['t'][$j]['r'] = array($t_nums[$talent['dependsOn']], $talent['dependsOnRank']+1);
					// Spell icons
					$p_arr[$i]['t'][$j]['iconname'] = (string) $talent['iconname'];
					$j++;
				}
				$i++;
			}

			save_cache(20, $class, $p_arr);
		}
		echo '$WowheadTalentCalculator.registerClass('.$class.', '.php2js($p_arr).')';
		break;
	case 'glyphs':
		/*
			name - ��� ����
			description - ������ ������
			icon - ������ ����
		*/
		$glyphs = array();
		$glyphs = $DB->select('
			SELECT it.`entry`, it.`name`, it.`spellid_1` as `spell`, it.`AllowableClass`, ic.`iconname`
			FROM `item_template` it
			LEFT JOIN (?_icons ic) ON ic.id=it.displayid
			WHERE
				it.`class` = 16
		');
		$g_glyphs = array();
		foreach ($glyphs as $glyph)
		{
			$g_glyphs[$glyph['entry']] = array();
			$g_glyphs[$glyph['entry']]['name'] = (string) $glyph['name'];
			$g_glyphs[$glyph['entry']]['description'] = (string) 'Test';
			$g_glyphs[$glyph['entry']]['icon'] = (string) 'Test';
			$g_glyphs[$glyph['entry']]['type'] = (integer) 1;
			$g_glyphs[$glyph['entry']]['classs'] = (integer) 1;
			$g_glyphs[$glyph['entry']]['skill'] = (integer) 1;
		}
		echo('var g_glyphs='.php2js($g_glyphs));
		break;
	case 'talent-icon':
		$iconname = strtolower($_GET['icon']);
		if(!$DB->selectCell('SELECT 1 FROM ?_spellicons WHERE iconname = ?', $iconname))
			exit;

		if($name = load_cache(21, $iconname))
		{
			header('Content-type: image/jpeg');
			imagejpeg(imagecreatefromjpeg('cache/images/'.$iconname.'.jpg'));
		}
		else
		{
			header('Content-type: image/jpeg');
			$im = @imagecreatefromjpeg('images/icons/medium/'.$iconname.'.jpg');

			if(!$im)
				exit;

			imagetograyscale($im);
			imagejpeg($im, 'cache/images/'.$iconname.'.jpg');
			imagejpeg($im);
		}
		break;
	default:
		break;
}
?>