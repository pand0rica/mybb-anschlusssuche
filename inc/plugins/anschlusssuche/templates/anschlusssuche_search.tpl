<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->anschlusssuche_search_title}</title>
{$headerinclude}
</head>
<body>
{$header}
<div class="anschlusssuche-tabs">{$tabs}</div>
<form action="anschluss.php" method="get">
<input type="hidden" name="type" value="{$search_type}" />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr><td class="thead"><strong>{$lang->anschlusssuche_search_title}</strong></td></tr>
<tr><td class="trow1">
<div class="anschlusssuche-filters">
<label class="anschlusssuche-filter"><span class="anschlusssuche-filter__title">{$lang->anschlusssuche_search_query}</span> <input type="text" class="textbox" name="q" value="{$search_q}" size="25" /></label>
{$filter_rows}
<label class="anschlusssuche-filter"><input type="checkbox" class="checkbox" name="archive" value="1"{$archive_checked} /> {$lang->anschlusssuche_search_archive}</label>
<input type="submit" class="button" value="{$lang->anschlusssuche_filter_submit}" />
</div>
</td></tr>
</table>
</form>
<br />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr><td class="thead"><strong>{$lang->anschlusssuche_search_results}</strong> <span class="smalltext">({$result_total})</span></td></tr>
<tr><td class="trow1">{$multipage}<div class="anschlusssuche-cards">{$results}</div>{$multipage}</td></tr>
</table>
<script type="text/javascript" src="{$jsurl}" defer="defer"></script>
{$footer}
</body>
</html>
