<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use Techieni3\StacktifyCli\Services\PhpParser\Visitors\ServiceProviderVisitor;

/**
 * Editor for Laravel service provider files using PHP AST manipulation.
 */
final class ServiceProviderEditor extends BasePhpEditor
{
    /**
     * Use statements to add to the file.
     *
     * @var array<string>
     */
    private array $useStatements = [];

    /**
     * Code statements to add to the register() method.
     *
     * @var array<string>
     */
    private array $registerStatements = [];

    /**
     * Code statements to add to the boot() method.
     *
     * @var array<string>
     */
    private array $bootStatements = [];

    /**
     * New methods to add to the service provider class.
     *
     * @var array<string>
     */
    private array $newMethods = [];

    /**
     * Add use statements to the service provider.
     *
     * @param  array<string>|string  $statements
     */
    public function addUseStatements(array|string $statements): self
    {
        $statementsArray = is_array($statements) ? $statements : [$statements];

        foreach ($statementsArray as $statement) {
            if ( ! in_array($statement, $this->useStatements, true)) {
                $this->useStatements[] = $statement;
                $this->isChanged = true;
            }
        }

        return $this;
    }

    /**
     * Add code to the register() method.
     *
     * @param  array<string>|string  $statements
     */
    public function addToRegister(array|string $statements): self
    {
        $statementsArray = is_array($statements) ? $statements : [$statements];

        foreach ($statementsArray as $statement) {
            $this->registerStatements[] = $statement;
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Add code to the boot() method.
     *
     * @param  array<string>|string  $statements
     */
    public function addToBoot(array|string $statements): self
    {
        $statementsArray = is_array($statements) ? $statements : [$statements];

        foreach ($statementsArray as $statement) {
            $this->bootStatements[] = $statement;
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Add new methods to the service provider class.
     *
     * @param  array<string>|string  $methods  Complete method definitions including PHPDoc, visibility, etc.
     */
    public function addMethods(array|string $methods): self
    {
        $methodsArray = is_array($methods) ? $methods : [$methods];

        foreach ($methodsArray as $method) {
            $this->newMethods[] = $method;
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Save changes to the service provider file.
     */
    public function save(): bool
    {
        if ( ! $this->isChanged) {
            return false;
        }

        // Create a visitor with current modifications
        $visitor = new ServiceProviderVisitor(
            $this->useStatements,
            $this->registerStatements,
            $this->bootStatements,
            $this->newMethods
        );

        // Traverse and modify the AST
        $modifiedAst = $this->traverse($visitor);

        // Pretty print and save
        $newCode = $this->prettyPrint($modifiedAst);
        $this->writeFile($newCode);

        // Reset state
        $this->useStatements = [];
        $this->registerStatements = [];
        $this->bootStatements = [];
        $this->newMethods = [];
        $this->isChanged = false;

        return true;
    }
}
