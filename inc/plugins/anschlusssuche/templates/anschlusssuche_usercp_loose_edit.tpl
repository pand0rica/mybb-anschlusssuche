<html>
<head>
<title>{$mybb->settings['bbname']} - {$page_title}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
{$errors}
<form action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="do_anschlusssuche_loose_save" />
<input type="hidden" name="id" value="{$entry_id}" />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr><td class="thead" colspan="2"><strong>{$page_title}</strong></td></tr>
{$form_rows}
</table>
<br />
<div align="center">
	<input type="submit" class="button" value="{$lang->anschlusssuche_save}" />
	<input type="submit" class="button" form="anschlusssuche_cancel_form" value="{$lang->anschlusssuche_cancel}" />
</div>
</form>
<form action="usercp.php" method="get" id="anschlusssuche_cancel_form">
	<input type="hidden" name="action" value="anschlusssuche_loose" />
</form>
</td>
</tr>
</table>
{$footer}
</body>
</html>
