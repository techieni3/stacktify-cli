<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\PhpParser\Visitors;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;

/**
 * AST visitor for modifying Laravel config files.
 */
final class ConfigFileVisitor extends NodeVisitorAbstract
{
    /**
     * Create a new visitor instance.
     *
     * @param  array<string, mixed>  $keysToSet
     * @param  array<string, array>  $keysToAppend
     * @param  array<string, array>  $keysToMerge
     * @param  array<string>  $keysToRemove
     */
    public function __construct(
        private readonly array $keysToSet,
        private readonly array $keysToAppend,
        private readonly array $keysToMerge,
        private readonly array $keysToRemove,
    ) {}

    /**
     * Enter a node during AST traversal.
     */
    public function enterNode(Node $node): Node
    {
        // Handle Return nodes (config files typically return an array)
        if ($node instanceof Return_ && $node->expr instanceof Array_) {
            $this->modifyArray($node->expr);
        }

        return $node;
    }

    /**
     * Modify an array node with the configured operations.
     */
    private function modifyArray(Array_ $array): void
    {
        // Remove keys
        foreach ($this->keysToRemove as $keyToRemove) {
            $this->removeKey($array, $keyToRemove);
        }

        // Set keys
        foreach ($this->keysToSet as $key => $value) {
            $this->setKey($array, $key, $value);
        }

        // Append to keys
        foreach ($this->keysToAppend as $key => $value) {
            $this->appendToKey($array, $key, $value);
        }

        // Merge arrays
        foreach ($this->keysToMerge as $key => $values) {
            $this->mergeKey($array, $key, $values);
        }
    }

    /**
     * Set a value at a specific key (supports dot notation).
     */
    private function setKey(Array_ $array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $this->setNestedKey($array, $keys, $value);
    }

    /**
     * Set a nested key value.
     *
     * @param  array<string>  $keys
     */
    private function setNestedKey(Array_ $array, array $keys, mixed $value): void
    {
        $firstKey = array_shift($keys);

        // Find existing item or create new one
        $existingItem = $this->findArrayItem($array, $firstKey);

        if ($keys === []) {
            // We're at the target key, set the value
            $valueNode = $this->convertToNode($value);
            if ($existingItem instanceof ArrayItem) {
                // Update existing item
                $existingItem->value = $valueNode;
            } else {
                // Add new item
                $array->items[] = new ArrayItem($valueNode, new String_($firstKey));
            }
        } elseif ($existingItem instanceof ArrayItem && $existingItem->value instanceof Array_) {
            // We need to go deeper
            $this->setNestedKey($existingItem->value, $keys, $value);
        } else {
            // Create a new nested array
            $nestedArray = new Array_();
            $this->setNestedKey($nestedArray, $keys, $value);

            if ($existingItem instanceof ArrayItem) {
                $existingItem->value = $nestedArray;
            } else {
                $array->items[] = new ArrayItem($nestedArray, new String_($firstKey));
            }
        }
    }

    /**
     * Append values to an array at a specific key.
     */
    private function appendToKey(Array_ $array, string $key, array $values): void
    {
        $existingItem = $this->findArrayItem($array, $key);

        foreach ($values as $value) {
            $valueNode = $this->convertToNode($value);

            if ($existingItem instanceof ArrayItem && $existingItem->value instanceof Array_) {
                // Append to an existing array
                $existingItem->value->items[] = new ArrayItem($valueNode);
            } else {
                // Create a new array with the value
                $newArray = new Array_([new ArrayItem($valueNode)]);
                if ($existingItem instanceof ArrayItem) {
                    $existingItem->value = $newArray;
                    // Update the existingItem to point to the new array for later iterations
                    $existingItem = $this->findArrayItem($array, $key);
                } else {
                    $array->items[] = new ArrayItem($newArray, new String_($key));
                    // Update the existingItem to point to the new array for later iterations
                    $existingItem = $this->findArrayItem($array, $key);
                }
            }
        }
    }

    /**
     * Merge values into an array at a specific key.
     */
    private function mergeKey(Array_ $array, string $key, array $values): void
    {
        $existingItem = $this->findArrayItem($array, $key);

        foreach ($values as $k => $v) {
            $valueNode = $this->convertToNode($v);

            if ($existingItem instanceof ArrayItem && $existingItem->value instanceof Array_) {
                // Add to an existing array
                if (is_string($k)) {
                    $existingItem->value->items[] = new ArrayItem($valueNode, new String_($k));
                } else {
                    $existingItem->value->items[] = new ArrayItem($valueNode);
                }
            } else {
                // Create a new array
                $newArray = new Array_();
                if (is_string($k)) {
                    $newArray->items[] = new ArrayItem($valueNode, new String_($k));
                } else {
                    $newArray->items[] = new ArrayItem($valueNode);
                }

                if ($existingItem instanceof ArrayItem) {
                    $existingItem->value = $newArray;
                } else {
                    $array->items[] = new ArrayItem($newArray, new String_($key));
                }
            }
        }
    }

