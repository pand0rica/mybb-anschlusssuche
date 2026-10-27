<form action="anschluss.php" method="get" class="anschlusssuche-filterbar">
<input type="hidden" name="type" value="{$filter_type}" />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr><td class="thead"><strong>{$lang->anschlusssuche_filterbar_title}</strong></td></tr>
<tr><td class="trow1"><div class="anschlusssuche-filters">{$filter_rows}<input type="submit" class="button" value="{$lang->anschlusssuche_filter_submit}" /></div></td></tr>
</table>
</form>
<br />
