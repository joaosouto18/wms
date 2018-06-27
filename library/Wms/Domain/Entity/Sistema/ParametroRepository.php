<?php

namespace Wms\Domain\Entity\Sistema;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\Parametro as ParametroEntity;

/**
 * Parametro
 */
class ParametroRepository extends EntityRepository
{
    /**
     *
     * @param ParametroEntity $parametro
     * @param array $values 
     */
    public function save(ParametroEntity $parametro, array $values)
    {
	extract($values['identificacao']);
	
	$em = $this->getEntityManager();
	
	$contexto = $em->getReference('wms:Sistema\Parametro\Contexto', $idContexto);
	
	$parametro->setContexto($contexto)
                ->setConstante($constante)
                ->setTitulo($titulo)
                ->setIdTipoAtributo($idTipoAtributo);
	
	$em->persist($parametro);
    }
    
    /**
     *
     * @param type $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Sistema\Parametro', $id);
	$em->remove($proxy);
    }    

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
        $contexts = array();

        foreach ($this->findAll() as $context)
            $contexts[$context->getId()] = $context->getDescricao();

        return $contexts;
    }

    /**
     *
     * @param array $params 
     */
    public function update($params)
    {
        $em = $this->getEntityManager();

        foreach ($params as $idContext => $parametros) {
            if (!is_array($parametros))
                continue;

            foreach ($parametros as $constante => $valor) {
                
                // parametro
                $parametro = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('idContexto' => $idContext, 'constante' => $constante));
                
                $parametro->setValor($valor);
                
                $em->persist($parametro);
            }
        }
    }
    
    /**
     * Retorna o valor especifico de um parametro solicitado
     * @param int $contexto
     * @param string $constante
     * @return mixed retorna o valor, caso nao encontre um registro retorna falso
     */
    public function getValor($contexto, $constante)
    {
        $contexts = array();

        $parametro = $this->findOneBy(array('idContexto' => $contexto, 'constante' => $constante));
        
        return (is_object($parametro)) ? $parametro->getValor() : false;
    }

}
