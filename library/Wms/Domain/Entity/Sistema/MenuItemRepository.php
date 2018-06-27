<?php
namespace Wms\Domain\Entity\Sistema;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\MenuItem as MenuItemEntity,
        Wms\Domain\Entity\RecursoAcao,
    \Doctrine\Common\Persistence\ObjectRepository;


class MenuItemRepository extends EntityRepository implements ObjectRepository
{
    
        /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('dscMenuItem' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getDscMenuItem();
        }

        return $valores;
    }
    
     /**
     *
     * @param MenuItemEntity $menu
     * @param array $values
     * @throws \Exception 
     */
    public function save(MenuItemEntity $menu, array $values)
    {
              
        extract($values);
        $em = $this->getEntityManager();
        $pai = $em->getReference('wms:Sistema\MenuItem', $idPai);
        $recursoAcao = $em->getReference('wms:Sistema\Recurso\Vinculo', $idRecursoAcao);
        
        // request
        $menu->setPeso($peso);
        $menu->setUrl($url);
        $menu->setPermissao($recursoAcao);
        $menu->setPai($pai);
        $menu->setTarget($target);
        $menu->setDscMenuItem($dscMenuItem);
        
        $em->persist($menu);        
    }
    
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Sistema\MenuItem', $id);
	$em->remove($proxy);
    }

}