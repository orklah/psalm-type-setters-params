<?php declare(strict_types=1);

namespace Orklah\TypeSetters\Hooks;

use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\Variable;
use Psalm\FileManipulation;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Type\Atomic;
use function count;

class TypeSettersHooks implements AfterFunctionLikeAnalysisInterface
{
    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $functionlike_storage = $event->getClasslikeStorage();
        //the function is a setter
        if ($functionlike_storage->cased_name === null || stripos($functionlike_storage->cased_name, 'set') !== 0) {
            return true;
        }
        $params = $functionlike_storage->params;
        //the setter has only one param
        if (count($params) !== 1) {
            return true;
        }

        //the parameter is not typed
        if ($params[0]->signature_type !== null) {
            return true;
        }

        $stmt = $event->getStmt();
        $stmts = $stmt->getStmts();

        //there is only one statement in the setter
        if (count($stmts) !== 1) {
            return true;
        }

        //the statement is an expression
        if (!$stmts[0] instanceof Expression) {
            return true;
        }

        //the expression is an assignment
        if (!$stmts[0]->expr instanceof Assign) {
            return true;
        }

        //were the left part is a property
        if (!$stmts[0]->expr->var instanceof PropertyFetch && !$stmts[0]->expr->var instanceof StaticPropertyFetch) {
            return true;
        }

        //and the right part is a simple variable
        if (!$stmts[0]->expr->var->name instanceof Identifier) {
            return true;
        }

        //The variable happen to match with the param name
        if ($stmts[0]->expr->expr->name !== $params[0]->name) {
            return true;
        }

        $context = $event->getContext();
        $object_type = $context->vars_in_scope['$this'];

        $atomic_types = $object_type->getAtomicTypes();
        //if $this has only one type
        if (count($atomic_types) !== 1) {
            return true;
        }

        $atomic_type = array_shift($atomic_types);
        if (!$atomic_type instanceof TNamedObject) {
            return true;
        }

        $property_id = $atomic_type->value . '::$' . (string)$stmts[0]->expr->var->name->name;

        $property_type = $event->getCodebase()->properties->getPropertyType(
            $property_id,
            true,
            $event->getStatementsSource(),
            $context
        );

        //the property has been found
        if ($property_type === null) {
            return true;
        }

        //the property is typed
        if ($property_type->from_docblock) {
            return true;
        }

        //yay, we have an assignment of a non-typed param into a typed property
        $pos = $params[0]->location->getSelectionBounds();

        $type = $property_type->toPhpString(
            $event->getStatementsSource()->getNamespace(),
            $event->getStatementsSource()->getAliasedClassesFlipped(),
            $event->getStatementsSource()->getFQCLN(),
            $event->getCodebase()->php_major_version,
            $event->getCodebase()->php_minor_version
        );
        $file_manipulation = new FileManipulation($pos[0], $pos[0], $type.' ');
        $event->setFileReplacements([$file_manipulation]);

        return true;
    }
}
