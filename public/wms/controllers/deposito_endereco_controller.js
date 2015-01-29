/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.DepositoEndereco',
/* @Static */
{
    pluginName: 'depositoEndereco'
},
/* @Prototype */
{
    '{window} load': function() {
        var id = $('#identificacao-id').val();
        
        if(id != '') {
            $('#endereco-checar-intervalo').css('display','none');
            $('#fieldset-identificacao').slideDown(500);
        }
    },
    
    /**
     * When the page loads, gets all bars to be displayed.
     */
    '#endereco-checar-intervalo click': function(el, ev){        
        var valores = $('form').formParams();
        
        Wms.Models.DepositoEndereco.findAll(valores, this.callback('list'));
        return false;
    },
    '#btn-continuar click': function(el, ev){
        $('#fieldset-identificacao').slideDown(1000);
        $('#btn-continuar').css('display', 'none');

        return false;
    },    
    /**
     * Exibe uma lista de enderecos 
     * @param {Array} enderecos An array of Wms.Models.DepositoEndereco objects.
     */
    list: function( enderecos ){
        if(enderecos.length == 0) {
            $('#fieldset-identificacao').slideDown(500);
            $('#fieldset-lista-intervalo-enderecos').slideUp(1000);
        } else {
            $('#fieldset-identificacao').slideUp(1000);
            $('#div-lista-intervalo-enderecos').html(this.view('init', {
                enderecos:enderecos
            } ));
            $('#fieldset-lista-intervalo-enderecos').slideDown();
        }
    }
    
});