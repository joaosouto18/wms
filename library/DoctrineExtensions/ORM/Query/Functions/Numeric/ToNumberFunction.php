<?php

namespace DoctrineExtensions\ORM\Query\Functions\Numeric;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "TO_NUMBER" "(" StringPrimary ")"
 */
class ToNumberFunction extends FunctionNode
{
    // (1)
    public $firstExpression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstExpression = $parser->ArithmeticPrimary(); // (4)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (5)
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'TO_NUMBER(' .
            $this->firstExpression->dispatch($sqlWalker) .
        ')'; // (6)
    }
}

