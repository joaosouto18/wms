/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.FiltroNotaFiscal',
/* @Static */
{
    pluginName: 'filtroNotaFiscal'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all produto_volumes to be displayed.
     */
    "{window} load": function(){
        
        //autocomplete fornecedor
        this.autocompleteFornecedor();
        $( "#fornecedor" ).keyup(function(event) {
            if ($( "#fornecedor" ).val() == '') {
                $( "#idFornecedor" ).val('');
            }
        });

        this.autocompleteFabricante();
        $( "#fabricante" ).keyup(function(event) {
            if ($( "#fabricante" ).val() == '') {
                $( "#idFabricante" ).val('');
            }
        });
    },
    
    autocompleteFornecedor: function(  ){
        $( "#fornecedor" ).autocomplete({
            source: function(data, callback) {
                $.ajax({
                    global: false,
                    url:  '/fornecedor/get-fornecedor-json',
                    dataType: 'json',
                    data: data,
                    success: callback
                });
            },
            minLength: 3,
            autoFocus: true,
            select: function( event, ui ) {
                $( "#idFornecedor" ).val( ui.item.id);
            },
            search : function() {
                $("#idFornecedor").val('');
            }
        });
    },

    autocompleteFabricante: function(  ){
        $( "#fabricante" ).autocomplete({
            source: function(data, callback) {
                $.ajax({
                    global: false,
                    url:  '/fornecedor/get-fabricante-json',
                    dataType: 'json',
                    data: data,
                    success: callback
                });
            },
            minLength: 3,
            autoFocus: true,
            select: function( event, ui ) {
                $( "#idFabricante" ).val( ui.item.id);
            },
            search : function() {
                $("#idFabricante").val('');
            }
        });
    }
    
    
    
});