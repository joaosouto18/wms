<?php

use Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity;

/**
 * Description of Veiculo
 *
 * @author vinicius
 */
class Wms_WebService_Veiculo extends Wms_WebService {

  /**
   * Salva os dados de volume para um produto
   * @param array $data
   * @return boolean
   */
  public function salvar(array $data) {

	$em = $this->__getDoctrineContainer()->getEntityManager();

	try {
	  $transportadorRepository = $em->getRepository('wms:Pessoa\Papel\Transportador');
	  $transportadora = $transportadorRepository->findOneBy(array(
		  'idExterno' => $data['transportador']
	  ));

	  if (empty($transportadora))
		throw new Exception('Transportadora nao encontrada.');

	  $veiculoRepository = $em->getRepository('wms:Movimentacao\Veiculo');

	  // veiculo
	  $veiculoEntity = $veiculoRepository->find($data['id']);

	  if ($veiculoEntity == null)
		$veiculoEntity = new VeiculoEntity;

	  $values = array(
		  'identificacao' => array(
			  'id' => $data['id'],
			  'idTransportador' => $transportadora->getId(),
			  'idTipo' => $data['tipo'],
			  'cubagem' => $data['cubagem'],
			  'capacidade' => $data['capacidade'],
			  'descricao' => $data['descricao'],
		  )
	  );

	  $veiculoRepository->save($veiculoEntity, $values);
	} catch (Exception $e) {
	  throw new Exception('Aconteceu um erro ao gravar o Veiculo. Erro: ' . $e->getMessage());
	}
	return true;
  }

}

