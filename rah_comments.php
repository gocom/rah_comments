<?php

	################
	#
	#	rah_comments-plugin for Textpattern
	#	version 0.4
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	################

	function rah_comments($atts) {
		global $thisarticle;
		extract(lAtts(array(
			'form' => 'comments',
			'sort' => 'posted asc',
			'wraptag' => '',
			'break' => '',
			'class' => '',
			'breakclass' => '',
			'offset' => '',
			'limit' => 10,
			'prepend' => 1,
			'append' => 1,
			'pg_wraptag' => 'ul',
			'pg_break' => 'li',
			'pg_class' => '',
			'pg_breakclass' => '',
			'label_next' => '&gt;&gt;',
			'label_prev' => '&lt;&lt;',
			'class_next' => '',
			'class_prev' => '',
			'showalways' => 1,
		),$atts));
		$number = array();
		if(is_numeric(gps('pg'))) $pg = gps('pg');
		if(empty($pg)) $pg = 1;
		$i = 0;
		$prevpg = $pg-1;
		$nextpg = $pg+1;
		$next = '';
		$prev = '';
		$total = $thisarticle['comments_count'];
		$totalpg = $total/$limit;
		$totalpg = ceil($totalpg);
		$sep = (safe_field('val','txp_prefs',"name='permlink_mode'") == 'messy') ? '&amp;' : '?';
		if(1<$pg && 1<$totalpg) $number[] = doTag($label_prev,'a',$class_prev,' href="'.permlink(array()).$sep.'pg='.$prevpg.'"');
		while ($i<$totalpg) {
			$i++;
			$number[] = doTag($i,'a','page-'.$i,' href="'.permlink(array()).$sep.'pg='.$i.'"');
		}
		if($pg<$totalpg) $number[] = doTag($label_next,'a',$class_next,' href="'.permlink(array()).$sep.'pg='.$nextpg.'"');
		$pagination = ($showalways != 1 && $totalpg < 2) ? '' : doWrap($number, $pg_wraptag, $pg_break, $pg_class, $pg_breakclass);
		if(1<$pg){
			$offset = $limit*$pg;
			$offset = $offset-$limit;
		}
		return (($prepend) ? $pagination : '').comments(array(
			'sort' => $sort,
			'form' => $form,
			'wraptag' => $wraptag,
			'breakclass' => $breakclass,
			'break' => $break,
			'class' => $class,
			'offset' => $offset,
			'limit' => $limit
		)).(($append) ? $pagination : '');
	}

	function rah_recent_comments($atts) {
		extract(lAtts(array(
			'comments_limit' => 10,
			'comments_sort' => 'posted asc',
			'break' => 'br',
			'class' => 'recent_comments',
			'label' => '',
			'labeltag' => '',
			'limit' => 10,
			'sort' => 'posted desc',
			'wraptag' => ''
		),$atts));

		$sep = (safe_field('val','txp_prefs',"name='permlink_mode'") == 'messy') ? '&amp;' : '?';
		$sort = preg_replace('/\bposted\b/', 'd.posted', $sort);
		$rs = startRows('select d.name, d.parentid, d.discussid, t.ID as thisid, unix_timestamp(t.Posted) as posted, t.Title as title, t.Section as section, t.url_title '.'from '. safe_pfx('txp_discuss') .' as d inner join '. safe_pfx('textpattern') .' as t on d.parentid = t.ID '.'where t.Status >= 4 and d.visible = '.VISIBLE.' order by '.doSlash($sort).' limit 0,'.intval($limit));

		if ($rs) {
			$out = array();
			while ($c = nextRow($rs)) {
				$position = 0;
				$pg = 0;	
				$rs2 = safe_rows_start('discussid','txp_discuss',"visible = ".VISIBLE." and parentid = ".$c['parentid']." order by $comments_sort");
				while ($x = nextRow($rs2)) {
					extract($x);
					$position++;
					if($discussid == $c['discussid']) break;
				}
				$pg = ceil($position/$comments_limit);
				$out[] = href($c['name'].' ('.escape_title($c['title']).')',permlinkurl($c).$sep.'pg='.$pg.'#c'.$c['discussid']);
			}
			if ($out) return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
		}
		return '';
	}
