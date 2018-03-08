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

            $('#aguarde').attr('style','display:none');
            $("#gerar").live('click', function() {
                    $('#gerar').attr('style','display:none');
                    $('#aguarde').attr('style','background-color: lightsteelblue; text-align: center; padding: 5px');
            });

            function processoCancelado() {
                $('#aguarde').attr('style','display:none');
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        $(this).prop('checked', false);
                    }
                });
            }

            /*
             * Valida seleção de expedições
             * @array checkBoxes de expedições
             * return action submit / alert
             */
            $("#gerar").live('click', function() {
                // clickSelection=false;
                // $("input[name*='expedicao[]']").each(function( index, value ){
                //     if ( $(this).prop('checked') ){
                //         clickSelection=true;
                //     }
                // });
                var este = this;

                if ($("input[name*='expedicao[]']:checked").length > 0){
                    var liberado = true;
                    $.ajax({
                        url: URL_BASE + '/expedicao/onda-ressuprimento/verificar-expedicoes-processando-ajax',
                        type: 'post',
                        async: false,
                        dataType: 'json',
                        data: $('#relatorio-picking-listar').serialize()
                    }).success(function (data) {
                        if (data.status === "Ok") {
                            liberado = true;
                        } else if (data.status === "Error") {
                            $.wmsDialogAlert({
                                title: "Notificação!",
                                msg: data.msg
                            }, function(){
                                window.location = URL_BASE + "/expedicao/onda-ressuprimento";
                            });
                            liberado = false;
                            processoCancelado();
                        }
                    });
                    if (liberado) {
                        var msgs = null;
                        var expedicoes = null;
                        $.ajax({
                            url: URL_BASE + "/expedicao/onda-ressuprimento/gerar",
                            type: 'post',
                            async: false,
                            dataType: 'json',
                            data: $('#relatorio-picking-listar').serialize()
                        }).success(function (data) {
                            if (data.status === "Ok") {
                                expedicoes = data.expedicoes;
                                msgs = data.response;
                            } else if (data.status === "Error") {
                                msgs = data.response;
                            }
                        });
                        dispararMsg(msgs);

                        if (expedicoes !== null) {
                            $.wmsDialogConfirm({
                                title: "Sistema!",
                                msg: "Deseja já gerar e imprimir os mapas e etiquetas destas expedições ressupridas?"
                            }, this.callback("gerarMapasEtiquetas"), expedicoes)
                        }
                        //$('#relatorio-picking-listar').submit();
                    }
                } else {
                    alert('Selecione pelo menos uma expedição');
                }
            });

            function dispararMsg(msgs) {
                $.wmsDialogAlert({
                    title: "Notificação!",
                    msg: msgs
                });
            }

            function gerarMapasEtiquetas(expedicoes) {

            }

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
        }


    });