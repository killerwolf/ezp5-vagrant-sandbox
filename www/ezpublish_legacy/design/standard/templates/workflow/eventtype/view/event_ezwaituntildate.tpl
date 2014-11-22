{* DO NOT EDIT THIS FILE! Use an override template instead. *}

<div class="element">

{if $event.data_int1}
{'Publish date will be modified.'|i18n('design/standard/workflow/eventtype/view')}
{else}
{'Publish date will not be modified.'|i18n('design/standard/workflow/eventtype/view')}
{/if}

<table class="list">
<tr>
    <th> Class name </th>
    <th> ClassAttribute name </th>
</tr>
{section name=Entries loop=$event.content.entry_list sequence=array(bglight,bgdark)}
<tr>
    <td class="{$Entries:sequence}">{if $Entries:item.class_name|is_null|not}{$Entries:item.class_name}{else}<b><i>{"Item can not be found"|i18n("design/standard/workflow/eventtype/view")}</i></b>{/if}</td>
    <td class="{$Entries:sequence}">{if $Entries:item.classattribute_name|is_null|not}{$Entries:item.classattribute_name}{else}<b><i>{"Item can not be found"|i18n("design/standard/workflow/eventtype/view")}</i></b>{/if}</td>
</tr>
{/section}
</table>

</div>