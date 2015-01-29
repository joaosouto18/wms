<?php

namespace Wms\Domain\Entity\Acesso;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Acesso\Perfil as PerfilEntity;

/**
 * 
 */
class PerfilRepository extends EntityRepository {
    
    /**
     *
     * @param PerfilEntity $perfil
     * @param array $values 
     */
    public function save(PerfilEntity $perfil, array $values)
    {
        extract($values['identificacao']);
        $perfil->setNome($nome)
                ->setDescricao($descricao);

        // removo todas as acoes do perfil
        $perfil->getAcoes()->clear();
        $this->getEntityManager()->persist($perfil);
        
        // lista de permissoes
        $permissoes = $values['acoes'];
        
        //caso tenha algum
        if (count($permissoes) > 0) {
            // loop para inserir as novas acoes
            foreach ($permissoes as $idPermissao) {
                // caso algum valor nao numerico
                if(!is_numeric($idPermissao)) continue;
                
                $permissao = $this->getEntityManager()->getReference('wms:Sistema\Recurso\Vinculo', $idPermissao);
                $perfil->getAcoes()->add($permissao);
            }
        }
        
        //apago o cache da acl
        $cache = new \Core\Cache();
        $cache->delete('acl');

        //persisto dados
        $this->getEntityManager()->persist($perfil);
    }

    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Acesso\Perfil', $id);

        // check whether I have any user within the role
        $dql = $em->createQueryBuilder()
                ->select('count(upu) qtty')
                ->from('wms:Usuario', 'upu')
                ->where('upu.perfil = ?1')
                ->setParameter(1, $id);
        $resultSet = $dql->getQuery()->execute();
        $count = (integer) $resultSet[0]['qtty'];

        // case perfilUsuario has any user 
        if ($count > 0)
            throw new \Exception('Não é possivel remover o Perfil "' . $proxy->getNome() . '", há ' . $count . 'usuário(s) vinculados ao perfil.');

        // remove
        $em->remove($proxy);
    }

    /**
     * Retorna todos os dados como matriz (apenas ID e Nome)
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findAll() as $item)
            $valores[$item->getId()] = $item->getNome();

        return $valores;
    }

}
