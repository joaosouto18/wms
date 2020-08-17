
let itens = [];
let embs = [];
const groups = {};

function testShowGrid() {
    let grid = $("#gridCorte");
    if (!isEmpty(itens)) {
        grid.show();
    } else {
        grid.hide();
    }
}

function agroupItens() {
    groups.maxByMap = [];
    groups.maxByCli = [];
    $.each(itens, function (i, item) {
        let prop = '';
        let uniqIndex = '';
        if (isEmpty(item.consolidado)) {
            prop = 'maxByMap';
            uniqIndex = item.mapa;
        } else {
            uniqIndex = item.codcli + "-*-" + item.mapa;
            prop = 'maxByCli';
        }
        let index = groups[prop].findIndex(i => i.unikId === uniqIndex)
        if (index < 0) {
            const obj = {
                unikId: uniqIndex,
                qtd: parseFloat(item.quantidadeUnitaria),
                corte: parseFloat(item.qtdCortadaUnitaria),
                conf: parseFloat(item.qtdConf)
            }
            groups[prop].push(obj);
        } else {
            let obj = groups[prop][index];
            obj.qtd += parseFloat(item.quantidadeUnitaria);
            obj.corte += parseFloat(item.qtdCortadaUnitaria);
            groups[prop][index] = obj;
        }
    })
}

function updateList(showColLote)
{
    let newTd = function (content) {
        return $("<td style='text-align:left'></td>").append( content );
    };
    let tbody = $("#result-list");
    tbody.html("");

    agroupItens();

    $.each(itens, function (i, item) {
        let newRow = $("<tr class='gTResultSet' id='row-item-" + i + "' >");

        newRow.append( newTd( $("#gridCorte #idExpedicaoCorte").val() ) );
        newRow.append( newTd( item.carga ) );
        newRow.append( newTd( item.id ) );
        newRow.append( newTd( item.mapa ) );
        newRow.append( newTd( item.dscEndereco ) );
        newRow.append( newTd( item.codcli ) );
        newRow.append( newTd( item.cliente ) );
        newRow.append( newTd( item.itinerario ) );
        newRow.append( newTd( item.quantidade ) );
        if (showColLote) newRow.append( newTd( item.lote ) );

        let forcarEmbVenda = $("#forcarEmbVenda").val();
        let newEmbSelector = "";
        if (!isEmpty(embs)) {
            newEmbSelector = $("<select id='emb-" + i + "' data-index='" + i + "' class='qtdCortar' ></select>");
            if (forcarEmbVenda === 'true') newEmbSelector.prop("disabled", true);
            $.each(embs, function (l, emb) {
                let selecionado = (forcarEmbVenda === 'true' && (emb.fator == item.fatorEmbalagemVenda)) ? "selected" : "";
                newEmbSelector.append("<option " + selecionado + " value='" + emb.id + "' data-index='" + l + "' >" + emb.dscEmb + "</option>");
            });
        }
        newRow.append( newTd( newEmbSelector ) );
        newRow.append( newTd( $("<input style='width:40px;' type='text' class='qtdCortar' id='qtdCortar-" + i + "' data-index='" + i + "'>") ) );
        newRow.append( newTd( item.qtdCortada ) );

        tbody.append(newRow);
    })
}

function getSaldoCorte(index, returnCorte) {

    returnCorte = (!isEmpty(returnCorte)) ? returnCorte : false;

    let item = itens[index];
    let obj = {};
    let totalCorte = getCorte(index);

    if (parseFloat(item.quantidadeUnitaria - item.qtdCortadaUnitaria) < totalCorte) {
        return false;
    }

    if (!isEmpty(item.consolidado)) {
        obj = groups.maxByCli.find(i => i.unikId === item.codcli + "-*-" + item.mapa);
    } else {
        $.each(itens, function (i, itemList) {
            if (item.mapa === itemList.mapa && item.id !== itemList.id) {
                totalCorte += getCorte(i);
            }
        });
        obj = groups.maxByMap.find(i => i.unikId === item.mapa);
    }

    let fator = parseFloat(embs[0].fator);

    if (!returnCorte) {
        let saldo = parseFloat((obj.qtd - parseFloat(obj.corte + obj.conf)) / fator);
        return (saldo > 0 && (totalCorte / fator) <= saldo);
    } else {
        return parseFloat(item.quantidadeUnitaria - item.qtdCortadaUnitaria);
    }
}

function getCorte(i) {
    let indexEmb = $("#emb-"+i).find(":selected").data("index");
    let qtd = $("#qtdCortar-"+i).val();
    return (indexEmb !== undefined && qtd !== undefined) ? parseFloat(qtd * embs[indexEmb].fator) : 0;
}

