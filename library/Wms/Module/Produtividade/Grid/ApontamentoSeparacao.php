<?php

namespace Wms\Module\Produtividade\Grid;

use Wms\Module\Web\Grid;

class ApontamentoSeparacao extends Grid
{
    public function init($params)
    {
        $cpf = str_replace(array('.','-'),'',$params['cpf']);

        /** @var \Wms\Domain\Entity\UsuarioRepository $usuarioRepo */
        $usuarioRepo = $this->getEntityManager()->getRepository('wms:Usuario');
        $usuarioEn = $usuarioRepo->getPessoaByCpf($cpf,$params['qtdEtiquetas']);

        $this->setAttrib('title','apontamento-separacao');
        $this->setSource(new \Core\Grid\Source\ArraySource($usuarioEn))
                ->addColumn(array(
                    'label' => 'Nome',
                    'index' => 'NOM_PESSOA',
                ))
                ->addColumn(array(
                    'label' => 'CPF',
                    'index' => 'NUM_CPF',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Etiquetas',
                    'index' => 'QUANTIDADE',
                ));

        $this->setShowExport(false);

        return $this;
    }

}

