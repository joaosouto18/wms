/**
 * @tag models, home
 * Wraps backend produto_embalagem services.  Enables
 * [Wms.Models.ProdutoEmbalagem.static.findAll retrieving],
 * [Wms.Models.ProdutoEmbalagem.static.update updating],
 * [Wms.Models.ProdutoEmbalagem.static.destroy destroying], and
 * [Wms.Models.ProdutoEmbalagem.static.create creating] produto_embalagens.
 */
$.Model.extend('Wms.Models.ProdutoEmbalagem',
    /* @Static */
    {
        /**
         * Retrieves produto_embalagens data from your backend services.
         * @param {Object} params params that might refine your results.
         * @param {Function} success a callback function that returns wrapped produto_embalagem objects.
         * @param {Function} error a callback function for an error in the ajax request.
         */
        findAll: function( params, success, error ){
            $.ajax({
                url: URL_MODULO + '/produto-embalagem/list-json',
                type: 'post',
                dataType: 'json',
                data: params,
                success: this.callback(['wrapMany',success]),
                error: error
            });
        },
        /**
         * Updates a produto_embalagem's data.
         * @param {String} id A unique id representing your produto_embalagem.
         * @param {Object} attrs Data to update your produto_embalagem with.
         * @param {Function} success a callback function that indicates a successful update.
         * @param {Function} error a callback that should be called with an object of errors.
         */
        update: function( id, attrs, success, error ){
            //alert('atualizando');
            $.ajax({
                url: URL_MODULO + '/produto-embalagem/edit/id/' + id,
                type: 'post',
                dataType: 'json',
                data: attrs,
                success: success,
                error: error
            });
        },
        /**
         * Destroys a produto_embalagem's data.
         * @param {String} id A unique id representing your produto_embalagem.
         * @param {Function} success a callback function that indicates a successful destroy.
         * @param {Function} error a callback that should be called with an object of errors.
         */
        destroy: function( id, success, error ){
            $.ajax({
                url: URL_MODULO + '/produto-embalagem/delete/id/'+id,
                type: 'post',
                dataType: 'json',
                success: success,
                error: error
            });
        },
        /**
         * Creates a produto_embalagem.
         * @param {Object} attrs A produto_embalagem's attributes.
         * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
         * @param {Function} error a callback that should be called with an object of errors.
         */
        create: function( attrs, success, error ){
            alert('inserindo');
            $.ajax({
                url: URL_MODULO + '/produto-embalagem/add',
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
