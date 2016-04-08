{if file_exists("$template/includes/pageheader.tpl")}
    {include file="$template/includes/pageheader.tpl" title={$LANG.domaingeteppcode}}
{elseif file_exists("$template/pageheader.tpl")}
    {include file="$template/pageheader.tpl" title={$LANG.domaingeteppcode}}
{/if}

{if $error}
    {include file="$template/includes/alert.tpl" type="error" msg=$LANG.domaingeteppcodefailure|cat:" $error"}
{elseif $eppcode}
    {include file="$template/includes/alert.tpl" type="success" msg=$LANG.domaingeteppcodeis|cat:" $eppcode"}
{/if}

