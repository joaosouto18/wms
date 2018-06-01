/**
 * @tag models, home
 * Wraps backend pessoa_endereco services.  Enables 
 * [Wms.Models.PessoaEndereco.static.findAll retrieving],
 * [Wms.Models.PessoaEndereco.static.update updating],
 * [Wms.Models.PessoaEndereco.static.destroy destroying], and
 * [Wms.Models.PessoaEndereco.static.create creating] pessoa_enderecos.
 */
$.Model.extend('Wms.Models.PessoaEndereco',
/* @Static */
{
    /**
     * Retrieves pessoa_enderecos data from your backend services.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped pessoa_endereco objects.
     * @param {Function} error a callback function for an error in the ajax request.
     */
    findAll: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/pessoa-endereco/list-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: this.callback(['wrapMany',success]),
            error: error
        });
    },
    /**
     * Updates a pessoa_endereco's data.
     * @param {String} id A unique id representing your pessoa_endereco.
     * @param {Object} attrs Data to update your pessoa_endereco with.
     * @param {Function} success a callback function that indicates a successful update.
     * @param {Function} error a callback that should be called with an object of errors.
    */
    update: function( id, attrs, success, error ){
        //alert('atualizando');
        $.ajax({
            url: URL_MODULO + '/pessoa-endereco/edit/id/' + id,
            type: 'post',
            dataType: 'json',
            data: attrs,
            success: success,
            error: error
        });
    },
    /**
     * Destroys a pessoa_endereco's data.
     * @param {String} id A unique id representing your pessoa_endereco.
     * @param {Function} success a callback function that indicates a successful destroy.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    destroy: function( id, success, error ){
        $.ajax({
            url: URL_MODULO + '/pessoa-endereco/delete/id/'+id,
            type: 'post',
            dataType: 'json',
            success: success,
            error: error
        });
    },
    /**
     * Creates a pessoa_endereco.
     * @param {Object} attrs A pessoa_endereco's attributes.
     * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    create: function( attrs, success, error ){
        alert('inserindo');
        $.ajax({
            url: URL_MODULO + '/pessoa-endereco/add',
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
