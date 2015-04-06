{if $sErrorMessages || $vatIdCheck.errorMessages|count > 0}
    <div class="account--error">
        {if $sErrorMessages}
            {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
        {/if}
        {if $vatIdCheck.errorMessages|count > 0}
            {include file="frontend/register/error_message.tpl" error_messages=$vatIdCheck.errorMessages}
        {/if}
    </div>
{/if}