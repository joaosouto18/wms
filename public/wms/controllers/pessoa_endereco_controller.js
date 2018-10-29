/**
 * @tag controllers, home
 * Displays a table of pessoa_enderecos.	 Lets the user 
 * ["Wms.Controllers.PessoaEndereco.prototype.form submit" create], 
 * ["Wms.Controllers.PessoaEndereco.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.PessoaEndereco.prototype.&#46;destroy click" destroy] pessoa_enderecos.
 */
$.Controller.extend('Wms.Controllers.PessoaEndereco',
/* @Static */
{
    pluginName: 'pessoaEndereco'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all pessoa_enderecos to be displayed.
     */
    "{window} load": function(){
        if(!$("#div-lista-enderecos").length){
            $("#fieldset-enderecos-cadastrados").append($('<div/>').attr('id','div-lista-enderecos'));
            var idPessoa = $('#fieldset-endereco #enderecos-idPessoa').val();
            if (idPessoa != '') {
                Wms.Models.PessoaEndereco.findAll({idPessoa:idPessoa}, this.callback('list'));
            }
        }
    },
    /**
     * Displays a list of pessoa_enderecos and the submit form.
     * @param {Array} pessoa_enderecos An array of Wms.Models.PessoaEndereco objects.
     */
    list: function( pessoa_enderecos ){
        
        $('#div-lista-enderecos').html(this.view('init', {
            pessoa_enderecos:pessoa_enderecos
        } ));
    },
    /**
     * Responds to the create form being submitted by creating a new Wms.Models.PessoaEndereco.
     * @param {jQuery} el A jQuery wrapped element.
     * @param {Event} ev A jQuery event whose default action is prevented.
     */
    '#btn-salvar-endereco click': function(el, ev) {
        if ($('#fieldset-endereco #enderecos-idTipo').val() == '') {
            alert('Infome um tipo para o endereço');
            return;
        }
        
        var valores = $('#fieldset-endereco').formParams().enderecos;
        var id = $("#fieldset-endereco #enderecos-id").val(); 
        
        if (id != '') {
            valores.acao = id.indexOf('-new') == -1 ? 'alterar' : 'incluir';
            valores.lblTipoEndereco = $('#fieldset-endereco #enderecos-idTipo option:selected').text();
            valores.lblUfEndereco = $('#fieldset-endereco #enderecos-idUf option:selected').text();
            pessoa_endereco = new Wms.Models.PessoaEndereco(valores);
            this.show(pessoa_endereco);
        } else {
            var d = new Date();
            valores.id = d.getTime()+ '-new';
            valores.acao = 'incluir';
            valores.lblTipoEndereco = $('#fieldset-endereco #enderecos-idTipo option:selected').text();
            valores.lblUfEndereco = $('#fieldset-endereco #enderecos-uf option:selected').text();
            pessoa_endereco = new Wms.Models.PessoaEndereco(valores);
            $('#div-lista-enderecos').append( this.view("show", pessoa_endereco) );
        }
        $("#fieldset-endereco input[type=text], #fieldset-endereco input[type=hidden], #fieldset-endereco select").val('');
        $('#fieldset-endereco legend').html('Identificação');
        $('#fieldset-endereco button').html('Adicionar');
    },
    /**
     * Creates and places the edit interface.
     * @param {jQuery} el The pessoa_endereco's edit link element.
     */
    '.pessoa_endereco click': function( el ){
        var pessoa_endereco = el.model();
        // altera informacao
        $('#fieldset-endereco legend').html('Editar Endereço');
        $('#fieldset-endereco button').html('Atualizar');
        
        $('#fieldset-endereco #enderecos-id').val(pessoa_endereco.id);
        $('#fieldset-endereco #enderecos-bairro').val(pessoa_endereco.bairro);
        $('#fieldset-endereco #enderecos-cep').val(pessoa_endereco.cep);
        $('#fieldset-endereco #enderecos-complemento').val(pessoa_endereco.complemento);
        $('#fieldset-endereco #enderecos-descricao').val(pessoa_endereco.descricao);
        $('#fieldset-endereco #enderecos-idTipo').val(pessoa_endereco.idTipo);
        $('#fieldset-endereco #enderecos-isEct-' + pessoa_endereco.isEct).attr('checked', true);
        $('#fieldset-endereco #enderecos-localidade').val(pessoa_endereco.localidade);
        $('#fieldset-endereco #enderecos-numero').val(pessoa_endereco.numero);
        $('#fieldset-endereco #enderecos-pontoReferencia').val(pessoa_endereco.pontoReferencia);
        $('#fieldset-endereco #enderecos-idUf').val(pessoa_endereco.idUf);
        //$('#mainForm').populate(pessoa_endereco);
    },
    /**
     * Shows a pessoa_endereco's information.
     */
    show: function( pessoa_endereco ){
        pessoa_endereco.elements().replaceWith(this.view('show',pessoa_endereco));
    },
    /**
     *	 Handle's clicking on a pessoa_endereco's destroy link.
     */
    '.btn-excluir-endereco click': function(el, ev){
        //evita a propagação do click para a div
        ev.stopPropagation();
        
        if(confirm("Tem certeza que deseja excluir este endereço?")){
            var model = el.closest('.pessoa_endereco').model();
            var id = model.id.toString();
            
            //se é um endereço existente (não haja a palavra '-new' no id)
            if (id.indexOf('-new') == -1) {
                //limpa o ID
                id.replace('-new', '');
                //adiciona à fila para excluir 
                $('<input/>', {
                    name: 'enderecos[' + id + '][acao]',
                    value: 'excluir',
                    type: 'hidden'
                }).appendTo('#fieldset-enderecos-cadastrados'); 
            }
            //remove a div do endereco
            model.elements().remove();
        }
    }
});