    /**
     * Remove a key from the array.
     */
    private function removeKey(Array_ $array, string $key): void
    {
        foreach ($array->items as $index => $item) {
            if ($item instanceof ArrayItem && $item->key instanceof String_ && $item->key->value === $key) {
                unset($array->items[$index]);
                // Re-index array
                $array->items = array_values($array->items);

                return;
            }
        }
    }

    /**
     * Find an array item by key.
     */
    private function findArrayItem(Array_ $array, string $key): ?ArrayItem
    {
        foreach ($array->items as $item) {
            if ($item instanceof ArrayItem && $item->key instanceof String_ && $item->key->value === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Convert a PHP value to a PhpParser node.
     *
     * @throws ReflectionException
     */
    private function convertToNode(mixed $value): Expr
    {
        return match (true) {
            is_callable($value) => $this->closureToNode($value),
            is_string($value) => new String_($value),
            is_int($value) => new Int_($value),
            is_float($value) => new Float_($value),
            is_bool($value) => new ConstFetch(new Name($value ? 'true' : 'false')),
            $value === null => new ConstFetch(new Name('null')),
            is_array($value) => $this->arrayToNode($value),
            default => new String_((string) $value),
        };
    }

    /**
     * Convert a PHP array to an Array_ node.
     *
     * @throws ReflectionException
     */
    private function arrayToNode(array $array): Array_
    {
        $items = [];
        foreach ($array as $key => $value) {
            $valueNode = $this->convertToNode($value);
            $keyNode = is_string($key) ? new String_($key) : null;
            $items[] = new ArrayItem($valueNode, $keyNode);
        }

        return new Array_($items);
    }

    /**
     * @throws ReflectionException
     */
    private function closureToNode(callable $value): Expr
    {
        $reflection = new ReflectionFunction($value);
        $fileName = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        if ($fileName === false || $startLine === false || $endLine === false) {
            throw new RuntimeException('Unable to read closure source');
        }

        $fileContent = file($fileName);
        if ($fileContent === false) {
            throw new RuntimeException('Unable to read file for closure parsing');
        }

        // Extract the closure source lines
        $closureBody = implode('', array_slice($fileContent, $startLine - 1, $endLine - $startLine + 1));

        $lines = array_filter(explode(PHP_EOL, $closureBody));
        $lineCount = count($lines);

        // Handle single-line closures
        if ($lineCount === 1) {
            $closureBody = str_replace(PHP_EOL, '', $closureBody);

            if (!str_ends_with($closureBody, ';')) {
                $closureBody = rtrim($closureBody, ", \t\n\r\0\x0B") . ';' . PHP_EOL;
            }

            // Remove the leading arrow operator in short closures
            if (str_starts_with(trim($closureBody), '->')) {
                $closureBody = ltrim($closureBody, " \t\n\r\0\x0B->");
            }
        }

        // Handle "double arrow" logic ( => occurrences == 2 )
        if (mb_substr_count($closureBody, '=>') === 2 && (str_ends_with($closureBody, ','.PHP_EOL) || str_ends_with($closureBody, PHP_EOL) || str_ends_with($closureBody, ';'.PHP_EOL))) {
            $parts = explode('=>', $closureBody);

            array_shift($parts);
            $closureBody = str_replace(','.PHP_EOL, ';', implode('=>', $parts));
        }

        $closureBody = trim($closureBody);
        $parser = new ParserFactory()->createForNewestSupportedVersion();

        try {
            $stmts = $parser->parse("<?php\n{$closureBody}");

            // Recursively search for ArrowFunction only
            foreach ($stmts as $stmt) {
                $found = $this->findClosureNode($stmt);

                if ($found instanceof ArrowFunction) {
                    return $found->expr;
                }

                if ($found instanceof Closure) {
                    throw new RuntimeException(
                        'Only arrow functions are supported. Please use arrow function syntax (fn() => ...) instead of closure syntax (function() {...})',
                    );
                }
            }
        } catch (Error $error) {
            throw new RuntimeException("Failed to parse callable: {$error->getMessage()}", $error->getCode(), $error);
        }

        throw new RuntimeException(
            'Only arrow functions are supported. Please provide an arrow function (fn() => ...)',
        );
    }

    private function findClosureNode(Node $node): ?Node
    {
        if ($node instanceof ArrowFunction || $node instanceof Closure) {
            return $node;
        }

        foreach ($node->getSubNodeNames() as $subName) {
            $child = $node->{$subName};

            // Single child node
            if (
                $child instanceof Node &&
                ($found = $this->findClosureNode($child))
            ) {
                return $found;
            }

            // List of child nodes
            if (is_array($child)) {
                foreach ($child as $item) {
                    if (
                        $item instanceof Node &&
                        ($found = $this->findClosureNode($item))
                    ) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }
}