function msg(input, inputQtd, text) {
    let funct = function (objInput) {
        objInput.inputQtd.val("");
        objInput.inputEven.focus();
    };
    let args = {inputQtd: inputQtd, inputEven: input};

    $.wmsDialogAlert({msg: text },
        funct ,
        args
    );
}

$("select.qtdCortar, input.qtdCortar").live("change", function () {
    event.preventDefault();
    let input = $(this);
    let index = input.data("index");
    let inputQtd = $("#qtdCortar-"+index);
    if (!getSaldoCorte(index))
    {
        let txt = "A quantidade excede o saldo disponível para corte do endereço, do mapa ou do pedido!" +
            "<br><br> Para efetuar o corte: " +
            "<br>Utilize uma embalagem menor, " +
            "<br>Reduza a quantidade, " +
            "<br> Ou reinicie a conferência do item!";
        msg(input, inputQtd, txt);
    }
});

$("#btnCortar").live("click",function () {
    event.preventDefault();

    let cortes = [];
    let errorQtdZero = false;
    $.each(itens, function (i, item) {
        let input = $("#qtdCortar-" + i);
        let qtdCortar = input.val();
        if (qtdCortar < 0) {
            msg(input, input, "A quantidade à ser cortada não pode ser menor que zero");
            errorQtdZero = true;
            return;
        }
        if (!isEmpty(qtdCortar)) {
            var corte = [];
            corte[0] = item.ID;
            corte[1] = $("#emb-" + i).val();
            corte[2] = qtdCortar;
            corte[3] = item.mapa;
            corte[4] = (!isEmpty(item.idEndereco)) ? item.idEndereco : null;
            corte[5] = (!isEmpty(item.lote)) ? item.lote : '';

            cortes[cortes.length] = corte;
        }
    });

    if (errorQtdZero) return;

    if (cortes.length <= 0) {
        $.wmsDialogAlert({msg:"Selecione ao menos um pedido para cortar"});
        return;
    }

    if ($('#motivo').val() == "") {
        $.wmsDialogAlert({msg:"Selecione um motivo de corte"});
        return;
    }

    $.ajax({
        url:  URL_MODULO + '/corte/confirma-corte-produto-ajax/',
        type: 'post',
        data: {
            codProduto: $('#codProduto').val(),
            grade: $('#grade').val(),
            motivo: $('#motivo').val(),
            cortes: cortes,
        },
        success: function (data) {
            if (data.error) {
                $.wmsDialogAlert({msg:data.error});
            } else {
                itens = [];
                testShowGrid();
                $("#result-list").html("");
                $("#motivoCorte").html("");
                $.wmsDialogAlert({msg:"Produto cortado com sucesso"}, function () {
                    $('#grade').val("UNICA");
                    $('#codProduto').val("").focus();
                });
            }
        }
    });
});

$("#corteTotal").live("click", function  () {
    event.preventDefault();
    let temConf = false;
    $.each(itens, function (i, item) {
        let prop = '';
        let uniqIndex = '';
        if (isEmpty(item.consolidado)) {
            prop = 'maxByMap';
            uniqIndex = item.mapa;
        } else {
            prop = 'maxByCli';
            uniqIndex = item.codcli + "-*-" + item.mapa;
        }
        temConf = (groups[prop].findIndex(i => i.unikId === uniqIndex && i.conf > 0) > -1)

        if (temConf) return false;
        $("#emb-"+i).val($("emb-"+i+" option:first").val());
        $("#qtdCortar-"+i).val(getSaldoCorte(i, true));
    });

    if (temConf) {
        $.wmsDialogAlert({msg: "O produto já teve conferências, o corte total não pode ser aplicado! <br> Reinicie a(s) conferência(s) e tente novamente!"});
    }
});

function executeRequest() {
    itens = [];
    testShowGrid();
    $.ajax({
        url: URL_MODULO + '/corte/get-data-produto-corte-ajax/',
        type: 'post',
        data: {
            id: $("#gridCorte #idExpedicaoCorte").val(),
            codProduto: $('#codProduto').val(),
            grade: $('#grade').val()
        },
        success: function (data) {
            if (data.status === "error") {
                $("#motivoCorte").html("");
                $.wmsDialogAlert({msg: data.msg});
            } else {
                itens = data.itens;
                embs = data.embs;
                updateList(data.controlaLote);
                if (data.controlaLote) {
                    $("#colLote").show();
                } else {
                    $("#colLote").hide();
                }
                $("#motivoCorte").html(data.formMotivo);
                testShowGrid();
            }
        }
    });
}

$('#btnSubmit').live("click", function () {
    validate();
});

$("input#codProduto, input#grade").live("keypress", function( event ) {
    if ((event.which === 13 || event.keyCode === 13)) {
        validate();
    }
});

function validate()
{
    if (!isEmpty($("input#codProduto").val())){
        executeRequest();
    }
    else {
        $.wmsDialogAlert({msg: "Informe o código do produto!"});
    }
}
