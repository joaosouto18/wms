<?php

namespace DoctrineExtensions\ORM\Query\Functions\Numeric;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "NLV" "(" StringPrimary "," StringPrimary ")" 
 */
class NvlFunction extends FunctionNode
{
    // (1)
    public $firstExpression = null;
    public $secondExpression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->firstExpression = $parser->StringPrimary(); // (4)
	$parser->match(Lexer::T_COMMA); // (5)
	$this->secondExpression = $parser->StringPrimary(); // (6)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (7)
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'NVL(' .
            $this->firstExpression->dispatch($sqlWalker) . ', ' .
	    $this->secondExpression->dispatch($sqlWalker) .
        ')'; // (8)
    }
}

