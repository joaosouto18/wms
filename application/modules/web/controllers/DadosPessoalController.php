<?php

use Wms\Domain\Entity\Pessoa;

/**
 * Description of SystemParamsController
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Web_DadosPessoalController extends \Wms\Module\Web\Controller\Action\Crud {

    /**
     * Nome na entidade do CRUD
     * @var string
     */
    protected $entityName = 'Pessoa';

    /**
     * Lista a pessoa fisica cadastrada no sistema
     */
    public function listPessoaFisicaJsonAction() {

        try {
            $em = $this->getEntityManager();
            $cpf = $this->getRequest()->getParam('cpf');
            $cpf = \Core\Util\String::retirarMaskCpfCnpj($cpf);

            $pessoaFisicaEntity = $em->getRepository('wms:Pessoa\Fisica')->findOneBy(array('cpf' => $cpf));

            if ($pessoaFisicaEntity) {

                $telefones = $em->getRepository('wms:Pessoa\Telefone')->findBy(array('pessoa' => $pessoaFisicaEntity->getId()));
                $arrayTelefones = array();

                foreach ($telefones as $telefone) {
                    $arrayTelefones[] = array(
                        'id' => $telefone->getId(),
                        'idTipo' => $telefone->getIdTipo(),
                        'lblTipoTelefone' => $telefone->getTipo()->getNome(),
                        'ddd' => $telefone->getDdd(),
                        'numero' => $telefone->getNumero(),
                        'ramal' => $telefone->getRamal(),
                        'acao' => 'nula',
                    );
                }

                $enderecos = $em->getRepository('wms:Pessoa\Endereco')->findBy(array('pessoa' => $pessoaFisicaEntity->getId()));
                $arrayEnderecos = array();

                foreach ($enderecos as $endereco) {
                    $arrayEnderecos[] = array(
                        'id' => $endereco->getId(),
                        'idTipo' => $endereco->getIdTipo(),
                        'lblTipoEndereco' => $endereco->getTipo()->getNome(),
                        'lblUfEndereco' => ($endereco->getUf() != null) ? $endereco->getUf()->getSigla() : '',
                        'localidade' => $endereco->getLocalidade(),
                        'descricao' => $endereco->getDescricao(),
                        'numero' => $endereco->getNumero(),
                        'complemento' => $endereco->getComplemento(),
                        'pontoReferencia' => $endereco->getPontoReferencia(),
                        'bairro' => $endereco->getBairro(),
                        'cep' => $endereco->getCep(),
                        'idUf' => $endereco->getIdUf(),
                        'isEct' => $endereco->getIsEct(),
                        'acao' => 'nula',
                    );
                }

                $arrayPessoaFisica = array(
                    'acao' => 'edit',
                    'id' => $pessoaFisicaEntity->getId(),
                    'cpf' => $pessoaFisicaEntity->getCpf(),
                    'nome' => $pessoaFisicaEntity->getNome(),
                    'nomeMae' => $pessoaFisicaEntity->getNomeMae(),
                    'nomePai' => $pessoaFisicaEntity->getNomePai(),
                    'sexo' => $pessoaFisicaEntity->getSexo(),
                    'dataNascimento' => $pessoaFisicaEntity->getDataNascimento(),
                    'idGrauEscolaridade' => $pessoaFisicaEntity->getIdGrauEscolaridade(),
                    'apelido' => $pessoaFisicaEntity->getApelido(),
                    'idSituacaoConjugal' => $pessoaFisicaEntity->getIdSituacaoConjugal(),
                    'naturalidade' => $pessoaFisicaEntity->getNaturalidade(),
                    'nacionalidade' => $pessoaFisicaEntity->getNacionalidade(),
                    'rg' => $pessoaFisicaEntity->getRg(),
                    'orgaoExpedidorRg' => $pessoaFisicaEntity->getOrgaoExpedidorRg(),
                    'ufOrgaoExpedidorRg' => $pessoaFisicaEntity->getUfOrgaoExpedidorRg(),
                    'dataExpedicaoRg' => $pessoaFisicaEntity->getDataExpedicaoRg(),
                    'nomeEmpregador' => $pessoaFisicaEntity->getNomeEmpregador(),
                    'idTipoAtividade' => $pessoaFisicaEntity->getIdTipoAtividade(),
                    'idTipoOrganizacao' => $pessoaFisicaEntity->getIdTipoOrganizacao(),
                    'matriculaEmprego' => $pessoaFisicaEntity->getMatriculaEmprego(),
                    'dataAdmissaoEmprego' => $pessoaFisicaEntity->getDataAdmissaoEmprego(),
                    'cargo' => $pessoaFisicaEntity->getCargo(),
                    'salario' => $pessoaFisicaEntity->getSalario(),
                    'telefones' => $arrayTelefones,
                    'enderecos' => $arrayEnderecos,
                    'ajaxStatus' => 'success',
                    'msg' => '',
                );
            } else {
                throw new \Exception('Não foi cadastrada nenhuma pessoa no sistema com o CPF informado.');
            }
        } catch (\Exception $e) {
            $arrayPessoaFisica = array(
                'ajaxStatus' => 'error',
                'msg' => $e->getMessage(),
            );
        }

        $this->_helper->json($arrayPessoaFisica, true);
    }

}