<tr class="inline_row {$anschlusssuche_rowclass}">
	<td align="center" class="{$bgcolor}{$thread_type_class}" width="2%"><span class="thread_status {$folder}" title="{$folder_label}">&nbsp;</span></td>
	<td align="center" class="{$bgcolor}{$thread_type_class}" width="2%">{$icon}</td>
	<td class="{$bgcolor}{$thread_type_class}">
		{$attachment_count}
		<div>
			<span>{$prefix} {$gotounread} {$threadsolved} {$thread['threadprefix']}{$anschlusssuche_status} <span class="{$inline_edit_class} {$new_class}" id="tid_{$inline_edit_tid}"><a href="{$thread['threadlink']}">{$thread['subject']}</a></span>{$thread['multipage']}</span>
			<div class="anschlusssuche-meta smalltext">{$anschlusssuche_fieldline}{$anschlusssuche_links}</div>
			<div><span class="author smalltext">{$thread['profilelink']}{$lang->comma}</span><span class="thread_start_datetime smalltext">{$thread['start_datetime']}</span></div>
		</div>
	</td>
	<td align="center" class="{$bgcolor}{$thread_type_class} h4w-mobile-invis"><a href="{$mybb->settings['bburl']}/misc.php?action=whoposted&tid={$thread['tid']}" onclick="MyBB.whoPosted({$thread['tid']}); return false;">{$thread['replies']}</a>{$unapproved_posts}</td>
	<td align="center" class="{$bgcolor}{$thread_type_class} h4w-mobile-invis">{$thread['views']}</td>
	{$rating}
	<td class="{$bgcolor}{$thread_type_class}" style="white-space: nowrap; text-align: right;">
		<span class="lastpost smalltext">{$lastpostdate}<br />
		<span class="h4w-threadlist-lastpost-lastpost"><a href="{$thread['lastpostlink']}">{$lang->lastpost}</a>: {$lastposterlink}</span></span>
	</td>
{$modbit}
</tr>
