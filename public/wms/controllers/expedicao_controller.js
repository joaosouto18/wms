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
                $('#modelo-separacao').attr('style','display:block');
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    $('#gerar').attr('style','display:inline');
                    $('#modelo-separacao').attr('style','height: 26px');
                } else {
                    $('#gerar').attr('style','display:none');
                    $('#modelo-separacao').attr('style','display:none');
                }
            });

            $('#aguarde').attr('style','display:none');
            $("#gerar").live('click', function() {
                    $('#gerar').attr('style','display:none');
                    $('#modelo-separacao').attr('style','display:none');
                    $('#aguarde').attr('style','background-color: lightsteelblue; text-align: center; padding: 5px');
            });

            $("#modelo-separacao").live('click', function() {
                $('#gerar').attr('style','display:none');
                $('#modelo-separacao').attr('style','display:none');
            });

            function processoCancelado() {
                $('#aguarde').attr('style','display:none');
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        $(this).prop('checked', false);
                    }
                });
            }

            $('#modelo-separacao').live('click', function () {
                clickSelection=false;
                $("input[name*='expedicao[]']").each(function( index, value ){
                    if ( $(this).prop('checked') ){
                        clickSelection=true;
                    }
                });

                if (clickSelection){
                    var liberado = true;
                    $.ajax({
                        url: URL_BASE + '/expedicao/onda-ressuprimento/modelo-separacao-expedicao-ajax',
                        type: 'post',
                        async: false,
                        dataType: 'html',
                        data: $('#relatorio-picking-listar').serialize()
                    }).success(function (data) {
                        console.log(data);
                        $('#inside-modal-dialog').append(data);
                    });
                } else {
                    alert('Selecione pelo menos uma expedição');
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
                        $('#relatorio-picking-listar').submit();
                    }
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
        }


    });