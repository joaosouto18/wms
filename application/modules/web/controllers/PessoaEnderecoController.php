<?php

use Wms\Domain\Entity\Pessoa\Endereco;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_PessoaEnderecoController extends \Wms\Module\Web\Controller\Action\Crud
{

    /**
     * Nome na entidade do CRUD
     * @var string
     */
    protected $entityName = 'Pessoa\Endereco';

    /**
     * Lista todos os endereÃƒÆ’Ã‚Â§os cadastrados para uma determinada pessoa
     */
    public function listJsonAction()
    {
        $params = $this->getRequest()->getParams();
        $repo = $this->repository;
        $enderecos = $repo->findBy(array('pessoa' => $params['idPessoa']));
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

        $this->_helper->json($arrayEnderecos, true);
    }

}