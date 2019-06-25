<?php

namespace DoctrineExtensions\ORM\Query\Functions\Numeric;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\QueryException;

/**
 * "ROUND" "(" StringPrimary [, StringSecondary] ")"
 */
class RoundFunction extends FunctionNode
{
    // (1)
    public $firstExpression = null;
    public $secondExpression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstExpression = $parser->ArithmeticPrimary(); // (4)
        try {
            $parser->match(Lexer::T_COMMA); // (3)
            $this->secondExpression = $parser->ArithmeticPrimary(); // (4)
        } catch (QueryException $e) {
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (5)
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $args[] = "ROUND(";
        $args[] = $this->firstExpression->dispatch($sqlWalker);
        if (!empty($this->secondExpression)) {
            $args[] = ",";
            $args[] = $this->secondExpression->dispatch($sqlWalker);
        }
        $args[] = ")";
        return implode(" ", $args); // (8)
    }
}

