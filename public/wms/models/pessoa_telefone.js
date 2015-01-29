/**
 * @tag models, home
 * Wraps backend pessoa_telefone services.  Enables 
 * [Wms.Models.PessoaTelefone.static.findAll retrieving],
 * [Wms.Models.PessoaTelefone.static.update updating],
 * [Wms.Models.PessoaTelefone.static.destroy destroying], and
 * [Wms.Models.PessoaTelefone.static.create creating] pessoa_telefones.
 */
$.Model.extend('Wms.Models.PessoaTelefone',
/* @Static */
{
    /**
     * Retrieves pessoa_telefones data from your backend services.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped pessoa_telefone objects.
     * @param {Function} error a callback function for an error in the ajax request.
     */
    findAll: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/pessoa-telefone/list-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: this.callback(['wrapMany',success]),
            error: error
        });
    },
    /**
     * Updates a pessoa_telefone's data.
     * @param {String} id A unique id representing your pessoa_telefone.
     * @param {Object} attrs Data to update your pessoa_telefone with.
     * @param {Function} success a callback function that indicates a successful update.
     * @param {Function} error a callback that should be called with an object of errors.
    */
    update: function( id, attrs, success, error ){
        //alert('atualizando');
        $.ajax({
            url: URL_MODULO + '/pessoa-telefone/edit/id/' + id,
            type: 'post',
            dataType: 'json',
            data: attrs,
            success: success,
            error: error
        });
    },
    /**
     * Destroys a pessoa_telefone's data.
     * @param {String} id A unique id representing your pessoa_telefone.
     * @param {Function} success a callback function that indicates a successful destroy.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    destroy: function( id, success, error ){
        $.ajax({
            url: URL_MODULO + '/pessoa-telefone/delete/id/'+id,
            type: 'post',
            dataType: 'json',
            success: success,
            error: error
        });
    },
    /**
     * Creates a pessoa_telefone.
     * @param {Object} attrs A pessoa_telefone's attributes.
     * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    create: function( attrs, success, error ){
        alert('inserindo');
        $.ajax({
            url: URL_MODULO + '/pessoa-telefone/add',
            type: 'post',
            dataType: 'json',
            success: success,
            error: error,
            data: attrs
        });
    }
},
/* @Prototype */
{});
