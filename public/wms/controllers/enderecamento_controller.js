/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Enderecamento',
    /* @Static */
    {
        pluginName: 'enderecamento'
    },
    /* @Prototype */
    {
        '{window} load' : function() {
            prodId = null;

            $('.imprimir').click(function(){
                tamCheckboxSelecionado = $('[name="palete[]"]:checked').length;

                if (tamCheckboxSelecionado == 0 && tamCheckboxSelecionado != undefined) {
                    alert('É necessário selecionar no mínimo uma U.M.A!');
                    return false;
                }

                var urlImpressao = $(this).attr('href');
                urlImpressao = urlImpressao+'?'+$('#quantidade-grid input:checked').serialize();
                window.location.href = urlImpressao;
                return false;
            });

            $('.btnEnderecar').live('click',function(){

                if ($('#idPessoa').val() == 0) {
                    alert("Por favor selecione um conferente responsável");
                    return false;
                }
                params = '/id/'+$('#id').val()+'/codigo/'+$('#codigo').val()+'/grade/'+encodeURIComponent($('#grade').val());
                urlRedirect = URL_BASE+'/enderecamento/palete/enderecar'+params+'?'+$('#quantidade-grid input:checked').serialize()+'&idPessoa='+$('#idPessoa').val();
                window.location.href = urlRedirect;
                return false;
            });

            $('.ui-tabs-nav-item a').live('click',function(){
                $('#deposito-endereco-filtro-form-container-frag-1').fadeToggle("fast");
            });

            $('.filtro-enderecamento-palete #submit').live('click', function(){
                Wms.Models.Enderecamento.findAll($('#deposito-endereco-filtro-form').serialize());
                return false;
            });

            var numEndSelecionados = null;

            $('#resultado-filtro .selecionar').live('click',function(){
                quantidadeUma = $('#quantidade-grid input:checked').length;
                if ($('#todos:checked').length) {
                    quantidadeUma--;
                }

                if (numEndSelecionados >= quantidadeUma) {
                    alert('Número de endereços máximo atigindo');
                    return false;
                }
                numEndSelecionados++;

                enderecoId = $(this).attr('data-id');
                enderecoName = $(this).attr('data-name');

                if ($('#selecionados tr[data-id="'+enderecoId+'"]').length > 0) {
                    numEndSelecionados--;
                    alert('Endereço já selecionado');
                    return false;
                }

                $("#selecionados table").append(
                    "<tr data-id="+enderecoId+">" +
                    "<td>"+ enderecoName +"<a class='linkremover' href='#'> - remover</a> <input type='hidden' name='enderecos["+enderecoId+"]' /> </td>" +
                    "</tr>"
                );

                $(this).parent().parent().remove();
                return false;
            });

            $('.linkremover').live('click',function(){
                numEndSelecionados--;
                $(this).parent().parent().remove();
            });

            $('.selecionar-endereco').click(function(){
                numEndSelecionados = 0;
            });

            $('#confirmar-selecionados input[type="submit"]').live('click',function(){
                if (numEndSelecionados == 0) {
                    alert("Selecione ao menos um endereço");
                    return false;
                }
                umas = "";
                $('#quantidade-grid input:checked').each(function(i) {
                    umas = umas+','+$(this).val();
                });
                $('#umas').val(umas);
            });

            $('#volumes').parent().hide();
            $('#embalagens').parent().hide();
            $('#lote').parent().hide();
            $('#validade').parent().hide();
            if($('#controle-proprietario').val() != 'S') {
                $('#codPessoa').parent().hide();
            }
            $("#buscarestoque").click(function(){

                if ($('#rua').val() != '' || $('#uma').val() != '') {
                    Wms.Models.Enderecamento.findMovimentacao($('#cadastro-movimentacao').serialize());
                }
                else {
                    if ($("#idProduto").val() == '') {
                        alert("Preencha o código do produto");
                        return false;
                    }
                    Wms.Models.Enderecamento.findMovimentacao($('#cadastro-movimentacao').serialize());
                }

            });

            $(".ctrSize").keyup(function(e){
                if(e.keyCode == 9 || e.keyCode == 16 || e.keyCode == 37 || e.keyCode == 39)
                    return false;

                if ($(this).val().length == $(this).prop("maxlength"))
                {
                    var inputs = $(".ctrSize");
                    inputs.eq(inputs.index($(this)) + 1).focus();
                }
            });

            $('.limparMovimentacao').click(function(){
                $('#volumes').parent().hide();
                $('#volumes').empty();
                $('#cadastro-movimentacao').trigger("reset");
                $('#idProduto').focus();
                return false;
            });

//            $("#cadastro-movimentacao #submit").click(function(){
//                if ($("#rua").val() == '' || $("#predio").val() == '' || $("#nivel").val() == '' || $("#apto").val() == '' || $("#quantidade").val() == '') {
//                    alert("Preencha o endereço e a quantidade");
//                    return false;
//                }
//            });

            //$('#idProduto').focus();

            $('.exportar-saldo-csv').click(function(){
                var urlImpressao = $(this).attr('href');
                urlImpressao = urlImpressao+'?'+$('#filtro-inventario-por-rua').serialize();
                window.location.href = urlImpressao;
                return false;
            });

            idProduto.focusout(function(){
                getVolumes(idProduto.val(),grade.val());
                if (!isEmpty(clicky)) {
                    var id = clicky.attr('id');
                    var value = clicky.attr('value');

                    if (id == 'buscarestoque') {
                        if ($('#rua').val() != '' || $('#uma').val() != '') {
                            Wms.Models.Enderecamento.findMovimentacao($('#cadastro-movimentacao').serialize());
                        } else {
                            if ($("#idProduto").val() == '') {
                                alert("Preencha o código do produto");
                            } else {
                                Wms.Models.Enderecamento.findMovimentacao($('#cadastro-movimentacao').serialize());
                            }
                        }
                    }
//                  else if ((id =='submit') && (value =='Movimentar')) {
//                      if ($("#rua").val() == '' || $("#predio").val() == '' || $("#nivel").val() == '' || $("#apto").val() == '' || $("#quantidade").val() == '') {
//                          alert("Preencha o endereço e a quantidade");
//                      }
//                  }
                }
            });


            $('#idProduto').keypress(function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                prodId = $('#idProduto').val();

                if(keycode == '13') {
                    $('#buscarestoque').click();
                    getVolumes(prodId, 'UNICA');
                } else {
                    return;
                }
            });

            $('#rua, #predio, #nivel, #apto').focusout(function(event){
                $('#endereco_origem').val($('#rua').val() +'.'+ $('#predio').val()
                    +'.'+ $('#nivel').val() +'.'+ $('#apto').val());
            });

            function getVolumes(idProduto,grade){
                $.post("/enderecamento/movimentacao/get-validade/", {idproduto:idProduto, grade:grade}, function(data){
                    var divValidade = $('#validade').parent();
                    var divLote = $('#lote').parent();
                    divValidade.hide();
                    divLote.hide();
                    if (data.validade == 'S') {
                        divValidade.show();
                    }
                    if (data.lote == 'S') {
                        divLote.show();
                    }

                });
                $.post("/enderecamento/movimentacao/volumes/", {idproduto:idProduto, grade:grade},function(dataReturn){
                    $('#volumes').empty();
                    $('#volumes').parent().hide();
                    $('#embalagens').hide();
                    $('#embalagens').parent().hide();

                    if (dataReturn.volumes.length >0) {
                        var options = '<option selected value="">Selecione um agrupador de volumes...</option>';

                        for (var i = 0; i < dataReturn.volumes.length; i++) {
                            options += '<option selected value="' + dataReturn.volumes[i].cod + '">' + dataReturn.volumes[i].descricao + '</option>';
                        }

                        $('#volumes').html(options);
                        $('#volumes').parent().show();
                        $('#volumes').focus();
                    } else {

                        if (dataReturn.embalagens.length >0) {
                            var list= '';
                            list = '<label">Embalagens</label><ul id="embalagens">';
                            for (var i = 0; i < dataReturn.embalagens.length; i++) {
                                list += '<li><h2>' + dataReturn.embalagens[i] + '</h2></li>';
                            }
                            list += '</ul>';

                            $('#embalagens').parent().show();
                            $('#embalagens').parent().html(list)
                        }
                    }
                })
            }

        }

    });