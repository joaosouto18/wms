var clickSelection=false;
/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Expedicao',
    /* @Static */
    {
        pluginName: 'expedicao'
    },
    /* @Prototype */
    {
        '{window} load' : function() {

            $('#centrais a').live('click', function() {
                var urlImpressao = $(this).attr('href');
                urlImpressao = urlImpressao+'?'+$('#cargas input:checked').serialize();
                window.location.href = urlImpressao;
                return false;
            });

            /*
             * Exibe/Oculta botão gerar
             * @array checkBoxes de expedições
             */
            $("input[name*='expedicao[]']").live('click', function() {
                $('#gerar').attr('style','display:block');
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    $('#gerar').attr('style','display:block');
                } else {
                    $('#gerar').attr('style','display:none');
                }
            });

            /*
             * Valida seleção de expedições
             * @array checkBoxes de expedições
             * return action submit / alert
             */
            $("#gerar").live('click', function() {
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    $('#relatorio-picking-listar').submit();
                } else {
                    alert('Selecione pelo menos uma expedição');
                }

            });

            grade = $("#grade");

            grade.autocomplete({
                source: "/enderecamento/movimentacao/filtrar/idproduto/",
                minLength: 0
            });

            grade.keyup(function(e){
                if ($("#idProduto").val() == '' || $("#id").val() == '') {
                    return false;
                }
                var produtoVal  = $("#idProduto").val();
                if (typeof  produtoVal == 'undefined') {
                    var produtoVal  = $("#id").val();
                }
                grade.autocomplete({
                    source:"/enderecamento/movimentacao/filtrar/idproduto/"+produtoVal,
                    select: function( event, ui ) {
                        $.getJSON("/enderecamento/movimentacao/volumes/idproduto/"+produtoVal+"/grade/"+encodeURIComponent(ui['item']['value']),function(dataReturn){
                            if (dataReturn.length > 0) {
                                var options = '<option value="">Selecione um agrupador de volumes...</option>';
                                for (var i = 0; i < dataReturn.length; i++) {
                                    options += '<option value="' + dataReturn[i].cod + '">' + dataReturn[i].descricao + '</option>';
                                }
                                $('#volumes').html(options);
                                $('#volumes').parent().show();
                            } else {
                                $('#volumes').empty();
                                $('#volumes').parent().hide();
                            }
                        })
                    }
                });
            });

            $("#filtro-expedicao-mercadoria-form #submit").click(function() {
                var url = location.href;
                console.log(url.indexOf("expedicao/index"));
                if (url.indexOf("expedicao/index") == 17) {
                    console.log(url);
                    $("#filtro-expedicao-mercadoria-form").attr('action', '/expedicao/index');
                }
            });

        }


    });