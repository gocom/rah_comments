<?php

/*
 * rah_comments - Paginated article comments for Textpattern CMS
 * https://github.com/gocom/rah_comments
 *
 * Copyright (C) 2015 Jukka Svahn
 *
 * This file is part of rah_comments.
 *
 * rah_comments is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_comments is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_comments. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Returns number of comment pages.
 *
 * Number of pages is calculated based on
 * the provided offset and limit attributes.
 * The resulting value should be passed to
 * etc_pagination or similar pagination generator.
 *
 * <code>
 * <txp:variable name="numPages" value='<txp:rah_comments_numpages limit="10" />' />
 * <txp:etc_pagination pages='<txp:variable name="numPages" />' />
 * <txp:comments offset='<txp:etc_offset pageby="10" />' limit="10" />
 * </code>
 *
 * This tag is also responsible of redirecting comment requests
 * to the correct page.
 *
 * @param  array $atts Attributes
 * @return int   Number of pages
 */

function rah_comments_numpages($atts)
{
    global $etc_pagination_total, $thisarticle;

    extract(lAtts(array(
        'limit'     => 10,
        'offset'    => 0,
        'sort'      => 'posted asc',
        'parameter' => 'pg',
    ), $atts));

    assert_article();

    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $commentId = (int) gps('rah_comments_id');
    $commented = (int) gps('commented');

    // If requesting a specific comment, find the proper page.

    if ($commentId || $commented) {

        $sql = array(
            "visible = ".VISIBLE." and parentid = ".intval($thisarticle['thisid'])
        );

        if ($sort) {
            $sql[] = 'order by '.doSlash($sort);
        }

        $sql[] = 'limit '.intval($offset).', '.PHP_INT_MAX;

        $comments = safe_column(
            'discussid',
            'txp_discuss',
            implode(' ', $sql)
        );

        $finalPosition = $position = $currentId = 0;

        foreach ($comments as $comment) {
            $position++;
            $comment = (int) $comment;

            if ($commentId === $comment) {
                $finalPosition = $position;
                break;
            }

            if ($commented && $comment >= $currentId) {
                $currentId = $comment;
                $finalPosition = $position;
            }
        }

        if ($finalPosition) {
            $page = ceil($finalPosition / $limit);
            $_POST[$parameter] = $_GET[$parameter] = $page;
        }

        // Prevents etc_paginate from picking up the 'commented' parameter.

        if ($commented) {
            $_POST['commented'] = gps('commented');
            unset($_GET['commented']);
        }
    }

    $totalcomments = max(0, $thisarticle['comments_count'] - $offset);
    $totalpages = ceil($totalcomments / $limit);
    $etc_pagination_total = $totalcomments;

    return $totalpages;
}

/**
 * Renders list of recent comments.
 *
 * @param  array  $atts  Attributes
 * @param  string $thing Contained statement
 * @return string User markup
 */

function rah_recent_comments($atts, $thing = null)
{
    $out = recent_comments($atts, $thing);

    $out = preg_replace_callback(
        '/(<a[^>]+href=")([^"#]+)#c([0-9]+)(")/i',

        function ($m) {

            if (strpos($m[2], '?') === false) {
                $sep = '?';
            } else {
                $sep = '&amp;';
            }

            return $m[1].$m[2].
                $sep.'rah_comments_id='.$m[3].'#c'.$m[3].$m[4];
        },

        $out
    );

    return $out;
}
