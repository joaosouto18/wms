/**
 * @tag controllers, home
 * Displays a table of pessoa_enderecos.	 Lets the user 
 * ["Wms.Controllers.PessoaEndereco.prototype.form submit" create], 
 * ["Wms.Controllers.PessoaEndereco.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.PessoaEndereco.prototype.&#46;destroy click" destroy] pessoa_enderecos.
 */
$.Controller.extend('Wms.Controllers.PessoaDadosPessoais',
/* @Static */
{
    pluginName: 'pessoaDadosPessoais'
},
/* @Prototype */
{

    "{window} load": function(){
        if($('#pessoa-fisica-acao').val() == 'edit'){
            $('#btn-pesquisar-pessoa, #btn-limpar-pessoa').hide();
        }
    },
    
    '#btn-pesquisar-pessoa click': function(el, ev) {
        if($('#pessoa-fisica-cpf').val() == "") {
            alert('Digite um cpf.');
            return false;
        }else{
            this.verificarCPF();
        }
    },
    
    '#btn-limpar-pessoa click': function(el, ev) {
        $('#pessoa-fisica-acao').val('');
        $('#pessoa-fisica-id').val('');
        $('#pessoa-fisica-cpf').val('');
        $('#pessoa-fisica-nome').val('');
        $('#pessoa-fisica-nomeMae').val('');
        $('#pessoa-fisica-nomePai').val('');
        $('#pessoa-fisica-sexo').val('');
        $('#pessoa-fisica-dataNascimento').val('');
        $('#pessoa-fisica-idGrauEscolaridade').val('');
        $('#pessoa-fisica-apelido').val('');
        $('#pessoa-fisica-idSituacaoConjugal').val('');
        $('#pessoa-fisica-naturalidade').val('');
        $('#pessoa-fisica-nacionalidade').val('');
        $('#pessoa-fisica-orgaoExpedidorRg').val('');
        $('#pessoa-fisica-ufOrgaoExpedidorRg').val('');
        $('#pessoa-fisica-dataExpedicaoRg').val('');
        $('#pessoa-fisica-nomeEmpregador').val('');
        $('#pessoa-fisica-idTipoAtividade').val('');
        $('#pessoa-fisica-idTipoOrganizacao').val('');
        $('#pessoa-fisica-matriculaEmprego').val('');
        $('#pessoa-fisica-dataAdmissaoEmprego').val('');
        $('#pessoa-fisica-cargo').val('');
        $('#pessoa-fisica-salario').val('');
    },
    
    /**
     * Shows a produto_embalagem's information.
     */
    showDadosPessoa: function( params ){
        $('#pessoa-fisica-acao').val(params.acao);
        $('#pessoa-fisica-id').val(params.id);
        $('#pessoa-fisica-cpf').val(params.cpf);
        $('#pessoa-fisica-nome').val(params.nome);
        $('#pessoa-fisica-nomeMae').val(params.nomeMae);
        $('#pessoa-fisica-nomePai').val(params.nomePai);
        
        $('#pessoa-fisica-sexo-M').attr('checked', 'checked');
        if(params.sexo == 'F'){
            $('#pessoa-fisica-sexo-F').attr('checked', 'checked');
        }
        
        $('#pessoa-fisica-sexo').val(params.sexo);
        $('#pessoa-fisica-dataNascimento').val(params.dataNascimento);
        $('#pessoa-fisica-idGrauEscolaridade').val(params.idGrauEscolaridade);
        $('#pessoa-fisica-apelido').val(params.apelido);
        $('#pessoa-fisica-idSituacaoConjugal').val(params.idSituacaoConjugal);
        $('#pessoa-fisica-naturalidade').val(params.naturalidade);
        $('#pessoa-fisica-nacionalidade').val(params.nacionalidade);
        $('#pessoa-fisica-rg').val(params.rg);
        $('#pessoa-fisica-orgaoExpedidorRg').val(params.orgaoExpedidorRg);
        $('#pessoa-fisica-ufOrgaoExpedidorRg').val(params.ufOrgaoExpedidorRg);
        $('#pessoa-fisica-dataExpedicaoRg').val(params.dataExpedicaoRg);
        $('#pessoa-fisica-nomeEmpregador').val(params.nomeEmpregador);
        $('#pessoa-fisica-idTipoAtividade').val(params.idTipoAtividade);
        $('#pessoa-fisica-idTipoOrganizacao').val(params.idTipoOrganizacao);
        $('#pessoa-fisica-matriculaEmprego').val(params.matriculaEmprego);
        $('#pessoa-fisica-dataAdmissaoEmprego').val(params.dataAdmissaoEmprego);
        $('#pessoa-fisica-cargo').val(params.cargo);
        $('#pessoa-fisica-salario').val(params.salario);

        //carregar os telefones e enderecos por meio do controller de cada um
        Wms.Controllers.PessoaTelefone.prototype.list(params.telefones);
        Wms.Controllers.PessoaEndereco.prototype.list(params.enderecos);
    },
    
    /**
     * Verifica se existe o cpf informado
     */
    verificarCPF:function() {
        var cpf = $('#pessoa-fisica-cpf').val();
        
        new Wms.Models.PessoaDadosPessoais.verificarCPF({
            cpf:cpf
        }, this.callback('validarCPF'));
    },
    
    /**
     * Valida o cpf informado
     * @param {Array} params Matriz de objetos Wms.Models.PessoaDadosPessoais.
     */
    validarCPF: function( params ){
        if(params.ajaxStatus == 'error'){
            alert(params.msg);
            $('#pessoa-fisica-cpf').focus();
            return false;
        }

        this.showDadosPessoa(params);
    }

});