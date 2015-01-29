/**
 * @tag models, home
 * Wraps backend produto_volume services.  Enables 
 * [Wms.Models.ProdutoVolume.static.findAll retrieving],
 * [Wms.Models.ProdutoVolume.static.update updating],
 * [Wms.Models.ProdutoVolume.static.destroy destroying], and
 * [Wms.Models.ProdutoVolume.static.create creating] produto_volumes.
 */
$.Model.extend('Wms.Models.ProdutoVolume',
/* @Static */
{
    findNormasPaletizacao: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/produto-volume/list-norma-paletizacao-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: this.callback(['wrapMany',success]),
            error: error
        });
    },
    /**
     * Retrieves produto_volumes data from your backend services.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped produto_volume objects.
     * @param {Function} error a callback function for an error in the ajax request.
     */
    findAll: function( params, success, error ){
        $.ajax({
            url: URL_MODULO + '/produto-volume/list-json',
            type: 'post',
            dataType: 'json',
            data: params,
            success: this.callback(['wrapMany',success]),
            error: error
        });
    },
    /**
     * Updates a produto_volume's data.
     * @param {String} id A unique id representing your produto_volume.
     * @param {Object} attrs Data to update your produto_volume with.
     * @param {Function} success a callback function that indicates a successful update.
     * @param {Function} error a callback that should be called with an object of errors.
    */
    update: function( id, attrs, success, error ){
        //alert('atualizando');
        $.ajax({
            url: URL_MODULO + '/produto-volume/edit/id/' + id,
            type: 'post',
            dataType: 'json',
            data: attrs,
            success: success,
            error: error
        });
    },
    /**
     * Destroys a produto_volume's data.
     * @param {String} id A unique id representing your produto_volume.
     * @param {Function} success a callback function that indicates a successful destroy.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    destroy: function( id, success, error ){
        $.ajax({
            url: URL_MODULO + '/produto-volume/delete/id/'+id,
            type: 'post',
            dataType: 'json',
            success: success,
            error: error
        });
    },
    /**
     * Creates a produto_volume.
     * @param {Object} attrs A produto_volume's attributes.
     * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    create: function( attrs, success, error ){
        alert('inserindo');
        $.ajax({
            url: URL_MODULO + '/produto-volume/add',
            type: 'post',
            dataType: 'json',
            success: success,
            error: error,
            data: attrs
        });
    },
    
    /**
     * Verifica se ja existe o codigo de barras informado.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped produto_embalagem objects.
     * @param {Function} error a callback function for an error in the ajax request.
    */
    verificarCodigoBarras: function( params, success, error ){
        //alert('verificando');
        $.ajax({
            url: URL_MODULO + '/produto-embalagem/verificar-codigo-barras-ajax',
            type: 'post',
            dataType: 'json',
            data: params,
            success: success,
            error: error
        });
    },
    
    /**
     * Verifica se existe o endereco informado.
     * @param {Object} params params that might refine your results.
     * @param {Function} success a callback function that returns wrapped produto_embalagem objects.
     * @param {Function} error a callback function for an error in the ajax request.
    */
    verificarEndereco: function( params, success, error ){
        //alert('verificando');
        $.ajax({
            url: URL_MODULO + '/endereco/verificar-endereco-ajax',
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
