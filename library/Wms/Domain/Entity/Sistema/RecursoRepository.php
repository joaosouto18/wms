<?php

namespace Wms\Domain\Entity\Sistema;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\Recurso as RecursoEntity,
    Wms\Domain\Entity\Sistema\Recurso\Vinculo;

/**
 * 
 */
class RecursoRepository extends EntityRepository {

    /**
     *
     * @param type $nome
     * @return type 
     */
    public function checkRecursoExiste($nome)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT COUNT(r) nome FROM wms:Sistema\Recurso r WHERE r.nome = :nome');

        $query->setParameter('nome', $nome);
        $recurso = $query->getOneOrNullResult();

        return ((string) $recurso["nome"] == 0) ? false : true;
    }

    /**
     *
     * @param RecursoEntity $recurso
     * @param array $values
     * @throws \Exception 
     */
    public function save(RecursoEntity $recurso, array $values)
    {
        extract($values['identificacao']);
        $em = $this->getEntityManager();
        // request
        $recurso->setNome($nome);
        $recurso->setDescricao($descricao);
        $recurso->setIdPai($idPai);

        if ($recurso->getId() == null || $recurso->getNome() != $nome) {
            if ($this->checkRecursoExiste($nome)) {
                throw new \Exception('Recurso ' . $nome . ' já cadastrado');
            }
        }

        //Remove vinculos existentes para atualização
        if ($recurso->getId() != null) {
            foreach ($recurso->getVinculos() as $recursoAcao) {
                $recurso->getVinculos()->removeElement($recursoAcao);
                $em->remove($recursoAcao);
            }
            $em->flush();
        }

        foreach ($values['acao'] as $key => $codAcao) {
            if (strpos($key, 'chk') && $codAcao != 0) {
                //para cada ação, há um input text que define o nome do relacionmento	
                $nomeVinculo = $values['acao'][$codAcao . 'txt'];
                $acao = $em->getReference('wms:Sistema\Acao', $codAcao);

                $recursoAcao = new Vinculo;
                $recursoAcao->setAcao($acao);
                $recursoAcao->setNome($nomeVinculo);
                $recursoAcao->setRecurso($recurso);
                $recurso->getVinculos()->add($recursoAcao);
                $em->persist($recursoAcao);
            }
        }

        $em->persist($recurso);
    }

    /**
     * Remove um registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Sistema\Recurso', $id);

        // check whether I have any user within the role
        $dql = $em->createQueryBuilder()
                ->select('count(ra) qtty')
                ->from('wms:Sistema\Recurso\Vinculo', 'ra')
                ->where('ra.recurso = ?1')
                ->setParameter(1, $id);
        $resultSet = $dql->getQuery()->execute();
        $count = (integer) $resultSet[0]['qtty'];

        // case perfilUsuario has any user 
        if ($count > 0)
            throw new \Exception("Não é possivel remover o Recurso 
				    {$proxy->getNome()}, há {$count} 
				    ações vinculados ao recurso");
        // remove
        $em->remove($proxy);
    }

    /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('descricao' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getDescricao();
        }

        return $valores;
    }

    /**
     * Retorna um vinculo pelo nome do recurso e nome da ação
     * @param string $nomeRecurso
     * @param string $nomeAcao 
     */
    public function getVinculoByNomeAndAcao($nomeRecurso, $nomeAcao)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder()
                ->select('v')
                ->from('wms:Sistema\Recurso\Vinculo', 'v')
                ->innerJoin('v.recurso', 're')
                ->innerJoin('v.acao', 'a')
                ->where('re.nome = :nomeRecurso AND a.nome = :nomeAcao')
                ->setParameters(array(
                    'nomeRecurso' => $nomeRecurso,
                    'nomeAcao' => $nomeAcao,
                ));

        $q = $qb->getQuery();

        return $q->getOneOrNullResult();
    }

}
