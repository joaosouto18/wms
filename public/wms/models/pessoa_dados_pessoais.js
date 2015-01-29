/**
 * @tag models, home
 * Wraps backend pessoa_dados_pessoais services.  Enables 
 * [Wms.Models.PessoaDadosPessoais.static.findAll retrieving],
 * [Wms.Models.PessoaDadosPessoais.static.update updating],
 * [Wms.Models.PessoaDadosPessoais.static.destroy destroying], and
 * [Wms.Models.PessoaDadosPessoais.static.create creating] pessoa_dados_pessoais.
 */
$.Model.extend('Wms.Models.PessoaDadosPessoais',
/* @Static */
{
    /**
     * Retrieves pessoa_dados_pessoais data from your backend services.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped pessoa_dados_pessoais objects.
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
     * Updates a pessoa_dados_pessoais's data.
     * @param {String} id A unique id representing your pessoa_endereco.
     * @param {Object} attrs Data to update your pessoa_dados_pessoais with.
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
     * Destroys a pessoa_dados_pessoais's data.
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
     * Creates a pessoa_dados_pessoais.
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
    },
    
    /**
     * Verifica se existe alguma pessoa com o cpf informado.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped pessoa_dados_pessoais objects.
     * @param {Function} error a callback function for an error in the ajax request.
    */
    verificarCPF: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/dados-pessoal/list-pessoa-fisica-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: success,
            error: error
        });
    }
},
/* @Prototype */
{});
