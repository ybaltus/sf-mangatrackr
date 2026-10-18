<?php

declare(strict_types=1);

namespace App\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * DQL function UNACCENT(string) mapped to the PostgreSQL unaccent() function
 * (extension created by the baseline migration, ADR 0012 / D4).
 *
 * PostgreSQL only: the function does not exist on other platforms.
 */
final class UnaccentFunction extends FunctionNode
{
    private Node $string;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->string = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'unaccent('.$this->string->dispatch($sqlWalker).')';
    }
}
