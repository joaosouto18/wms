/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Box',
/* @Static */
{
    pluginName: 'box'
},
/* @Prototype */
{
    '{window} load': function () {
        //alert('sdfsdfs');
    },
    '#identificacao-idPai change' : function (el){
        var codigo = $('#identificacao-id');
        if (el.val() != '') {
            codigo
            .val(el.val() + '.')
            .attr('readonly', true)
            .css('width', '20px')
            if (!$("#id-extra").exists()) {
                codigo.after('<span id="id-extra">&nbsp;<input id="identificacao[idFilho]" name="identificacao[idFilho]" class="required" style="width:20px;"/></span>');
            }
            
        } else {
            codigo
            .val('')
            .attr('readonly', false)
            .css('width', '265px')
            $('#id-extra').remove();
        }
    }
});