<table id="preferences" class="form-layout" style="display:none">
    <tbody>
    <tr class="crm-group-form-block-group_preference">
        <td class="label">{$form.is_preference.label}</td><td class="content">
            {$form.is_preference.html}
        </td>
    </tr>
    </tbody>
</table>

{literal}
    <script type="text/javascript">
        CRM.$(function($) {
            $('#preferences').find('tr').insertAfter('tr.crm-group-form-block-group_type');
        });
    </script>
{/literal}