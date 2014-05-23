{extends file='frontend/account/index.tpl'}

{block name='frontend_index_content' prepend}
    {if !$sErrorMessages && $vatIdCheck}
        {$sFormData.ustid = $vatIdCheck.vatId}
        {$sErrorFlag.ustid = true}
        {foreach $vatIdCheck.messages as $message}
            {$sErrorMessages[] = $message}
        {/foreach}
    {/if}
{/block}