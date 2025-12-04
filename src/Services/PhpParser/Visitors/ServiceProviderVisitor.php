<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\PhpParser\Visitors;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

use function count;
use function in_array;

/**
 * AST visitor for modifying Laravel service provider files.
 */
final class ServiceProviderVisitor extends NodeVisitorAbstract
{
    /**
     * Create a new visitor instance.
     *
     * @param  array<string>  $useStatements
     * @param  array<string>  $registerMethodBody
     * @param  array<string>  $bootMethodBody
     * @param  array<string>  $newMethods
     */
    public function __construct(
        private readonly array $useStatements,
        private readonly array $registerMethodBody,
        private readonly array $bootMethodBody,
        private readonly array $newMethods
    ) {}

    /**
     * Enter a node during AST traversal.
     */
    public function enterNode(Node $node): Node
    {
        // Handle ClassMethod nodes (register and boot methods)
        if ($node instanceof ClassMethod) {
            if ($this->registerMethodBody !== [] && $node->name->toString() === 'register') {
                $this->appendToMethod($node, $this->registerMethodBody);
            }

            if ($this->bootMethodBody !== [] && $node->name->toString() === 'boot') {
                $this->appendToMethod($node, $this->bootMethodBody);
            }
        }

        // Handle Class nodes (add new methods)
        if ($node instanceof Class_ && $this->newMethods !== []) {
            $this->addMethodsToClass($node);
        }

        // Handle Namespace nodes (add use statements)
        if ($node instanceof Namespace_ && $this->useStatements !== []) {
            $this->addUseStatements($node);
        }

        return $node;
    }

    /**
     * Append statements to a method.
     *
     * @param  array<string>  $statements
     */
    private function appendToMethod(ClassMethod $method, array $statements): void
    {
        if ($method->stmts === null) {
            return;
        }

        // Remove empty comment from the method body
        $method->stmts = $this->removeEmptyCommentFromMethodBody($method);

        // Parse each statement string into AST nodes
        foreach ($statements as $statement) {
            $parsed = $this->parseStatement($statement);
            if ($parsed instanceof Node) {
                $method->stmts[] = $parsed;
            }
        }
    }

    /**
     * Add new methods to a class.
     */
    private function addMethodsToClass(Class_ $class): void
    {
        // Parse each method string into a ClassMethod node
        foreach ($this->newMethods as $methodCode) {
            $methodNode = $this->parseMethod($methodCode);
            if ($methodNode instanceof ClassMethod) {
                $class->stmts[] = $methodNode;
            }
        }
    }

    /**
     * Add use statements to a namespace.
     */
    private function addUseStatements(Namespace_ $namespace): void
    {
        $existingUses = $this->getExistingUseStatements($namespace);

        foreach ($this->useStatements as $useStatement) {
            // Skip if already exists
            if (in_array($useStatement, $existingUses, true)) {
                continue;
            }

            // Create a new use statement node directly
            $use = new Use_([new UseItem(new Name($useStatement))]);
            array_unshift($namespace->stmts, $use);
        }
    }

    /**
     * Get existing use statements from namespace.
     *
     * @return array<string>
     */
    private function getExistingUseStatements(Namespace_ $namespace): array
    {
        $uses = [];

        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $use) {
                    $uses[] = $use->name->toString();
                }
            }
        }

        return $uses;
    }

    /**
     * Parse a statement string into an AST node.
     */
    private function parseStatement(string $statement): ?Node
    {
        $parser = new ParserFactory()->createForNewestSupportedVersion();

        try {
            $stmts = $parser->parse("<?php\n{$statement}");

            return $stmts[0] ?? null;
        } catch (Error) {
            return null;
        }
    }

    /**
     * Parse a method string into a ClassMethod node.
     */
    private function parseMethod(string $methodCode): ?ClassMethod
    {
        $parser = new ParserFactory()->createForNewestSupportedVersion();

        try {
            // Wrap the method in a class to parse it properly
            $code = "<?php\nclass TempClass {\n{$methodCode}\n}";
            $stmts = $parser->parse($code);

            // Extract the first statement from the temporary class
            if (isset($stmts[0]) && $stmts[0] instanceof Class_) {
                $classStmts = $stmts[0]->stmts;
                if (isset($classStmts[0]) && $classStmts[0] instanceof ClassMethod) {
                    return $classStmts[0];
                }
            }

            return null;
        } catch (Error) {
            return null;
        }
    }

    private function removeEmptyCommentFromMethodBody(ClassMethod $method): array
    {
        if (count($method->stmts) === 1 && $method->stmts[0] instanceof Nop) {
            $methodBodyComments = $method->stmts[0]->getComments();
            if (count($methodBodyComments) === 1 && $methodBodyComments[0]->getText() === '//') {
                return [];
            }
        }

        return $method->stmts;
    }
}
