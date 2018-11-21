<?php

use Wms\Domain\Entity\Pessoa\Telefone;
/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_PessoaTelefoneController extends \Wms\Module\Web\Controller\Action\Crud
{
    /**
     * Nome na entidade do CRUD
     * @var string
     */
    protected $entityName = 'Pessoa\Telefone';
        
    /**
     * Lista todos os telefones cadastrados para uma determinada pessoa
     */
    public function listJsonAction()
    {
	$idPessoa = $this->getRequest()->getParam('idPessoa');
	$repo = $this->repository;
	$telefones = $repo->findBy(array('pessoa' => $idPessoa));
	$arrayTelefones = array();
	
	foreach ($telefones as $telefone) {
	    $arrayTelefones[] = array(
		'id'	          => $telefone->getId(),
		'idTipo'	  => $telefone->getIdTipo(),		
		'lblTipoTelefone' => $telefone->getTipo()->getNome(),		
		'ddd'		  => $telefone->getDdd(),
		'numero'	  => $telefone->getNumero(),
		'ramal'	          => $telefone->getRamal(),
		'acao'		  => 'nula',
	    );
	}
        
	$this->_helper->json($arrayTelefones, true);
    }
}