<?php

namespace DoctrineExtensions\ORM\Query\Functions\String;

use Doctrine\ORM\Query\Lexer,
    Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "REPLACE" "(" String ","Search", "Replace To")"
 */
class ReplaceFunction extends FunctionNode
{
    public $string;
    public $search;
    public $replaceTo;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'REPLACE(' .
            $sqlWalker->walkStringPrimary($this->string) . ', ' .
            $sqlWalker->walkStringPrimary($this->search) . ', ' .
            $sqlWalker->walkStringPrimary($this->replaceTo) .
            ')'; // (8)
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->string = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->search = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->replaceTo = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
    
}

