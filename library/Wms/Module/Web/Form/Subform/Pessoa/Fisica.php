<?php

namespace Wms\Module\Web\Form\Subform\Pessoa;

/**
 * Description of PessoaFisica
 *
 * @author medina
 */
class Fisica extends \Core\Form\SubForm {

    public function init() {

        $em = $this->getEm();
        $sigla = $em->getRepository('wms:Util\Sigla');
        // grau de escolaridade 
        $grauEsc = $sigla->getIdValue(9);
        // situacao conjugal
        $situacaoConjugal = $sigla->getIdValue(10);
        // tipo atividade
        $tipoAtividade = $sigla->getIdValue(11);
        // tipo organizacao
        $tipoOrganizacao = $sigla->getIdValue(13);
        // uf
        $uf = $sigla->getIdValue(32);

        $this->addElement('hidden', 'id', array(
            'value' => ''
        ));
        $this->addElement('hidden', 'acao', array(
            'value' => ''
        ));
        $this->addElement('text', 'nome', array(
            'label' => 'Nome',
            'style' => 'width:465px;',
            'maxlength' => 50,
            'required' => true
        ));
        $this->addElement('text', 'nomeMae', array(
            'label' => 'Nome Mãe',
            'style' => 'width:465px;',
            'maxlength' => 50
        ));

        $this->addElement('text', 'nomePai', array(
            'label' => 'Nome Pai',
            'style' => 'width:450px;',
            'maxlength' => 50
        ));

        $this->addElement('button', 'btnPesquisar', array(
            'decorators' => array('ViewHelper'),
            'label' => 'Pesquisar',
            'style' => 'float:left; margin-top:17px; margin-right:20px;',
            'attribs' => array('id' => 'btn-pesquisar-pessoa')
        ));

        $this->addElement('button', 'btnLimpar', array(
            'decorators' => array('ViewHelper'),
            'label' => 'Limpar',
            'style' => 'float:left; margin-top:17px; margin-right:20px;',
            'attribs' => array('id' => 'btn-limpar-pessoa')
        ));

        $this->addElement('radio', 'sexo', array(
            'label' => 'Sexo',
            'multiOptions' => array(
                'M' => 'MASCULINO',
                'F' => 'FEMININO'
            ),
            'separator' => '',
        ));

        $this->addElement('date', 'dataNascimento', array(
            'label' => 'Data de Nascimento'
        ));
        $this->addElement('select', 'idGrauEscolaridade', array(
            'label' => 'Escolaridade',
            'multiOptions' => $grauEsc,
            'style' => 'max-width: 300px',
        ));

        $this->addElement('select', 'idSituacaoConjugal', array(
            'label' => 'Situação Conjugal',
            'multiOptions' => $situacaoConjugal
        ));
        $this->addElement('select', 'idTipoAtividade', array(
            'label' => 'Tipo Atividade',
            'style' => 'max-width: 300px',
            'multiOptions' => $tipoAtividade
        ));
        $this->addElement('text', 'apelido', array(
            'label' => 'Apelido',
            'maxlength' => 30
        ));

        $this->addElement('text', 'naturalidade', array(
            'label' => 'Naturalidade',
            'maxlength' => 60
        ));
        $this->addElement('text', 'nacionalidade', array(
            'label' => 'Nacionalidade',
            'maxlength' => 60
        ));

        $this->addElement('text', 'nomeEmpregador', array(
            'label' => 'Empregador',
            'maxlength' => 60
        ));
        $this->addElement('select', 'idTipoOrganizacao', array(
            'label' => 'Tipo Organização',
            'multiOptions' => $tipoOrganizacao
        ));
        $this->addElement('text', 'matriculaEmprego', array(
            'label' => 'Matrícula',
            'maxlength' => 20
        ));
        $this->addElement('date', 'dataAdmissaoEmprego', array(
            'label' => 'Data Admissão'
        ));
        $this->addElement('text', 'cargo', array(
            'label' => 'Cargo',
            'maxlength' => 60
        ));
        $this->addElement('money', 'salario', array(
            'label' => 'Valor Salário (R$)',
            'class' => 'pequeno'
        ));
        $this->addElement('cpf', 'cpf', array(
            'label' => 'CPF',
            'class' => 'medio',
            'required' => true
        ));
        $this->addElement('text', 'rg', array(
            'label' => 'RG',
            'class' => 'medio',
            'maxlength' => 15
        ));
        $this->addElement('text', 'orgaoExpedidorRg', array(
            'label' => 'Orgão Expedidor',
            'class' => 'medio',
            'maxlength' => 20
        ));
        $this->addElement('select', 'ufOrgaoExpedidorRg', array(
            'label' => 'UF Orgão Expedidor',
            'class' => 'medio',
            'multiOptions' => $uf
        ));
        $this->addElement('date', 'dataExpedicaoRg', array(
            'label' => 'Data de Expedição'
        ));

        $this->addDisplayGroup(array(
            'cpf',
            'btnPesquisar',
            'btnLimpar',
            'nome',
            'sexo',
            'nomeMae',
            'nomePai',
            'dataNascimento',
            'idGrauEscolaridade',
            'apelido',
            'idSituacaoConjugal',
            'apelido',
            'naturalidade',
            'nacionalidade',
            'rg',
            'orgaoExpedidorRg',
            'ufOrgaoExpedidorRg',
            'dataExpedicaoRg',
                ), 'pessoal', array('legend' => 'Pessoal'
        ));

        $this->addDisplayGroup(array(
            'nomeEmpregador',
            'idTipoAtividade',
            'idTipoOrganizacao',
            'matriculaEmprego',
            'dataAdmissaoEmprego',
            'cargo',
            'salario',
                ), 'documentos', array('legend' => 'Documentos e Contratação'
        ));

        $this->setDecorators(array(
            'FormElements',
            'PrepareElements',
            array('HtmlTag', array('tag' => 'div', 'id' => 'form-pessoa-fisica-container')),
        ));
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Pessoa\Fisica $pessoaFisica) {
        $values = array(
        'id' => $pessoaFisica->getId(),
        'nome' => $pessoaFisica->getNome(),
        'dataNascimento' => $pessoaFisica->getDataNascimento(),
        'idGrauEscolaridade' => $pessoaFisica->getIdGrauEscolaridade(),
        'sexo' => $pessoaFisica->getSexo(),
        'idSituacaoConjugal' => $pessoaFisica->getIdSituacaoConjugal(),
        'idTipoAtividade' => $pessoaFisica->getIdTipoAtividade(),
        'apelido' => $pessoaFisica->getApelido(),
        'nomeMae' => $pessoaFisica->getNomeMae(),
        'naturalidade' => $pessoaFisica->getNaturalidade(),
        'nacionalidade' => $pessoaFisica->getNacionalidade(),
        'nomePai' => $pessoaFisica->getNomePai(),
        'nomeEmpregador' => $pessoaFisica->getNomeEmpregador(),
        'idTipoOrganizacao' => $pessoaFisica->getIdTipoOrganizacao(),
        'matriculaEmprego' => $pessoaFisica->getMatriculaEmprego(),
        'dataAdmissaoEmprego' => $pessoaFisica->getDataAdmissaoEmprego(),
        'cargo' => $pessoaFisica->getCargo(),
        'salario' => $pessoaFisica->getSalario(),
        'cpf' => $pessoaFisica->getCpf(),
        'rg' => $pessoaFisica->getRg(),
        'orgaoExpedidorRg' => $pessoaFisica->getOrgaoExpedidorRg(),
        'ufOrgaoExpedidorRg' => $pessoaFisica->getUfOrgaoExpedidorRg(),
        'dataExpedicaoRg' => $pessoaFisica->getDataExpedicaoRg(),
        'isFalecido' => $pessoaFisica->getIsFalecido(),
        'acao' => 'edit'
        );

        $this->setDefaults($values);
    }

}