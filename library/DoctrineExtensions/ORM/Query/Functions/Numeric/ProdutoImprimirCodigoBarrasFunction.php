<?php

namespace DoctrineExtensions\ORM\Query\Functions\Numeric;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "PRODUTO_IMPRIMIR_CODIGO_BARRAS" "(" StringPrimary ")" 
 */
class ProdutoImprimirCodigoBarrasFunction extends FunctionNode
{
    public $firstExpression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'PRODUTO_IMPRIMIR_CODIGO_BARRAS(' .
            $this->firstExpression->dispatch($sqlWalker) .
        ')';
    }
}

