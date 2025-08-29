{$meta_title = $btr->sviat__blackbox__title|escape scope=global}

<div class="row">
    <div class="col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">{$btr->sviat__blackbox__title|escape}</div>
        </div>
    </div>
</div>

{if $message_success}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">{$btr->general_settings_saved|escape}</div>
                </div>
            </div>
        </div>
    </div>
{/if}

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">

    <div class="row row--xxl">
        <div class="col-lg-6 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">{$btr->sviat__blackbox__api_settings|escape}</div>
                <div class="toggle_body_wrap on">
                    <div class="row">
                        <div class="col-xxl-12 col-lg-12 col-md-12">
                            <div class="heading_label heading_label--required"><span>{$btr->sviat__blackbox__api_key|escape}</span></div>
                            <div class="mb-1">
                                <input type="text" name="blackbox_api_key" value="{$settings->blackbox_api_key|escape}" class="form-control">
                            </div>
                        </div>

                        <div class="col-xxl-12 col-lg-12 col-md-12">
                            <div class="alert alert--icon alert--info">
                                <div class="alert__content">
                                    <div class="alert__title">{$btr->sviat__blackbox__how_it_works|escape}</div>
                                    <p>{$btr->sviat__blackbox__how_it_works_desc|escape} <a href="https://blackbox.net.ua/" target="_blank">BlackBox</a>.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 col-md-12 mt-1">
                            <button type="submit" class="btn btn_small btn_blue float-md-right">
                                {include file='svg_icon.tpl' svgId='checked'}
                                <span>{$btr->general_apply|escape}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


