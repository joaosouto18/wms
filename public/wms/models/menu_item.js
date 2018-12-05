/**
 */
$.Model.extend('Wms.Models.MenuItem',
/* @Static */
{
    /**
     * Retrieves permissions data from your backend services.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped person_address objects.
     * @param {Function} error a callback function for an error in the ajax request.
     */
    findAll: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/recurso-acao-sistema/list-json',
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
