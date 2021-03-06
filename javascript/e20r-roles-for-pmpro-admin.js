/**
 * Copyright (c) $today.year. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

jQuery(document).ready(function ($) {
    "use strict";

    var html = '';
    var vars = e20r_roles_for_pmpro_vars;

    html += '<div id="repair_roles_container">';
    html += '<label for="repair_roles" id="repair_roles_label">' + e20r_roles_for_pmpro_vars.desc + '</label>';
    html += '<input id="repair_roles" name="repair_roles" type="submit" class="button-primary" value="' + e20r_roles_for_pmpro_vars.repair + '" />';
    html += '<p id="repaired_roles"></p>';
    html += '</div>';

    $('.widefat').after(html);

    $('#repair_roles').click(function (event) {

        event.preventDefault();

        $.ajax({
            type: "POST",
            url: e20r_roles_for_pmpro_vars.ajaxurl,
            timeout: e20r_roles_for_pmpro_vars.timeout,
            data: {
                action: vars.ajax_action,
                _ajax_nonce: vars.nonce
            },
            beforeSend: function () {
                $("#repair_roles").val(e20r_roles_for_pmpro_vars.working);
            }, //show loading just when link is clicked
            complete: function () {
                $("#repair_roles").val(e20r_roles_for_pmpro_vars.done);
            }, //stop showing loading when the process is complete
            success: function (html) { //so, if data is retrieved, store it in html
                var $repaired_roles = $('#repaired_roles');

                $repaired_roles.toggle();

                if (html === 'failed') {
                    $repaired_roles.text(html);
                } else {
                    $repaired_roles.text(html + e20r_roles_for_pmpro_vars.fixed);
                    $('#repair_roles').attr('disabled', 'disabled');
                }
            }
        });
    });
});