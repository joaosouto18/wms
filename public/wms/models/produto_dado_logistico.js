/**
 */
$.Model.extend('Wms.Models.ProdutoDadoLogistico',
/* @Static */
{
   findNormasPaletizacao: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/produto/listar-norma-por-dado-logistico-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: this.callback(['wrapMany',success]),
            error: error
        });
    }
},
/* @Prototype */
{});
