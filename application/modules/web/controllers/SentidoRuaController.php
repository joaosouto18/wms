<?php

use \Wms\Module\Web\Page,
    \Wms\Domain\Entity\Deposito\Endereco\SentidoRua;

/**
 * Description of Web_SentidoRuaController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_SentidoRuaController extends \Wms\Module\Web\Controller\Action
{

    public function indexAction()
    {
        try {
            //adding default buttons to the page
            Page::configure(array(
                'buttons' => array(
                    array(
                        'label' => 'Salvar',
                        'cssClass' => 'btnSave'
                    )
                )
            ));

            if ($this->getRequest()->isPost()) {
                // removo os sentidos de rua existentes para o deposito
                $dql = "DELETE FROM wms:Deposito\Endereco\SentidoRua s where s.deposito = :deposito";
                $query = $this->em->createQuery($dql);
                $query->setParameter('deposito', $this->view->idDepositoLogado);
                $query->execute();
                
                //cadastro os sentidos enviados
                $deposito = $this->em->getReference('wms:Deposito', $this->view->idDepositoLogado);
                
                foreach ($this->getRequest()->getParam('sentido') as $rua => $sentido) {
                    $entity = new SentidoRua;
                    $entity->setSentido($sentido);
                    $entity->setDeposito($deposito);
                    $entity->setRua($rua);
                    $this->em->persist($entity);
                }

                $this->em->flush();
                $this->_helper->messenger('success', 'Sentidos aplicados às ruas com sucesso');
            }

            $dql = 'SELECT s.rua, s.descricao sentido
		    FROM wms:Deposito\Endereco\Rua\Sentido s
		    ORDER BY s.rua';

            $query = $this->em->createQuery($dql);
            $ruas = $query->getResult();

            $this->view->ruas = $ruas;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

}