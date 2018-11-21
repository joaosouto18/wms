<?php

namespace DoctrineExtensions\ORM\Query\Functions\DateTime;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "TO_DATE" "("StringPrimary")"
 */
class ToDateFunction extends FunctionNode
{
    // (1)
    public $firstExpression = null;
    public $secondExpression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstExpression = $parser->StringPrimary(); // (4)
        $parser->match(Lexer::T_COMMA);
        $this->secondExpression = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (5)
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'TO_DATE(' .
            $this->firstExpression->dispatch($sqlWalker) .', '.
            $this->secondExpression->dispatch($sqlWalker) .
        ')'; // (6)
    }

}

