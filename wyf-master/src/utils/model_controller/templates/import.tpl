{$form}

<h3>{$response.message}</h3>
{if $response.failed}
    <p>
        The following errors were discoverd on line {$response.line} of your data file.
    </p>
    <ul>
    {foreach from=$response.errors item=error}
        <li>{$error}</li>
    {/foreach}
    </ul>
{else}
    <p>{$response.added} new record(s) were added and {$response.updated} were updated.</p>
{/if}
<div class="import-table-wrapper">
    <table class="import-table">
        <thead><tr>{foreach from=$response.headers item=header}<th>{$header}</th>{/foreach}</tr></thead>
        <tbody>
        {foreach from=$response.statuses item=status}
            <tr>
                {foreach from=$status.data item=cell key=key }
                    <td>{$cell}
                    {if $status.success eq false and $status.errors[$key] neq null}
                    <ul class="import-table-error">
                    {foreach from=$status.errors[$key] item=error}
                        <li>{$error}</li>
                    {/foreach}
                    </ul>
                    {/if}
                    </td>
                {/foreach}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
