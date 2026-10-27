<tr>
<td class="{$trow}">{$row_title}<div class="smalltext">{$row_fields}</div></td>
<td class="{$trow}">{$row_status}</td>
<td class="{$trow} smalltext">{$row_updated}</td>
<td class="{$trow} anschlusssuche-actions" align="center">
	<form action="usercp.php" method="get" class="anschlusssuche-inline-form">
		<input type="hidden" name="action" value="anschlusssuche_loose_edit" />
		<input type="hidden" name="id" value="{$row_id}" />
		<input type="submit" class="button" value="{$lang->anschlusssuche_edit}" />
	</form>
	<form action="usercp.php" method="post" class="anschlusssuche-inline-form" data-anschlusssuche-confirm="{$confirm_message}">
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="do_anschlusssuche_loose_delete" />
		<input type="hidden" name="id" value="{$row_id}" />
		<input type="submit" class="button" value="{$lang->anschlusssuche_delete}" />
	</form>
</td>
</tr>
