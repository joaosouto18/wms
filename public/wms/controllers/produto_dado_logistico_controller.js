/**
 * @tag controllers, home
 * Displays a table of produto_dado_logisticos.	 Lets the user 
 * ["Wms.Controllers.ProdutoDadoLogistico.prototype.form submit" create], 
 * ["Wms.Controllers.ProdutoDadoLogistico.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.ProdutoDadoLogistico.prototype.&#46;destroy click" destroy] produto_dado_logisticos.
 */
$.Controller.extend('Wms.Controllers.ProdutoDadoLogistico',
/* @Static */
{
    pluginName: 'produtoDadoLogistico'
},
/* @Prototype */
{
    /**
     * Carrega configuracoes iniciais
     */
    "{window} load": function() {
        if($(".grupoDadosLogisticos").size() == 0) {        
            var idProduto = $('#produto-id').val();
            var grade = $('#produto-grade').val();
        
            if (idProduto != '' && grade != '') {
                Wms.Models.ProdutoDadoLogistico.findNormasPaletizacao({
                    idProduto:idProduto, 
                    grade:grade
                }, this.callback('listNorma'));
            }
        }
    },
    /**
     * Responds to the create form being submitted by creating a new Wms.Models.ProdutoDadoLogistico.
     * @param {jQuery} el A jQuery wrapped element.
     * @param {Event} ev A jQuery event whose default action is prevented.
     */
    '#btn-salvar-dado-logistico click': function(el, ev) {
        // variaveis
        var idTipoComercializacao = parseInt($('#produto-idTipoComercializacao').val());
        var grupoDadosLogisticos = $('#fieldset-grupo-normas').find('div.grupoDadosLogisticos');
        
        //fieldset validation
        if (($('#dadoLogistico-idEmbalagem').val() == "") || ($('#dadoLogistico-idEmbalagem').val() == null)) {
            alert('Escolha uma embalagem');
            $('#dadoLogistico-idEmbalagem').focus();
            return false;
        }
        if($('#dadoLogistico-altura').val() == "") {
            alert('Preecha o Altura');
            $('#dadoLogistico-altura').focus();
            return false;
        }
        if($('#dadoLogistico-largura').val() == "") {
            alert('Preecha a Largura');
            $('#dadoLogistico-largura').focus();
            return false;
        }
        if($('#dadoLogistico-profundidade').val() == "") {
            alert('Preecha o Profundidade');
            $('#dadoLogistico-profundidade').focus();
            return false;
        }
        if($('#dadoLogistico-cubagem').val() == "") {
            alert('Preecha o Cubagem');
            $('#dadoLogistico-cubagem').focus();
            return false;
        }
        if($('#dadoLogistico-peso').val() == "") {
            alert('Preecha o Peso');
            $('#dadoLogistico-peso').focus();
            return false;
        }
        
        // caso a quantidade de grupos de normatizacao seja igual a zero
        if( grupoDadosLogisticos.size() ==  0 ) {
            alert('Crie ao menos uma norma de paletização para criar o volume');
            return false;
        }
        
        var valores = $('#fieldset-dado-logistico').formParams().dadoLogistico;
        var id = $("#dadoLogistico-id").val(); 
        
        // variables
        valores.lblEmbalagem = $('#fieldset-dado-logistico #dadoLogistico-idEmbalagem option:selected').text();
                
        if (id != '') {
            valores.acao = id.indexOf('-new') == -1 ? 'alterar' : 'incluir';
            produto_dado_logistico = new Wms.Models.ProdutoDadoLogistico(valores);
            this.show(produto_dado_logistico);
        } else {
            var d = new Date();
            valores.id = d.getTime()+ '-new';
            valores.idNormaPaletizacao = $(grupoDadosLogisticos[0]).find('.normasPaletizacao-id').val();
            valores.acao = 'incluir';
            produto_dado_logistico = new Wms.Models.ProdutoDadoLogistico(valores);
            $(grupoDadosLogisticos[0]).append( this.view("show", produto_dado_logistico) );
        }
        
        //limpo form
        this.resetarForm();
        //drag and drop dos volumes
        this.dragdropGrupoVolumes();
        //Calcula Peso e Cubagem Total para aba produto
        Wms.Controllers.Produto.prototype.pesoTotal();
        Wms.Controllers.Produto.prototype.cubagemTotal();
        //Calcula o peso para norma de paletizacao
        this.calcularPesoNormaPaletizacao();
        
        ev.preventDefault();
    },
    /**
     * 
     */
    '#fieldset-grupo-normas #btn-add-grupo click': function( el , ev ){
        this.createGroupBlock();
    },
    /**
     * Creates and places the edit interface.
     * @param {jQuery} el The produto_dado_logistico's edit link element.
     */
    '.btn-editar-dado-logistico click': function( el , ev ){
        
        ev.stopPropagation();
        var produto_dado_logistico = el.closest('.produto_dado_logistico').model();
        
        // altera informacao
        $('#fieldset-dado-logistico legend').html('Editando Dado Logistico');
        $('#btn-salvar-dado-logistico').val('Atualizar');
        
        // carrega dados
        $('#dadoLogistico-id').val(produto_dado_logistico.id);
        $('#dadoLogistico-largura').val(produto_dado_logistico.largura);
        $('#dadoLogistico-altura').val(produto_dado_logistico.altura);
        $('#dadoLogistico-profundidade').val(produto_dado_logistico.profundidade);
        $('#dadoLogistico-cubagem').val(produto_dado_logistico.cubagem);
        $('#dadoLogistico-peso').val(produto_dado_logistico.peso);
        $('#dadoLogistico-idNormaPaletizacao').val(produto_dado_logistico.idNormaPaletizacao);
        
        $('#btn-salvar-dado-logistico').val('Editar Dado Logistico');
    },    
    /**
     * Limpa form para cadastros
     */
    resetarForm: function() {        
        //reseta valores
        $("#fieldset-dado-logistico input[type=hidden]").val('');
        $('#dadoLogistico-altura, #dadoLogistico-largura, #dadoLogistico-profundidade, #dadoLogistico-cubagem, #dadoLogistico-peso').val('0,000');
        $('#btn-salvar-dado-logistico').val('Adicionar Dado Logistico');
    },
    /**
     * Shows a produto_dado_logistico's information.
     */
    show: function( produto_dado_logistico ){
        produto_dado_logistico.elements().replaceWith(this.view('show',produto_dado_logistico));
    },
    /**
     * Drag and Drop dos dados logisticos para os grupos
     */
    dragdropGrupoVolumes: function () {
        
        $( "#div-lista-volumes" ).sortable({
            placeholder: "ui-state-highlight"
        });
        
        $( "#div-lista-volumes" ).disableSelection();
        
        $('.produto_dado_logistico').draggable( {
            containment: '#content',
            stack: '#div-lista-volumes div',
            cursor: 'pointer',
            revert: true,
            drag: function(event, ui) {
                $(this).addClass('moveData');
            },
            stop: function(event, ui) {
                $(this).removeClass('moveData');
                // altera valor da norma paletizacao do volume
                var idNormaPaletizacao = $(this).parents('div.grupoDadosLogisticos').find($('input.normasPaletizacao-id')).val();
                $(this).find('input.idNormaPaletizacao').val(idNormaPaletizacao);
                
                Wms.Controllers.ProdutoDadoLogistico.prototype.checarStatuNormasPaletizacao();
                //Calcula o peso para norma de paletizacao
                Wms.Controllers.ProdutoDadoLogistico.prototype.calcularPesoNormaPaletizacao();
            }
        } );
        
        $('.grupoDadosLogisticos').droppable( {
            accept: '.produto_dado_logistico',
            //activeClass: "ui-state-highlight",
            drop: function( event, ui ) {
                ui.draggable.appendTo( this );
                $( "#div-lista-volumes div.ui-draggable-dragging" ).remove();
            }
        } );
    },
    
    /**
     * Checa status dos grupos de norma de paletizacao
     */
    checarStatuNormasPaletizacao: function () {
        var normasPaletizacao = $('.grupoDadosLogisticos');
        
        normasPaletizacao.each(function() {
            
            var qtdVolumes = $(this).find('.produto_dado_logistico').size();
            var inputAcao = $(this).find('input.normasPaletizacao-acao');
            var inputId = $(this).find('input.normasPaletizacao-id');
            
            // controle da acao com o grupo da norma de paletizacao
            if(qtdVolumes == 0)
                inputAcao.val('excluir');
            else {
                if (inputId.val().indexOf('-new') == -1)
                    inputAcao.val('alterar');
                else 
                    inputAcao.val('incluir');
            }
            
        });
    },
    
    /**
     * Cria um grupo para agregar volumes pela norma de paletização
     */
    createGroupBlock : function () {
        // criando um novo objeto grupo
        var valores = new Object();
        var d = new Date();
        valores.acao = 'incluir';
        valores.numLastro = '0';
        valores.numCamadas = '0';
        valores.numNorma = '0';
        valores.numPeso = '0,000';
        valores.unitizadores = eval($('#produto-unitizadores').val());
        
        valores.id = d.getTime()+ '-new';
        norma_paletizacao = new Wms.Models.NormaPaletizacao(valores);
        
        $('#fieldset-grupo-normas').append($.View('//wms/views/norma_paletizacao/show', norma_paletizacao));
        
        //drag and drop dos volumes
        this.dragdropGrupoVolumes();   
    },
    /**
     * Lista de normas de paletizacao
     * @param {Array} produto_dado_logisticos An array of Wms.Models.ProdutoVolume objects.
     */
    listNorma: function( normas_paletizacao ) {
        
        $('#fieldset-grupo-normas').append( $.View('//wms/views/norma_paletizacao/init', {
            normas_paletizacao:normas_paletizacao
        }));

        
        var grupoDadosLogisticos = $('div.grupoDadosLogisticos');
        
        grupoDadosLogisticos.each(function() {
            var normaId = $(this).find($('input.normasPaletizacao-id')).val();
            
            for(var k=0; k<normas_paletizacao.length; k++) {
                var dadosLogisticos = normas_paletizacao[k].dadosLogisticos;
                
                if(normaId != normas_paletizacao[k].id) 
                    continue;
                
                for(var j=0; j<dadosLogisticos.length; j++) {
                    // transformo em um model do tipo produto_dado_logistico
                    var produto_dado_logistico = new Wms.Models.ProdutoDadoLogistico(dadosLogisticos[j]);
                    // mando exibir
                    $(this).append( $.View('//wms/views/produto_dado_logistico/show',  produto_dado_logistico));
                }
            }
        });
        
        // drag and drop dos volumes
        this.dragdropGrupoVolumes();
        //Calcula Peso e Cubagem Total para aba produto
        Wms.Controllers.Produto.prototype.pesoTotal();
        Wms.Controllers.Produto.prototype.cubagemTotal();
        
    },
    
    /**
     *	 Handle's clicking on a produto_volume's destroy link.
     */
    '.btn-excluir-dado-logistico click': function(el, ev){
        //evita a propagação do click para a div
        ev.stopPropagation();
        
        if(confirm("Tem certeza que deseja excluir?")){
            var model = el.closest('.produto_dado_logistico').model();
            var id = model.id.toString();
            
            //se é um endereço existente (não haja a palavra '-new' no id)
            if (id.indexOf('-new') == -1) {
                //limpa o ID
                id.replace('-new', '');
                //adiciona à fila para excluir
                $('<input/>', {
                    name: 'dadosLogisticos[' + id + '][acao]',
                    value: 'excluir',
                    type: 'hidden'
                }).appendTo('.grupoDadosLogisticos'); 
            }
            
            //remove a div do endereco
            model.elements().remove();
            //limpo form
            this.resetarForm();
            
            //Calcula Peso e Cubagem Total para aba produto
            Wms.Controllers.Produto.prototype.pesoTotal();
            Wms.Controllers.Produto.prototype.cubagemTotal();
            //Calcula o peso para norma de paletizacao
            this.calcularPesoNormaPaletizacao();
        }
    },
    
    /**
     * 
     */
    '#btn-excluir-grupo click': function( el , ev ){
        var grupoVolume = el.closest('.grupoDadosLogisticos');
        
        if(grupoVolume.find('.produto_dado_logistico').size() != ''){
            if(confirm("Esta norma de paletização deve está vazia para poder ser excluida.")) {
                return false;
            }
        }else if(confirm("Tem certeza que deseja excluir esta norma de paletização?")){
            grupoVolume.remove();
        }
    },
    
    /**
     * Calculo de cubagem
     */
    '.parametro-cubagem change' : function () {
        var largura = $('#dadoLogistico-largura').val().replace('.','').replace(',','.');
        var altura = $('#dadoLogistico-altura').val().replace('.','').replace(',','.');
        var profundidade = $('#dadoLogistico-profundidade').val().replace('.','').replace(',','.');
        var cubagem = Wms.Controllers.CalculoMedida.prototype.calculaCubagem(largura, altura, profundidade, 4);
        
        cubagem = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(cubagem.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
        $('#dadoLogistico-cubagem').val(cubagem);
    },
    
    /**
     * Calculo de norma de paletizacao
     */
    '.parametro-norma change' : function (el, ev) {
        var grupoVolume = el.closest('.grupoDadosLogisticos');
        var lastro = grupoVolume.find('#normaPaletizacao-numLastro').val().replace('.','').replace(',','.');
        var camadas = grupoVolume.find('#normaPaletizacao-numCamadas').val().replace('.','').replace(',','.');
        var numNormaPaletizacao = Wms.Controllers.CalculoMedida.prototype.calculaNormaPaletizacao(lastro, camadas);

        grupoVolume.find('#normaPaletizacao-numNorma').val(numNormaPaletizacao);
        
        //Calcula o peso para norma de paletizacao
        this.calcularPesoNormaPaletizacao();
    },
    
    /**
     * Calculo de peso para norma de paletizacao
     */
    calcularPesoNormaPaletizacao: function (el, ev) {
        var grupoVolume = $('.grupoDadosLogisticos');
        
        //soma os volumes por grupo
        grupoVolume.each(function(el, ev) {
            var pesoNormaPaletizacao = 0;
            var inputsPeso = $(this).find('.produto_dado_logistico input.peso');
            inputsPeso.each(function(el, ev) {
                pesoVolume = parseFloat(this.value.replace('.','').replace(',','.'));
                pesoNormaPaletizacao = pesoNormaPaletizacao + pesoVolume;
            });
            
            //pega o valor de norma de paletizacao
            var numNormaPaletizacao = $(this).find('#normaPaletizacao-numNorma').val();
            //multiplica norma pelo total do peso
            pesoNormaPaletizacao = numNormaPaletizacao * pesoNormaPaletizacao;

            pesoNormaPaletizacao = Wms.Controllers.CalculoMedida.prototype.formatMoney(parseFloat(pesoNormaPaletizacao.toString().replace(',', '.')).toFixed(3), 3, ',', '.');
            $(this).find('#normaPaletizacao-numPeso').val(pesoNormaPaletizacao);
        });
    }
    
});