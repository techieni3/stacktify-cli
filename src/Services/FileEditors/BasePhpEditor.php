<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use PhpParser\Error;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\Token;
use RuntimeException;

/**
 * Base class for PHP file editors using AST manipulation.
 */
abstract class BasePhpEditor extends BaseFileEditor
{
    /**
     * Parsed AST of the PHP file.
     *
     * @var array<Stmt>|null
     */
    protected ?array $ast = null;

    /**
     * Tokens of the PHP file.
     *
     * @var Token[]
     */
    protected array $token;

    /**
     * Save changes to the PHP file.
     *
     * Child classes must implement this to define their specific save logic.
     */
    abstract public function save(): bool;

    /**
     * Parse the PHP file into an AST.
     *
     * @return array<Stmt>
     */
    protected function parse(): array
    {
        if ($this->ast !== null) {
            return $this->ast;
        }

        try {
            $parser = new ParserFactory()->createForNewestSupportedVersion();
            $this->ast = $parser->parse($this->content);
            $this->token = $parser->getTokens();

            if ($this->ast === null) {
                throw new RuntimeException('Failed to parse PHP file');
            }

            return $this->ast;
        } catch (Error $error) {
            throw new RuntimeException("PHP Parser error: {$error->getMessage()}", $error->getCode(), $error);
        }
    }

    /**
     * Traverse the AST with a visitor.
     *
     * @return array<Stmt>
     */
    protected function traverse(NodeVisitorAbstract $visitor): array
    {
        $ast = $this->parse();

        $traverser = new NodeTraverser(new CloningVisitor());
        $newStmts = $traverser->traverse($ast);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        return $traverser->traverse($newStmts);
    }

    /**
     * Pretty print the AST to PHP code.
     *
     * @param  array<Stmt>  $ast
     */
    protected function prettyPrint(array $ast): string
    {
        return new Standard()->printFormatPreserving($ast, $this->ast, $this->token);
    }
}
