/**
 * Controller para administrar Embalagens dos produtos
 */
$.Controller.extend('Wms.Controllers.ProdutoEmbalagem',
        /* @Static */
                {
                    pluginName: 'produtoEmbalagem'

                },
                /* @Prototype */
                        {
                            /**
                             * Ações à serem executadas ao carregar documento
                             */
                            "{window} load": function () {
                                // verifica se lista de embalagens ja foi carregada
                                if (!$("#div-lista-embalagens").length) {
                                    $("#fieldset-embalagens-cadastradas").append($('<div/>').attr('id', 'div-lista-embalagens'));
                                    var idProduto = $('#embalagem-idProduto').val();
                                    var grade = $('#embalagem-grade').val();
                                    if (idProduto !== '' && grade !== '') {
                                        Wms.Models.ProdutoEmbalagem.findAll({
                                            idProduto: idProduto,
                                            grade: grade
                                        }, this.callback('list'));
                                    }
                                }
                            },

                            /**
                             *
                             * @param {jQuery} el A jQuery wrapped element.
                             * @param {Event} ev A jQuery event whose default action is prevented.
                             */
                            "#ativarDesativar click": function (el, ev) {
                                var check = $(el).parent('div').find('.ativarDesativar');
                                var date = $(el).parent('div').find('.dataInativacao');
                                var div = $(el).parent('div').parent('td');
                                var este = this;

                                $.ajax({
                                    url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                                    type: 'post',
                                    dataType: 'json',
                                    success: function (data) {
                                        if (data === 'N') {
                                            check.checked = !check.is(":checked");
                                            check.prop("checked", !check.is(":checked"));
                                            este.dialogAlert("Não é permitido ativar/inativar embalagens no WMS");
                                            return false;
                                        } else {
                                            if (check.is(":checked") === true) {
                                                if (date.text() === "EMB. ATIVA") {
                                                    var today = new Date();
                                                    var dd = today.getDate();
                                                    var mm = today.getMonth() + 1;
                                                    var yyyy = today.getFullYear();

                                                    if (dd < 10) {
                                                        dd = '0' + dd
                                                    }
                                                    if (mm < 10) {
                                                        mm = '0' + mm
                                                    }
                                                    today = dd + '/' + mm + '/' + yyyy;

                                                    date.text(today);
                                                }
                                                div.css("color", "red");
                                            } else {
                                                date.text("EMB. ATIVA");
                                                div.css("color", "green");
                                            }
                                        }
                                    }
                                });
                            },

                            /**
                             * Responds to the create form being submitted by creating a new Wms.Models.ProdutoEmbalagem.
                             *
                             * @param {jQuery} el A jQuery wrapped element.
                             * @param {Event} ev A jQuery event whose default action is prevented.
                             */
                            '#btn-salvar-embalagem click': function (el, ev) {

                                var fieldEmbalagem = $('#fieldset-embalagem');
                                var valores = fieldEmbalagem.formParams(false).embalagem;
                                var id = $("#fieldset-embalagem #embalagem-id").val();
                                var este = this;

                                if (!this.verificarEmbalagemRecebimento(id, valores)) {
                                    return false;
                                }

                                if (este.verificarCodigoBarras(valores)) {
                                    este.salvarDadosEmbalagem(valores);
                                } else {
                                    $('#embalagem-codigoBarras').focus();
                                }

                                if (fieldEmbalagem.find(".invalid").length > 0) {
                                    este.dialogAlert("Os campos em vermelho são obrigatórios");
                                    return false
                                }
                                ev.preventDefault();
                            },

                            salvarDadosEmbalagem: function (valores) {
                                var id = valores.id;
                                valores.lblIsPadrao = $('#fieldset-embalagem #embalagem-isPadrao option:selected').text();
                                valores.lblCBInterno = $('#fieldset-embalagem #embalagem-CBInterno option:selected').text();
                                valores.lblImprimirCB = $('#fieldset-embalagem #embalagem-imprimirCB option:selected').text();
                                valores.lblEmbalado = $('#fieldset-embalagem #embalagem-embalado option:selected').text();

                                if (valores.acao === 'incluir') {
                                    valores.dataInativacao = 'EMB. ATIVA';
                                } else {
                                    valores.dataInativacao = $('#fieldset-embalagem #embalagem-dataInativacao').val();
                                    if (valores.dataInativacao !== 'EMB. ATIVA') {
                                        valores.ativarDesativar = ' checked ';
                                    }
                                }

                                if (id !== '') {
                                    valores.acao = id.indexOf('-new') === -1 ? 'alterar' : 'incluir';

                                    this.show(new Wms.Models.ProdutoEmbalagem(valores));
                                } else {
                                    var d = new Date();
                                    valores.id = d.getTime() + '-new';
                                    valores.acao = 'incluir';
                                    $('#div-lista-embalagens').append(this.view("show", new Wms.Models.ProdutoEmbalagem(valores)));
                                }
                                var value = parseFloat(valores.quantidade.replace(',', '.')).toFixed(3);
                                var dsc = valores.descricao + ' (' + valores.quantidade + ')';
                                $('#fieldset-campos-comuns #embalagem-fator').append('<option value="' + value + '" label="' + dsc + '">' + dsc + '</option>');
                                $('#fieldset-grupo-normas #embalagens-norma').append('<option value="' + value + '" label="' + dsc + '">' + dsc + '</option>');
                                // limpo form
                                this.resetarForm();
                                // carregar embalagens nos dados logisticos
                                this.carregarSelectEmbalagens(valores);
                            },

                            /**
                             * Creates and places the edit interface.
                             * @param {jQuery} el The produto_embalagem's edit link element.
                             * @param {Event} ev A jQuery event whose default action is prevented.
                             */
                            '.btn-editar-embalagem click': function (el, ev) {
                                $.ajax({
                                    url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                                    type: 'post',
                                    dataType: 'json',
                                    success: function (data) {
                                        if (data === 'N') {
                                            $('#fieldset-embalagem #embalagem-codigoBarras').attr("disabled", true);
                                            $('#fieldset-embalagem #embalagem-quantidade').attr("disabled", true);
                                        }
                                    }
                                });

                                var permiteAlterarcao = true;
                                $.ajax({
                                    url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                                    type: 'post',
                                    async: false,
                                    dataType: 'json',
                                    success: function (data) {
                                        if (data === 'N') {
                                            permiteAlterarcao = false;
                                        }
                                    }
                                });

                                ev.stopPropagation();
                                var produto_embalagem = el.closest('.produto_embalagem').model();

                                // campos da embalagem
                                var inputsEmbalagem = $('#div-lista-embalagens input');
                                // controle da quantidade de embalagens do tipo recebimento
                                var qtdEmbalagensRecebimento = 0;
                                // controle da quantidade de embalagens do tipo expedicao
                                var qtdEmbalagensExpedicao = 0;

                                // verifico se existe embalagem de recebimento
                                inputsEmbalagem.each(function (i, v) {

                                    if (this.className == 'isPadrao') {
                                        if (this.value == 'S') {
                                            // incremento a qtd de embalagens de recebimento cadastradas
                                            qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                                        } else {
                                            // incremento a qtd de embalagens de expedicao cadastradas
                                            qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                                        }
                                    }
                                });

                                $('#fieldset-embalagem #embalagem-quantidade').attr('disabled', false);
                                //caso embalagem de recebimento, não pode alterar a quantidade
                                if ((produto_embalagem.isPadrao == 'S') && (qtdEmbalagensExpedicao > 0)) {
                                    $('#fieldset-embalagem #embalagem-quantidade').attr('disabled', true);
                                }

                                // altera informacao
                                $('#fieldset-embalagem legend').html('Editando embalagem');
                                $('#fieldset-embalagem #btn-salvar-embalagem').val('Atualizar');
                                $('#embalagem-acao').val('alterar');
                                // carrega dados
                                $('#fieldset-embalagem #embalagem-id').val(produto_embalagem.id);
                                $('#fieldset-embalagem #embalagem-isPadrao').val(produto_embalagem.isPadrao);
                                $('#fieldset-embalagem #embalagem-imprimirCB').val(produto_embalagem.imprimirCB);
                                $('#fieldset-embalagem #embalagem-endereco').val(produto_embalagem.endereco).removeClass("invalid");
                                $('#fieldset-embalagem #embalagem-embalado').val(produto_embalagem.embalado);
//                                $('#fieldset-embalagem #embalagem-capacidadePicking').val(produto_embalagem.capacidadePicking);
                                $('#fieldset-embalagem #embalagem-enderecoAntigo').val(produto_embalagem.endereco);
//                                $('#fieldset-embalagem #embalagem-pontoReposicao').val(produto_embalagem.pontoReposicao);
                                $('#fieldset-embalagem #embalagem-dataInativacao').val(produto_embalagem.dataInativacao);
                                $('#fieldset-embalagem #embalagem-CBInterno').val(produto_embalagem.CBInterno);
                                $('#fieldset-embalagem #embalagem-codigoBarras').val(produto_embalagem.codigoBarras).removeClass("invalid");
                                $('#fieldset-embalagem #embalagem-codigoBarrasAntigo').val(produto_embalagem.codigoBarras);
                                $('#fieldset-embalagem #embalagem-descricao').val(produto_embalagem.descricao).removeClass("invalid");
                                $('#fieldset-embalagem #embalagem-quantidade').val(produto_embalagem.quantidade).removeClass("invalid");

                                $('#span-capacidade').remove();
                                $('#span-reposicao').remove();

                                if (!permiteAlterarcao) {
                                    $('#fieldset-embalagem #embalagem-CBInterno').prop("disabled", true);
                                    $('#fieldset-embalagem #embalagem-codigoBarras').prop("disabled", true);
                                    $('#fieldset-embalagem #embalagem-codigoBarrasAntigo').prop("disabled", true);
                                    $('#fieldset-embalagem #embalagem-descricao').prop("disabled", true);
                                    $('#fieldset-embalagem #embalagem-quantidade').prop("disabled", true);
                                }

                                // checa opcoes de Codigo de Barras Interno
                                this.checarCBInterno();

                            },

                            /**
                             * Handle's clicking on a produto_embalagem's destroy link.
                             */
                            '.btn-excluir-embalagem click': function (el, ev) {

                                var model = el.closest('.produto_embalagem').model();
                                var id = model.id.toString();
                                var este = this;

                                if (id.indexOf('-new') !== -1) {
                                    //limpa o ID
                                    this.deleteConfirmed(model);
                                    return true;
                                }

                                var temReserva = false;
                                $.ajax({
                                    url: URL_MODULO + '/produto-embalagem/verificar-estoque-reserva-ajax',
                                    type: 'POST',
                                    data: {id: id},
                                    async: false,
                                    success: function (data) {
                                        if (data.status === 'error') {
                                            este.dialogAlert(data.msg);
                                            temReserva = true;
                                        }
                                    }
                                });

                                if (temReserva)
                                    return false;

                                var idProduto = $('#embalagem-idProduto').val();
                                var grade = $('#embalagem-grade').val();
                                var enderecoAntigo = model.endereco.toString();
                                var temEstoque = false;
                                $.ajax({
                                    url: URL_MODULO + '/endereco/verificar-estoque-ajax',
                                    type: 'POST',
                                    async: false,
                                    data: {
                                        enderecoAntigo: enderecoAntigo,
                                        grade: grade,
                                        produto: idProduto
                                    },
                                    success: function (data) {
                                        if (data.status === 'error') {
                                            este.dialogAlert(data.msg);
                                            temEstoque = true;
                                        }
                                    }
                                });
                                if (temEstoque)
                                    return false;

                                var count = 0;
                                //Verifica se existe dados logisticos com esta embalagem
                                $('.produto_dado_logistico input.idEmbalagem').each(function () {
                                    if (this.value === id) {
                                        count++
                                    }
                                });
                                if (count > 0) {
                                    este.dialogAlert('Não é possível excluir esta embalagem. <br>Remova os dados logísticos cadastrados dela primeiro.');
                                    return false;
                                }

                                var isPadrao = $(el).parent('div').find('.isPadrao').val();
                                // caso seja uma embalagem de recebimento
                                if (isPadrao === 'S') {
                                    // embalagens
                                    var inputsIsPadrao = $('#div-lista-embalagens input.isPadrao');
                                    // controle da quantidade de embalagens do tipo expedicao
                                    var qtdEmbalagensExpedicao = 0;

                                    // verifico se existe embalagem de recebimento
                                    inputsIsPadrao.each(function (i, v) {
                                        if (this.value === 'N')
                                            qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                                    });

                                    if (qtdEmbalagensExpedicao > 0)
                                        this.dialogAlert('Remova as embalagens de expedição antes de remover a de recebimento.');
                                }

                                var permisao = true;
                                $.ajax({
                                    url: URL_MODULO + '/produto/verificar-parametro-codigo-barras-ajax',
                                    type: 'post',
                                    async: false,
                                    dataType: 'json',
                                    success: function (data) {
                                        if (data === 'N') {
                                            este.dialogAlert("Pelos parâmetros definidos <br>Não é permitido excluir embalagens no WMS apenas no ERP");
                                            permisao = false;
                                        }
                                    }
                                });

                                if (!permisao)
                                    return false;

                                this.dialogConfirm("Tem certeza que deseja excluir esta embalagem?", this.callback("deleteConfirmed"), {model: model});

                            },

                            deleteConfirmed: function (params) {
                                var model = params.model;
                                var id = model.id.toString();

                                $('#fieldset-embalagem #embalagem-enderecoAntigo').val(model.endereco);

                                $('<input/>', {
                                    name: 'embalagens[' + id + '][acao]',
                                    value: 'excluir',
                                    type: 'hidden'
                                }).appendTo('#fieldset-embalagens-cadastradas');

                                //remove a div do endereco
                                model.elements().remove();
                                //reseta o form
                                this.resetarForm();
                                // carregar embalagens nos dados logisticos
                                this.carregarSelectEmbalagens(params);
                            },

                            /**
                             * Exibe a lista de produto_embalagens e submete o formulario
                             *
                             * @param {Array} produto_embalagens Matriz de objetos Wms.Models.ProdutoEmbalagem.
                             */
                            list: function (produto_embalagens) {
                                $('#div-lista-embalagens').html(this.view('init', {
                                    produto_embalagens: produto_embalagens
                                }));

                                // carregar embalagens nos dados logisticos
                                this.carregarSelectEmbalagens(produto_embalagens);
                            },

                            /**
                             * @param {jQuery} el A jQuery wrapped element.
                             * @param {Event} ev A jQuery event whose default action is prevented.
                             */
                            '##embalagem-CBInterno change': function (el, ev) {
                                var inptCodBarras = $('#embalagem-codigoBarras');
                                if (el.val() === "S") {
                                    $('#embalagem-imprimirCB').val('S');
                                    inptCodBarras.removeClass("required invalid").val('').attr('readonly', true);
                                } else if (el.val() === "N") {
                                    $('#embalagem-imprimirCB').val('N');
                                    inptCodBarras.addClass("required").val('').attr('readonly', false);
                                }
                            },

                            '#embalagem-endereco change': function (el, ev) {
                                var fieldEmbalagem = $('#fieldset-campos-comuns');
                                var valores = fieldEmbalagem.formParams(false).embalagem;
                                $('.ui-icon.ui-icon-closethick').click();
                                if (!this.verificarEndereco(valores)) {
                                    $('#fieldset-campos-comuns #embalagem-endereco').val('');
                                }
                                ev.stopImmediatePropagation();
                                ev.stopPropagation();

                            },
                            '#embalagem-fator change': function (el, ev) {
                                var capacidade = parseFloat($('#fieldset-campos-comuns #capacidadePicking-real').val()).toFixed(3);
                                var pontoRep = $('#fieldset-campos-comuns #pontoReposicao-real').val();
                                var altura = $('#fieldset-campos-comuns #altura-real').val();
                                var peso = parseFloat($('#fieldset-campos-comuns #peso-real').val().replace(',', '.')) * el.val();
                                var cubagemReal = (parseFloat(altura.replace(',', '.')) * el.val());
                                $('#fieldset-campos-comuns #embalagem-capacidadePicking').val(parseFloat(capacidade / el.val()).toFixed(3).replace('.', ','));
                                $('#fieldset-campos-comuns #embalagem-pontoReposicao').val(parseFloat(pontoRep / el.val()).toFixed(3).replace('.', ','));
                                $('#fieldset-campos-comuns #embalagem-altura').val(cubagemReal.toFixed(3).replace('.', ','));
                                $('#fieldset-campos-comuns #embalagem-peso').val(peso.toFixed(3).replace('.', ','));
                                $('#embalagem-largura').change();
                                ev.stopImmediatePropagation();
                            },

                            '#embalagem-pontoReposicao change': function (el, ev) {
                                var fator = parseFloat($("#embalagem-fator option:selected").val().replace(',', '.'));
                                var qtdMaior = 0;
                                $('.qtdItens').each(function () {
                                    if (parseInt($(this).val()) > parseInt(qtdMaior)) {
                                        qtdMaior =  parseFloat($(this).val().replace(',', '.'));
                                    }
                                });
                                // if (((parseFloat(el.val().replace(',', '.')) * fator) % qtdMaior) !== 0) {
                                //     this.dialogAlert('<b>Ponto de Reposição</b> deve ser múltiplo da <b>Quantidade de itens</b>');
                                //     el.val(parseFloat(parseFloat($('#fieldset-campos-comuns #pontoReposicao-real').val().replace(',', '.')).toFixed(3) / fator).toFixed(3).replace('.', ','));
                                //     return false;
                                // }

                                $('#fieldset-campos-comuns #pontoReposicao-real').val(parseFloat(fator * parseFloat(el.val())).toFixed(3));
                                ev.stopImmediatePropagation();
                            },
                            '#embalagem-capacidadePicking change': function (el, ev) {
                                var fator = parseFloat($("#embalagem-fator option:selected").val().replace(',', '.'));
                                var qtdMaior = 0;
                                $('.qtdItens').each(function () {
                                    if (parseInt($(this).val()) > parseInt(qtdMaior)) {
                                        qtdMaior =  parseFloat($(this).val().replace(',', '.'));
                                    }
                                });
                                // if (((parseFloat(el.val().replace(',', '.')) * fator) % qtdMaior) !== 0) {
                                //     this.dialogAlert('<b>Capacidade de Picking</b> deve ser múltiplo da <b>Quantidade de itens</b>');
                                //     el.val(parseFloat(parseFloat($('#fieldset-campos-comuns #capacidadePicking-real').val().replace(',', '.')).toFixed(3) / fator).toFixed(3).replace('.', ','));
                                //     return false;
                                // }

                                $('#fieldset-campos-comuns #capacidadePicking-real').val(parseFloat(fator * parseFloat(el.val())).toFixed(3));
                                ev.stopImmediatePropagation();
                            },
                            '#embalagem-altura change': function (el, ev) {
                                var qtdSel = parseInt($('#embalagem-fator').val());
                                var altura = $('#embalagem-altura').val();
                                var altruaReal = altura.replace(',', '.') / qtdSel;
                                $('#altura-real').val(altruaReal);

                                var largura = $('#embalagem-largura').val().replace('.', '').replace(',', '.');
                                altura = $('#embalagem-altura').val().replace('.', '').replace(',', '.');
                                var profundidade = $('#embalagem-profundidade').val().replace('.', '').replace(',', '.');
                                var cubagem = Wms.Controllers.CalculoMedida.prototype.calculaCubagem(largura, altura, profundidade, 4);
                                cubagem = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagem.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
                                $('#embalagem-cubagem').val(cubagem);
                                ev.stopImmediatePropagation();
                            },

                            '#embalagem-peso change': function (el, ev) {
                                var qtdSel = parseInt($('#embalagem-fator').val());
                                var peso = $('#embalagem-peso').val();
                                var pesoReal = peso.replace(',', '.') / qtdSel;
                                $('#peso-real').val(pesoReal);
                                ev.stopImmediatePropagation();
                            },
                            /**
                             * Calculo de cubagem
                             */
                            '.parametro-cubagem change': function () {
                                var largura = $('#embalagem-largura').val().replace('.', '').replace(',', '.');
                                var altura = $('#embalagem-altura').val().replace('.', '').replace(',', '.');
                                var profundidade = $('#embalagem-profundidade').val().replace('.', '').replace(',', '.');
                                var cubagem = Wms.Controllers.CalculoMedida.prototype.calculaCubagem(largura, altura, profundidade, 4);

                                cubagem = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagem.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
                                $('#embalagem-cubagem').val(cubagem);

                            },
                            /**
                             * Reseta o form base para novo cadastro
                             */
                            resetarForm: function () {
                                if ($('.produto_embalagem').size() >= 1)
                                    $('#embalagem-isPadrao').val('N');

                                $('#embalagem-quantidade').attr('disabled', false);
                                $('#embalagem-descricao, #embalagem-quantidade, #embalagem-id, #embalagem-codigoBarras, #embalagem-codigoBarrasAntigo').val('');
//                                $('#embalagem-pontoReposicao, #embalagem-capacidadePicking').val('0');
                                $('#embalagem-isPadrao').val('N').attr('disabled', false);
                                $('#embalagem-codigoBarras').attr('disabled', false);
                                $('#embalagem-capacidadePicking').attr('disabled', false);
                                $('#embalagem-pontoReposicao').attr('disabled', false);
                                $('#embalagem-acao').val('incluir');

                                $('#embalagem-CBInterno, #embalagem-imprimirCB, #embalagem-embalado').val('N');

                                $('#btn-salvar-embalagem').val('Adicionar');
                                $('#fieldset-embalagem legend').html('Criar Novo');
                            },

                            /**
                             * Valida as embalagens cadastradas
                             */
                            verificarEmbalagemRecebimento: function (id, valores) {

                                // constantes tipo comercializacao
                                var UNITARIO = 1;
                                var COMPOSTO = 2;
                                var KIT = 3;

                                // acao do form
                                var inputAcao = $('#embalagem-acao').val();
                                // variaveis
                                var qtdEmbalagensCadastradas = $('.produto_embalagem').size();
                                // campos da embalagem
                                var inputsEmbalagem = $('#div-lista-embalagens input');
                                // controle da quantidade de embalagens do tipo recebimento
                                var qtdEmbalagensRecebimento = 0;
                                // controle da quantidade de embalagens do tipo expedicao
                                var qtdEmbalagensExpedicao = 0;
                                // quantidades de itens da embalagem de recebimento cadastrada
                                var qtdItemEmbalagemRecebimento = 0;

                                // verifico se existe embalagem de recebimento
                                inputsEmbalagem.each(function (i, v) {

                                    if (this.className == 'isPadrao') {
                                        if (this.value == 'S') {
                                            // adiciono o valor da embalagem de recebimento
                                            qtdItemEmbalagemRecebimento = parseFloat($(this).parent('div').find('.qtdItens').val().replace(',', '.'));
                                            // incremento a qtd de embalagens de recebimento cadastradas
                                            qtdEmbalagensRecebimento = qtdEmbalagensRecebimento + 1;
                                        } else
                                            qtdEmbalagensExpedicao = qtdEmbalagensExpedicao + 1;
                                    }
                                });

                                // se não houver embalagem cadastrada
                                if (qtdEmbalagensCadastradas == 0) {
                                    // caso primeira embalagem seja de expedicao, lanco erro
                                    if ($('#embalagem-isPadrao').val() == 'N') {
                                        this.dialogAlert('A primeira embalagem deve ser de recebimento. <br>Altere o padrão recebimento para SIM.');
                                        return false;
                                    }
                                }

                                // caso cadastro de nova embalagem
                                switch (inputAcao) {
                                    case 'incluir':
                                        // o tipo de comercializacao do produto seja composto e já tenha outras embalagens cadastradas lanço o erro
                                        //if ( ( $('#produto-idTipoComercializacao').val() == COMPOSTO ) && ( qtdEmbalagensCadastradas >= 1 ) ) {
                                        //    alert('O tipo de comercialização deste produto é Composto. \n Só é possível cadastrar uma Embalagem por produto.');
                                        //    return false;
                                        //}

                                        // controle de cadastro de embalagens de recebimento
                                        if ((qtdEmbalagensRecebimento >= 1) && ($('#embalagem-isPadrao').val() == 'S')) {
                                            this.dialogAlert('O produto deve conter APENAS uma embalagem cadastrada do tipo recebimento. Altere "Embalagem de Recebimento" para "Não"');
                                            return false;
                                        }
                                        break;
                                    case 'alterar':
                                        // verifico a possibilidade de editar embalagens de recebimento.
                                        // caso haja embalagens de expedicao dar alerta.
                                        //if ( ( $('#embalagem-isPadrao').val() == 'S' ) && ( qtdEmbalagensExpedicao > 0 ) ) {
                                        //    alert('Remova as embalagens de expedição para poder editar esta embalagem de recebimento.');
                                        //    return false;
                                        //}
                                        if ((qtdEmbalagensRecebimento >= 1) && ($('#embalagem-isPadrao').val() == 'S')) {
                                            if ($('#embalagem-id').val() != $('.embalagem-id').val()) {
                                                this.dialogAlert('O produto deve conter APENAS uma embalagem cadastrada do tipo recebimento. Altere "Embalagem de Recebimento" para "Não"');
                                                return false;
                                            }
                                        }
                                        break;

                                }

                                // calculo de quantidade de itens para embalagens de expedição de produto unitarios
                                if (($('#produto-idTipoComercializacao').val() == UNITARIO) && ($('#embalagem-isPadrao').val() == 'N')) {
                                    var qtdItensEmbalagem = parseFloat($('#embalagem-quantidade').val().replace(',', '.'));

                                    if (qtdItemEmbalagemRecebimento < qtdItensEmbalagem) {
                                        this.dialogAlert('Quantidade de itens da embalagem de expedição, deve ser menor ou igual da quantidade de itens da embalagem de recebimento.');
                                        return false;
                                    }

                                    //multiplicacao para transformar itens em inteiro para nao ter erro de divisao causado pela linguagem
                                    qtdItemEmbalagemRecebimento = qtdItemEmbalagemRecebimento * 100;
                                    qtdItensEmbalagem = qtdItensEmbalagem * 100;
                                    if (((qtdItemEmbalagemRecebimento) % (qtdItensEmbalagem)) !== 0) {
                                        this.dialogAlert('Quantidade de itens da embalagem de expedição deve ser multipla da quantidade de itens da embalagem de recebimento.');
                                        return false;
                                    }
                                }

                                return true;
                            },

                            /**
                             * Shows a produto_embalagem's information.
                             */
                            show: function (produto_embalagem) {
                                produto_embalagem.elements().replaceWith(this.view('show', produto_embalagem));
                            },

                            /**
                             * Carrega todas as embalagens cadastras no select dos dados logisticos
                             */
                            carregarSelectEmbalagens: function (produto_embalagem) {
                                //
                                var select = $('select#dadoLogistico-idEmbalagem');
                                var blocosEmbalagem = $('div.produto_embalagem');

                                // remove all
                                select.find('option').remove();
                                if (produto_embalagem.length > 0) {
                                    produto_embalagem.forEach(function (valor, chave) {
                                        var value = valor.quantidade;
                                        var dsc = valor.descricao + ' (' + valor.quantidade + ')';
                                        $('#fieldset-campos-comuns #embalagem-fator').append('<option value="' + value + '" label="' + dsc + '">' + dsc + '</option>');

                                    });
//                                    setTimeout(function () {
//                                        produto_embalagem.forEach(function (valor, chave) {
//                                            var dsc = valor.descricao + ' (' + valor.quantidade + ')';
//                                            $('#fieldset-grupo-normas #embalagens-norma').append('<option embalagem="' + valor.id + '" value="' + valor.quantidade + '" label="' + dsc + '">' + dsc + '</option>');
//                                        });
//                                    }, 2000);
                                }
//                                var produto_embalagem = el.closest('.produto_embalagem').model();
                                if (produto_embalagem.length > 0) {
                                    $('#fieldset-campos-comuns #embalagem-capacidadePicking').val(produto_embalagem[0].capacidadePicking);
                                    $('#fieldset-campos-comuns #embalagem-endereco').val(produto_embalagem[0].endereco);
                                    $('#fieldset-campos-comuns #embalagem-enderecoAntigo').val(produto_embalagem[0].endereco);
                                    $('#fieldset-campos-comuns #embalagem-pontoReposicao').val(produto_embalagem[0].pontoReposicao);
                                    $('#fieldset-campos-comuns #embalagem-largura').val(produto_embalagem[0].largura);
                                    $('#fieldset-campos-comuns #embalagem-peso').val(produto_embalagem[0].peso);
                                    $('#fieldset-campos-comuns #embalagem-cubagem').val(produto_embalagem[0].cubagem);
                                    $('#fieldset-campos-comuns #embalagem-profundidade').val(produto_embalagem[0].profundidade);
                                }

                                blocosEmbalagem.each(function () {
                                    var id = $(this).find('.embalagem-id').val();
                                    var descricao = $(this).find('.embalagem-descricao').val() + ' ( ' + $(this).find('.qtdItens').val() + ' )';
                                    select.append('<option qtdEmb="' + parseFloat($(this).find('.qtdItens').val()).toFixed(3) + '" value="' + id + '">' + descricao + '</option>');
                                });
                                var qtdPadrao = 1;
                                if (produto_embalagem.length > 0) {
                                    produto_embalagem.forEach(function (valor, chave) {
                                        if (valor.isPadrao == 'S') {
                                            qtdPadrao = parseInt(valor.quantidade);
                                            $('#fieldset-campos-comuns #embalagem-altura').val(valor.altura);
                                            $('#fieldset-campos-comuns #embalagem-largura').val(valor.largura);
                                            $('#fieldset-campos-comuns #embalagem-peso').val(valor.peso);
                                            $('#fieldset-campos-comuns #embalagem-cubagem').val(valor.cubagem);
                                            $('#fieldset-campos-comuns #embalagem-profundidade').val(valor.profundidade);
                                        }
                                    });
                                    $('#fieldset-campos-comuns #altura-real').val((parseFloat(produto_embalagem[0].altura.replace(',', '.')) / parseInt(qtdPadrao)).toFixed(5));
                                    $('#fieldset-campos-comuns #peso-real').val((parseFloat(produto_embalagem[0].peso.replace(',', '.')) / parseInt(qtdPadrao)).toFixed(5));
                                    $('#embalagem-fator option[value=' + qtdPadrao + ']').attr('selected', 'selected');
                                    $('#fieldset-campos-comuns #embalagem-capacidadePicking').val(parseFloat(parseFloat(produto_embalagem[0].capacidadePicking) / qtdPadrao).toFixed(3).replace('.', ','));
                                    $('#fieldset-campos-comuns #embalagem-pontoReposicao').val(parseFloat(parseFloat(produto_embalagem[0].pontoReposicao / qtdPadrao)).toFixed(3).replace('.', ','));
                                    $('#fieldset-campos-comuns #capacidadePicking-real').val(parseFloat(produto_embalagem[0].capacidadePicking).toFixed(3));
                                    $('#fieldset-campos-comuns #pontoReposicao-real').val(parseFloat(produto_embalagem[0].pontoReposicao).toFixed(3));

                                }
                                Wms.Controllers.Produto.prototype.pesoTotal();
                                Wms.Controllers.Produto.prototype.cubagemTotal();
                            },

                            /**
                             * Retorna o id da embalagem de recebimento
                             */
                            buscarEmbalagemRecebimento: function () {
                                var idEmbalagemRecebimento = 0;
                                var blocosEmbalagem = $('div.produto_embalagem');

                                blocosEmbalagem.each(function () {
                                    var id = $(this).find('.embalagem-id').val();
                                    var isPadrao = $(this).find('.isPadrao').val();

                                    if (isPadrao === 'S') {
                                        idEmbalagemRecebimento = id;
                                    }
                                });

                                return idEmbalagemRecebimento;
                            },

                            /**
                             *
                             */
                            checarCBInterno: function () {
                                var inputCB = $('#embalagem-codigoBarras');
                                if ($('#embalagem-CBInterno').val() === 'S'){
                                    inputCB.attr('readonly', true).removeClass('.invalid').removeClass('.required');
                                } else {
                                    inputCB.attr('readonly', false).addClass('.invalid').addClass('.required')
                                }
                            },

                            dialogAlert: function (msg) {
                                $.wmsDialogAlert({
                                    title: 'Alerta',
                                    msg: msg,
                                    height: 150,
                                    resizable: false
                                });
                            },

                            dialogConfirm: function (msg, callback, params) {
                                return $.wmsDialogConfirm({
                                    title: 'Tem certeza?',
                                    msg: msg
                                }, callback, params);
                            },

                            /**
                             * Verifica se ja existe o codigo de barras informado.
                             */
                            verificarCodigoBarras: function (valores) {
                                var codigoBarras = valores.codigoBarras;
                                var codigoBarrasAntigo = valores.codigoBarrasAntigo;
                                var codigosBarras = $('.codigoBarras');
                                var cbInterno = valores.imprimirCB;
                                var este = this;

                                este.checarCBInterno();

                                if ((codigoBarras === "" && cbInterno === "S") || codigoBarras === codigoBarrasAntigo) {
                                    return true;
                                }

                                // verifico se existe embalagens neste produto com o mesmo codigo de barras
                                codigosBarras.each(function () {
                                    if (this.value === codigoBarras) {
                                        este.dialogAlert("Este código de barras já foi cadastrado neste produto.");
                                        return false;
                                    }
                                });

                                var result = null;
                                $.ajax({
                                    url: URL_MODULO + '/produto/verificar-codigo-barras-ajax',
                                    type: 'post',
                                    async: false,
                                    dataType: 'json',
                                    data: {codigoBarras: codigoBarras}
                                }).success(function (data) {
                                    if (data.status === "success") {
                                        result = true;
                                    } else if (data.status === "error") {
                                        este.dialogAlert(data.msg);
                                        result = false;
                                    }
                                });
                                return result;
                            },

                            /**
                             * Verifica se existe o endereco informado.
                             */
                            verificarEndereco: function (valores) {

                                var endereco = valores.endereco;
                                var enderecoAntigo = valores.enderecoAntigo;
                                var este = this;
                                if (endereco !== enderecoAntigo && endereco !== "") {
                                    var result = null;
                                    $.ajax({
                                        url: URL_MODULO + '/endereco/verificar-endereco-ajax',
                                        type: 'post',
                                        async: false,
                                        dataType: 'json',
                                        data: {
                                            valores: valores
                                        }
                                    }).success(function (data) {
                                        if (data.status === "success") {
                                            result = true;

                                        } else if (data.status === "error") {
                                            este.dialogAlert(data.msg);
                                            result = false;
                                        }
                                    });
                                    return result;
                                } else {
                                    return true;
                                }
                            },

                            updateValores: function (valores) {
                                var endereco = valores.endereco;
                                var pontoReposicao = valores.pontoReposicao * valores.quantidade;
                                var capacidadePicking = valores.capacidadePicking * valores.quantidade;
                                var quantidade = valores.quantidade;
                                $('input.acao').each(function () {
                                    $(this).val('alterar');
                                });
                                $('input.endereco').each(function () {
                                    $(this).val(endereco);
                                });
                                $('input.capacidadePicking').each(function () {
                                    var fator = $(this).parent().find('input.qtdItens').val();
                                    var capacidade = capacidadePicking / fator;
                                    $(this).val(capacidade * fator);
                                    $(this).parent().find('span.capacidadePicking').text(capacidade);
                                });
                                $('input.pontoReposicao').each(function () {
                                    var fator = $(this).parent().find('input.qtdItens').val();
                                    var ponto = pontoReposicao / fator;
                                    $(this).val(ponto * fator);
                                    $(this).parent().find('span.pontoReposicao').text(ponto);
                                });
                                $('span.dscEndereco').each(function () {
                                    $(this).text(endereco);
                                });

                                $('#span-capacidade').remove();
                                $('#span-reposicao').remove();
                            },

                            verificaMultiplos: function (valores) {
                                var quantidade = valores.quantidade;
                                var pontoReposicao = valores.pontoReposicao;
                                var capacidadePicking = valores.capacidadePicking;
                                var restoDivisao = 0;
                                var ret = false;
                                // if ((capacidadePicking % quantidade) == 0) {
                                //     if ((pontoReposicao % quantidade) == 0) {
                                //         ret = true;
                                //     } else {
                                //         this.dialogAlert('<b>Ponto de Reposição</b> deve ser múltiplo da <b>Quantidade de itens</b>');
                                //     }
                                // }
                                // else {
                                //     this.dialogAlert('<b>Capacidade de Picking</b> deve ser múltiplo da <b>Quantidade de itens</b>');
                                // }
                                return ret;
                            }
                        }
                );