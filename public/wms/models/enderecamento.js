$.Model.extend('Wms.Models.Enderecamento',
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
            url: URL_MODULO + '/endereco/filtrar/',
            type: 'post',
            dataType: 'html',
            data: params,
            error: error,
            success: function(data) {
                $('#resultado-filtro').html (data);
            }
        })
    },

    findMovimentacao: function(params, success, error) {
        $.ajax({
            url: URL_MODULO + '/movimentacao/list/',
            type: 'post',
            dataType: 'html',
            data: params,
            error: error,
            success: function(data) {
                $('#estoque-por-produto').html (data);
            }
        })
    },

    movimentaEstoque: function(params, success, error) {
        listagem = this;
        $.ajax({
            url: URL_MODULO + '/movimentacao/',
            type: 'post',
            dataType: 'json',
            data: params,
            error: error,
            success: function(data) {
                if (data.status == 'success') {
                    $("#rua, #predio, #nivel, #quantidade, #apto").val('');
                    listagem.findMovimentacao($('#cadastro-movimentacao').serialize());
                    if (data.link) {
                        var s = "<a title=\"Imprimir UMA\" href=\"" + data.link +"\">Imprimir uma</a>";
                        $('#link-url').html(s);
                    }
                } else {
                    alert(data.msg);
                }
            }
        })
    }

},
/* @Prototype */
{});
