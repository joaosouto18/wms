<?php

namespace DoctrineExtensions\ORM\Query\Functions\String;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "TO_CHAR" "(" StringPrimary "," StringPrimary ")" 
 */
class Concat extends FunctionNode
{
    public $firstStringPrimary;
    public $secondStringPrimary;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'CONCAT(' .
            $sqlWalker->walkStringPrimary($this->firstStringPrimary) . ', ' . 
            $sqlWalker->walkStringPrimary($this->secondStringPrimary) .
        ')'; // (8)
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstStringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondStringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
    
}

