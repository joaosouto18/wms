<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Ator as AtorInterface,
    Wms\Domain\Entity\Filial as FilialEntity,
    Wms\Domain\Entity\Pessoa\Endereco,
    Wms\Domain\Entity\Pessoa\Telefone as TelefoneEntity,
    Wms\Domain\Entity\Pessoa\Juridica,
    Wms\Domain\Entity\Pessoa\Fisica;

/**
 * 
 */
class AtorRepository extends EntityRepository {

    /**
     * Persiste todos os dados relacionados ao ator e retorna uma pessoa
     * 
     * @param Ator $ator
     * @param array $values
     * @return Pessoa
     */
    public function persistirAtor(AtorInterface $ator, array $values, $flush = true) {
        $pessoa = $this->persistirPessoa($ator, $values);

        if (isset($values['enderecos']))
            $this->persistirEnderecos($pessoa, $values['enderecos']);

        if (isset($values['telefones']))
            $this->persistirTelefones($pessoa, $values['telefones']);

        if ($flush == true) {
            $this->getEntityManager()->flush();
        }

        return $pessoa;
    }

    /**
     * Persiste os dados pessoais e retorna a pessoa
     * 
     * @param PessoaInterface $pessoa
     * @param array $dados
     * @return Pessoa
     */
    public function persistirPessoa(AtorInterface $ator, array $values) {
        $em = $this->getEntityManager();
        $permitirCnpjIguais = $this->getParametroCNPJ();

        //Configura a pessoa de acorodo  o seu tipo
        if ($values['pessoa']['tipo'] == 'J') { //pessoa jurídica
            //retorna uma pessoa existente ou cria uma nova
            if (isset($values['id']) && $values['id'] > 0) {
                $pessoa = $ator->getPessoa();
            } else {
                $pessoa = new Juridica;
            }
            //transforma as datas de string ara DateTime
            if ($values['pessoa']['juridica']['dataAbertura'] != null) {
                $data = \DateTime::createFromFormat('d/m/Y', $values['pessoa']['juridica']['dataAbertura']);
                $values['pessoa']['juridica']['dataAbertura'] = $data;
            }

            $cnpj = str_replace(array(".", "-", "/"), "", $values['pessoa']['juridica']['cnpj']);
            $values['pessoa']['juridica']['cnpj'] = $cnpj;


            if ($values['pessoa']['juridica']['idTipoOrganizacao'] != null) {
                $tipoOrganizacao = $em->getReference('wms:Pessoa\Organizacao\Tipo', $values['pessoa']['juridica']['idTipoOrganizacao']);
                $pessoa->setTipoOrganizacao($tipoOrganizacao);
            }

            if ($values['pessoa']['juridica']['idRamoAtividade'] != null) {
                $tipoRamoAtividade = $em->getReference('wms:Pessoa\Atividade\Tipo', $values['pessoa']['juridica']['idRamoAtividade']);
                $pessoa->setTipoRamoAtividade($tipoRamoAtividade);
            }

            $pessoa->setNome($values['pessoa']['juridica']['nome']);
            $pessoa->setNomeFantasia($values['pessoa']['juridica']['nome']);

            //configura através de um array de opções
            \Zend\Stdlib\Configurator::configure($pessoa, $values['pessoa']['juridica']);
        } elseif ($values['pessoa']['tipo'] == 'F') { //pessoa física
            //retorna uma pessoa existente ou cria uma nova
            if (isset($values['pessoa']['fisica']['id']) && (int) $values['pessoa']['fisica']['id'] > 0) {
                if ($values['pessoa']['fisica']['acao'] == 'edit') {
                    $pessoa = $em->getRepository('wms:Pessoa\Fisica')->findOneBy(array('id' => $values['pessoa']['fisica']['id']));
                } else {
                    $pessoa = $ator->getPessoa();
                }
            } else {
                $pessoa = new Fisica;
            }

            //verifica se ja foi cadastrado o cpf informado
            $cpf = $values['pessoa']['fisica']['cpf'];
            $cpf = \Core\Util\String::retirarMaskCpfCnpj($cpf);

            $pessoaFisicaEntity = $em->getRepository('wms:Pessoa\Fisica')->findOneBy(array('cpf' => $cpf));

            if (($pessoa->getId() == null) && ($pessoaFisicaEntity != null)) {
                throw new \Exception('CPF ' . $pessoaFisicaEntity->getCpf() . ' já cadastrado.');
            } else if (($pessoaFisicaEntity != null) && ($pessoaFisicaEntity->getId() != $pessoa->getId())) {
                throw new \Exception('CPF ' . $pessoaFisicaEntity->getCpf() . ' já cadastrado.');
            }

            //transforma as datas de string ara DateTime
            if (isset($values['pessoa']['fisica']['dataAdmissaoEmprego'])) {
                foreach (array('dataAdmissaoEmprego', 'dataExpedicaoRg', 'dataNascimento') as $item) {
                    $data = \DateTime::createFromFormat('d/m/Y', $values['pessoa']['fisica'][$item]);
                    if ($data) {
                        $values['pessoa']['fisica'][$item] = $data;
                    } else {
                        unset($values['pessoa']['fisica'][$item]);
                    }
                }
            }

            $pessoa->setNome($values['pessoa']['fisica']['nome']);

            //configura através de um array de opções
            \Zend\Stdlib\Configurator::configure($pessoa, $values['pessoa']['fisica']);
        } else { //tipo inválido
            throw new \Exception('Tipo de Pessoa inválido');
        }
        var_dump($pessoa);
        $ator->setPessoa($pessoa);
        $em->persist($pessoa);
        return $pessoa;
    }

