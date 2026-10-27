<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->anschlusssuche_usercp_title}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr><td class="thead" colspan="4"><div class="float_right">{$new_button}</div><strong>{$lang->anschlusssuche_usercp_title}</strong></td></tr>
<tr><td class="trow1 smalltext" colspan="4">{$lang->anschlusssuche_usercp_intro}</td></tr>
<tr>
<td class="tcat"><strong>{$lang->anschlusssuche_core_title}</strong></td>
<td class="tcat" width="15%"><strong>{$lang->anschlusssuche_core_status}</strong></td>
<td class="tcat" width="20%"><strong>{$lang->anschlusssuche_usercp_updated}</strong></td>
<td class="tcat" width="20%" align="center"><strong>{$lang->anschlusssuche_usercp_actions}</strong></td>
</tr>
{$rows}
</table>
<div class="smalltext">{$limit_note}</div>
</td>
</tr>
</table>
<script type="text/javascript" src="{$jsurl}" defer="defer"></script>
{$footer}
</body>
</html>
