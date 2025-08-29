<div id="addClientModal" class="bb-modal" style="display: none;">
    <div class="bb-modal-content">
        <span class="bb-modal-close">
            {include file='svg_icon.tpl' svgId='delete'}
        </span>
        <div>
            <div class="bb-modal_heading">
                {$btr->sviat__blackbox__add_client_info|escape}
                <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                    <a class="btn-minimize" href="javascript:;"><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                </div>
            </div>
            <div class="toggle_body_wrap on fn_card">
                <div class="box_border_buyer">
                    <form id="addClientForm">
                        <input type=hidden name="session_id" value="{$smarty.session.id}">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="heading_label heading_label--required">
                                    <span>{$btr->sviat__blackbox__client_type|escape}</span>
                                </div>
                                <div class="form-group">
                                    <select id="blackbox-type_track" name="blackbox-type_track"
                                        class="selectpicker form-control" required>
                                        <option value="1" selected>
                                            {$btr->sviat__blackbox__select_type_novaposhta|escape}</option>
                                        <option value="4">{$btr->sviat__blackbox__select_type_delivery|escape}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="heading_label heading_label--required">
                                    <span>{$btr->sviat__blackbox__client_ttn|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-ttn" name="blackbox-ttn" class="form-control"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="heading_label heading_label--required">
                                    <span>{$btr->sviat__blackbox__client_phone|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-phonenumber" name="blackbox-phonenumber"
                                        class="form-control" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="heading_label heading_label--required">
                                    <span>{$btr->sviat__blackbox__client_cost|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="number" id="blackbox-cost" name="blackbox-cost" class="form-control"
                                        min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="heading_label heading_label--required">
                                    <span>{$btr->sviat__blackbox__client_last_name|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-last_name" name="blackbox-last_name"
                                        class="form-control" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="heading_label">
                                    <span>{$btr->sviat__blackbox__client_first_name|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-first_name" name="blackbox-first_name"
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="heading_label">
                                    <span>{$btr->sviat__blackbox__client_city|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-city" name="blackbox-city" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="heading_label">
                                    <span>{$btr->sviat__blackbox__client_date|escape}</span>
                                </div>
                                <div class="form-group">
                                    <input type="text" id="blackbox-date" name="blackbox-date" class="form-control"
                                        placeholder="dd.mm.yyyy">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="heading_label">
                                    <span>{$btr->sviat__blackbox__client_comment|escape}</span>
                                </div>
                                <div class="form-group">
                                    <textarea id="blackbox-comment" name="blackbox-comment"
                                        class="form-control okay_textarea" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 mt-1">
                                <button type="submit" class="fn_blackbox_submit btn btn_small btn_blue float-md-right">
                                    {$btr->sviat__blackbox__send|escape}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{literal}
    <script>
        const modal = document.getElementById('addClientModal');
        const btn = document.querySelector('.fn_add_client_info');
        const span = document.getElementsByClassName('bb-modal-close')[0];
        const form = document.getElementById('addClientForm');

        if (btn && modal) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                const typeTrackInput = document.getElementById('blackbox-type_track');
                const phoneInput = document.getElementById('blackbox-phonenumber');
                const lastNameInput = document.getElementById('blackbox-last_name');
                const firstNameInput = document.getElementById('blackbox-first_name');
                const cityInput = document.getElementById('blackbox-city');
                const dateInput = document.getElementById('blackbox-date');

                const orderPhone = $('input[name="phone"]').val() || '';
                const orderLastName = $('input[name="last_name"]').val() || '';
                const orderName = $('input[name="name"]').val() || '';
                const orderAddress = $('input[name="address"]').val() || '';

                phoneInput.value = orderPhone;
                lastNameInput.value = orderLastName;

                if (orderName) {
                    const nameParts = orderName.trim().split(' ');
                    firstNameInput.value = nameParts[0] || '';
                    if (nameParts.length > 1 && !orderLastName) {
                        lastNameInput.value = nameParts.slice(1).join(' ');
                    }
                }

                if (orderAddress) {
                    const addressParts = orderAddress.split(',');
                    cityInput.value = addressParts[0].trim() || '';
                }

                var $newpostCity = $('.fn_np_warehouse_delivery_block .fn_newpost_city_name');
                if ($newpostCity.length && $newpostCity.val().trim() !== '') {
                    cityInput.value = $newpostCity.val().trim();
                }

                const today = new Date();
                const day = String(today.getDate()).padStart(2, '0');
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const year = today.getFullYear();
                dateInput.value = `${day}.${month}.${year}`;

                modal.style.display = 'block';
            });
        }

        if (span && modal) {
            span.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const requiredFields = [
                    'blackbox-type_track',
                    'blackbox-ttn',
                    'blackbox-phonenumber',
                    'blackbox-cost',
                    'blackbox-last_name'
                ];

                let isValid = true;
                let firstInvalid = null;

                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field || !String(field.value || '').trim()) {
                        isValid = false;
                        if (!firstInvalid) firstInvalid = field;
                    }
                });

                const costField = document.getElementById('blackbox-cost');
                const costValue = costField ? parseFloat(costField.value) : NaN;
                if (isNaN(costValue) || costValue < 0) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = costField;
                }

                if (!isValid) {
                    if (firstInvalid && typeof firstInvalid.focus === 'function') firstInvalid.focus();

                    if (typeof toastr !== 'undefined' && typeof toastr.error === 'function') {
                        toastr.error('{/literal}{$btr->sviat__blackbox__error_required|escape}{literal}');
                    } else {
                        alert('{/literal}{$btr->sviat__blackbox__error_required|escape}{literal}');
                    }

                    return;
                }

                const data = {
                    'blackbox-type_track': document.getElementById('blackbox-type_track').value,
                    'blackbox-ttn': document.getElementById('blackbox-ttn').value,
                    'blackbox-phonenumber': document.getElementById('blackbox-phonenumber').value,
                    'blackbox-cost': document.getElementById('blackbox-cost').value,
                    'blackbox-last_name': document.getElementById('blackbox-last_name').value,
                    'blackbox-first_name': document.getElementById('blackbox-first_name').value,
                    'blackbox-city': document.getElementById('blackbox-city').value,
                    'blackbox-date': document.getElementById('blackbox-date').value,
                    'blackbox-comment': document.getElementById('blackbox-comment').value,
                    order_id: '{/literal}{$order->id}{literal}'
                };

                $.ajax({
                    url: okay.router['Sviat_BlackBox_add'],
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success('', "{/literal}{$btr->toastr_success|escape}{literal}");
                            modal.style.display = 'none';
                        } else if (response.error) {
                            toastr.error(response.error.message, "API error " + response.error.code);
                        } else {
                            toastr.error('', "{/literal}{$btr->toastr_error|escape}{literal}");
                        }
                    },
                    error: function() {
                        alert('Помилка запиту');
                    }
                });
            });
        }
    </script>
{/literal}