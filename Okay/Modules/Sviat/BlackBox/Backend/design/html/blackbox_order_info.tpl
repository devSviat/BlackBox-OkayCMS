<div class="fn_blackbox_container">
    {if isset($blackbox_result)}
        <div class="mb-1">
            <div class="heading_label boxes_inline">{$btr->sviat__blackbox__order_panel_title|escape}</div>
            {if $blackbox_result && $blackbox_result.success}
                {if $blackbox_result.data}
                    <div class="boxes_inline">
                        <span class="tag tag-danger">{$btr->sviat__blackbox__found|escape}</span>
                    </div>
                    {if $clientInfo}
                        <div class="fn_switch_client_info boxes_inline">
                            <span title="{$btr->sviat__blackbox__learn_more|escape}">
                                {include file='svg_icon.tpl' svgId='info_icon'}
                            </span>
                        </div>
                        <div class="client_info mb-q">
                            <div class="text_grey font_12">
                                {if $clientInfo.phone}
                                    <span class="text_label">{$btr->sviat__blackbox__client_phone|escape}:</span>
                                    {$clientInfo.phone|escape}<br>
                                {/if}
                                {if $clientInfo.fios}
                                    <span class="text_label">{$btr->sviat__blackbox__client_names|escape}:</span>
                                    {', '|implode:$clientInfo.fios|escape}<br>
                                {/if}
                                {if $clientInfo.tracks}
                                    {foreach $clientInfo.tracks as $track}
                                        <hr class="mt-q mb-q">
                                        {if $track.date}
                                            <span class="text_label">{$btr->sviat__blackbox__client_date|escape}:</span>
                                            {$track.date|escape}<br>
                                        {/if}
                                        {if $track.type}
                                            <span class="text_label">{$btr->sviat__blackbox__client_type|escape}:</span>
                                            {$track.type|escape}<br>
                                        {/if}
                                        {if $track.city}
                                            <span class="text_label">{$btr->sviat__blackbox__client_city|escape}:</span>
                                            {$track.city|escape}<br>
                                        {/if}
                                        {if $track.warehouse}
                                            <span class="text_label">{$btr->sviat__blackbox__client_warehouse|escape}:</span>
                                            {$track.warehouse|escape}<br>
                                        {/if}
                                        {if $track.cost}
                                            <span class="text_label">{$btr->sviat__blackbox__client_cost|escape}:</span>
                                            {$track.cost|escape} {$currency->sign|escape}<br>
                                        {/if}
                                        {if $track.comment}
                                            <span class="text_label">{$btr->sviat__blackbox__client_comment|escape}:</span>
                                            {$track.comment|escape}<br>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </div>
                        </div>
                    {/if}
                {else}
                    <div class="boxes_inline">
                        <span class="tag tag-success">{$btr->sviat__blackbox__not_found|escape}</span>
                    </div>
                {/if}
            {elseif $blackbox_result && $blackbox_result.error}
                <div class="boxes_inline">
                    <span class="tag tag-warning">
                        {$btr->sviat__blackbox__api_error|escape} ({$blackbox_result.error.code}):
                        {$blackbox_result.error.message|escape}
                        {if $blackbox_result.error.code == 101}
                            - {$btr->sviat__blackbox__error_101|escape}
                        {elseif $blackbox_result.error.code == 102}
                            - {$btr->sviat__blackbox__error_102|escape}
                        {elseif $blackbox_result.error.code == 103}
                            - {$btr->sviat__blackbox__error_103|escape}
                        {elseif $blackbox_result.error.code == 104}
                            - {$btr->sviat__blackbox__error_104|escape}
                        {elseif $blackbox_result.error.code == 105}
                            - {$btr->sviat__blackbox__error_105|escape}
                        {else}
                            - {$btr->sviat__blackbox__error_unknown|escape}
                        {/if}
                    </span>
                </div>
            {else}
                <div class="boxes_inline">
                    <span class="tag tag-default">{$btr->sviat__blackbox__no_data|escape}</span>
                </div>
            {/if}
            <div class="fn_add_client_info boxes_inline">
                <span title="{$btr->sviat__blackbox__add_client_info|escape}">
                    {include file='svg_icon.tpl' svgId='add'}
                </span>
            </div>
            {if $blackbox_result}
                <div class="boxes_inline text_grey">
                    <div class="boxes_inline text_grey">
                        <small class="">
                            <span class="fn_bb_updated">{$btr->sviat__blackbox__updated|escape}:
                                <span>{$blackbox_result.cached_at|escape}</span>
                            </span>
                            <a href="#" title="{$btr->sviat__blackbox__refresh|escape}"
                                class="fn_bb_refresh btn btn_refresh">{include file='svg_icon.tpl' svgId='refresh_icon'}
                            </a>
                        </small>
                    </div>
                </div>
            {/if}
        </div>
    {/if}
</div>

{literal}
    <script>
        $(document).on('click', '.fn_bb_refresh', function(e) {
            e.preventDefault();
            let client_phone = $('input[name="phone"]').val();
            let client_name = $('input[name="name"]').val();
            let client_last_name = $('input[name="last_name"]').val();
            $.ajax({
                url: okay.router['Sviat_BlackBox_update'],
                data: {
                    phone: client_phone,
                    name: client_name,
                    last_name: client_last_name,
                    order_id: '{/literal}{$order->id}{literal}'
                },
                dataType: 'json',
                success: function(data) {
                    if (!data || !data.blackbox_info.success) {
                        toastr.error('', "{/literal}{$btr->toastr_error|escape}{literal}");
                        return;
                    }
                    if (data.blackbox_info.success) {
                        toastr.success('', "{/literal}{$btr->toastr_success|escape}{literal}");
                        $('.fn_bb_updated').children('span').text(data.blackbox_info.cached_at);
                    }
                }
            });
        });
    </script>
{/literal}