    /**
     * Metodo que persiste endereços baseados em uma matriz
     * 
     * @param AtorInterface $pessoa
     * @param array $enderecos Matriz com enderecos para cadastro
     */
    public function persistirEnderecos(AtorInterface $pessoa, array $enderecos) {
        $em = $this->getEntityManager();

        if (count($enderecos) == 0)
            return false;

        foreach ($enderecos as $id => $itemEndereco) {

            if (isset($itemEndereco['acao'])) {

                switch ($itemEndereco['acao']) {
                    case 'incluir':
                        //cria novo endereco
                        $endereco = new Endereco;

                        \Zend\Stdlib\Configurator::configure($endereco, $itemEndereco);

                        $tipoEndereco = $em->getReference('wms:Pessoa\Endereco\Tipo', $itemEndereco['idTipo']);
                        $uf = $em->getReference('wms:Util\Sigla', $itemEndereco['idUf']);

                        $endereco->setUf($uf)
                                ->setTipo($tipoEndereco)
                                ->setPessoa($pessoa);

                        $enderecoEntity = $em->getRepository('wms:Pessoa\Endereco')->findBy($endereco->toArray());
                        if (!empty($enderecoEntity))
                            break;

                        $em->persist($endereco);
                        $pessoa->addEndereco($endereco);
                        break;
                    case 'excluir':
                        $endereco = $em->getReference('wms:Pessoa\Endereco', $id);

                        $em->remove($endereco);
                        break;
                    case 'alterar':
                        $endereco = $em->getReference('wms:Pessoa\Endereco', $id);
                        \Zend\Stdlib\Configurator::configure($endereco, $itemEndereco);

                        $tipoEndereco = $em->getReference('wms:Pessoa\Endereco\Tipo', $itemEndereco['idTipo']);
                        $uf = $em->getReference('wms:Util\Sigla', $itemEndereco['idUf']);

                        $endereco->setUf($uf)
                                ->setTipo($tipoEndereco)
                                ->setPessoa($pessoa);

                        $em->persist($endereco);
                        break;
                }
            }
        }
    }

    /**
     * Metodo que persiste os telefones baseados em uma matriz
     * 
     * @param AtorInterface $pessoa
     * @param array $telefones Matriz com telefones para cadastro 
     */
    public function persistirTelefones(AtorInterface $pessoa, array $telefones) {
        $em = $this->getEntityManager();

        if (count($telefones) == 0)
            return false;

        foreach ($telefones as $id => $itemTelefone) {

            if (isset($itemTelefone['acao'])) {

                switch ($itemTelefone['acao']) {
                    case 'incluir':
                        $telefoneEntity = new TelefoneEntity;

                        \Zend\Stdlib\Configurator::configure($telefoneEntity, $itemTelefone);

                        $tipoTelefone = $em->getReference('wms:Pessoa\Telefone\Tipo', $itemTelefone['idTipo']);

                        $telefoneEntity->setTipo($tipoTelefone)
                                ->setPessoa($pessoa);

                        $em->persist($telefoneEntity);
                        $pessoa->addTelefone($telefoneEntity);
                        break;
                    case 'excluir':
                        $telefoneEntity = $em->getReference('wms:Pessoa\Telefone', $id);

                        $em->remove($telefoneEntity);
                        break;
                    case 'alterar':
                        $telefoneEntity = $em->getReference('wms:Pessoa\Telefone', $id);
                        \Zend\Stdlib\Configurator::configure($telefoneEntity, $itemTelefone);

                        $tipoTelefone = $em->getReference('wms:Pessoa\Telefone\Tipo', $itemTelefone['idTipo']);

                        $telefoneEntity->setTipo($tipoTelefone)
                                ->setPessoa($pessoa);

                        $em->persist($telefoneEntity);
                        break;
                }
            }
        }
    }

    /**
     * Remove o registro no banco através do seu id
     * @param integer $id 
     */
    public function remove($id) {
        $em = $this->getEntityManager();
        $pessoa = $em->getReference('wms:Pessoa', $id);
        $em->remove($pessoa);
    }

    public function getParametroCNPJ()
    {
        return $this->getSystemParameterValue('PERMITE_CLIENTES_CNPJ_IGUAIS');
    }

}
